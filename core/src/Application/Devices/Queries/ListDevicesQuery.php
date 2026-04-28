<?php

declare(strict_types=1);

namespace Core\Application\Devices\Queries;

final readonly class ListDevicesQuery
{
    public function __construct(public int $perPage = 50)
    {
    }
}
