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
namespace spec\Prooph\Proophessor\EventStore;

use PhpSpec\ObjectBehavior;
use Prooph\Common\Event\ActionEventDispatcher;
use Prooph\EventStore\Aggregate\AggregateTranslator;
use Prooph\EventStore\Aggregate\DefaultAggregateTranslator;
use Prooph\EventStore\Configuration\Exception\ConfigurationException;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Stream\SingleStreamStrategy;
use Prooph\EventStore\Stream\StreamStrategy;
use Prooph\Proophessor\Stub\UserRepositoryStub;
use Prooph\Proophessor\Stub\UserStub;
use Zend\ServiceManager\ServiceManager;

/**
 * Class RepositoryAbstractFactorySpec
 *
 * @package ProophTest\Proophessor\EventStore
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class RepositoryAbstractFactorySpec extends ObjectBehavior
{

    public function let(ServiceManager $serviceLocator, ActionEventDispatcher $actionEventDispatcher, EventStore $eventStore, AggregateTranslator $aggregateTranslator, StreamStrategy $streamStrategy)
    {
        $eventStore->getActionEventDispatcher()->willReturn($actionEventDispatcher);
        $serviceLocator->get('proophessor.event_store.default_aggregate_translator')->willReturn($aggregateTranslator);
        $serviceLocator->get('proophessor.event_store.default_stream_strategy')->willReturn($streamStrategy);
        $serviceLocator->get('proophessor.event_store')->willReturn($eventStore);
        $serviceLocator->has('config')->willReturn(true);
        $serviceLocator->get('config')->willReturn([
            'proophessor' => [
                'event_store' => [
                    'repository_map' => [
                        'test_repo' => [
                            'repository_class' => UserRepositoryStub::class,
                            'aggregate_type' => UserStub::class,
                        ]
                    ]
                ]
            ]
        ]);
    }

    function it_can_create_a_configured_repository_using_a_default_strategy_and_aggregate_translator($serviceLocator, $eventStore, $aggregateTranslator, $streamStrategy)
    {
        $this->canCreateServiceWithName($serviceLocator, 'test_repo', 'test_repo')->shouldReturn(true);

        $repo = $this->createServiceWithName($serviceLocator, 'test_repo', 'test_repo');

        $repo->getEventStore()->shouldBe($eventStore);
        $repo->getStreamStrategy()->shouldBe($streamStrategy);
        $repo->getTranslator()->shouldBe($aggregateTranslator);
    }

    function it_uses_defined_strategy_and_aggregate_translator_if_specified($serviceLocator, $eventStore)
    {
        $strategy = new SingleStreamStrategy($eventStore->getWrappedObject(), 'other_stream');
        $translator = new DefaultAggregateTranslator();

        $serviceLocator->get('other_stream_strategy')->willReturn($strategy);
        $serviceLocator->get('other_aggregate_translator')->willReturn($translator);
        $serviceLocator->get('config')->willReturn([
            'proophessor' => [
                'event_store' => [
                    'repository_map' => [
                        'test_repo' => [
                            'repository_class' => UserRepositoryStub::class,
                            'aggregate_type' => UserStub::class,
                            'stream_strategy' => 'other_stream_strategy',
                            'aggregate_translator' => 'other_aggregate_translator'
                        ]
                    ]
                ]
            ]
        ]);



        $this->canCreateServiceWithName($serviceLocator, 'test_repo', 'test_repo')->shouldReturn(true);

        $repo = $this->createServiceWithName($serviceLocator, 'test_repo', 'test_repo');

        $repo->getEventStore()->shouldBe($eventStore);
        $repo->getStreamStrategy()->shouldBe($strategy);
        $repo->getTranslator()->shouldBe($translator);
    }

    function it_cannot_create_repository_if_no_config_is_available(ServiceManager $emptyServiceManager)
    {
        $this->canCreateServiceWithName($emptyServiceManager, 'test_repo', 'test_repo')->shouldReturn(false);
    }

    function it_cannot_create_repository_if_no_proophessor_event_store_config_is_available(ServiceManager $emptyServiceManager)
    {
        $emptyServiceManager->has('config')->willReturn(true);
        $emptyServiceManager->get('config')->willReturn([
            'proophessor' => []
        ]);

        $this->canCreateServiceWithName($emptyServiceManager, 'test_repo', 'test_repo')->shouldReturn(false);
    }

    function it_throws_configuration_exception_if_repository_class_is_missing(ServiceManager $emptyServiceManager)
    {
        $emptyServiceManager->has('config')->willReturn(true);
        $emptyServiceManager->get('config')->willReturn([
            'proophessor' => [
                'event_store' => [
                    'repository_map' => [
                        'test_repo' => [
                            'aggregate_type' => UserStub::class,
                        ]
                    ]
                ]
            ]
        ]);

        $this->canCreateServiceWithName($emptyServiceManager, 'test_repo', 'test_repo')->shouldReturn(true);

        $this->shouldThrow(ConfigurationException::class)->during('createServiceWithName', [$emptyServiceManager, 'test_repo', 'test_repo']);
    }

    function it_throws_configuration_exception_if_aggregate_type_is_missing(ServiceManager $emptyServiceManager)
    {
        $emptyServiceManager->has('config')->willReturn(true);
        $emptyServiceManager->get('config')->willReturn([
            'proophessor' => [
                'event_store' => [
                    'repository_map' => [
                        'test_repo' => [
                            'repository_class' => UserRepositoryStub::class,
                        ]
                    ]
                ]
            ]
        ]);

        $this->canCreateServiceWithName($emptyServiceManager, 'test_repo', 'test_repo')->shouldReturn(true);

        $this->shouldThrow(ConfigurationException::class)->during('createServiceWithName', [$emptyServiceManager, 'test_repo', 'test_repo']);
    }

    function it_throws_configuration_exception_if_repository_class_does_not_exist(ServiceManager $emptyServiceManager)
    {
        $emptyServiceManager->has('config')->willReturn(true);
        $emptyServiceManager->get('config')->willReturn([
            'proophessor' => [
                'event_store' => [
                    'repository_map' => [
                        'test_repo' => [
                            'repository_class' => '\Acme\UnknownRepository',
                            'aggregate_type' => UserStub::class,
                        ]
                    ]
                ]
            ]
        ]);

        $this->canCreateServiceWithName($emptyServiceManager, 'test_repo', 'test_repo')->shouldReturn(true);

        $this->shouldThrow(ConfigurationException::class)->during('createServiceWithName', [$emptyServiceManager, 'test_repo', 'test_repo']);
    }
} 