<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Devices;

use App\Http\Controllers\Controller;
use App\Models\User;
use Core\Application\Devices\DeviceRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class StreamController extends Controller
{
    #[OA\Get(
        path: '/devices/{device}/stream',
        summary: 'Get Mercure stream configuration for user device packets',
        security: [['bearerAuth' => []]],
        tags: ['Devices'],
        responses: [
            new OA\Response(response: 200, description: 'Mercure stream configuration.'),
            new OA\Response(response: 401, description: 'Missing or invalid bearer token.'),
            new OA\Response(response: 403, description: 'Device belongs to another user.'),
        ],
    )]
    public function __invoke(Request $request, int $device, DeviceRepository $devices): JsonResponse
    {
        $user = $request->user();

        if (!$user instanceof User) {
            throw new AuthenticationException();
        }

        $model = $devices->findById($device);

        if ($model === null || (int) $model->getAttribute('user_id') !== (int) $user->getKey()) {
            throw new AuthorizationException('Device is not available for this user.');
        }

        $externalId = (string) $model->getAttribute('external_id');

        return response()->json([
            'mercure_url' => config('mercure.public_url'),
            'topic' => sprintf('/devices/%s/packets', rawurlencode($externalId)),
        ]);
    }
}
