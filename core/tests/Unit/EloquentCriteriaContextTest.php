<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Device;
use Core\Application\Persistence\SearchCriteria\Units\FilterType;
use Core\Application\Persistence\SearchCriteria\Units\Order;
use Core\Application\Persistence\Units\Criteria;
use Core\Application\Persistence\Units\Filter;
use Core\Application\Persistence\Units\OrderType;
use Core\Infrastructure\Persistence\SQL\EloquentCriteriaContext;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * @psalm-suppress PossiblyUnusedMethod
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-suppress UnusedClass
 */
final class EloquentCriteriaContextTest extends TestCase
{
    public function test_applies_scalar_filters_ordering_and_limit(): void
    {
        $criteria = new Criteria(
            filters: [
                new Filter('external_id', FilterType::EQUAL, 'device-42'),
                new Filter('name', FilterType::LIKE, '%sensor%'),
            ],
            orders: [
                new Order('id', OrderType::DESC),
            ],
            limit: 10,
        );

        $query = new EloquentCriteriaContext(Device::query())->query($criteria);

        self::assertSame(
            'select * from "devices" where "external_id" = ? and "name" like ? order by "id" desc limit 10',
            $query->toSql(),
        );
        self::assertSame(['device-42', '%sensor%'], $query->getBindings());
    }

    public function test_applies_in_filters(): void
    {
        $criteria = new Criteria(filters: [
            new Filter('id', FilterType::IN, [1, 2, 3]),
            new Filter('status', FilterType::NOT_IN, ['archived']),
        ]);

        $query = new EloquentCriteriaContext(Device::query())->query($criteria);

        self::assertSame(
            'select * from "devices" where "id" in (?, ?, ?) and "status" not in (?)',
            $query->toSql(),
        );
        self::assertSame([1, 2, 3, 'archived'], $query->getBindings());
    }

    public function test_rejects_non_iterable_value_for_in_filter(): void
    {
        $criteria = new Criteria(filters: [
            new Filter('id', FilterType::IN, 7),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter value must be iterable for IN operators.');

        new EloquentCriteriaContext(Device::query())->query($criteria);
    }
}
