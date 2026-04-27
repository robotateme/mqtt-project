<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    security: [
        ['bearerAuth' => []],
    ],
)]
#[OA\Info(
    version: '1.0.0',
    title: 'MQTT Project Core API',
    description: 'Core API for authentication and administrative profile endpoints.',
)]
#[OA\Server(
    url: '/api/v1',
    description: 'Versioned API',
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    description: 'JWT access token issued by auth endpoints.',
    bearerFormat: 'JWT',
    scheme: 'bearer',
)]
#[OA\Schema(
    schema: 'AuthToken',
    required: ['access_token', 'refresh_token', 'token_type', 'expires_in'],
    properties: [
        new OA\Property(property: 'access_token', type: 'string'),
        new OA\Property(property: 'refresh_token', type: 'string'),
        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
        new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'User',
    required: ['id', 'name', 'email', 'role'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Admin'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@example.com'),
        new OA\Property(property: 'role', type: 'string', example: 'admin'),
        new OA\Property(property: 'email_verified_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'AuthResponse',
    required: ['user', 'token'],
    properties: [
        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
        new OA\Property(property: 'token', ref: '#/components/schemas/AuthToken'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'MeResponse',
    required: ['user'],
    properties: [
        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'AdminMeResponse',
    required: ['user', 'panel'],
    properties: [
        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
        new OA\Property(property: 'panel', type: 'string', example: 'admin'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'TokenResponse',
    required: ['token'],
    properties: [
        new OA\Property(property: 'token', ref: '#/components/schemas/AuthToken'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'ValidationError',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string')),
        ),
    ],
    type: 'object',
)]
final readonly class CoreApi
{
}
