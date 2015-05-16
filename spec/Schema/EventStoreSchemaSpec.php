<?php

namespace spec\Prooph\Proophessor\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use PhpSpec\ObjectBehavior;
use Prooph\Proophessor\Schema\EventStoreSchema;
use Prophecy\Argument;

class EventStoreSchemaSpec extends ObjectBehavior
{
    function it_creates_event_stream_schema_using_default_stream_name(Schema $doctrineSchema, Table $table)
    {
        $doctrineSchema->createTable('proophessor_event_stream')->willReturn($table);
        EventStoreSchema::createSchema($doctrineSchema->getWrappedObject());
    }

    function it_creates_event_stream_schema_using_custom_stream_name(Schema $doctrineSchema, Table $table)
    {
        $doctrineSchema->createTable('custom_stream_name')->willReturn($table);
        EventStoreSchema::createSchema($doctrineSchema->getWrappedObject(), 'custom_stream_name');
    }

    function it_drops_event_stream_schema_using_default_stream_name(Schema $doctrineSchema, Table $table)
    {
        $doctrineSchema->dropTable('proophessor_event_stream')->shouldBeCalled();
        EventStoreSchema::dropSchema($doctrineSchema->getWrappedObject());
    }

    function it_drops_event_stream_schema_using_custom_stream_name(Schema $doctrineSchema, Table $table)
    {
        $doctrineSchema->dropTable('custom_stream_name')->shouldBeCalled();
        EventStoreSchema::dropSchema($doctrineSchema->getWrappedObject(), 'custom_stream_name');
    }
}
