<?php
/*
 * This file is part of prooph/link.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 4/4/15 - 9:58 PM
 */
namespace Prooph\Proophessor\Schema;

use Doctrine\DBAL\Schema\Schema;

/**
 * Class EventStoreSchema
 *
 * @package Prooph\Proophessor\Schema
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
final class EventStoreSchema
{
    public static function createSchema(Schema $schema, $streamName = 'proophessor_event_stream')
    {
        $eventStream = $schema->createTable($streamName);

        $eventStream->addColumn('event_id', 'string', ['length' => 36]);        //UUID of the event
        $eventStream->addColumn('version', 'integer');                          //Version of the aggregate after event was recorded
        $eventStream->addColumn('event_name', 'string', ['length' => 100]);     //Name of the event
        $eventStream->addColumn('event_class', 'string', ['length' => 100]);    //Class of the event
        $eventStream->addColumn('payload', 'text');                             //Event payload
        $eventStream->addColumn('created_at', 'string', ['length' => 100]);     //DateTime ISO8601 when the event was recorded
        $eventStream->addColumn('aggregate_id', 'string', ['length' => 36]);    //UUID of linked aggregate
        $eventStream->addColumn('aggregate_type', 'string', ['length' => 100]); //Class of the linked aggregate
        $eventStream->addColumn('causation_id', 'string', ['length' => 36]);    //UUID of the command which caused the event
        $eventStream->addColumn('causation_name', 'string', ['length' => 100]); //Name of the command which caused the event
        $eventStream->addColumn('dispatch_status', 'integer', ['length' => 1]); //EventDispatcher Status: 0 = not dispatched, 1 = in progress, 2 = success, 3 = failed
        $eventStream->setPrimaryKey(['event_id']);
        $eventStream->addUniqueIndex(['aggregate_id', 'aggregate_type', 'version'], $streamName . '_m_v_uix'); //Concurrency check on database level
    }

    public static function dropSchema(Schema $schema, $streamName = 'proophessor_event_stream')
    {
        $schema->dropTable($streamName);
    }
} 