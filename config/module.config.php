<?php
/*
 * This file is part of prooph/proophessor.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 3/29/15 - 10:42 PM
 */
return [
    'proophessor' => [
        'event_store' => [
            'single_stream_name' => 'proophessor_event_stream',
            'repository_map' => [
                //you can define custom dependencies for your repository by
                //pointing to aliases of custom translator or stream implementations
                /*'MyRepositoryAlias' => [
                    'repository_class' => 'My\Aggregate\Repository',
                    'aggregate_type' => 'My\Aggregate\Class',
                    'aggregate_translator' => 'AliasPointingToTranslator', //optional, defaults to: proophessor.event_store.default_aggregate_translator
                    'stream_strategy' => 'AliasPointingToAStreamStrategy', //optional, defaults to: proophessor.event_store.default_stream_strategy
                ],*/
            ],
        ],
        /**
         * Define a list of utils that should be used by the command bus.
         * Each util should be available as a service.
         * Use the ServiceManager alias in the list.
         */
        'command_bus' => [
            //Default list
            'prooph.psb.command_router',
            'prooph.psb.service_locator_proxy',
            'prooph.psb.handle_command_invoke_strategy',
            'proophessor.transaction_manager',
        ],
        /**
         * Define a list of utils that should be used by the event bus.
         * Each util should be available as a service.
         * Use the ServiceManager alias in the list.
         */
        'event_bus' => [
            //Default list
            'prooph.psb.event_router',
            'prooph.psb.service_locator_proxy',
            'prooph.psb.on_event_invoke_strategy',
        ],
        /**
         * Configure command routing
         * @see https://github.com/prooph/service-bus/blob/master/docs/plugins.md#proophservicebusroutercommandrouter
         */
        'command_router_map' => [

        ],
        /**
         * Configure event routing
         * @see https://github.com/prooph/service-bus/blob/master/docs/plugins.md#proophservicebusroutereventrouter
         */
        'event_router_map' => [

        ]
    ],
    'service_manager' => [
        'invokables' => [
            'proophessor.event_store.default_aggregate_translator' => \Prooph\EventSourcing\EventStoreIntegration\AggregateTranslator::class,
            'prooph.psb.handle_command_invoke_strategy' => \Prooph\ServiceBus\InvokeStrategy\HandleCommandStrategy::class,
            'prooph.psb.on_event_invoke_strategy' => \Prooph\ServiceBus\InvokeStrategy\OnEventStrategy::class,
        ],
        'factories' => [
            'proophessor.event_store' => \Prooph\Proophessor\EventStore\EventStoreFactory::class,
            'proophessor.command_bus' => \Prooph\Proophessor\ServiceBus\CommandBusFactory::class,
            'proophessor.event_bus'   => \Prooph\Proophessor\ServiceBus\EventBusFactory::class,
            'proophessor.transaction_manager' => \Prooph\Proophessor\EventStore\TransactionManagerFactory::class,
            'prooph.psb.command_router' => \Prooph\Proophessor\ServiceBus\CommandRouterFactory::class,
            'prooph.psb.event_router' => \Prooph\Proophessor\ServiceBus\EventRouterFactory::class,
            'prooph.psb.service_locator_proxy' => \Prooph\Proophessor\ServiceBus\ServiceLocatorProxyFactory::class,
            'proophessor.event_store.single_stream_strategy' => \Prooph\Proophessor\EventStore\SingleStreamStrategyFactory::class,
            'proophessor.event_store.aggregate_type_stream_strategy' => \Prooph\Proophessor\EventStore\AggregateTypeStreamStrategyFactory::class,
            'proophessor.event_store.aggregate_stream_strategy' => \Prooph\Proophessor\EventStore\AggregateStreamStrategyFactory::class,
        ],
        'abstract_factories' => [
            \Prooph\Proophessor\EventStore\RepositoryAbstractFactory::class,
        ],
        'aliases' => [
            'proophessor.event_store.default_stream_strategy' => 'proophessor.event_store.single_stream_strategy',
        ]
    ]
];