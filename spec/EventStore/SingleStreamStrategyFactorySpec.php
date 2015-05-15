<?php

namespace spec\Prooph\Proophessor\EventStore;

use PhpSpec\ObjectBehavior;
use Prooph\Common\Messaging\DomainEvent;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Stream\SingleStreamStrategy;
use Prooph\Proophessor\Stub\UserStub;
use Prophecy\Argument;
use Rhumsaa\Uuid\Uuid;
use Zend\ServiceManager\ServiceManager;

class SingleStreamStrategyFactorySpec extends ObjectBehavior
{
    function it_provide_a_single_stream_strategy_using_the_default_stream_name(ServiceManager $serviceManager, EventStore $eventStore, AggregateType $aggregateType, DomainEvent $domainEvent, UserStub $user)
    {
        $serviceManager->get('proophessor.event_store')->willReturn($eventStore);
        $serviceManager->has('configuration')->willReturn(true);
        $serviceManager->get('configuration')->willReturn([
            'proophessor' => [
                'event_store' => []
            ]
        ]);

        $eventStore->appendTo("event_stream", [$domainEvent])->shouldBeCalled();
        $strategy = $this->createService($serviceManager);
        $strategy->shouldBeAnInstanceOf(SingleStreamStrategy::class);
        $strategy->addEventsForNewAggregateRoot($aggregateType, Uuid::uuid4()->toString(), [$domainEvent], $user);
    }

    function it_provide_a_single_stream_strategy_using_the_configured_stream_name(ServiceManager $serviceManager, EventStore $eventStore, AggregateType $aggregateType, DomainEvent $domainEvent, UserStub $user)
    {
        $serviceManager->get('proophessor.event_store')->willReturn($eventStore);
        $serviceManager->has('configuration')->willReturn(true);
        $serviceManager->get('configuration')->willReturn([
            'proophessor' => [
                'event_store' => [
                    'single_stream_name' => 'custom_stream'
                ]
            ]
        ]);

        $eventStore->appendTo("custom_stream", [$domainEvent])->shouldBeCalled();
        $strategy = $this->createService($serviceManager);
        $strategy->shouldBeAnInstanceOf(SingleStreamStrategy::class);
        $strategy->addEventsForNewAggregateRoot($aggregateType, Uuid::uuid4()->toString(), [$domainEvent], $user);
    }
}
