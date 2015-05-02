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

## Introduction

to be defined ...

## Example Application

to be defined ...

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

As a ZF2 dev you should be familiar with the way of configuring things in a ZF2 application. The module groups everything
related to the CQRS + ES infrastructure under the root config key `proophessor`.

### Event Store Adapter

One of the crucial configuration points is the database connection for the event store. Proophessor assumes that you have
a doctrine connection defined called `orm_default`, because this is the default when using the doctrine-orm-module
mentioned above. However, you can override the default with a dedicated connection or even another adapter for the event store:

#### Configure Own Doctrine Adapter Connection
```php
<?php
//In your own module.config.php or in the application autoload/local.php
return [
    'proophessor' => [
        'event_store' => [
            'adapter' => [
                'options' => [
                    'connection' => [
                        'dbname' => 'my_db',
                        'driver' => 'pdo_mysql',
                        'host' => 'localhost',
                        'user' => 'root',
                        'charset' => 'utf8',
                    ]
                ]
            ]
        ]
    ]
];
```

#### Use Another Doctrine Connection Alias

```php
<?php
//In your own module.config.php or in the application autoload/local.php
return [
    'proophessor' => [
        'event_store' => [
            'adapter' => [
                'options' => [
                    'doctrine_connection_alias' => 'event_store_default'
                ]
            ]
        ]
    ]
];
```

### Aggregate Repository

Proophessor ships with a [AbstractRepositoryFactory](src/EventStore/AbstractRepositoryFactory.php) which simplifies the
instantiation of a repository. As long as you use the defaults provided by proophessor you don't need to worry about stream
strategies or a translator for your aggregates. Just tell proophessor which repository class is responsible for which aggregate type.

```php
<?php
//In your own module.config.php or in the application autoload/global.php
return [
    'proophessor' => [
        'event_store' => [
            'repository_map' => [
                'my.aggregate_repository.alias' => [
                    'repository_class' => MyAggregateRepository::class,
                    'aggregate_type'   => MyAggregate::class,
                ]
            ]
        ]
    ]
];
```

A configuration like shown above allows you to retrieve a repository from the service manager:

```php
<?php
//Somewhere in a command handler factory ...
$myAggregateRepository = $serviceLocator->get('my.aggregate_repository.alias');
```

### Event Store Features

to be defined ....

### Service Bus Utils

Command/event bus utilities like a custom invoke strategy or a logger can be added via configuration.
Checkout the [module.config.php](config/module.config.php) for details.

### Command Routing

The command bus is set up with a [CommandRouter](https://github.com/prooph/service-bus/blob/master/docs/plugins.md#proophservicebusroutercommandrouter).
The routing map can be defined in the configuration. Checkout the [module.config.php](config/module.config.php) for details.

### Event Routing

The event bus is set up with a [EventRouter](https://github.com/prooph/service-bus/blob/master/docs/plugins.md#proophservicebusroutereventrouter).
The routing map can be defined in the configuration. Checkout the [module.config.php](config/module.config.php) for details.


## Registered Services

### Retrieving The EventStore

The ProophEventStore can be retrieved from the service manager by using the alias `proophessor.event_store`.

### Retrieving The CommandBus

The ProophServiceBus command bus can be retrieved from the service manager by using the alias `proophessor.command_bus`.

### Retrieving The EventBus

The ProophServiceBus event bus can be retrieved from the service manager by using the alias `proophessor.event_bus`.

## Working With The Command Bus

The command bus is the gate to the domain model. It is a rule of thumb to not use domain classes outside of the model.
The domain model should be protected by an application layer with a well defined API of actions that can be triggered in the
domain model. In CQRS the application API is defined through commands which are messages (like DTOs) with a specific intention.
The command bus is responsible for dispatching these commands to so called command handlers. Each command should have exactly one
command handler and each command handler should only handle one command (1:1 relationship).
In a proophessor system you define such a connection with a `command_router_map` in the application configuration.

```php
<?php
//In your own module.config.php or in the application autoload/global.php
return [
    'proophessor' => [
        'command_router_map' => [
            \Application\Model\Command\RegisterUser::class => \Application\Model\User\RegisterUserHandler::class,
        ],
    ];
```

The target of the command (RegisterUserHandler::class in the example) should be a service name known by the service manager.
Command handlers are only instantiated when an appropriate command is dispatched (lazy loading), so you can define hundreds
of commands and command handlers in your application without worrying about performance issues caused by heavy object creation.

```php
<?php
//In your own module.config.php or in the application autoload/global.php
return [
    'service_manager' => array(
        'factories' => [
            \Application\Model\User\RegisterUserHandler::class => \Application\Infrastructure\HandlerFactory\RegisterUserHandlerFactory::class,
        ],
    ];
```

In our example above we use the PHP 5.5 class constant feature to define the class name of the `RegisterUserHandler` as service name
and map it to a factory which is responsible to instantiate a `RegisterUserHandler` object. But again, the factory is only invoked when
a `RegisterUser` command is dispatched by the command bus.

### Command

All commands should extend `Prooph\Common\Messaging\Command`. The `RegisterUser` command would look something like this:

```php
<?php
namespace Application\Model\Command;

use Application\Model\User\EmailAddress;
use Application\Model\User\UserId;
use Prooph\Common\Messaging\Command;

final class RegisterUser extends Command
{
    /**
     * @param string $userId
     * @param string $name
     * @param string $email
     * @return RegisterUser
     */
    public static function withData($userId, $name, $email)
    {
        return new self(__CLASS__, [
            'user_id' => (string)$userId,
            'name' => (string)$name,
            'email' => (string)$email
        ]);
    }

    /**
     * @return UserId
     */
    public function userId()
    {
        return UserId::fromString($this->payload['user_id']);
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->payload['name'];
    }

    /**
     * @return EmailAddress
     */
    public function emailAddress()
    {
        return EmailAddress::fromString($this->payload['email']);
    }
}
```

We use a named constructor to instantiate the command. The method takes only native PHP types as arguments.
This is due to the fact that the command is instantiated in userland code (for example a controller). In userland code
our domain model classes should not be used or even not be known.
The first argument of the class constructor is the name of the command. We use the class as command name but you could
also use another name if you don't want your command name look like a PHP namespace.
The second argument is a payload array. It is later used in the getter methods to instantiate value objects from it if
required by the model. `The payload should only contain scalar types and arrays` because only these types allow
a secure way to convert a command to a remote message which can be send to a remote system or pushed on a job queue.
Proophessor doesn't work with serializers or annotations to help you with type mapping, because they slow down the system
and add complexity.

### Transaction Handling

Proophessor automatically handles transactions for you. Each time you dispatch a command a new transaction is started.
A successful dispatch commits the transaction and an error causes a rollback. `Proophessor only opens one transaction.`
If you work with a process manager which listens on synchronous dispatched events and the process manager dispatches
follow up commands, these commands are handled `in the same transaction as the first command`. If a follow up command fails
the transaction is completely rolled back including `all recorded events` and potential `changes in the read model`.
Again, this only happens if your events are dispatched `synchronous` and if the event store and the read model `share the same
database connection`.

## Domain Model

Proophessor ships with [prooph/event-sourcing](https://github.com/prooph/event-sourcing) which turns your entities into
event sourced aggregate roots. You should follow two rules so that proophessor is able to handle your aggregate roots correctly.

1. All aggregate roots should extend `Prooph\EventSourcing\AggregateRoot` and implement the protected function `AggregateRoot::aggregateId`
2. All aggregate root domain events should extend `Prooph\EventSourcing\AggregateChanged` which inherits from `Prooph\Common\Messaging\DomainEvent`.

### Aggregate Root

```php
<?php
namespace Application\Model\User;

use Assert\Assertion;
use Prooph\EventSourcing\AggregateRoot;

final class User extends AggregateRoot
{
    /**
     * @var UserId
     */
    private $userId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var EmailAddress
     */
    private $emailAddress;

    /**
     * @param UserId $userId
     * @param string $name
     * @param EmailAddress $emailAddress
     * @return User
     */
    public static function registerWithData(UserId $userId, $name, EmailAddress $emailAddress)
    {
        $self = new self();

        $self->assertName($name);

        $self->recordThat(UserWasRegistered::withData($userId, $name, $emailAddress));

        return $self;
    }

    /**
     * @return UserId
     */
    public function userId()
    {
        return $this->userId;
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @return EmailAddress
     */
    public function emailAddress()
    {
        return $this->emailAddress;
    }

    /**
     * @return string representation of the unique identifier of the aggregate root
     */
    protected function aggregateId()
    {
        return $this->userId->toString();
    }

    /**
     * @param UserWasRegistered $event
     */
    protected function whenUserWasRegistered(UserWasRegistered $event)
    {
        $this->userId = $event->userId();
        $this->name = $event->name();
        $this->emailAddress = $event->emailAddress();
    }

    /**
     * @param string $name
     * @throws InvalidName
     */
    private function assertName($name)
    {
        try {
            Assertion::string($name);
            Assertion::notEmpty($name);
        } catch (\Exception $e) {
            throw InvalidName::reason($e->getMessage());
        }
    }
}
```

An event sourced aggregate root looks different from entities you may have used in the past. `Prooph\EventSourcing\AggregateRoot`
forces implementers to provide at least one named constructor like the `User::registerWithData` method shown in the example.
Such a named constructor should then invoke the class constructor without any arguments and then record the first
domain event of the aggregate root. Internally the new event is added to the list of pending events (waiting to be stored in the event stream on transaction commit)
and then forwarded to a special setter method which has the naming convention `when<ShortEventName>`. In the example it is the method
`User::whenUserWasRegistered`. Object properties should be first set in such a domain event setter method because the setter is also used later when reconstituting
the aggregate root. Methods changing the state of the aggregate root should work in a similar way:

1. Assert that changes can be made
2. Record new domain event which indicates the changes
3. Use a domain event setter method to adopt the changes

### AggregateChanged Domain Event

```php
<?php
namespace Application\Model\User;

use Assert\Assertion;
use Prooph\EventSourcing\AggregateChanged;
use Prooph\Proophessor\EventStore\IsProcessedAsync;

final class UserWasRegistered extends AggregateChanged
{
    private $userId;

    private $username;

    private $emailAddress;

    /**
     * @param UserId $userId
     * @param string $name
     * @param EmailAddress $emailAddress
     * @return UserWasRegistered
     */
    public static function withData(UserId $userId, $name, EmailAddress $emailAddress)
    {
        Assertion::string($name);

        $event = self::occur($userId->toString(), [
            'name' => $name,
            'email' => $emailAddress->toString(),
        ]);

        $event->userId = $userId;
        $event->username = $name;
        $event->emailAddress = $emailAddress;

        return $event;
    }

    /**
     * @return UserId
     */
    public function userId()
    {
        if (is_null($this->userId)) {
            $this->userId = UserId::fromString($this->aggregateId);
        }
        return $this->userId;
    }

    /**
     * @return string
     */
    public function name()
    {
        if (is_null($this->username)) {
            $this->username = $this->payload['name'];
        }
        return $this->username;
    }

    /**
     * @return EmailAddress
     */
    public function emailAddress()
    {
        if (is_null($this->emailAddress)) {
            $this->emailAddress = EmailAddress::fromString($this->payload['email']);
        }
        return $this->emailAddress;
    }
}
```

The AggregateChanged class inherits from `Prooph\Common\Messaging\DomainEvent` which has the same base class as
`Prooph\Common\Messaging\Command` namely `Prooph\Common\Messaging\DomainMessage`. Other than a command the named
constructor of an AggregateChanged domain event should take value objects of the domain model as arguments whenever possible.
As you can see in the example we use some kind of object caching to avoid object creation of the value objects when the getter methods
are called.


`But why can't we simply pass the value objects to the payload?` The answer is the same as for commands.
Proophessor doesn't use serializers or other type mapping techniques. The payload of a domain event is just `json_encode`d when
storing it in the event store or dispatching it to a remote system. `So the payload should only contain scalar values or arrays!`
It is your job to convert your value objects into native PHP types and back. Proophessor can't know the structure of your value objects
and we don't want to rely on generic mapping. It simplifies things in the first place but sooner or later you would run into trouble with it
or encounter performance bottlenecks. Some seconds more work when coding the classes will save you a lot of headache later!


The AggregateChanged class provides a named constructor which should be used internally: `AggregateChanged::occur`.
First argument of the method should be the identifier of the related aggregate root given as a string. Second argument is the
already mentioned payload. The message name of a AggregateChanged domain event defaults to the class name of the implementing class.
However, you can change the name by setting the `name` property of the event to another value after instantiation.

## Working With Repositories

to be defined ...

## Working With The Event Bus

to be defined ...


