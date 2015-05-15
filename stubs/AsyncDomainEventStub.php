<?php
/*
 * This file is part of prooph/proophessor.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 5/15/15 - 11:13 PM
 */
namespace Prooph\Proophessor\Stub;

use Prooph\Proophessor\EventStore\IsProcessedAsync;

class AsyncDomainEventStub extends DomainEventStub implements IsProcessedAsync
{
}