# First Steps With Event Sourcing

> The Dreyfus model distinguishes five levels of competence, from novice to mastery. At the absolute beginner level people execute tasks based on “rigid adherence to taught rules or plans”. Beginners need recipes. They don’t need a list of parts, or a dozen different ways to do the same thing. Instead what works are step by step instructions that they can internalize. As they practice them over time they learn the reasoning behind them, and learn to deviate from them and improvise, but they first need to feel like they’re doing something.

(source: https://lambdaisland.com/blog/25-05-2017-simple-and-happy-is-clojure-dying-and-what-has-ruby-got-to-do-with-it)

## Event Sourcing Framework

We teach event sourcing for a long time now and we can confirm that the Dreyfus model is right.
Hence, the prooph software Team has created an event sourcing framework on top of prooph components.

### A framework on top of a framework? 

Yes and no ... We don't call prooph a framework. It is more like a toolbox that helps
you work with CQRS / ES in PHP applications. You can mix and match prooph components with your ideas to create your
very own CQRS / ES framework. Later in the tutorial you'll learn how to do it. But first we need to understand the basics.

On the other hand [prooph software Event Machine](https://github.com/proophsoftware/event-machine) is a framework. 
It is optimized for ease of use. So it's a perfect match for our beginner tutorial.

*Note: As stated in the introduction you need to have Docker installed. Please see official Docker docs for
installation. If it does not run properly on your operating system you can install a linux virtual machine and
run Docker inside that virtual machine.*

## Event Machine Quick Start

Event Machine has a [skeleton](https://github.com/proophsoftware/event-machine-skeleton) with a quick start.
Follow that quick start. It uses the same example as we used in the introduction of the tutorial so you should identify
some similarity. Use `my_iam` as project name when checking out the skeleton. We'll use that name in the tutorial.

### Quick start completed?

Great! Let's have a look at what we did. Point two of the Quick start asks us to use [that UserDescription](https://gist.github.com/codeliner/20c3944195d0c60ceb2a4bbe6d3d2638#file-userdescription-php)
to tell Event Machine about our domain. Event Machine offers a programmatic API to set up and describe a CQRS and Event Sourcing
system. **It emphasis the use of functions rather than handling everything in an object oriented fashion**. And that's good
to understand the basics of event sourcing because ES and functional programming fit together naturally.

### CQRS Again

The UserDescription has a few static methods. The only public one is `UserDescription::describe` that delegates to
different internal methods. `UserDescription::describeMessages` is called first. It registers different messages in Event Machine.
We learned about prooph messages in the introduction. Event Machine manages message handling internally. We just need to tell
it about our messages, namely what **name, type and payload** they have.

The message type is defined by using either `EventMachine::registerCommand` or `EventMachine::registerEvent`.
Both methods take the message name as second argument and a [JSON Schema](http://json-schema.org/documentation.html) (represented as a PHP array)
as third argument. Event Machine uses JSON Schema for payload validation. This is not part of the prooph message definition but
only a convention by Event Machine to make sure that message payload is validated properly before doing anything else.

#### Commands

Commands trigger use cases in our model. Think of them like http requests but independent of any protocol. In fact most of the time you'll translate
http POST/PUT/PATCH/DELETE requests into commands, but http is not the only input channel. Your system can receive the same
commands via CLI, a messaging queue or other transport layers. That's one of the big advantages of commands. 
Another one is that commands describe intent. At least if you try to find good names for them (and you should! Looking at you Domain-Driven Design).
The rule of thumb is to name a command with a verb in the imperative mood plus the addressed aggregate type (read on to learn about aggregates):
`DoSomething` or in the example `RegisterUser`, `ChangeUsername`, ...

#### Events

We haven't talked much about events yet. But we already know that events are the basic building block of event sourcing and that they describe facts.
Hence, events are named in past tense. `SomethingHappened` versus `DoSomething`. Keep that in mind. It is the foundation of an entire
architecture. A simple rule that allows us to design powerful tools around it. And even more important is that events help us
to think about business problems differently. We no longer focus on system state (a row in a database table f.e.),
we focus on behaviour. *What should our system do so that this specific event will happen?* *And what are the next required steps (commands)
when the event happened?* Simple questions that are often not asked, at least not for all aspects of a domain. With event sourcing you're forced
to ask those questions otherwise you have nothing to implement.

#### Aggregates

We've learned that commands tell our system what should happen next and if a command is accepted by our system (payload and other attributes are valid)
someone or something needs to handle the command. This is the point when aggregates come into play. You might have heard about aggregates already.
We talk about the same kind of aggregate like [Domain-Driven Design does](https://vaughnvernon.co/?p=838).
Understanding aggregates is not an easy task. Let's do it step by step.
For now (and possibly forever) you should forget the term `Entity`. Vaughn Vernon says in the linked article:

> Clustering Entities and Value Objects into an Aggregate with a carefully crafted consistency boundary may at first seem like quick work, but among all DDD tactical guidance, this pattern is one of the least well understood.
 
He speaks about entities and that is of course the right term, but our brain is overloaded with the term and when reading or hearing 
entity we directly think of it as a row in a database table identified by an id. If not a table in a row then at least an object in our code composed of one or more rows in a database put
together by fancy object relational mapping. Delete that view in your brain completely! We need to learn from scratch.

Back to the UserDescription used in the Event Machine quick start. [UserDescription::describeRegisterUser](https://gist.github.com/codeliner/20c3944195d0c60ceb2a4bbe6d3d2638#file-userdescription-php-L57)
illustrates the basic concept of an aggregate with just a few lines of code. Below you find an annotated version of the method:

```php
<?php

private static function describeRegisterUser(EventMachine $eventMachine): void
{
    //The "register user" use case is triggered by command App.RegisterUser
    //(A common approach is to group messages by context - here the context is simply named "App")
    $eventMachine->process(self::COMMAND_REGISTER_USER)
        //Responsible for the use case is the aggregate: User
        //In case of App.RegisterUser a new aggregate will be created in the system
        ->withNew(self::AGGREGATE_USER)
        //Each User aggregate is identified by its "userId" - a unique identifier (often UUID is used as type)
        //Each command must include the "userId" in the payload so that the correct aggregate
        //can handle the command
        //Even if a command creates a new aggregate the aggregate identifier must be included in payload and therefor
        //is defined by the sender of the command (we'll look at this concept in detail soon)
        ->identifiedBy(self::IDENTIFIER)
        //Once Event Machine knows which aggregate is responsible it passes the payload
        //to the handle function. Here a new User is registered, hence we have no aggregate state yet
        //(see second example for difference if use case is handled by existing aggregate)
        ->handle(function(array $registerUser) {
            //yield $registerUser; is a shorthand for:
            //$userWasRegistered = $registerUser;
            //yield $userWasRegistered;
            //We turn the command into a fact - an event
            //In this case no additional logic is needed, payload of command matches 1:1 with the event
            yield $registerUser;
        })
        //For each yielded event in the handle function (we could yield more if necessary)
        //we need to assign an event name and ...
        ->recordThat(self::EVENT_USER_WAS_REGISTERED)
        // ... apply that event to the current state of the aggregate
        //As we're creating a new aggregate here, no state is passed to the apply function
        //but we turn the payload of App.UserWasRegistered event into initial state of our new aggregate
        ->apply(function (array $userWasRegistered) {
            $userState = $userWasRegistered;
            //And finally return that state back to Event Machine
            return $userState;
        });
}
```











