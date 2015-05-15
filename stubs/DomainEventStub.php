<?php
/*
 * This file is part of prooph/proophessor.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 5/15/15 - 11:03 PM
 */
namespace Prooph\Proophessor\Stub;


use Prooph\Common\Messaging\DomainEvent;

class DomainEventStub extends DomainEvent
{
    /**
     * @param string $eventName
     * @param array $payload
     * @return DomainEventStub
     */
    public static function record($eventName, array $payload = null)
    {
        return new static($eventName, $payload);
    }
} 