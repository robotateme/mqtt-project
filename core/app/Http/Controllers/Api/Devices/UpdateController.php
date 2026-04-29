<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Devices;

use App\Http\Controllers\Api\Concerns\RespondsWithDevice;
use App\Http\Controllers\Controller;
use App\Models\User;
use Core\Application\Devices\Commands\UpdateDeviceCommand;
use Core\Application\Devices\Handlers\UpdateDeviceHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

final class UpdateController extends Controller
{
    use RespondsWithDevice;

    #[OA\Put(
        path: '/devices/{device}',
        summary: 'Update authenticated user device',
        security: [['bearerAuth' => []]],
        tags: ['Devices'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Updated device.',
                content: new OA\JsonContent(ref: '#/components/schemas/DeviceResponse'),
            ),
            new OA\Response(response: 401, description: 'Missing or invalid bearer token.'),
            new OA\Response(response: 403, description: 'Device belongs to another user.'),
            new OA\Response(response: 422, description: 'Validation error.'),
        ],
    )]
    public function __invoke(Request $request, int $device, UpdateDeviceHandler $handler): JsonResponse
    {
        $user = $request->user();

        if (!$user instanceof User) {
            throw new AuthenticationException();
        }

        $data = $request->validate([
            'external_id' => ['required', 'string', 'max:255', Rule::unique('devices', 'external_id')->ignore($device)],
            'name' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ]);
        $externalId = (string) $data['external_id'];
        $name = isset($data['name']) ? (string) $data['name'] : null;
        /** @var array<string, mixed>|null $metadata */
        $metadata = isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null;

        $updated = $handler->handle(new UpdateDeviceCommand(
            $device,
            (int) $user->getKey(),
            $externalId,
            $name,
            $metadata,
        ));

        return response()->json(['device' => $this->devicePayload($updated)]);
    }
}
