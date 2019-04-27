# Testing Aggregates

**Event Sourcing makes it easy to test aggregates**. In this chapter we will learn why. Don't underestimate this advantage.
Easy testability is crucial for long-lived applications that evolve over time.

## Test Recorded Events

When testing an event sourced aggregate we only need to test two things:

1) Does the aggregate record the correct domain event(s)?
2) Does the aggregate either reject an action or record a failed event if a business rule is not met?

The fact that an aggregate is event sourced is hidden from the public API. If we want to test recorded events,
we need a test helper to get them from the aggregate. `prooph/event-sourcing` uses a so-called `AggregateTranslator`
to perform operations on protected methods of an aggregate. We can use the `AggregateTranslator` for our tests, too.
Add a project `TestCase` class that includes some helper methods which we can use in our test cases.

*File: ./Basket/tests/TestCase.php*
```php
<?php

declare(strict_types=1);

namespace App\BasketTest;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Prooph\EventSourcing\Aggregate\AggregateType;
use Prooph\EventSourcing\AggregateRoot;
use Prooph\EventSourcing\EventStoreIntegration\AggregateTranslator;

class TestCase extends PHPUnitTestCase
{
    /**
     * @var AggregateTranslator
     */
    private $aggregateTranslator;

    protected function popRecordedEvents(AggregateRoot $aggregateRoot): array
    {
        return $this->getAggregateTranslator()->extractPendingStreamEvents($aggregateRoot);
    }
    /**
     * @return object
     */
    protected function reconstituteAggregateFromHistory(string $aggregateRootClass, array $events)
    {
        return $this->getAggregateTranslator()->reconstituteAggregateFromHistory(
            AggregateType::fromAggregateRootClass($aggregateRootClass),
            new \ArrayIterator($events)
        );
    }

    private function getAggregateTranslator(): AggregateTranslator
    {
        if (null === $this->aggregateTranslator) {
            $this->aggregateTranslator = new AggregateTranslator();
        }
        return $this->aggregateTranslator;
    }
}

```
The same `TestCase` is used in the example application, `proophessor-do`¹. Besides the `popRecordedEvents` method
we've also added a `reconstituteAggregateFromHistory` method. With the latter we can prepare an aggregate for a test
case just using events, so we can "move" the aggregate to a certain point in the business process and start testing
from there. This is really powerful and, as an added benefit, also self documenting. We'll see that in a minute.

In the "Event Sourcing Basics" chapter we implemented the `Basket::startShoppingSession()` method. Let's
test it now with a `BasketTest`.

*File: ./Basket/tests/Model/BasketTest.php*
```php
<?php

declare(strict_types=1);

namespace App\BasketTest\Model;

use App\Basket\Model\Basket;
use App\Basket\Model\Event\ShoppingSessionStarted;
use App\Basket\Model\Basket\BasketId;
use App\Basket\Model\Basket\ShoppingSession;
use App\BasketTest\TestCase;
use Prooph\EventSourcing\AggregateChanged;
use Ramsey\Uuid\Uuid;

class BasketTest extends TestCase
{
    /**
     * @var ShoppingSession
     */
    private $shoppingSession;

    /**
     * @var BasketId
     */
    private $basketId;

    protected function setUp()
    {
        $this->shoppingSession = ShoppingSession::fromString('123');
        $this->basketId = BasketId::fromString(Uuid::uuid4()->toString());
    }

    /**
     * @test
     */
    public function it_starts_a_shopping_session()
    {
        $basket = Basket::startShoppingSession($this->shoppingSession, $this->basketId);

        /** @var AggregateChanged[] $events */
        $events = $this->popRecordedEvents($basket);

        $this->assertCount(1, $events);

        /** @var ShoppingSessionStarted $event */
        $event = $events[0];

        $this->assertSame(ShoppingSessionStarted::class, $event->messageName());
        $this->assertTrue($this->basketId->equals($event->basketId()));
        $this->assertTrue($this->shoppingSession->equals($event->shoppingSession()));
    }
}

```
The two value objects needed to start a shopping session are created in the `setUp()` method of the test case. This way
we get fresh objects for each test without needing to create them manually in each test method.

To run the test, we need to tell `PHPUnit` where to find our test cases and how to use `composer autoloader` to locate
the domain model. Just put a `phpunit.xml.dist` file in the project root with the following content:

*File: ./phpunit.xml.dist*
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php">
    <testsuites>
        <testsuite name="BasketModel">
            <directory>./Basket/tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

Now we can run the tests with `php vendor/bin/phpunit`

```bash
$ php vendor/bin/phpunit

PHPUnit 6.3.1 by Sebastian Bergmann and contributors.

.                                                                   1 / 1 (100%)

Time: 20 ms, Memory: 4.00MB

OK (1 test, 4 assertions)
```

Nice, a green test. That's a good feeling, isn't it? Half a year later the feeling is even better when we have hundreds
of green tests and need to refactor something in the domain model. So do yourself and your colleagues a favor
and test the domain model!

## Reconstitute From History

For the first test, we did not need the `reconstituteAggregateFromHistory()` test helper, but if we add more behaviour to the
`Basket` aggregate, the test helper becomes quite handy. To see the test helper in action we're going to add a second
method to the `Basket` aggregate that allows us to add a `Product`.

We start simple in this chapter and leave a more complex implementation to the next chapter, "Aggregate Dependencies".
First we need a new value object to reference a `Product`. Products are not part of our domain but are managed by an external ERP system.

*File: ./Basket/src/Model/ERP/ProductId.php*
```php
<?php

declare(strict_types=1);

namespace App\Basket\Model\ERP;

final class ProductId
{
    private $id;

    public static function fromString(string $id): self
    {
        return new self($id);
    }

    private function __construct(string $id)
    {
        if($id === '') {
            throw new \InvalidArgumentException("Product id must not be an empty string");
        }

        $this->id = $id;
    }

    public function toString(): string
    {
        return $this->id;
    }

    public function equals($other): bool
    {
        if(!$other instanceof self) {
            return false;
        }

        return $this->id === $other->id;
    }

    public function __toString(): string
    {
        return $this->id;
    }
}

```
We need a new domain event, too.

*File: ./Basket/src/Model/Event/ProductAddedToBasket.php*
```php
<?php

declare(strict_types=1);

namespace App\Basket\Model\Event;

use App\Basket\Model\Basket\BasketId;
use App\Basket\Model\ERP\ProductId;
use Prooph\EventSourcing\AggregateChanged;

final class ProductAddedToBasket extends AggregateChanged
{
    public function basketId(): BasketId
    {
        return BasketId::fromString($this->aggregateId());
    }

    public function productId(): ProductId
    {
        return ProductId::fromString($this->payload()['product_id']);
    }
}

```

And finally we add a new method to the `Basket` aggregate that we're going to test in a few seconds.

*File: ./Basket/src/Model/Basket.php*
```php
<?php

//...
use App\Basket\Model\ERP\ProductId;
use App\Basket\Model\Exception\ProductAddedTwice;
use App\Basket\Model\Event\ProductAddedToBasket;

final class Basket extends AggregateRoot
{
    //...

    /**
     * @var ProductId[]
     */
    private $products = [];

    public static function startShoppingSession(ShoppingSession $shoppingSession, BasketId $basketId)
    {
        //...
    }

    public function addProduct(ProductId $productId): void
    {
        if(array_key_exists($productId->toString(), $this->products)) {
            throw ProductAddedTwice::toBasket($this->basketId, $productId);
        }

        //@TODO: Check stock
        $this->recordThat(ProductAddedToBasket::occur($this->basketId->toString(), [
            'product_id' => $productId->toString()]
        ));
    }

    //...

    /**
     * Apply given event
     */
    protected function apply(AggregateChanged $event): void
    {
        switch ($event->messageName()) {
            case ShoppingSessionStarted::class:
                //...
            case ProductAddedToBasket::class:
                /** @var $event ProductAddedToBasket */

                //Use ProductId as index to avoid adding a product twice
                $this->products[$event->productId()->toString()] = $event->productId();
                break;
        }
    }
}
```
We have a new `$products` property which is a list of `ProductId`s, where the string representation of the `ProductId`
is also used as index. This way we can easily check if a product is added twice.

The new `addProduct()` method takes a `ProductId` as an argument and records a new domain event `ProductAddedToBasket`,
which then gets applied using a new `case` in the `apply` method.

If a product is added twice, the method throws a `ProductAddedTwice` exception. Here is the implementation of
that exception.

*File: ./Basket/src/Model/Exception/ProductAddedTwice.php*
```php
<?php

declare(strict_types=1);

namespace App\Basket\Model\Exception;

use App\Basket\Model\Basket\BasketId;
use App\Basket\Model\ERP\ProductId;

final class ProductAddedTwice extends \RuntimeException
{
    public static function toBasket(BasketId $basketId, ProductId $productId): self
    {
        return new self(sprintf(
            'Product %s added twice to basket %s',
            $productId->toString(),
            $basketId->toString()
        ));
    }
}

```

Back to the test case. We want to see if our implementation works as expected.

*File: ./Basket/tests/Model/BasketTest.php*
```php
<?php

declare(strict_types=1);

namespace App\BasketTest\Model;

use App\Basket\Model\Basket;
use App\Basket\Model\ERP\ProductId;
use App\Basket\Model\Event\ProductAddedToBasket;
use App\Basket\Model\Event\ShoppingSessionStarted;
use App\Basket\Model\Basket\BasketId;
use App\Basket\Model\Basket\ShoppingSession;
use App\BasketTest\TestCase;
use Prooph\EventSourcing\AggregateChanged;
use Ramsey\Uuid\Uuid;

class BasketTest extends TestCase
{
    /**
     * @var ShoppingSession
     */
    private $shoppingSession;

    /**
     * @var BasketId
     */
    private $basketId;

    /**
     * @var ProductId
     */
    private $product1;

    protected function setUp()
    {
        $this->shoppingSession = ShoppingSession::fromString('123');
        $this->basketId = BasketId::fromString(Uuid::uuid4()->toString());
        $this->product1 = ProductId::fromString('A1');
    }

    /**
     * @test
     */
    public function it_starts_a_shopping_session()
    {
        $basket = Basket::startShoppingSession($this->shoppingSession, $this->basketId);

        /** @var AggregateChanged[] $events */
        $events = $this->popRecordedEvents($basket);

        $this->assertCount(1, $events);

        /** @var ShoppingSessionStarted $event */
        $event = $events[0];

        $this->assertSame(ShoppingSessionStarted::class, $event->messageName());
        $this->assertTrue($this->basketId->equals($event->basketId()));
        $this->assertTrue($this->shoppingSession->equals($event->shoppingSession()));
    }

    /**
     * @test
     */
    public function it_adds_a_product()
    {
        $basket = $this->reconstituteBasketFromHistory(
            $this->shoppingSessionStarted()
        );

        $basket->addProduct($this->product1);

        /** @var AggregateChanged[] $events */
        $events = $this->popRecordedEvents($basket);

        $this->assertCount(1, $events);

        /** @var ProductAddedToBasket $event */
        $event = $events[0];

        $this->assertSame(ProductAddedToBasket::class, $event->messageName());
        $this->assertTrue($this->basketId->equals($event->basketId()));
        $this->assertTrue($this->product1->equals($event->productId()));
    }

    /**
     * @test
     * @expectedException \App\Basket\Model\Exception\ProductAddedTwice
     */
    public function it_throws_exception_if_product_is_added_twice()
    {
        $basket = $this->reconstituteBasketFromHistory(
            $this->shoppingSessionStarted(),
            $this->product1Added()
        );

        //Add same product again
        $basket->addProduct($this->product1);
    }

    /**
     * Helper method to reconstitute a Basket from history
     *
     * With this helper we get better type hinting in the test methods
     * because type hint for reconstituteAggregateFromHistory() is only AggregateRoot
     *
     * @param AggregateChanged[] ...$events
     * @return Basket
     */
    private function reconstituteBasketFromHistory(AggregateChanged ...$events): Basket
    {
        return $this->reconstituteAggregateFromHistory(
            Basket::class,
            $events
        );
    }

    /**
     * Test helper to create a ShoppingSessionStarted event
     *
     * If we need to change signature of the event later, we have a central place in the test case
     * where we can align the creation.
     *
     * @return ShoppingSessionStarted
     */
    private function shoppingSessionStarted(): ShoppingSessionStarted
    {
        return ShoppingSessionStarted::occur($this->basketId->toString(), [
            'shopping_session' => $this->shoppingSession->toString()
        ]);
    }

    /**
     * Test helper to create a ProductAddedToBasket event
     *
     * If we need to change signature of the event later, we have a central place in the test case
     * where we can align the creation.
     *
     * @return ProductAddedToBasket
     */
    private function product1Added(): ProductAddedToBasket
    {
        return ProductAddedToBasket::occur($this->basketId->toString(), [
            'product_id' => $this->product1->toString()
        ]);
    }
}

```

Looks good.

```bash
$ php vendor/bin/phpunit

PHPUnit 6.3.1 by Sebastian Bergmann and contributors.

...                                                                 3 / 3 (100%)

Time: 19 ms, Memory: 4.00MB

OK (3 tests, 9 assertions)
```

We covered two new possibilities. First, we learned how to reconstitute an aggregate from history. We also added a
few basket specific test helpers to get better type hinting support and keep creation of events in a central place
for easier refactoring.

Second, we learned how to test exceptions. Using exceptions to communicate errors is not always the best choice.
Another possibility is to use domain events for failure scenarios, too. Later in the tutorial we will see this in action
and discuss the pros and cons of such an approach.

Either way, testing failure cases where the aggregate records a failed event is not different from testing normal event recording.
Reconstitute the aggregate to move it to a certain point in the business process, provoke the recording of a failure event, and
test if the event was correctly recorded. It's as easy as that.

## Links

- [proophessor-do example app](https://github.com/prooph/proophessor-do)¹
