# proophessor

[deprecated - please read below] CQRS + ES for ZF2

[![Build Status](https://travis-ci.org/prooph/proophessor.svg?branch=master)](https://travis-ci.org/prooph/proophessor)
[![Coverage Status](https://coveralls.io/repos/prooph/proophessor/badge.svg?branch=master)](https://coveralls.io/r/prooph/proophessor?branch=master)

## Deprecated Warning: Meta Package

Proophessor started as a ZF2 module to ease integration of prooph components with a ZF2 application. Things have changed in the meanwhile.
Zend is working on ZF3 and a Psr-7 middleware application skeleton which will be an alternative to the MVC stack.
This made us thinking. Currently, proophessor depends on the MVC stack (or at least on the module system). It can not be used in any other context. Which is a bad situation.
Sure, one can take the single prooph components and integrate them in his/her framework of choice. But all the factory logic included in proophessor
can not be used outside of this package.

Since zend-servicemanager v2.6 the `Interop\ContainerInterface` is supported and more importantly we will release new major versions
for all prooph components soon which will no longer have hard dependencies to heavy zend components like zend-eventmanager and
zend-servicemanager.

Both facts bring us to our next tasks. We will extract proophessor's factories and convert them to `invokable container factories`.
If you are interested in what that means refer to one of the components issues like [this one](https://github.com/prooph/event-store/issues/57).

The `TransactionManager` shipped with proophessor will be moved into a new prooph component. If all these tasks are done proophessor
will be empty.

### So what purpose will it have then?
Well, proophessor will act as a meta package containing no source code but a cookbook full
of usage examples and an overview of the prooph ecosystem.

In the meanwhile please sit back or help us with the tasks mentioned above. You can join our [![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/prooph/improoph)
and ask for a task. We appreciate any help.

------

What follows is the old README of proophessor. Still useful if you want to read through a full description of how prooph components work together and
how a CQRS + ES set up can look like in a ZF2 application.

Proophessor combines [prooph/service-bus](https://github.com/prooph/service-bus), [proop/event-store](https://github.com/prooph/event-store) and [prooph/event-sourcing](https://github.com/prooph/event-sourcing) in a single ZF2 module to simplify the set up process for a full featured CQRS + ES system.

## Key Facts
- [x] CQRS messaging tools
- [x] Event Store implementation
- [x] Event Sourcing base classes
- [x] Default configuration to get started in no time
- [x] Automatic transaction handling
  - handle command dispatch in a transaction
  - link recorded events with causative command
  - include synchronous dispatched events in transaction
- [ ] Synchronous and asynchronous event dispatching
  - [x] Sync dispatch: within the command transaction to make sure that read model is always up to date
  - [ ] Async dispatch: the event store will act as a job queue so that multiple worker can pull events from it
- [x] Common command and event objects to ease communication between prooph components and reduce translation overhead
- [ ] Apigility support
  - Messagebox endpoint for commands
  - Read access to the event store
- [ ] ZF2 developer toolbar integration
  - monitor commands and recorded events
  - replay event stream to a specific version
- [ ] Snapshot functionality for aggregates
 
## Apps Using Proophessor
- [prooph LINK](https://github.com/prooph/link)

## Example Application

Try out [proophessor-do](https://github.com/prooph/proophessor-do) and [pick up a task](https://github.com/prooph/proophessor-do#learning-by-doing)!

## Installation

### Proophessor Module
Of course proophessor is available on packagist. Simply add `"prooph/proophessor" : "~0.1"` to your composer.json.
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

Proophessor ships with a [RepositoryAbstractFactory](src/EventStore/RepositoryAbstractFactory.php) which simplifies the
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
    'service_manager' =>[
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
required by the model.


`The payload should only contain scalar types and arrays` because only these types allow
a secure way to convert a command to a remote message which can be send to a remote system or pushed on a job queue.
Proophessor doesn't work with serializers or annotations to help you with type mapping, because they slow down the system
and add complexity.

### Command Flow

A command is normally dispatched from inside a MVC controller action or a process manager (when an domain event causes a follow up command).
Dispatching a command is relatively simple:

```php
<?php
class UserRegistrationController extends AbstractActionController
{
    //The user id is generated by the controller, so that we can provide the client with
    //the identifier to request the user data after registration
    $userId = Uuid::uuid4()->toString();

    //We skip validation in the example for the sake of simplicity
    $command = RegisterUser::withData(
        $userId,
        $this->params()->fromPost('username'),
        $this->params()->fromPost('email')
    );

    //We directly access the service manager in the example for the sake of simplicity
    $this->getServiceLocator()->get('proophessor.command_bus')->dispatch($command);

    //When dispatching a command you get no response from the command bus except an exception is thrown!
    //The client has to request the user data from the read model
    return ['user_id' => $userId];
}
```

Like already mentioned the receiver of such a command should always be a command handler implementing a `handle` method:

```php
<?php
namespace Application\Model\User;

use Application\Model\Command\RegisterUser;

final class RegisterUserHandler
{
    /**
     * @var UserCollection
     */
    private $userCollection;

    /**
     * @param UserCollection $userCollection
     */
    public function __construct(UserCollection $userCollection)
    {
        $this->userCollection = $userCollection;
    }

    /**
     * @param RegisterUser $command
     */
    public function handle(RegisterUser $command)
    {
        $user = User::registerWithData($command->userId(), $command->name(), $command->emailAddress());

        $this->userCollection->add($user);
    }
}
```

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

Each aggregate root should have a corresponding repository. Proophessor works with `collection like repositories` that means
you don't have to call save, persist or a similar method on the repository. Just add a aggregate root to its collection repository
and retrieve it later when you need to trigger changes on the aggregate root.
It is a common practice to define an interface for each repository in your domain model but put the implementation in the infrastructure
because the implementation is connected to a data storage (in our case the event store) which should not leak into the domain model.
A typical repository interface looks like the following:

```php
<?php
namespace Application\Model\User;

interface UserCollection
{
    /**
     * @param User $user
     * @return void
     */
    public function add(User $user);

    /**
     * @param UserId $userId
     * @return User
     */
    public function get(UserId $userId);
}
```

And this is the corresponding implementation using `Prooph\EventStore\Aggregate\AggregateRepository`:

```php
<?php
namespace Application\Infrastructure\Repository;

use Application\Model\User\User;
use Application\Model\User\UserCollection;
use Application\Model\User\UserId;
use Prooph\EventStore\Aggregate\AggregateRepository;

final class EventStoreUserCollection extends AggregateRepository implements UserCollection
{
    /**
     * @param User $user
     * @return void
     */
    public function add(User $user)
    {
        $this->addAggregateRoot($user);
    }

    /**
     * @param UserId $userId
     * @return User
     */
    public function get(UserId $userId)
    {
        return $this->getAggregateRoot($userId->toString());
    }
}
```

Easy, isn't it?

How you can configure proophessor to provide you with such a repository is explained in the [configuration](#aggregate-repository) section.

## Working With The Event Bus

Normally you don't need to interact with the event bus directly. Just add event handlers to the `event_router_map` like described in the [configuration](#event-routing) section.
Like command handlers event handlers are only instantiated when an event is dispatched to a interested handler.
One event can have many event handlers. Therefor the `event_routing_map` defines a 1:n connection:

```php
<?php
//In your own module.config.php or in the application autoload/global.php
return [
    'proophessor' => [
        'event_router_map' => [
            \Application\Model\User\UserWasRegistered::class => [
                \Application\Projection\User\UserProjector::class,
            ]
        ],
    ];
```

Also in this case we use the PHP 5.5 class constant feature to define the class name of the UserProjector as a service name
so that the event bus can retrieve the UserProjector from the service manager.

```php
<?php
//In your own module.config.php or in the application autoload/global.php
return [
    'service_manager' => [
        'factories' => [
            \Application\Model\User\RegisterUserHandler::class => \Application\Infrastructure\HandlerFactory\RegisterUserHandlerFactory::class,
            \Application\Projection\User\UserProjector::class => \Application\Projection\User\UserProjectorFactory::class,
        ],
    ];
```

### Handle Domain Events

Event handlers should implement a method for each event they are interested in. The convention for such a handle method is `on<ShortEventName>`.
Checkout the read model projection example below.

## Read Model Projection

Proophessor doesn't provide a specific read model implementation. It is up to you to implement it. The read model
should be as simple as possible. It is so called `throw away code` because all important information is stored in form of
domain events in the event store. The read model can be regenerated at any time and in any form you need it to let your
application respond fast!

The example shows a simple read model projection using a `Doctrine\DBAL\Connection` to persist the user data in a relational
database table. By default the event store and the read model share the same database. However, if you need more performance or
scalability you can use different storage mechanisms for the event store and the read model.

```php
<?php
namespace Application\Projection\User;

use Application\Model\User\UserWasRegistered;
use Application\Projection\Table;
use Doctrine\DBAL\Connection;

final class UserProjector
{
    /**
     * @var Connection
     */
    private $connection;


    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param UserWasRegistered $event
     */
    public function onUserWasRegistered(UserWasRegistered $event)
    {
        $this->connection->insert(Table::USER, [
            'id' => $event->userId()->toString(),
            'name' => $event->name(),
            'email' => $event->emailAddress()->toString()
        ]);
    }
}
```

### Transaction Handling

Proophessor automatically handles transactions for you. Each time you dispatch a command a new transaction is started.
A successful dispatch commits the transaction and an error causes a rollback. `Proophessor only opens one transaction.`
If you work with a process manager which listens on synchronous dispatched events and the process manager dispatches
follow up commands, these commands are handled `in the same transaction as the first command`. If a follow up command fails
the transaction is completely rolled back including `all recorded events` and potential `changes in the read model`.
Again, this only happens if your events are dispatched `synchronous` and if the event store and the read model `share the same
database connection`.

The TransactionManager is responsible for handling these scenarios. It makes use of the action event systems provided by
prooph/service-bus and prooph/event-store to seamlessly integrate transaction handling.

#### Begin, Commit, Rollback
The TransactionManager registers a listener on the `Prooph\ServiceBus\Process\CommandDispatch::INITIALIZE` action event with a low
priority of `-1000` to begin a new transaction if no one is already started and only if the command extends `Prooph\Common\Messaging\Command` and
does not implement `Prooph\Proophessor\EventStore\AutoCommitCommand`. The latter is a marker interface to tell the TransactionManager that it
should ignore such a command and all domain events caused by it.

Furthermore, the TransactionManager registers a listener on the `Prooph\ServiceBus\Process\CommandDispatch::FINALIZE` action event with a high
priority of `1000`. In the listener the TransactionManager decides either if it commits the current transaction or if it performs a rollback
depending on the current CommandDispatch.

A rollback is performed if `CommandDispatch::getException` returns a caught exception. With that in mind you
can influence the rollback behaviour by registering an own listener on `Prooph\ServiceBus\Process\CommandDispatch::ERROR`, handle a caught exception of
your own (f.e. retry current command, translate exception into DomainEvent, etc.) and unset the exception in the CommandDispatch by invoking
`CommandDispatch::setException` with `NULL` as argument.

If the TransactionManager has an active transaction and no exception was caught during dispatch (or was unset) the transaction gets committed.

#### Event Dispatch and Status
The TransactionManager has a second job which is related to handling the transaction. It adds meta information to each recorded domain event
namely the UUID of the current command - referenced as `causation_id`, the message name of the current command - referenced as `causation_name` and
a `dispatch_status`. The latter can either be `0 = event dispatch not started` or `2 = event dispatch was successful`.

To determine the dispatch status of a domain event the TransactionManager checks if the recorded event should be dispatched synchronous or
asynchronous based on the marker interface: `Prooph\Proophessor\EventStore\IsProcessedAsync`.

All synchronous domain events are forwarded to the EventBus by the TransactionManager and the dispatch status is set to `2`
(a failing event dispatch causes a transaction rollback, so that status will never be set for sync events).

For all asynchronous domain events the dispatch status is set to `0` and they are not forwarded to the EventBus!


Support
-------

- Ask questions on [prooph-users](https://groups.google.com/forum/?hl=de#!forum/prooph) google group.
- File issues at [https://github.com/prooph/proophessor/issues](https://github.com/prooph/proophessor/issues).


Contribute
----------

Please feel free to fork and extend existing or add new features and send a pull request with your changes!
To establish a consistent code quality, please provide unit tests for all your changes and may adapt the documentation.
