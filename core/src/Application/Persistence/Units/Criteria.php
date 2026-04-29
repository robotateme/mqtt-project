<?php

declare(strict_types=1);

namespace Core\Application\Persistence\Units;

use Core\Application\Persistence\SearchCriteria\Contracts\Criteria as CriteriaContract;
use Core\Application\Persistence\SearchCriteria\Units\Order;
use Override;

readonly final class Criteria implements CriteriaContract
{
    /**
     * @param list<Filter> $filters
     * @param list<Order> $orders
     */
    public function __construct(
        private array $filters = [],
        private array $orders = [],
        private ?int $limit = null,
    ) {
    }

    /**
     * @return list<Filter>
     */
    #[Override]
    public function getFilters(): array
    {
        return $this->filters;
    }

    #[Override]
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * @return list<Order>
     */
    #[Override]
    public function getOrders(): array
    {
        return $this->orders;
    }
}
