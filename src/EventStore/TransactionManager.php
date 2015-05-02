<?php
/*
 * This file is part of prooph/link.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 4/4/15 - 12:25 AM
 */
namespace Prooph\Proophessor\EventStore;

use Prooph\Common\Event\ActionEvent;
use Prooph\Common\Event\ActionEventDispatcher;
use Prooph\Common\Event\ActionEventListenerAggregate;
use Prooph\Common\Event\DetachAggregateHandlers;
use Prooph\Common\Messaging\Command;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Stream\DomainEventMetadataWriter;
use Prooph\ServiceBus\Process\CommandDispatch;

/**
 * TransactionManager PSB and PES plugin
 *
 * The transaction manager starts a new transaction when a command is dispatched on the command bus.
 * If the command dispatch finishes without an error the transaction manager commits the transaction otherwise it does a rollback.
 * Furthermore it attaches a listener to the event store create.pre and appendTo.pre action events with a low priority to
 * set causation information and the dispatch status as metadata for all DomainEvents which are going to be persisted.
 *
 * @package Prooph\Proophessor\EventStore
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
final class TransactionManager implements ActionEventListenerAggregate
{
    use DetachAggregateHandlers;

    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * @var bool
     */
    private $inTransaction = false;

    /**
     * @var Command
     */
    private $currentCommand;

    /**
     * @param EventStore $eventStore
     */
    public function __construct(EventStore $eventStore)
    {
        $this->eventStore = $eventStore;
        $this->eventStore->getActionEventDispatcher()->attachListener('create.pre', [$this, 'onEventStoreCreateStream'], -1000);
        $this->eventStore->getActionEventDispatcher()->attachListener('appendTo.pre', [$this, 'onEventStoreAppendToStream'], -1000);
    }

    /**
     * Attaches itself to the command dispatch of the application command bus
     *
     * @param ActionEventDispatcher $events
     *
     * @return void
     */
    public function attach(ActionEventDispatcher $events)
    {
        //Attach with a low priority, so that a potential message translator has done its job
        $this->trackHandler($events->attachListener(CommandDispatch::INITIALIZE, [$this, 'onInitialize'], -1000));
        $this->trackHandler($events->attachListener(CommandDispatch::HANDLE_ERROR, [$this, 'onError']));
        $this->trackHandler($events->attachListener(CommandDispatch::FINALIZE, [$this, 'onFinalize']));
    }

    /**
     * @param CommandDispatch $commandDispatch
     */
    public function onInitialize(CommandDispatch $commandDispatch)
    {
        $command = $commandDispatch->getCommand();
        if ($command instanceof Command) {
            if (! $this->inTransaction) {
                $this->eventStore->beginTransaction();
                $this->inTransaction = true;
            }

            $this->currentCommand = $command;
        }
    }

    public function onError(CommandDispatch $commandDispatch)
    {
        if (! $commandDispatch->getCommand() instanceof Command) return;
        if (! $this->inTransaction) return;

        $this->eventStore->rollback();
        $this->inTransaction = false;
        $this->currentCommand = null;
    }

    public function onFinalize(CommandDispatch $commandDispatch)
    {
        if (! $commandDispatch->getCommand() instanceof Command) return;
        if (! $this->inTransaction) return;

        $this->eventStore->commit();
        $this->currentCommand = null;
    }

    /**
     * @param ActionEvent $createEvent
     */
    public function onEventStoreCreateStream(ActionEvent $createEvent)
    {
        $this->addCausationInformationAndDispatchStatusToEvents($createEvent->getParam('stream')->streamEvents());
    }

    /**
     * @param ActionEvent $appendToStreamEvent
     */
    public function onEventStoreAppendToStream(ActionEvent $appendToStreamEvent)
    {
        $this->addCausationInformationAndDispatchStatusToEvents($appendToStreamEvent->getParam('streamEvents'));
    }

    /**
     * @param array $recordedEvents
     */
    private function addCausationInformationAndDispatchStatusToEvents(array &$recordedEvents)
    {
        if (is_null($this->currentCommand)) return;

        $causationId = $this->currentCommand->uuid()->toString();
        $causationName = $this->currentCommand->messageName();

        foreach($recordedEvents as $recordedEvent) {
            //For events which are processed synchronous we set the status to succeed even if they are not processed yet.
            //If processing fails the whole transaction will be rolled back and the event won't be stored at all.
            $dispatchStatus = ($recordedEvent instanceof IsProcessedAsync)? DispatchStatus::NOT_STARTED : DispatchStatus::SUCCEED;

            DomainEventMetadataWriter::setMetadataKey($recordedEvent, 'causation_id', $causationId);
            DomainEventMetadataWriter::setMetadataKey($recordedEvent, 'causation_name', $causationName);
            DomainEventMetadataWriter::setMetadataKey($recordedEvent, 'dispatch_status', $dispatchStatus);
        }
    }
}