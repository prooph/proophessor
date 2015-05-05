<?php
/*
 * This file is part of prooph/proophessor.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 5/5/15 - 7:00 PM
 */
namespace ProophTest\Proophessor\Mock;
use Prooph\EventStore\Aggregate\AggregateRepository;


/**
 * Class UserMockRepository
 *
 * @package ProophTest\Proophessor\Mock
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
final class UserMockRepository extends AggregateRepository
{
    /**
     * @return \Prooph\EventStore\Aggregate\AggregateTranslator
     */
    public function getTranslator()
    {
        return $this->aggregateTranslator;
    }

    /**
     * @return \Prooph\EventStore\Stream\StreamStrategy
     */
    public function getStreamStrategy()
    {
        return $this->streamStrategy;
    }

    /**
     * @return \Prooph\EventStore\EventStore
     */
    public function getEventStore()
    {
        return $this->eventStore;
    }

    /**
     * @return \Prooph\EventStore\Aggregate\AggregateType
     */
    public function getAggregateType()
    {
        return $this->aggregateType;
    }
} 