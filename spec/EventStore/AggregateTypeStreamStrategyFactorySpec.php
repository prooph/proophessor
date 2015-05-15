<?php
/*
 * This file is part of prooph/proophessor.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 5/5/15 - 10:00 PM
 */
namespace spec\Prooph\Proophessor\EventStore;

use PhpSpec\ObjectBehavior;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Stream\AggregateTypeStreamStrategy;
use Zend\ServiceManager\ServiceManager;

final class AggregateTypeStreamStrategyFactorySpec extends ObjectBehavior
{
    function it_provides_a_aggregate_type_stream_strategy(ServiceManager $serviceManager, EventStore $eventStore)
    {
        $serviceManager->get('proophessor.event_store')->willReturn($eventStore);
        $serviceManager->has('configuration')->willReturn(true);
        $serviceManager->get('configuration')->willReturn([
            'proophessor' => [
                'event_store' => [
                    'aggregate_type_stream_map' => [
                        'Acme\User' => 'user_stream'
                    ]
                ]
            ]
        ]);

        $this->createService($serviceManager)->shouldReturnAnInstanceOf(AggregateTypeStreamStrategy::class);
    }
} 