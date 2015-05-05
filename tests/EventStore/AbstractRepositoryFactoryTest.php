<?php
/*
 * This file is part of prooph/proophessor.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 5/5/15 - 6:47 PM
 */
namespace ProophTest\Proophessor\EventStore;

use Prooph\EventStore\Aggregate\DefaultAggregateTranslator;
use Prooph\EventStore\Stream\SingleStreamStrategy;
use Prooph\Proophessor\EventStore\AbstractRepositoryFactory;
use ProophTest\Proophessor\Mock\UserMock;
use ProophTest\Proophessor\Mock\UserMockRepository;
use ProophTest\Proophessor\TestCase;
use Zend\ServiceManager\ServiceManager;

/**
 * Class AbstractRepositoryFactoryTest
 *
 * @package ProophTest\Proophessor\EventStore
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
final class AbstractRepositoryFactoryTest extends TestCase
{
    /**
     * @var ServiceManager
     */
    private $serviceLocator;

    private $strategy;

    private $translator;


    protected function setUp()
    {
        $this->strategy = new SingleStreamStrategy($this->getEventStore(), 'test_stream');
        $this->translator = new DefaultAggregateTranslator();

        $this->serviceLocator = new ServiceManager();

        $this->serviceLocator->setService('proophessor.event_store.default_aggregate_translator', $this->translator);
        $this->serviceLocator->setService('proophessor.event_store.default_stream_strategy', $this->strategy);
        $this->serviceLocator->setService('proophessor.event_store', $this->getEventStore());
    }

    /**
     * @test
     */
    function it_can_create_a_configured_repository_using_a_default_strategy_and_aggregate_translator()
    {
        $this->serviceLocator->setService('config', [
            'proophessor' => [
                'event_store' => [
                    'repository_map' => [
                        'test_repo' => [
                            'repository_class' => UserMockRepository::class,
                            'aggregate_type' => UserMock::class,
                        ]
                    ]
                ]
            ]
        ]);

        $factory = new AbstractRepositoryFactory();

        $this->assertTrue($factory->canCreateServiceWithName($this->serviceLocator, 'test_repo', 'test_repo'));

        /** @var $repo UserMockRepository */
        $repo = $factory->createServiceWithName($this->serviceLocator, 'test_repo', 'test_repo');

        $this->assertInstanceOf(UserMockRepository::class, $repo);
        $this->assertEquals(UserMock::class, $repo->getAggregateType()->toString());
        $this->assertSame($this->getEventStore(), $repo->getEventStore());
        $this->assertSame($this->strategy, $repo->getStreamStrategy());
        $this->assertSame($this->translator, $repo->getTranslator());
    }

    /**
     * @test
     */
    function it_uses_defined_strategy_and_aggregate_translator_if_specified()
    {
        $strategy = new SingleStreamStrategy($this->getEventStore(), 'other_stream');
        $translator = new DefaultAggregateTranslator();

        $this->serviceLocator->setService('other_stream_strategy', $strategy);
        $this->serviceLocator->setService('other_aggregate_translator', $translator);

        $this->serviceLocator->setService('config', [
            'proophessor' => [
                'event_store' => [
                    'repository_map' => [
                        'test_repo' => [
                            'repository_class' => UserMockRepository::class,
                            'aggregate_type' => UserMock::class,
                            'stream_strategy' => 'other_stream_strategy',
                            'aggregate_translator' => 'other_aggregate_translator'
                        ]
                    ]
                ]
            ]
        ]);

        $factory = new AbstractRepositoryFactory();

        $this->assertTrue($factory->canCreateServiceWithName($this->serviceLocator, 'test_repo', 'test_repo'));

        /** @var $repo UserMockRepository */
        $repo = $factory->createServiceWithName($this->serviceLocator, 'test_repo', 'test_repo');

        $this->assertInstanceOf(UserMockRepository::class, $repo);
        $this->assertEquals(UserMock::class, $repo->getAggregateType()->toString());
        $this->assertSame($this->getEventStore(), $repo->getEventStore());
        $this->assertSame($strategy, $repo->getStreamStrategy());
        $this->assertSame($translator, $repo->getTranslator());
    }
} 