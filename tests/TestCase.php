<?php
/*
 * This file is part of prooph/proophessor.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 5/5/15 - 6:37 PM
 */
namespace ProophTest\Proophessor;

use Prooph\EventStore\Adapter\InMemoryAdapter;
use Prooph\EventStore\Configuration\Configuration;
use Prooph\EventStore\EventStore;

class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventStore
     */
    protected $eventStore;

    /**
     * @return EventStore
     */
    protected function getEventStore()
    {
        if (is_null($this->eventStore)) {
            $inMemoryAdapter = new InMemoryAdapter();

            $config = new Configuration();

            $config->setAdapter($inMemoryAdapter);

            $this->eventStore = new EventStore($config);
        }

        return $this->eventStore;
    }
} 