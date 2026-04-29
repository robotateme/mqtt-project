<?php

declare(strict_types=1);

namespace Core\Infrastructure\Persistence\SQL;

use Core\Application\Persistence\SearchCriteria\Contracts\Criteria;
use Core\Application\Persistence\SearchCriteria\Units\FilterType;
use Core\Application\Persistence\Units\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Traversable;

/**
 * @template TModel of Model
 */
final class EloquentCriteriaContext
{
    /** @var Builder<TModel> */
    private Builder $queryBuilder;

    /**
     * @param Builder<TModel> $queryBuilder
     */
    public function __construct(Builder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @return Builder<TModel>
     */
    public function query(Criteria $criteria): Builder
    {
        $this->applyFilters($criteria);
        $this->applyOrdering($criteria);
        $this->applyLimit($criteria);

        return $this->queryBuilder;
    }

    private function applyFilters(Criteria $criteria): void
    {
        foreach ($criteria->getFilters() as $filter) {
            match ($filter->operator) {
                FilterType::IN => $this->queryBuilder->whereIn($filter->column, $this->arrayValue($filter)),
                FilterType::NOT_IN => $this->queryBuilder->whereNotIn($filter->column, $this->arrayValue($filter)),
                default => $this->queryBuilder->where($filter->column, $filter->operator->value, $filter->value),
            };
        }
    }

    private function applyOrdering(Criteria $criteria): void
    {
        foreach ($criteria->getOrders() as $order) {
            $this->queryBuilder->orderBy($order->field, $order->direction->value);
        }
    }

    private function applyLimit(Criteria $criteria): void
    {
        $limit = $criteria->getLimit();

        if ($limit !== null) {
            $this->queryBuilder->limit($limit);
        }
    }

    /**
     * @return array<array-key, mixed>
     */
    private function arrayValue(Filter $filter): array
    {
        if (!is_iterable($filter->value)) {
            throw new InvalidArgumentException('Filter value must be iterable for IN operators.');
        }

        if ($filter->value instanceof Traversable) {
            return iterator_to_array($filter->value);
        }

        return $filter->value;
    }
}
