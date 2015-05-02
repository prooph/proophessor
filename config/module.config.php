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
            'adapter' => [
                'type' => 'Prooph\\EventStore\\Adapter\\Doctrine\\DoctrineEventStoreAdapter',
                'options' => [
                    'doctrine_connection_alias' => 'orm_default',
                    'serializer_adapter' => 'json',
                ],
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
            'prooph.psb.handle_command_invoke_strategy' => \Prooph\ServiceBus\InvokeStrategy\HandleCommandStrategy::class,
            'prooph.psb.on_event_invoke_strategy' => \Prooph\ServiceBus\InvokeStrategy\OnEventStrategy::class,
        ],
        'factories' => [
            'proophessor.event_store' => \Prooph\Proophessor\EventStore\EventStoreFactory::class,
            'proophessor.command_bus' => \Prooph\Proophessor\ServiceBus\CommandBusFactory::class,
            'proophessor.event_bus'   => \Prooph\Proophessor\ServiceBus\EventBusFactory::class,
            'prooph.psb.command_router' => \Prooph\Proophessor\ServiceBus\CommandRouterFactory::class,
            'prooph.psb.event_router' => \Prooph\Proophessor\ServiceBus\EventRouterFactory::class,
            'prooph.psb.service_locator_proxy' => \Prooph\Proophessor\ServiceBus\ServiceLocatorProxyFactory::class,
        ]
    ]
];