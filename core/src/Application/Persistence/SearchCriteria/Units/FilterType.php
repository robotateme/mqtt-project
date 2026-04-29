<?php

declare(strict_types=1);

namespace Core\Application\Persistence\SearchCriteria\Units;

enum FilterType: string
{
    case EQUAL = '=';
    case NOT_EQUAL = '!=';
    case GREATER_THAN = '>';
    case GREATER_OR_EQUAL = '>=';
    case LESS_THAN = '<';
    case LESS_OR_EQUAL = '<=';
    case LIKE = 'like';
    case IN = 'in';
    case NOT_IN = 'not in';
}
