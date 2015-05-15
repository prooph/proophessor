<?php

namespace spec\Prooph\Proophessor\ServiceBus;

use PhpSpec\ObjectBehavior;
use Prooph\ServiceBus\Process\EventDispatch;
use Prooph\ServiceBus\Router\EventRouter;
use Prophecy\Argument;
use Zend\ServiceManager\ServiceManager;

class EventRouterFactorySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Prooph\Proophessor\ServiceBus\EventRouterFactory');
    }

    function it_uses_configured_routing_map_to_route_an_evnet_to_a_list_of_event_listeners(ServiceManager $serviceManager, EventDispatch $eventDispatch)
    {
        $eventDispatch->getEventName()->willReturn("test-event");
        $eventDispatch->setEventListeners(['Acme\Listener1', 'Acme\Listener2'])->shouldBeCalled();

        $serviceManager->get('config')->willReturn([
            'proophessor' => [
                'event_router_map' => [
                    'test-event' => ['Acme\Listener1', 'Acme\Listener2']
                ]
            ]
        ]);

        $eventRouter = $this->createService($serviceManager);

        $eventRouter->shouldBeAnInstanceOf(EventRouter::class);

        $eventRouter->onRouteEvent($eventDispatch);
    }
}
