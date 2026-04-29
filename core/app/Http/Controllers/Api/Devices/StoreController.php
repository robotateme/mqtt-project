<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Devices;

use App\Http\Controllers\Api\Concerns\RespondsWithDevice;
use App\Http\Controllers\Controller;
use App\Models\User;
use Core\Application\Devices\Commands\CreateDeviceCommand;
use Core\Application\Devices\Handlers\CreateDeviceHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class StoreController extends Controller
{
    use RespondsWithDevice;

    #[OA\Post(
        path: '/devices',
        summary: 'Create authenticated user device',
        security: [['bearerAuth' => []]],
        tags: ['Devices'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Created device.',
                content: new OA\JsonContent(ref: '#/components/schemas/DeviceResponse'),
            ),
            new OA\Response(response: 401, description: 'Missing or invalid bearer token.'),
            new OA\Response(response: 422, description: 'Validation error.'),
        ],
    )]
    public function __invoke(Request $request, CreateDeviceHandler $handler): JsonResponse
    {
        $user = $request->user();

        if (!$user instanceof User) {
            throw new AuthenticationException();
        }

        $data = $request->validate([
            'external_id' => ['required', 'string', 'max:255', 'unique:devices,external_id'],
            'name' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ]);
        $externalId = (string) $data['external_id'];
        $name = isset($data['name']) ? (string) $data['name'] : null;
        /** @var array<string, mixed>|null $metadata */
        $metadata = isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null;

        $device = $handler->handle(new CreateDeviceCommand(
            (int) $user->getKey(),
            $externalId,
            $name,
            $metadata,
        ));

        return response()->json(['device' => $this->devicePayload($device)], 201);
    }
}
