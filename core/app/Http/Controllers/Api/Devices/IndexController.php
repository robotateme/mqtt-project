<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Devices;

use App\Http\Controllers\Api\Concerns\RespondsWithDevice;
use App\Http\Controllers\Controller;
use App\Models\User;
use Core\Application\Devices\Handlers\ListUserDevicesHandler;
use Core\Application\Devices\Queries\ListUserDevicesQuery;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class IndexController extends Controller
{
    use RespondsWithDevice;

    #[OA\Get(
        path: '/devices',
        summary: 'List authenticated user devices',
        security: [['bearerAuth' => []]],
        tags: ['Devices'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User devices.',
                content: new OA\JsonContent(ref: '#/components/schemas/DeviceCollectionResponse'),
            ),
            new OA\Response(response: 401, description: 'Missing or invalid bearer token.'),
        ],
    )]
    public function __invoke(Request $request, ListUserDevicesHandler $handler): JsonResponse
    {
        $user = $request->user();

        if (!$user instanceof User) {
            throw new AuthenticationException();
        }

        $devices = $handler->handle(new ListUserDevicesQuery((int) $user->getKey()));
        $payload = [];

        foreach ($devices as $device) {
            $payload[] = $this->devicePayload($device);
        }

        return response()->json(['data' => $payload]);
    }
}
