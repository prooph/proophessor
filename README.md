# proophessor

CQRS + ES for ZF2

Proophessor combines [prooph/service-bus](https://github.com/prooph/service-bus), [proop/event-store](https://github.com/prooph/event-store) and [prooph/event-sourcing](https://github.com/prooph/event-sourcing) in a single ZF2 module. Goal is to simplify the set up process for a full featured CQRS + ES system.

## Planned Key Facts
- Default configuration to get started in no time
- Transaction handling
  - wrap command dispatch with a transaction
  - link recorded events with command to ease debugging
- Synchronous and asynchronous event dispatching
  - Sync dispatch within the command transaction to make sure that read model is always up to date
  - Async dispatch, the event store will act as a job queue so that multiple worker can pull events from it
- Common command and event objects to ease communication between prooph components and reduce translation overhead
- Apigility support
  - Messagebox endpoint for commands
  - Read access to the event store
- ZF2 developer toolbar integration
  - monitor commands and recorded events
  - replay event stream to a specific version
- Snapshot functionality for aggregates

## Installation

### Proophessor Module
Of course proophessor is available on packagist. Simply add `"prooph/proophessor" : "dev-master"` to your composer.json.
As this is a ZF2 Module you need to enable it in your `application.config.php` with the module name `Prooph\Proophessor`.

### Event Store Schema

By default proophessor connects to a RDBMS using the [doctrine adapter](https://github.com/prooph/event-store-doctrine-adapter) for prooph/event-store.
The [stream strategy](https://github.com/prooph/event-store#streamstrategies) defaults to a SingleStreamStrategy using a table called `proophessor_event_stream`.
There are two ways available to create this table:

#### Using Doctrine Migrations

The recommended way is to use doctrine migrations. Therefor you need to install the [doctrine-orm-module](https://github.com/doctrine/DoctrineORMModule)
and use the appropriate CLI commands of the module to create and run migrations.
Proophessor ships with a `EventStoreSchema` class which you can use in a migrations script to set up the event stream table.

```php
<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;
use Prooph\Proophessor\Schema\EventStoreSchema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20150429205328 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        EventStoreSchema::createSchema($schema);
    }

    public function down(Schema $schema)
    {
        EventStoreSchema::dropSchema($schema);
    }
}
```

#### Using SQL

If you don't want to use doctrine migrations or doctrine at all (there are also other event store adapters available)
you can use the SQL located in the `scripts` folder to create the event stream table manually.

## Configuration





