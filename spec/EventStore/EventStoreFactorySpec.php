<?php

namespace spec\Prooph\Proophessor\EventStore;

use Doctrine\DBAL\Connection;
use PhpSpec\ObjectBehavior;
use Prooph\EventStore\Adapter\Doctrine\DoctrineEventStoreAdapter;
use Prooph\EventStore\Adapter\Zf2\Zf2EventStoreAdapter;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Feature\Feature;
use Prooph\EventStore\Feature\ZF2FeatureManager;
use Prophecy\Argument;
use Zend\Db\Adapter\Adapter;
use Zend\ServiceManager\ServiceManager;

class EventStoreFactorySpec extends ObjectBehavior
{
    function it_provide_an_event_store_using_the_orm_default_doctrine_connection(ServiceManager $serviceManager, Connection $connection)
    {
        $serviceManager->get('doctrine.connection.orm_default')->willReturn($connection);
        $serviceManager->has('configuration')->willReturn(true);
        $serviceManager->get('configuration')->willReturn([
            'proophessor' => [
                'event_store' => [
                    'adapter' => [
                        'type' => DoctrineEventStoreAdapter::class,
                        'options' => [
                            'doctrine_connection_alias' => 'orm_default'
                        ]
                    ]
                ]
            ]
        ]);

        $eventStore = $this->createService($serviceManager);

        $eventStore->shouldBeAnInstanceOf(EventStore::class);
        $eventStore->getAdapter()->shouldReturnAnInstanceOf(DoctrineEventStoreAdapter::class);
    }

    function it_provide_an_event_store_using_a_doctrine_connection_configuration(ServiceManager $serviceManager, Connection $connection)
    {
        $serviceManager->has('configuration')->willReturn(true);
        $serviceManager->get('configuration')->willReturn([
            'proophessor' => [
                'event_store' => [
                    'adapter' => [
                        'type' => DoctrineEventStoreAdapter::class,
                        'options' => [
                            'connection' => [
                                'driver' => 'pdo_sqlite',
                                'memory'   => true
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $eventStore = $this->createService($serviceManager);

        $eventStore->shouldBeAnInstanceOf(EventStore::class);
        $eventStore->getAdapter()->shouldReturnAnInstanceOf(DoctrineEventStoreAdapter::class);
    }

    function it_provide_an_event_store_using_the_zf2_adapter_with_the_default_zend_db_adapter(ServiceManager $serviceManager, Adapter $dbAdapter)
    {
        $serviceManager->get('Zend\Db\Adapter')->willReturn($dbAdapter);
        $serviceManager->has('configuration')->willReturn(true);
        $serviceManager->get('configuration')->willReturn([
            'proophessor' => [
                'event_store' => [
                    'adapter' => [
                        'type' => Zf2EventStoreAdapter::class,
                        'options' => [
                            'zend_db_adapter' => 'Zend\Db\Adapter'
                        ]
                    ]
                ]
            ]
        ]);

        $eventStore = $this->createService($serviceManager);

        $eventStore->shouldBeAnInstanceOf(EventStore::class);
        $eventStore->getAdapter()->shouldReturnAnInstanceOf(Zf2EventStoreAdapter::class);
    }

    function it_set_up_a_feature_manager_with_a_reference_to_the_main_service_locator(ServiceManager $serviceManager, Adapter $dbAdapter, Feature $feature)
    {
        $serviceManager->get('Zend\Db\Adapter')->willReturn($dbAdapter);
        $serviceManager->has('es_feature')->willReturn(true);
        $serviceManager->get('es_feature')->willReturn($feature);
        $serviceManager->has('configuration')->willReturn(true);
        $serviceManager->get('configuration')->willReturn([
            'proophessor' => [
                'event_store' => [
                    'adapter' => [
                        'type' => Zf2EventStoreAdapter::class,
                        'options' => [
                            'zend_db_adapter' => 'Zend\Db\Adapter'
                        ]
                    ],
                    'feature_manager' => [
                        'factories' => [
                            'es_feature' => function (ZF2FeatureManager $featureManager) {
                                return $featureManager->getServiceLocator()->get('es_feature');
                            }
                        ]
                    ],
                    'features' => [
                        'es_feature'
                    ]
                ]
            ]
        ]);

        $eventStore = $this->createService($serviceManager);

        $feature->setUp($eventStore)->shouldHaveBeenCalled();
    }
}
