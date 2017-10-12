# Introduction

On the following pages we will walk through all prooph components step by step 
and take a look on how things work together. You will learn the basic concepts as well
as some advanced techniques to push your PHP applications up to the next level and 
make them ready for the future.

## Objectives of this tutorial

In the prooph ecosystem everything is bound together by messages.
So when you want to get started with prooph components you should know the
basic building block - **prooph messages**. Once you know how they work we give a short overview
of CQRS and Event Sourcing and why messages play an important role in this architecture.

## The initial setup

To start with the tutorial you need PHP 7.1 and composer installed. If you're on Windows please consider using a Linux VM
for the tutorial. The tutorial is tested on Linux only and the commands needed for project set up (not many) are shown in
their Linux version only. 


## prooph messages

Every prooph component deals with messages in one way or the other so we've put them
in a common package to share them. Time to get our hands dirty and install the package.

First create an empty folder called `prooph_tutorial` and `cd` into it. 

### Require prooph/common

`$ composer require prooph/common`

This command will run composer that generates a fresh `composer.json` for us and adds `prooph/common`
as first package to our new project. Let's check what we've installed.

### The first message

Create a file called `hello_world.php` with the following content and run it with `php`:

```php
<?php
//All prooph components use strict types enabled
declare(strict_types=1);

namespace Prooph\Tutorial;

use Prooph\Common\Messaging\Command;
use Prooph\Common\Messaging\PayloadConstructable;
use Prooph\Common\Messaging\PayloadTrait;

//Require composer's autoloader
require 'vendor/autoload.php';

//Our first message
final class SayHello extends Command implements PayloadConstructable
{
    use PayloadTrait;

    public function to(): string
    {
        return $this->payload['to'];
    }
}

$sayHello = new SayHello(['to' => 'World']);

echo 'Hello ' . $sayHello->to();

//Hello World

```

In the script above we've created our first message of message type `command`.
We've also used `Prooph\Common\Messaging\PayloadTrait` in conjunction with the `Prooph\Common\Messaging\PayloadConstructable` interface
to be able to instantiate our command with a `payload` - a simple array - and get access to it using
`$this->payload` within the message. 
While this is a very easy and fast way to create message classes it is completely optional.
The most important thing to note here is that `Prooph\Common\Messaging\Command` implements `Prooph\Common\Messaging\Message`

```php
<?php

declare(strict_types=1);

namespace Prooph\Common\Messaging;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

interface Message extends HasMessageName
{
    public const TYPE_COMMAND = 'command';
    public const TYPE_EVENT = 'event';
    public const TYPE_QUERY = 'query';

    /**
     * Should be one of Message::TYPE_COMMAND, Message::TYPE_EVENT or Message::TYPE_QUERY
     */
    public function messageType(): string;

    public function uuid(): Uuid;

    public function createdAt(): DateTimeImmutable;

    public function payload(): array;

    public function metadata(): array;

    public function withMetadata(array $metadata): Message;

    /**
     * Returns new instance of message with $key => $value added to metadata
     *
     * Given value must have a scalar or array type.
     */
    public function withAddedMetadata(string $key, $value): Message;
}
```
That is the basic contract, an immutable message with a unique identifier, a type, an created at timestamp, 
a message name (by extending the `HasMessageName` interface), payload and metadata.
Payload should only contain scalar types and arrays (no objects) and metadata only scalars to be truly immutable.

## Command Query Responsibility Segregation

Command Query Responsibility Segregation - short CQRS - is one of the two main patterns we've built the
prooph components for. CQRS was first described by [Greg Young](http://codebetter.com/gregyoung/). 
Our goal is to port his idea to the PHP world and make it easy to use for PHP developers.

The basic concept is very simple. Let's look at a common service class:

```php
<?php

declare(strict_types = 1);

namespace Prooph\Tutorial;

class UserService
{
    private $userRepository;

    public function __construct(UserRepository $repository)
    {
        $this->userRepository = $repository;
    }

    public function createUser(int $id, string $name, string $email): User
    {
        $user = new User($id);

        $user->setName($name);
        $user->setEmail($email);

        $this->userRepository->save($user);
        
        return $user;
    }
    
    public function getUser(int $id): User
    {
        $user = $this->userRepository->get($id);
        
        if(!$user) {
            throw UserNotFoundException::withId($id);
        }
        
        return $user;
    }
}

```

The `UserService` is responsible for all actions related to a user. 
In a CQRS system this looks slightly different:

`CreateUserHandler.php`
```php
<?php

declare(strict_types = 1);

namespace Prooph\Tutorial;

class CreateUserHandler
{
    private $userRepository;

    public function __construct(UserRepository $repository)
    {
        $this->userRepository = $repository;
    }

    public function createUser(int $id, string $name, string $email): void
    {
        $user = new User($id);

        $user->setName($name);
        $user->setEmail($email);

        $this->userRepository->save($user);
    }
}

```

`UserFinder.php`
```php
<?php

declare(strict_types = 1);

namespace Prooph\Tutorial;

class UserFinder
{
    private $userRepository;

    public function __construct(UserRepository $repository)
    {
        $this->userRepository = $repository;
    }

    public function getUser(int $id): User
    {
        $user = $this->userRepository->get($id);

        if(!$user) {
            throw UserNotFoundException::withId($id);
        }

        return $user;
    }
}

```

Instead of one service, we have two separate services one for handling the write action 
and one for handling the query. Note that `CreateUserHandler::createUser(): void` no longer returns
the new user object. The method signature follows a basic rule of CQRS: 
**Write operations don't have a return value**

To summarize CQRS in one sentence:

A CQRS system is divided into two parts, a write model to handle all state changes 
and a read model to query that state.

While this looks like overkill in the first place it enables you to design write and read side 
independent of each other. Let's look at a read-optimized `UserFinder`

```php
<?php

declare(strict_types = 1);

namespace Prooph\Tutorial;

class UserFinder
{
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getUser(int $id): array
    {
        $userData = $this->connection->findOneBy(['id' => $id]);

        if(!$userData) {
            throw UserNotFoundException::withId($id);
        }

        return $userData;
    }
}

```
 
We no longer use the `UserRepository` but instead directly access the database.
So we avoid the object relational mapping and return pure user data from our finder.
We can do this because we know that our read model won't do anything with the data other than
forwarding it to a client that for example requires the data in JSON or XML format.
If it is quaranteed that no state changes happen within the read model, we don't need to deal with 
objects as we don't need to enforce any rules. Select the data and return it to the client as fast as possible
that is the target of the read model.

The write model however has to protect invariants. At the moment our `user` object does a bad job on this.

```php
$user = new User($id);

$user->setName($name);
$user->setEmail($email);
```

These three lines tell us that a `User` can exist in our system without a name and an email, only
the id is required. For most of the systems this is not true. What about this?


```php
$user = User::create($id, $name, $email);
```

Yeah, looks better now. Let's put it in the handler.

```php
<?php

declare(strict_types = 1);

namespace Prooph\Tutorial;

class CreateUserHandler
{
    private $userRepository;

    public function __construct(UserRepository $repository)
    {
        $this->userRepository = $repository;
    }

    public function createUser(int $id, string $name, string $email): void
    {
        $user = User::create($id, $name, $email);

        $this->userRepository->save($user);
    }
}

```

Ok, but not perfect because we are missing intent. The code does not express why
a user is created.

The following code looks better, right?

```php
<?php

declare(strict_types = 1);

namespace Prooph\Tutorial;

class RegisterUserHandler
{
    private $userRepository;

    public function __construct(UserRepository $repository)
    {
        $this->userRepository = $repository;
    }

    public function registerUser(int $id, string $name, string $email): void
    {
        $user = User::register($id, $name, $email);

        $this->userRepository->save($user);
    }
}

```

Finally, we add some prooph flavour and change the method of the handler to handle a prooph message

```php
<?php

declare(strict_types = 1);

namespace Prooph\Tutorial;

class RegisterUserHandler
{
    private $userRepository;

    public function __construct(UserRepository $repository)
    {
        $this->userRepository = $repository;
    }

    public function handle(RegisterUser $command): void
    {
        $user = User::register($command->userId(), $command->userName(), $command->email());

        $this->userRepository->save($user);
    }
}

```

With a few changes we turned our original `UserService` into two distinct classes supporting the basic idea of CQRS.
In the last step we enabled the write side to handle a prooph command that expresses its intent of how the system state should change
(a new user should be registered) using the message name `RegisterUser` and payload of the command.
The `RegisterUserHandler` is a so called *glue component*. Its task is to take the command and
translate the intent into an action performed by the write model (in our case the user).
Finally, the command handler makes use of the infrastructure (represented by the `UserRepository`) to persist 
the state change caused by the command.

## Event Sourcing

Event Sourcing is covered in detail in the next chapter. What follows is a very basic introduction so that
you can get an idea of what you'll learn when continue reading.

In an event sourced system all state changes are described by events. The fact that a new user was registered in
our system would be one of those events. Another one would be that the user has logged in or changed the email address.

Let's analyze the last example. We start by looking at our database table after the user was registered.

{.table}
id  | name     | email
--- | -------- | ------------
1   | John Doe | doe@test.com


Applying CQRS again we end up with a new command `ChangeEmail`, an appropriate command handler and a matching action
in the write model owned by the responsible object `User::changeEmail`

```php
<?php

declare(strict_types = 1);

namespace Prooph\Tutorial;

class ChangeEmailHandler
{
    private $userRepository;

    public function __construct(UserRepository $repository)
    {
        $this->userRepository = $repository;
    }

    public function handle(ChangeEmail $command): void
    {
        $user = $this->userRepository->get($command->userId());
        
        $user->changeEmail($command->newEmail());

        $this->userRepository->save($user);
    }
}

```

Performing a command like this:

```php
$changeEmail = new ChangeEmail([
    'userId' => 1, 
    'newEmail' => 'john.doe@test.com'
]);

$handler->handle($changeEmail);

```



will result in an updated database row

{.table}
id  | name     | email
--- | -------- | ------------
1   | John Doe | john.doe@test.com

What is wrong here? We've changed state but we don't know why and when it happened.
Wouldn't it be nice if we could look at the database and see what caused the state change?

What would you say if your database would give you this information instead?

```json
[{
    event: UserWasRegistered,
    createdAt: 2017-01-13
    payload: {id: 1, name: "John Doe", email: "doe@test.com"}
},
{
    event: EmailWasChanged,
    createdAt: 2017-01-14
    payload: {id: 1, newEmail: "john.doe@test.com"}
}]
```

Welcome to the world of Event Sourcing. This is only the beginning. When you are done with the walk-through tutorial
you don't want to look back. Event Sourcing makes it so much simpler to create software that reflects intent and you have
all information available to find bugs faster and add new features without pain. Messages are the basic building block but
we need to look at a few other things. Fasten your seatbelt and enjoy the journey.

## Quick Version

The next pages cover all the things you need to know about Event Sourcing. This includes working on an example project along
with detailed explanations. If you want to do a quick walk-through instead or get your hands dirty before the theory
then you can head over to [Prooph: CQRS+ES in PHP. How to use. - by Marcin Pilśniak](https://pilsniak.com/cqrs-es-php-prooph/).
