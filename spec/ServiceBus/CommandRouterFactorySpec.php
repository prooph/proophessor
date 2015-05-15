<?php

namespace spec\Prooph\Proophessor\ServiceBus;

use PhpSpec\ObjectBehavior;
use Prooph\Common\Messaging\Command;
use Prooph\ServiceBus\Process\CommandDispatch;
use Prooph\ServiceBus\Router\CommandRouter;
use Prophecy\Argument;
use Zend\ServiceManager\ServiceManager;

class CommandRouterFactorySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Prooph\Proophessor\ServiceBus\CommandRouterFactory');
    }

    function it_uses_configured_routing_map_to_route_a_command_to_a_handler(ServiceManager $serviceManager, CommandDispatch $commandDispatch)
    {
        $commandDispatch->getCommandName()->willReturn("test-command");
        $commandDispatch->setCommandHandler('Acme\CommandHandler')->shouldBeCalled();

        $serviceManager->get('config')->willReturn([
            'proophessor' => [
                'command_router_map' => [
                    'test-command' => 'Acme\CommandHandler'
                ]
            ]
        ]);

        $commandRouter = $this->createService($serviceManager);

        $commandRouter->shouldBeAnInstanceOf(CommandRouter::class);

        $commandRouter->onRouteCommand($commandDispatch);
    }
}
