<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class LogoutController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
