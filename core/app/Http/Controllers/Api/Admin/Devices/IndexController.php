<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\Devices;

use App\Http\Controllers\Api\Concerns\RespondsWithDevice;
use App\Http\Controllers\Controller;
use Core\Application\Devices\Handlers\ListDevicesHandler;
use Core\Application\Devices\Queries\ListDevicesQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class IndexController extends Controller
{
    use RespondsWithDevice;

    #[OA\Get(
        path: '/admin/devices',
        summary: 'List devices for admin tables',
        security: [['bearerAuth' => []]],
        tags: ['Admin'],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                description: 'Items per page.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', maximum: 100, minimum: 1, example: 50),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated devices.',
                content: new OA\JsonContent(ref: '#/components/schemas/DeviceListResponse'),
            ),
            new OA\Response(response: 401, description: 'Missing or invalid bearer token.'),
            new OA\Response(response: 403, description: 'User is not an administrator.'),
        ],
    )]
    public function __invoke(Request $request, ListDevicesHandler $handler): JsonResponse
    {
        $data = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $devices = $handler->handle(new ListDevicesQuery((int) ($data['per_page'] ?? 50)));
        $payload = [];

        foreach ($devices->items() as $device) {
            $payload[] = $this->devicePayload($device, true);
        }

        return response()->json([
            'data' => $payload,
            'meta' => [
                'current_page' => $devices->currentPage(),
                'last_page' => $devices->lastPage(),
                'per_page' => $devices->perPage(),
                'total' => $devices->total(),
            ],
        ]);
    }

}
