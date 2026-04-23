<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Core\Application\Auth\Commands\RefreshTokenCommand;
use Core\Application\Auth\Handlers\RefreshTokenHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RefreshController extends Controller
{
    public function __invoke(Request $request, RefreshTokenHandler $handler): JsonResponse
    {
        $data = $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        return response()->json([
            'token' => $handler->handle(new RefreshTokenCommand($data['refresh_token']))->toArray(),
        ]);
    }
}
