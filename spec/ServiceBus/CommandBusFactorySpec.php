<?php

namespace spec\Prooph\Proophessor\ServiceBus;

use PhpSpec\ObjectBehavior;
use Prooph\Common\Event\ActionEventDispatcher;
use Prooph\Common\Event\ActionEventListenerAggregate;
use Prophecy\Argument;
use Zend\ServiceManager\ServiceManager;

class CommandBusFactorySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Prooph\Proophessor\ServiceBus\CommandBusFactory');
    }

    function it_provides_a_command_bus_with_attached_utils(ServiceManager $serviceManager, ActionEventListenerAggregate $util)
    {
        $serviceManager->get('psb_util')->willReturn($util);
        $serviceManager->get('config')->willReturn([
            'proophessor' => [
                'command_bus' => [
                    'psb_util'
                ]
            ]
        ]);

        $bus = $this->createService($serviceManager);

        $util->attach($bus->getActionEventDispatcher())->shouldHaveBeenCalled();
    }
}
