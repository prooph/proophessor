<?php

namespace spec\Prooph\Proophessor\EventStore;

use PhpSpec\ObjectBehavior;
use Prooph\Common\Event\ActionEvent;
use Prooph\Common\Event\ActionEventDispatcher;
use Prooph\Common\Event\ListenerHandler;
use Prooph\Common\Messaging\Command;
use Prooph\Common\Messaging\DomainEvent;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Stream\Stream;
use Prooph\Proophessor\EventStore\DispatchStatus;
use Prooph\Proophessor\Stub\AsyncDomainEventStub;
use Prooph\Proophessor\Stub\AutoCommitCommandStub;
use Prooph\Proophessor\Stub\DomainEventStub;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\Process\CommandDispatch;
use Prophecy\Argument;
use Rhumsaa\Uuid\Uuid;

class TransactionManagerSpec extends ObjectBehavior
{
    function let(EventStore $eventStore, EventBus $eventBus, ActionEventDispatcher $actionEventDispatcher, CommandDispatch $commandDispatch, Command $command)
    {
        $commandDispatch->getCommand()->willReturn($command);
        $eventStore->getActionEventDispatcher()->willReturn($actionEventDispatcher);
        $this->beConstructedWith($eventStore, $eventBus);
    }

    function it_is_initializable(ActionEventDispatcher $actionEventDispatcher)
    {
        $this->shouldHaveType('Prooph\Proophessor\EventStore\TransactionManager');
        $actionEventDispatcher->attachListener('create.pre', [$this, 'onEventStoreCreateStream'], -1000)->shouldHaveBeenCalled();
        $actionEventDispatcher->attachListener('appendTo.pre', [$this, 'onEventStoreAppendToStream'], -1000)->shouldHaveBeenCalled();
    }

    function it_acts_as_a_message_bus_plugin_to_monitor_command_dispatch(ActionEventDispatcher $actionEventDispatcher, ListenerHandler $listenerHandler)
    {
        $actionEventDispatcher->attachListener(CommandDispatch::INITIALIZE, [$this, 'onInitialize'], -1000)->willReturn($listenerHandler);
        $actionEventDispatcher->attachListener(CommandDispatch::FINALIZE, [$this, 'onFinalize'], 1000)->willReturn($listenerHandler);

        $this->attach($actionEventDispatcher);
    }

    function it_begins_a_new_transaction_when_a_command_is_dispatched(EventStore $eventStore, CommandDispatch $commandDispatch)
    {
        $eventStore->beginTransaction()->shouldBeCalled();

        $this->onInitialize($commandDispatch);
    }

    function it_does_not_begin_a_new_transaction_when_command_implements_auto_commit_command(EventStore $eventStore, CommandDispatch $commandDispatch, AutoCommitCommandStub $command)
    {
        $eventStore->beginTransaction()->shouldNotBeCalled();

        $commandDispatch->getCommand()->willReturn($command);

        $this->onInitialize($commandDispatch);
    }

    function it_performs_a_rollback_on_command_dispatch_error(EventStore $eventStore, CommandDispatch $commandDispatch)
    {
        $eventStore->beginTransaction()->shouldBeCalled();

        $this->onInitialize($commandDispatch);

        $eventStore->rollback()->shouldBeCalled();

        $commandDispatch->getException()->willReturn(new \Exception());

        $this->onFinalize($commandDispatch);
    }

    function it_does_not_perform_a_rollback_when_command_implements_auto_commit_command(EventStore $eventStore, CommandDispatch $commandDispatch, AutoCommitCommandStub $autoCommitCommand)
    {
        $eventStore->beginTransaction()->shouldBeCalled();

        $this->onInitialize($commandDispatch);

        $eventStore->rollback()->shouldNotBeCalled();

        $commandDispatch->getCommand()->willReturn($autoCommitCommand);

        $commandDispatch->getException()->willReturn(new \Exception());

        $this->onFinalize($commandDispatch);
    }

    function it_does_not_perform_a_rollback_when_it_is_not_in_transaction(EventStore $eventStore, CommandDispatch $commandDispatch)
    {
        $eventStore->rollback()->shouldNotBeCalled();

        $commandDispatch->getException()->willReturn(new \Exception());

        $this->onFinalize($commandDispatch);
    }

    function it_commits_the_transaction_on_finalize(EventStore $eventStore, CommandDispatch $commandDispatch)
    {
        $eventStore->beginTransaction()->shouldBeCalled();

        $this->onInitialize($commandDispatch);

        $eventStore->commit()->shouldBeCalled();
        $commandDispatch->getException()->shouldBeCalled();

        $this->onFinalize($commandDispatch);
    }

    function it_handles_nested_transactions_by_invoking_event_store_commit_only_when_all_commands_are_dispatched(EventStore $eventStore, CommandDispatch $commandDispatch)
    {
        $eventStore->beginTransaction()->shouldBeCalled();

        $this->onInitialize($commandDispatch);
        $this->onInitialize($commandDispatch);

        $eventStore->commit()->shouldNotBeCalled();
        $commandDispatch->getException()->shouldBeCalled();

        $this->onFinalize($commandDispatch);

        $eventStore->commit()->shouldBeCalled();

        $this->onFinalize($commandDispatch);
    }

    function it_does_not_commit_transaction_when_command_implements_auto_commit_command(EventStore $eventStore, CommandDispatch $commandDispatch, AutoCommitCommandStub $autoCommitCommand)
    {
        $eventStore->beginTransaction()->shouldBeCalled();

        $this->onInitialize($commandDispatch);

        $commandDispatch->getException()->shouldBeCalled();
        $eventStore->commit()->shouldNotBeCalled();

        $commandDispatch->getCommand()->willReturn($autoCommitCommand);

        $this->onFinalize($commandDispatch);
    }

    function it_does_not_commit_transaction_when_it_is_not_in_transaction(EventStore $eventStore, CommandDispatch $commandDispatch)
    {
        $commandDispatch->getException()->shouldBeCalled();
        $eventStore->commit()->shouldNotBeCalled();

        $this->onFinalize($commandDispatch);
    }

    function it_adds_meta_information_about_command_to_each_recorded_event_of_a_new_aggregate_root(
        EventStore $eventStore, ActionEvent $actionEvent, Stream $eventStream,
        CommandDispatch $commandDispatch, Command $command, EventBus $eventBus)
    {
        $event1 = DomainEventStub::record('test-event-1');
        $event2 = DomainEventStub::record('test-event-2');
        $commandId = Uuid::uuid4();
        $command->uuid()->willReturn($commandId);
        $command->messageName()->willReturn("test-command");
        $eventStore->beginTransaction()->shouldBeCalled();

        $this->onInitialize($commandDispatch);

        $eventStream->streamEvents()->willReturn([$event1, $event2]);
        $actionEvent->getParam('stream')->willReturn($eventStream);

        $this->onEventStoreCreateStream($actionEvent);

        expect($event1->metadata()['causation_id'])->toBe($commandId->toString());
        expect($event1->metadata()['causation_name'])->toBe('test-command');
        expect($event2->metadata()['causation_id'])->toBe($commandId->toString());
        expect($event2->metadata()['causation_name'])->toBe('test-command');
    }

    function it_adds_meta_information_about_command_to_each_recorded_event_of_an_updated_aggregate_root(
        EventStore $eventStore, ActionEvent $actionEvent,
        CommandDispatch $commandDispatch, Command $command, EventBus $eventBus)
    {
        $event1 = DomainEventStub::record('test-event-1');
        $event2 = DomainEventStub::record('test-event-2');
        $commandId = Uuid::uuid4();
        $command->uuid()->willReturn($commandId);
        $command->messageName()->willReturn("test-command");
        $eventStore->beginTransaction()->shouldBeCalled();

        $this->onInitialize($commandDispatch);

        $actionEvent->getParam('streamEvents')->willReturn([$event1, $event2]);

        $this->onEventStoreAppendToStream($actionEvent);

        expect($event1->metadata()['causation_id'])->toBe($commandId->toString());
        expect($event1->metadata()['causation_name'])->toBe('test-command');
        expect($event2->metadata()['causation_id'])->toBe($commandId->toString());
        expect($event2->metadata()['causation_name'])->toBe('test-command');
    }

    function it_adds_meta_information_about_dispatch_status_to_each_recorded_event_of_a_new_aggregate_root_but_only_dispatches_synchronous_events(
        EventStore $eventStore, ActionEvent $actionEvent, Stream $eventStream,
        CommandDispatch $commandDispatch, Command $command, EventBus $eventBus)
    {
        $event1 = DomainEventStub::record('sync-event');
        $event2 = AsyncDomainEventStub::record('async-event');
        $commandId = Uuid::uuid4();
        $command->uuid()->willReturn($commandId);
        $command->messageName()->willReturn("test-command");
        $eventStore->beginTransaction()->shouldBeCalled();

        $this->onInitialize($commandDispatch);

        $eventStream->streamEvents()->willReturn([$event1, $event2]);
        $actionEvent->getParam('stream')->willReturn($eventStream);

        $eventBus->dispatch($event1)->shouldBeCalled();
        $eventBus->dispatch($event2)->shouldNotBeCalled();

        $this->onEventStoreCreateStream($actionEvent);

        expect($event1->metadata()['dispatch_status'])->toBe(DispatchStatus::SUCCEED);
        expect($event2->metadata()['dispatch_status'])->toBe(DispatchStatus::NOT_STARTED);
    }

    function it_adds_meta_information_about_dispatch_status_to_each_recorded_event_of_an_updated_aggregate_root_but_only_dispatches_synchronous_events(
        EventStore $eventStore, ActionEvent $actionEvent,
        CommandDispatch $commandDispatch, Command $command, EventBus $eventBus)
    {
        $event1 = DomainEventStub::record('sync-event');
        $event2 = AsyncDomainEventStub::record('async-event');
        $commandId = Uuid::uuid4();
        $command->uuid()->willReturn($commandId);
        $command->messageName()->willReturn("test-command");
        $eventStore->beginTransaction()->shouldBeCalled();

        $this->onInitialize($commandDispatch);

        $actionEvent->getParam('streamEvents')->willReturn([$event1, $event2]);

        $eventBus->dispatch($event1)->shouldBeCalled();
        $eventBus->dispatch($event2)->shouldNotBeCalled();

        $this->onEventStoreAppendToStream($actionEvent);

        expect($event1->metadata()['dispatch_status'])->toBe(DispatchStatus::SUCCEED);
        expect($event2->metadata()['dispatch_status'])->toBe(DispatchStatus::NOT_STARTED);
    }
}
