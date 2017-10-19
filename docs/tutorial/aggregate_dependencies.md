# Aggregate Dependencies

In the previous chapters we've learned that an aggregate protects invariants and records an event for each state transition.
It then applies recorded events to its internal state and uses the state to validate if the next state transition can take place.

But not in all cases an aggregate can rely on its internal state only. Sometimes the aggregate needs to get information
from somewhere else to make a decision. In this case the aggregate has a dependency to an external service and in this
chapter we will learn how to give access to such a service and of course how to test it.

## Method Injection

You may have noticed that we've left a `@TODO Check stock` in the `Basket::addProduct()` method. Products are handled
in an external ERP system and we can request current stock of a product from that system. When implementing Domain-Driven Design
it is a common approach to integrate an external system by defining an interface for it in the domain model but move
the implementation to the `infrastucture` layer. A good example of such an integration is shown in the PHP DDD Cargo Sample¹
where the Cargo domain makes use of an external GraphTraversalService. This is called Hexagonal Architecture or Ports & Adapters².

We want to use the same approach for the ERP system but limit the implementation to the interface only as we don't have
access to the ERP system right now. We only know the interface specification. But that's enough because we can easily
mock the ERP and use the interface to decouple our domain model from it.

*File: ./Basket/src/Model/ERP/ERP.php*
```php
<?php

declare(strict_types=1);

namespace App\Basket\Model\ERP;

use App\Basket\Model\Exception\UnknownProduct;

interface ERP
{
    /**
     * Get stock information of given product
     *
     * If stock information cannot be fetched from the ERP system
     * this method returns null.
     *
     * If product is not known by the ERP system this method must throw an UnknownProduct exception
     *
     * @param ProductId $productId
     * @return ProductStock|null
     * @throws UnknownProduct
     */
    public function getProductStock(ProductId $productId): ?ProductStock;
}


```

As you can see the `ERP` interface defines a method `getProductStock` which takes a `ProductId` as argument and returns a
`ProductStock` value object or null in case the request failed. You may have noticed that we use value objects a lot. In fact everything except our `Basket`
aggregate is modeled as a value object so far. It is a rule of thumb to model everything as value objects and only
use an aggregate if the object really has a lifecycle in YOUR domain. Products of course have a lifecycle but in
the ERP system and not in our basket domain. We only consume product data as read-only information. Hence, we are better
of with using value objects to represent different aspects of a product like its stock information.

Having said this, let's add the value object.

*File: ./Basket/src/Model/ERP/ProductStock.php*
```php
<?php

declare(strict_types=1);

namespace App\Basket\Model\ERP;

final class ProductStock
{
    /**
     * @var ProductId
     */
    private $productId;

    /**
     * @var int
     */
    private $quantity;

    /**
     * @var int
     */
    private $version;

    public static function fromArray(array $data): self
    {
        return new self(
            ProductId::fromString($data['product_id'] ?? ''),
            $data['quantity'] ?? 0,
            $data['version'] ?? 0
        );
    }

    private function __construct(ProductId $productId, int $quantity, int $version)
    {
        $this->productId = $productId;
        $this->quantity = $quantity;
        $this->version = $version;
    }

    /**
     * @return ProductId
     */
    public function productId(): ProductId
    {
        return $this->productId;
    }

    /**
     * @return int
     */
    public function quantity(): int
    {
        return $this->quantity;
    }

    /**
     * @return int
     */
    public function version(): int
    {
        return $this->version;
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId->toString(),
            'quantity' => $this->quantity,
            'version' => $this->version
        ];
    }

    public function equals($other): bool
    {
        if(!$other instanceof self) {
            return false;
        }

        return $this->toArray() === $other->toArray();
    }

    public function __toString(): string
    {
        return json_encode($this->toArray());
    }
}

```

`ProductStock` gives us two important information:

- stock quantity
- a version

Stock quantity is self-explaining and the version is related to that quantity. Whenever stock quantity of a product
changes the version is increased by one. We can use the version during checkout to verify if the last known quantity of
the product is still valid or if the quantity known by the basket is out-of-date.

We also need a new `UnknownProduct` exception which is thrown by the `ERP` adapter in case the ERP system returns a not found response.

*File: ./Basket/src/Model/Exception/UnknownProduct.php*
```php
<?php

declare(strict_types=1);

namespace App\Basket\Model\Exception;

use App\Basket\Model\ERP\ProductId;

final class UnknownProduct extends \InvalidArgumentException
{
    public static function withProductId(ProductId $productId): self
    {
        return new self(sprintf(
            'Product with %s is unknown.',
            $productId->toString()
        ));
    }
}

```

Now the `Basket` aggregate should use the `ERP` system to request stock information before adding a product to the basket.
The aggregate should reject the product if it is out of stock.

*Note: We keep the example simple. In a real world system this stock check would include many more variants for example permanent or
temporarily out of stock products, checkout even if a product is out of stock, and so on.*

But how do we get the `ERP` adapter into the aggregate? The answer is **Method Injection**. The `Basket::addProduct` defines
the `ÈRP` system as a dependency for that method. The caller of the method is responsible for providing an implementation.
We see that in action when we look at command handlers. For now we align the method and update our test case.

*File: ./Basket/src/Model/Basket.php*
```php
<?php

declare(strict_types=1);

namespace App\Basket\Model;

//...
use App\Basket\Model\ERP\ERP;
use App\Basket\Model\Exception\ProductOutOfStock;

final class Basket extends AggregateRoot
{
    //...
    
    public function addProduct(ProductId $productId, ERP $ERP): void
    {
        if(array_key_exists($productId->toString(), $this->products)) {
            throw ProductAddedTwice::toBasket($this->basketId, $productId);
        }

        //If the ERP system does not know the product an exception will be thrown here
        //which will stop the operation. The aggregate can not deal with that situation
        //as this is one of these "this should never happen" situations
        //If we want an unbreakable domain model we would need to talk to the business
        //and work out a failover plan triggered by a UnknownProductAddedToBasket event.
        $productStock = $ERP->getProductStock($productId);

        if(!$productStock) {
            $this->recordThat(ProductAddedToBasket::occur($this->basketId->toString(), [
                    'product_id' => $productId->toString(),
                    //If we did not get a response, we add the product and check stock later again
                    //the shopping session should not be blocked by a temporarily unavailable ERP system
                    'stock_version' => null,
                    'stock_quantity'=> null,
                    'quantity' => 1,
            ]));
            return;
        }

        if($productStock->quantity() === 0) {
            throw ProductOutOfStock::withProductId($productId);
        }

        $this->recordThat(ProductAddedToBasket::occur($this->basketId->toString(), [
            'product_id' => $productId->toString(),
            'stock_version' => $productStock->version(),
            'stock_quantity' => $productStock->quantity(),
            'quantity' => 1,
        ]));
    }

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
                $this->products[$event->productId()->toString()] = [
                    'stock_quantity' => $event->stockQuantity(),
                    'stock_version' => $event->stockVersion(),
                    'quantity' => $event->quantity()
                ];
                break;
        }
    }
}

```

`Basket::addProduct()` has quite some logic now and uses an external `ERP` system to request stock information of the product.
In case the ERP system is temporarily unavailable the `Basket` aggregate accepts the product without a stock quantity
check. Later in the checkout process we need to take care of this situation and check stock again. If we have
a quantity stock conflict during checkout the order is routed to a support team who needs to contact the customer and
offer an alternative.

If we got stock information from the ERP system we check quantity and throw a `ProductOutOfStock` exception if it is zero.

*File: ./Basket/src/Model/Exception/ProductOutOfStock.php*
```php
<?php

declare(strict_types=1);

namespace App\Basket\Model\Exception;

use App\Basket\Model\ERP\ProductId;

final class ProductOutOfStock extends \RuntimeException
{
    public static function withProductId(ProductId $productId): self
    {
        return new self(sprintf(
            'Product with %s is out of stock.',
            $productId->toString()
        ));
    }
}

```

If everything is ok the `Basket` aggregate accepts the product and records the `ProductAddedToBasket` event but now
with additional information so we need to add new getter methods to the event.

*File: ./Baset/src/Model/Event/ProductAddedToBasket.php*
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

    public function stockQuantity(): ?int
    {
        return $this->payload()['stock_quantity'];
    }

    public function stockVersion(): ?int
    {
        return $this->payload()['stock_version'];
    }

    public function quantity(): int
    {
        return $this->payload()['quantity'];
    }
}

```

In the `apply` method of the `Basket` aggregate these additional information is added to the internal state
so that we have access to it later.

Testing the expanded version of `Basket::addProduct()` is still relatively simple but we end up with a lot more test methods
to cover the different results of the method call. Check the additions below and see how we can use PHPUnit's `prophecy`
integration to mock the ERP system and simulate different situations.

*File: ./Basket/tests/Model/BasketTest.php*
```php
<?php

declare(strict_types=1);

namespace App\BasketTest\Model;

use App\Basket\Model\Basket;
use App\Basket\Model\ERP\ERP;
use App\Basket\Model\ERP\ProductId;
use App\Basket\Model\ERP\ProductStock;
use App\Basket\Model\Event\ProductAddedToBasket;
use App\Basket\Model\Event\ShoppingSessionStarted;
use App\Basket\Model\Basket\BasketId;
use App\Basket\Model\Basket\ShoppingSession;
use App\Basket\Model\Exception\UnknownProduct;
use App\BasketTest\TestCase;
use Prooph\EventSourcing\AggregateChanged;
use Prophecy\Argument;
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
    public function it_adds_a_product_if_stock_quantity_is_greater_than_zero()
    {
        $basket = $this->reconstituteBasketFromHistory(
            $this->shoppingSessionStarted()
        );

        $basket->addProduct($this->product1, $this->product1ERP());

        /** @var AggregateChanged[] $events */
        $events = $this->popRecordedEvents($basket);

        $this->assertCount(1, $events);

        /** @var ProductAddedToBasket $event */
        $event = $events[0];

        $this->assertSame(ProductAddedToBasket::class, $event->messageName());
        $this->assertTrue($this->basketId->equals($event->basketId()));
        $this->assertTrue($this->product1->equals($event->productId()));
        $this->assertSame(5, $event->payload()['stock_quantity']);
        $this->assertSame(1, $event->payload()['stock_version']);
        $this->assertSame(1, $event->payload()['quantity']);
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
        $basket->addProduct($this->product1, $this->product1ERP());
    }

    /**
     * @test
     * @expectedException \App\Basket\Model\Exception\UnknownProduct
     */
    public function it_stops_operation_if_product_is_unknown()
    {
        $basket = $this->reconstituteBasketFromHistory(
            $this->shoppingSessionStarted()
        );

        $ERP = $this->prophesize(ERP::class);

        //This ERP mock knows no product
        $ERP->getProductStock($this->product1)->willThrow(UnknownProduct::withProductId($this->product1));

        $basket->addProduct($this->product1, $ERP->reveal());
    }

    /**
     * @test
     */
    public function it_adds_product_without_stock_info_if_ERP_is_unavailable()
    {
        $basket = $this->reconstituteBasketFromHistory(
            $this->shoppingSessionStarted()
        );

        $ERP = $this->prophesize(ERP::class);

        //This ERP is unavailable
        $ERP->getProductStock($this->product1)->willReturn(null);

        $basket->addProduct($this->product1, $ERP->reveal());

        /** @var AggregateChanged[] $events */
        $events = $this->popRecordedEvents($basket);

        $this->assertCount(1, $events);

        /** @var ProductAddedToBasket $event */
        $event = $events[0];

        $this->assertSame(ProductAddedToBasket::class, $event->messageName());
        $this->assertTrue($this->basketId->equals($event->basketId()));
        $this->assertTrue($this->product1->equals($event->productId()));
        $this->assertSame(1, $event->payload()['quantity']);
        //No stock info present
        $this->assertSame(null, $event->payload()['stock_quantity']);
        $this->assertSame(null, $event->payload()['stock_version']);
    }

    /**
     * @test
     * @expectedException \App\Basket\Model\Exception\ProductOutOfStock
     */
    public function it_does_not_add_product_if_product_is_out_of_stock()
    {
        $basket = $this->reconstituteBasketFromHistory(
            $this->shoppingSessionStarted()
        );

        //Set stock quantity to zero in the ERP mock
        $basket->addProduct($this->product1, $this->product1ERP(0));
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
            'product_id' => $this->product1->toString(),
            'stock_quantity' => 5,
            'stock_version' => 1,
            'quantity' => 1,
        ]);
    }

    private function product1ERP(int $stockQuantity = 5): ERP
    {
        //Create a Mock of the ERP interface
        $ERP = $this->prophesize(ERP::class);

        $ERP->getProductStock($this->product1)->willReturn(ProductStock::fromArray(
            [
                'product_id' => $this->product1->toString(),
                'quantity' => $stockQuantity,
                'version' => 1
            ]
        ));

        return $ERP->reveal();
    }
}

```

```bash
$ php vendor/bin/phpunit

PHPUnit 6.3.1 by Sebastian Bergmann and contributors.
......                                                              6 / 6 (100%)

Time: 27 ms, Memory: 4.00MB

OK (6 tests, 21 assertions)

```

## Be Careful

You need to be careful when Using a dependency insight of an aggregate method. In the example shown above we only
read data from an external system and we can handle the cases when communication goes wrong be it that the product 
could not be found or the external system is unavailable.

Things get even more complex when you want to write data to an external system. Let's say we want to update stock quantity 
for a product instead of requesting it. Such write operations should not be performed by the aggregate itself but instead
by a process manager or a saga. In the next two chapters we're going to learn more about process managers and sagas
and the difference between the two. 

## Links

- [PHP DDD Cargo Sample](https://github.com/codeliner/php-ddd-cargo-sample)¹
- [Hexagonal Architecture](http://alistair.cockburn.us/Hexagonal+architecture)²
