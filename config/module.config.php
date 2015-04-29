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
    ],
    'service_manager' => [
        'factories' => [
            'proophessor.event_store' => \Prooph\Proophessor\EventStore\EventStoreFactory::class,
        ]
    ]
];