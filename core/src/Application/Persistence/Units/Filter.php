<?php

declare(strict_types=1);

namespace Core\Application\Persistence\Units;

use Core\Application\Persistence\SearchCriteria\Units\FilterType;

readonly final class Filter
{
    public function __construct(
        public string $column,
        public FilterType $operator,
        public mixed $value,
    ) {
    }
}
