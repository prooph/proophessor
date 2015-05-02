<?php
/*
 * This file is part of prooph/proophessor.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 5/2/15 - 8:15 PM
 */
namespace Prooph\Proophessor\EventStore;

/**
 * Definition DispatchStatus
 *
 * The dispatch status of a domain event tells a worker if he should pull the event from the event stream to dispatch it.
 *
 * @package Prooph\Proophessor\EventStore
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
final class DispatchStatus 
{
    const NOT_STARTED = 0; //Pull event and dispatch it via event bus
    const RUNNING = 1;     //Event was pulled by another worker and is currently dispatched
    const SUCCEED = 2;     //Event dispatch was successful
    const FAILED  = 3;     //Event dispatch failed
} 