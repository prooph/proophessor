<?php

namespace spec\Prooph\Proophessor\EventStore;

use PhpSpec\ObjectBehavior;
use Prooph\Common\Event\ActionEventDispatcher;
use Prooph\EventStore\EventStore;
use Prooph\Proophessor\EventStore\TransactionManager;
use Prooph\ServiceBus\EventBus;
use Prophecy\Argument;
use Zend\ServiceManager\ServiceManager;

class TransactionManagerFactorySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Prooph\Proophessor\EventStore\TransactionManagerFactory');
    }

    function it_provides_a_transaction_manager(ServiceManager $serviceManager, EventStore $eventStore, EventBus $eventBus, ActionEventDispatcher $actionEventDispatcher)
    {
        $eventStore->getActionEventDispatcher()->willReturn($actionEventDispatcher);
        $serviceManager->get('proophessor.event_store')->willReturn($eventStore);
        $serviceManager->get('proophessor.event_bus')->willReturn($eventBus);

        $this->createService($serviceManager)->shouldReturnAnInstanceOf(TransactionManager::class);
    }
}
