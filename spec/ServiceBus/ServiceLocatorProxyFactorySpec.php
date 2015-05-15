<?php

namespace spec\Prooph\Proophessor\ServiceBus;

use PhpSpec\ObjectBehavior;
use Prooph\ServiceBus\Process\CommandDispatch;
use Prooph\ServiceBus\ServiceLocator\ServiceLocatorProxy;
use Prophecy\Argument;
use Zend\ServiceManager\ServiceManager;

class ServiceLocatorProxyFactorySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Prooph\Proophessor\ServiceBus\ServiceLocatorProxyFactory');
    }

    function it_passes_service_manager_to_the_proxy(ServiceManager $serviceManager, CommandDispatch $commandDispatch)
    {
        $serviceManager->has('test_handler')->willReturn(false);
        $commandDispatch->getCommandHandler()->willReturn("test_handler");

        $proxy = $this->createService($serviceManager);

        $proxy->shouldBeAnInstanceOf(ServiceLocatorProxy::class);

        $proxy->onLocateCommandHandler($commandDispatch);
    }
}
