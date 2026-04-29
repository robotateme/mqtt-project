<?php

declare(strict_types=1);

namespace Core\Application\Persistence\SearchCriteria\Units;

use Core\Application\Persistence\Units\OrderType;

readonly final class Order
{
    public function __construct(
        public string $field,
        public OrderType $direction,
    ) {
    }
}
