# A Deep Dive Into Event Sourcing

On the [first page](./beginner-tutorial.html) of our prooph walk-through guide we learned about **prooph messages** and the two main architectural patterns
supported by prooph components, namely **CQRS and Event Sourcing**. It has a very good reason that we focus on both:
Event Sourcing requires CQRS in most cases. 

This part of the walk-through guide covers the basics of Event Sourcing and
will show you one way of applying the pattern. We'll start with a functional style because it will help us understand the pure idea without 
mixing concepts of the object oriented world. Later we will walk the way back and apply Event Sourcing to an object oriented model.

## Focus on behaviour

Event Sourcing is a very simple concept and fits naturally with a **process** that consists of multiple steps. Every successful 
step or even a failed one is represented by an event. A checkout process of an eCommerce platform is a good example. It can be described by
events that need to happen for a successful checkout: 

`ProductsWereBought -> ShippingInformationWasFilled -> ShippingMehodWasSelected -> PaymentMethodWasSelected -> OrderWasConfirmed`

If we try to match the pattern with other processes we recognize that it is mostly the same. Nearly every process can be described by events no matter
the steps involved. A good part of our work as software developers is to write routines which automate processes. 

*But we often tend to focus on things and state rather than process logic*. At least we design our entities and database schema first and
build the logic around them. 

With Event Sourcing you look at a problem from the other side. **You design the process first** and think about important events
happening on the way. Doing this you get the benefit that you can discuss those events with your domain expert and identify conceptional problems early.

Ok enough theory. If you want to read more about Event Sourcing just put the phrase in your favorite search engine and hit enter.

## Functional Event Sourcing with prooph/micro

[prooph/micro](https://github.com/prooph/micro) is a tiny Microservices framework sitting on top of the prooph components. It emphasizes 
a functional programming style (as much as possible in PHP without hurting performance). Functional programming is a very interesting
approach to structure an application. The usage of side-effect free global functions feels uncommon for developers coming from an 
object oriented world, but this fact will help us to learn the basic ideas of Event Sourcing without mixing concepts.
And maybe you'll like the approach and don't want to look back. Who knows...

We're going to implement the `Checkout` described above.
 
@TODO: Explain concept of functional aggregate

First we need to install `prooph/micro` in an empty project folder:

```
$ composer require prooph/micro
```

This will also install required prooph components to get a functional event sourced Microservice up and running.
We don't want to lose time and directly start with implementing the model. First we need a good place for our functional model.
Create a `src` dir in the project root. Then create a `Model` dir in `src` and a `Checkout` dir in `Model`.
Finally, put a `Checkout.php` file in the `Checkout` dir.
Open the `composer.json` file (created by composer in the project root) and configure autoloading:

```json
{
    "require": {
      "prooph/micro": "^1.0",
      ...
    },
    "autoload": {
        "psr-4": {
            "Prooph\\Tutorial\\": "src/"
        },
        "files": [
            "src/Model/Checkout/Checkout.php"
        ]
    }
}
```

Let's define the first method of the `Checkout` process. Put the following code in the `Checkout.php` file:

```php
<?php

declare(strict_types = 1);

namespace Prooph\Tutorial\Model\Checkout;

const buyProducts = __NAMESPACE__ . '\buyProducts';

function buyProducts(callable $stateResolver, $command): array {
    
}

```

We've defined a namespace, but instead of writing a class we just put a function in the file called `buyProducts`.
A constant with the same name holding the full qualified function name of our `buyProducts` function is also defined.
You know the `::class` language construct in PHP? The constant can be used in the same manner. Whenever you want to reference
the function in a string use the constant. When you need to refactor later you can "find usage" of the constant and move functions around.

Before we continue with the function itself, we should think about its input and output. The first argument of the 
function is explained later. Let's skip it for now. The function takes a command as second argument.
We want to buy products so the obvious thing would be a `BuyProducts` command.

A common approach is to group messages together with their aggregates, because the messages define the API of the aggregate. Hence, create a `Command` dir
in the `Checkout` dir and put a `BuyProducts.php` file in it:

```php
<?php

declare(strict_types=1);

namespace Prooph\Tutorial\Model\Checkout\Command;

use Prooph\Common\Messaging\Command;
use Prooph\Common\Messaging\PayloadConstructable;
use Prooph\Common\Messaging\PayloadTrait;

final class BuyProducts extends Command implements PayloadConstructable
{
    use PayloadTrait;
}

```
Also update the `buyProducts` function:

```php
<?php

declare(strict_types=1);

namespace Prooph\Tutorial\Model\Checkout;

use Prooph\Tutorial\Model\Checkout\Command\BuyProducts;

const buyProducts = __NAMESPACE__ . '\buyProducts';

function buyProducts(callable $stateResolver, BuyProducts $command): array {

}
```

In `prooph/micro` an aggregate function should always return an array of `Prooph\Common\Messaging\Message` messages.
That is the basic interface used in Event Sourcing: **A command goes in and one or more events go out.**

Let's fulfill the interface. Analogous to the `Command` folder we need an `Event` folder with a `ProductsWereBought.php` event:

```php
<?php

declare(strict_types=1);

namespace Prooph\Tutorial\Model\Checkout\Event;

use Prooph\Common\Messaging\DomainEvent;
use Prooph\Common\Messaging\PayloadConstructable;
use Prooph\Common\Messaging\PayloadTrait;

final class ProductsWereBought extends DomainEvent implements PayloadConstructable
{
    use PayloadTrait;
}

```

## Testing an event sourced aggregate

Test first development is a good way to ensure high quality software right from the beginning. 
PHPUnit will help us with this so we should install it now:

```
$ composer require --dev phpunit/phpunit
```

Then create a `tests` folder in the project root with the same directory structure like we have in `src`:

```
\_src
  \_Model
    \_Checkout
\_tests
  \_Model
    \_Checkout
```
A `phpunit.xml.dist` file in the project root tells `PHPUnit` where to find the tests and how to autoload the production code using composer autoloader: 

```xml
<?xml version="1.0" encoding="UTF-8"?>

<!-- http://phpunit.de/manual/4.1/en/appendixes.configuration.html -->
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="http://schema.phpunit.de/5.6/phpunit.xsd"
         backupGlobals="false"
         colors="true"
         bootstrap="vendor/autoload.php"
>
    <php>
        <ini name="error_reporting" value="-1" />
    </php>

    <testsuites>
        <testsuite name="Prooph Tutorial Test Suite">
            <directory>tests/*</directory>
        </testsuite>
    </testsuites>

    <filter>
        <whitelist>
            <directory>src</directory>
        </whitelist>
    </filter>
</phpunit>

```

The first test should cover that an array with a `ProductWasBought` event is returned when a `BuyProducts` command
is passed to the `buyProducts` function. Therefor we write a test case in `tests/Model/Checkout/CheckoutTest.php`.

```php
<?php

declare(strict_types = 1);

namespace ProophTest\Tutorial\Model\Checkout;

use PHPUnit\Framework\TestCase;
use Prooph\Tutorial\Model\Checkout;

class CheckoutTest extends TestCase
{
    /**
     * @test
     */
    public function it_raises_a_products_were_bought_event()
    {
        $command = new Checkout\Command\BuyProducts(['products' => $this->getTestProducts()]);

        $noOpStateResolver = function () {};

        $events = Checkout\buyProducts($noOpStateResolver, $command);

        $this->assertEquals(1, count($events));
        $this->assertEquals(Checkout\Event\ProductsWereBought::class, get_class($events[0]));
        $this->assertEquals($this->getTestProducts(), $events[0]->payload()['products']);
    }
    
    private function getTestProducts(): array 
    {
        return [
            ['id' => 1, 'name' => 'T-Shirt', 'amount' => 3],
            ['id' => 2, 'name' => 'Jeans', 'amount' => 1]
        ];
    }
}

```

Test autoloading should also be configured in the `composer.json`:

```json
{
  "autoload": {
  ...
  },
  "autoload-dev": {
        "psr-4": {
            "ProophTest\\Tutorial\\": "tests/"
        }
    }
 }
```

*Note*: PHPUnit will find the test case without composer configuration but it becomes handy when we want to make use of mocks.

Ok, result of test execution should be a failing test. Run phpunit using docker and a prooph docker image:

```
$ docker run -v $(pwd):/app prooph/php:7.1-cli php vendor/bin/phpunit

E                                                                   1 / 1 (100%)


There was 1 error:

1) ProophTest\Tutorial\Model\Checkout\CheckoutTest::it_raises_a_products_were_bought_event
TypeError: Return value of Prooph\Tutorial\Model\Checkout\buyProducts() must be of the type array, none returned

/app/src/Model/Checkout/Checkout.php:13
/app/tests/Model/Checkout/CheckoutTest.php:24

ERRORS!
Tests: 1, Assertions: 0, Errors: 1.
```

And this should make the test pass:

```php
<?php

declare(strict_types=1);

namespace Prooph\Tutorial\Model\Checkout;

use Prooph\Tutorial\Model\Checkout\Command\BuyProducts;
use Prooph\Tutorial\Model\Checkout\Event\ProductsWereBought;

const buyProducts = __NAMESPACE__ . '\buyProducts';

function buyProducts(callable $stateResolver, BuyProducts $command): array {
    return [new ProductsWereBought(['products' => $command->payload()['products']])];
}

```
```
$ docker run -v $(pwd):/app prooph/php:7.1-cli php vendor/bin/phpunit

.                                                                   1 / 1 (100%)

OK (1 test, 3 assertions)
```

Ok cool, the test is green. But the `buyProducts` function doesn't do much. It just takes the input payload and passes it
to the output payload. In some cases this is really all you need but in most cases an aggregate has a very important task.
It should protect invariants. For our system one of these invariants could be that a product can only be bought if it is available
in stock. Following interface describes a possible contract between our "Checkout domain" and a "Warehouse domain".

```php
<?php

declare(strict_types=1);

namespace Prooph\Tutorial\Model\Checkout\Contract;

interface Warehouse
{
    public function getAvailableAmountOfProductInStock(int $productId): int;
}

```

Put the interface in `src/Model/Checkout/Contract/Warehouse.php` and make use of it in the `buyProducts` function:

```php
<?php

declare(strict_types=1);

namespace Prooph\Tutorial\Model\Checkout;
//...
use Prooph\Tutorial\Model\Checkout\Contract\Warehouse;

function buyProducts(callable $stateResolver, BuyProducts $command, Warehouse $warehouse): array {
    //...
}
```

The expectation is that the `Checkout` aggregate checks availability of each product in stock. We can ensure this with the help 
of a new test assertion:

```php
<?php

declare(strict_types = 1);

namespace ProophTest\Tutorial\Model\Checkout;

use PHPUnit\Framework\TestCase;
use Prooph\Tutorial\Model\Checkout;
use Prophecy\Argument;

class CheckoutTest extends TestCase
{
    /**
     * @test
     */
    public function it_raises_a_products_were_bought_event()
    {
        $command = new Checkout\Command\BuyProducts(['products' => $this->getTestProducts()]);

        $noOpStateResolver = function () {};

        $warehouse = $this->prophesize(Checkout\Contract\Warehouse::class);
        $warehouse->getAvailableAmountOfProductInStock(Argument::exact(1))->shouldBeCalled();
        $warehouse->getAvailableAmountOfProductInStock(Argument::exact(2))->shouldBeCalled();

        $events = Checkout\buyProducts($noOpStateResolver, $command, $warehouse->reveal());

        $this->assertEquals(1, count($events));
        $this->assertEquals(Checkout\Event\ProductsWereBought::class, get_class($events[0]));
        $this->assertEquals($this->getTestProducts(), $events[0]->payload()['products']);
    }

    private function getTestProducts(): array
    {
        return [
            ['id' => 1, 'name' => 'T-Shirt', 'amount' => 3],
            ['id' => 2, 'name' => 'Jeans', 'amount' => 1]
        ];
    }
}

```

And of course the test fails again:

```
$ docker run -v $(pwd):/app prooph/php:7.1-cli php vendor/bin/phpunit

F                                                                   1 / 1 (100%)

There was 1 failure:

1) ProophTest\Tutorial\Model\Checkout\CheckoutTest::it_raises_a_products_were_bought_event
Some predictions failed:
  Double\Warehouse\P1:
    No calls have been made that match:
      Double\Warehouse\P1->getAvailableAmountOfProductInStock(exact(1))
    but expected at least one.  No calls have been made that match:
      Double\Warehouse\P1->getAvailableAmountOfProductInStock(exact(2))
    but expected at least one.

FAILURES!
Tests: 1, Assertions: 5, Failures: 1.
```

## Handling the unhappy paths

Before we continue, we should get back to the whiteboard together with our domain expert and clarify some questions:

- What happens if a product is not available in stock?
- What happens if all products are not available in stock?
- What happens if a product is only partially available in stock?

Three questions dealing with error cases. Stop! Are they really error cases? Sounds more like normal business to me.
So should we handle these cases with exceptions or should we simply raise other events? The latter approach seems more appropriate.
Let's try it and see how it goes.

```php
<?php

declare(strict_types=1);

namespace Prooph\Tutorial\Model\Checkout;

use Prooph\Tutorial\Model\Checkout\Command\BuyProducts;
use Prooph\Tutorial\Model\Checkout\Contract\Warehouse;
use Prooph\Tutorial\Model\Checkout\Event\AllProductsWereNotAvailable;
use Prooph\Tutorial\Model\Checkout\Event\ProductListWasEmpty;
use Prooph\Tutorial\Model\Checkout\Event\ProductsWereBought;
use Prooph\Tutorial\Model\Checkout\Event\ProductsWerePartiallyNotAvailable;

const buyProducts = __NAMESPACE__ . '\buyProducts';

function buyProducts(callable $stateResolver, BuyProducts $command, Warehouse $warehouse): array {
    $products = $command->payload()['products'] ?? [];

    if(!count($products)) {
        return [new ProductListWasEmpty([])];
    }

    $availableProducts = [];
    $partiallyAvailableProducts = [];
    $notAvailableProducts = [];

    foreach ($products as $product) {
        $availableAmount = $warehouse->getAvailableAmountOfProductInStock($product['id']);
        
        $product['availableAmount'] = $availableAmount;

        if($availableAmount === 0) {
            $notAvailableProducts[] = $product;
            continue;
        }

        if($availableAmount < $product['amount']) {
            $partiallyAvailableProducts[] = $product;
            continue;
        }

        $availableProducts[] = $product;
    }

    if (count($notAvailableProducts) === count($products)) {
        return [new AllProductsWereNotAvailable(['products' => $products])];
    }

    if(count($notAvailableProducts) > 0 || count($partiallyAvailableProducts) > 0) {
        return [new ProductsWerePartiallyNotAvailable([
            'availableProducts' => $availableProducts,
            'partiallyAvailableProducts' => $partiallyAvailableProducts,
            'notAvailableProducts' => $notAvailableProducts
        ])];
    }

    return [new ProductsWereBought(['products' => $availableProducts])];
}

```

And the test case with tests to cover all possible outcomes of `Checkout\buyProducts`:

```php
<?php

declare(strict_types = 1);

namespace ProophTest\Tutorial\Model\Checkout;

use PHPUnit\Framework\TestCase;
use Prooph\Tutorial\Model\Checkout;
use Prophecy\Argument;

class CheckoutTest extends TestCase
{
    /**
     * @test
     */
    public function it_raises_a_products_were_bought_event()
    {
        $command = new Checkout\Command\BuyProducts(['products' => $this->getTestProducts()]);

        $noOpStateResolver = function () {};

        $warehouse = $this->prophesize(Checkout\Contract\Warehouse::class);
        $warehouse->getAvailableAmountOfProductInStock(Argument::exact(1))->willReturn(3)->shouldBeCalled();
        $warehouse->getAvailableAmountOfProductInStock(Argument::exact(2))->willReturn(2)->shouldBeCalled();

        $events = Checkout\buyProducts($noOpStateResolver, $command, $warehouse->reveal());

        $expected = [
            ['id' => 1, 'name' => 'T-Shirt', 'amount' => 3, 'availableAmount' => 3],
            ['id' => 2, 'name' => 'Jeans', 'amount' => 1, 'availableAmount' => 2]
        ];

        $this->assertEquals(1, count($events));
        $this->assertEquals(Checkout\Event\ProductsWereBought::class, get_class($events[0]));
        $this->assertEquals($expected, $events[0]->payload()['products']);
    }

    /**
     * @test
     */
    public function it_raises_a_all_products_were_not_available_event()
    {
        $command = new Checkout\Command\BuyProducts(['products' => $this->getTestProducts()]);

        $noOpStateResolver = function () {};

        $warehouse = $this->prophesize(Checkout\Contract\Warehouse::class);
        $warehouse->getAvailableAmountOfProductInStock(Argument::exact(1))->willReturn(0)->shouldBeCalled();
        $warehouse->getAvailableAmountOfProductInStock(Argument::exact(2))->willReturn(0)->shouldBeCalled();

        $events = Checkout\buyProducts($noOpStateResolver, $command, $warehouse->reveal());

        $expected = [
            ['id' => 1, 'name' => 'T-Shirt', 'amount' => 3, 'availableAmount' => 0],
            ['id' => 2, 'name' => 'Jeans', 'amount' => 1, 'availableAmount' => 0]
        ];

        $this->assertEquals(1, count($events));
        $this->assertEquals(Checkout\Event\AllProductsWereNotAvailable::class, get_class($events[0]));
        $this->assertEquals($expected, $events[0]->payload()['products']);
    }

    /**
     * @test
     */
    public function it_raises_a_products_were_partially_not_available_event()
    {
        $command = new Checkout\Command\BuyProducts(['products' => $this->getTestProducts()]);

        $noOpStateResolver = function () {};

        $warehouse = $this->prophesize(Checkout\Contract\Warehouse::class);
        $warehouse->getAvailableAmountOfProductInStock(Argument::exact(1))->willReturn(2)->shouldBeCalled();
        $warehouse->getAvailableAmountOfProductInStock(Argument::exact(2))->willReturn(0)->shouldBeCalled();

        $events = Checkout\buyProducts($noOpStateResolver, $command, $warehouse->reveal());

        $expected = [
            'availableProducts' => [],
            'partiallyAvailableProducts' => [['id' => 1, 'name' => 'T-Shirt', 'amount' => 3, 'availableAmount' => 2]],
            'notAvailableProducts' => [['id' => 2, 'name' => 'Jeans', 'amount' => 1, 'availableAmount' => 0]]
        ];

        $this->assertEquals(1, count($events));
        $this->assertEquals(Checkout\Event\ProductsWerePartiallyNotAvailable::class, get_class($events[0]));
        $this->assertEquals($expected, $events[0]->payload());
    }

    private function getTestProducts(): array
    {
        return [
            ['id' => 1, 'name' => 'T-Shirt', 'amount' => 3],
            ['id' => 2, 'name' => 'Jeans', 'amount' => 1]
        ];
    }
}

```
## State is just a projection of a series of events

Our aggregate function raises different events now depending on availability of the products in stock. The domain expert
was not able to answer the questions directly. So we don't know what steps should be taken if not all products can be bought.
We will get back to it later and continue with the `stateResolver` in the meanwhile.
 
