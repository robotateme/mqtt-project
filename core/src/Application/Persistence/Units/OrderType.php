<?php

declare(strict_types=1);

namespace Core\Application\Persistence\Units;

enum OrderType: string
{
    case ASC = 'ASC';
    case DESC = 'DESC';
}
