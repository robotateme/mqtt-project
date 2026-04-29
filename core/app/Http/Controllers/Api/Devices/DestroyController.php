<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Devices;

use App\Http\Controllers\Controller;
use App\Models\User;
use Core\Application\Devices\Commands\DeleteDeviceCommand;
use Core\Application\Devices\Handlers\DeleteDeviceHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class DestroyController extends Controller
{
    /**
     * @throws AuthenticationException
     */
    #[OA\Delete(
        path: '/devices/{device}',
        summary: 'Delete authenticated user device',
        security: [['bearerAuth' => []]],
        tags: ['Devices'],
        responses: [
            new OA\Response(response: 204, description: 'Device deleted.'),
            new OA\Response(response: 401, description: 'Missing or invalid bearer token.'),
            new OA\Response(response: 403, description: 'Device belongs to another user.'),
        ],
    )]
    public function __invoke(Request $request, int $device, DeleteDeviceHandler $handler): JsonResponse
    {
        $user = $request->user();

        if (!$user instanceof User) {
            throw new AuthenticationException("Unauthenticated!");
        }

        $handler->handle(new DeleteDeviceCommand($device, (int) $user->getKey()));

        return response()->json(null, 204);
    }
}
