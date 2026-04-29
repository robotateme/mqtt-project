<?php

declare(strict_types=1);

namespace Core\Application\Devices\Queries;

final readonly class ListUserDevicesQuery
{
    public function __construct(public int $userId)
    {
    }
}
