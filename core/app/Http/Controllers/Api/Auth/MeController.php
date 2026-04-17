<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\Concerns\RespondsWithUser;
use App\Http\Controllers\Controller;
use Core\Application\Auth\Handlers\GetAuthenticatedUserHandler;
use Core\Application\Auth\Queries\GetAuthenticatedUserQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MeController extends Controller
{
    use RespondsWithUser;

    public function __invoke(Request $request, GetAuthenticatedUserHandler $handler): JsonResponse
    {
        $user = $handler->handle(new GetAuthenticatedUserQuery((int) $request->user()->getKey()));

        return response()->json([
            'user' => $this->userPayload($user),
        ]);
    }
}
