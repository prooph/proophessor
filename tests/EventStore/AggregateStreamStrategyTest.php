<?php
/*
 * This file is part of prooph/proophessor.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 5/5/15 - 9:48 PM
 */
namespace ProophTest\Proophessor\EventStore;

use Prooph\EventStore\Stream\AggregateStreamStrategy;
use Prooph\Proophessor\EventStore\AggregateStreamStrategyFactory;
use ProophTest\Proophessor\Mock\UserMock;
use ProophTest\Proophessor\TestCase;
use Zend\ServiceManager\ServiceManager;

final class AggregateStreamStrategyFactoryTest extends TestCase
{
    /**
     * @test
     */
    function it_provides_a_aggregate_stream_strategy()
    {
        $serviceLocator = new ServiceManager();

        $serviceLocator->setService('proophessor.event_store', $this->getEventStore());
        $serviceLocator->setService('configuration', [
            'proophessor' => [
                'event_store' => [
                    'aggregate_type_stream_map' => [
                        UserMock::class => 'user_stream'
                    ]
                ]
            ]
        ]);

        $factory = new AggregateStreamStrategyFactory();

        $this->assertInstanceOf(AggregateStreamStrategy::class, $factory->createService($serviceLocator));
    }
} 