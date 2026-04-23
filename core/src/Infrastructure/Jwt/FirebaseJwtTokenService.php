<?php

declare(strict_types=1);

namespace Core\Infrastructure\Jwt;

use App\Models\User;
use Core\Application\Auth\Data\AuthToken;
use Core\Application\Auth\Data\JwtPayload;
use Core\Application\Auth\JwtTokenService;
use Core\Application\Users\UserRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Str;
use UnexpectedValueException;

final readonly class FirebaseJwtTokenService implements JwtTokenService
{
    public function __construct(
        private UserRepository $users,
        private string $secret,
        private string $issuer,
        private string $audience,
        private int $ttlMinutes,
        private int $refreshTtlMinutes,
        private int $leewaySeconds,
    ) {
        JWT::$leeway = $this->leewaySeconds;
    }

    public function issueFor(User $user): AuthToken
    {
        $now = time();

        return new AuthToken(
            $this->encode($user, 'access', $now, $now + ($this->ttlMinutes * 60)),
            $this->encode($user, 'refresh', $now, $now + ($this->refreshTtlMinutes * 60)),
            'bearer',
            $this->ttlMinutes * 60,
        );
    }

    public function refresh(string $refreshToken): AuthToken
    {
        $payload = $this->decode($refreshToken);

        if ($payload->type !== 'refresh') {
            throw new AuthenticationException('Invalid refresh token.');
        }

        $user = $this->users->findById($payload->userId);

        if ($user === null) {
            throw new AuthenticationException('User not found.');
        }

        return $this->issueFor($user);
    }

    public function decode(string $token): JwtPayload
    {
        try {
            $payload = JWT::decode($token, new Key($this->normalizedSecret(), 'HS256'));
        } catch (UnexpectedValueException $exception) {
            throw new AuthenticationException('Invalid token.', previous: $exception);
        }

        return new JwtPayload(
            (int) $payload->sub,
            (string) ($payload->role ?? 'user'),
            (string) ($payload->typ ?? 'access'),
            (int) $payload->exp,
        );
    }

    private function encode(User $user, string $type, int $issuedAt, int $expiresAt): string
    {
        return JWT::encode([
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'jti' => (string) Str::uuid(),
            'iat' => $issuedAt,
            'nbf' => $issuedAt,
            'exp' => $expiresAt,
            'sub' => (string) $user->getKey(),
            'role' => (string) $user->role,
            'typ' => $type,
        ], $this->normalizedSecret(), 'HS256');
    }

    private function normalizedSecret(): string
    {
        if (str_starts_with($this->secret, 'base64:')) {
            $decoded = base64_decode(substr($this->secret, 7), true);

            if ($decoded !== false) {
                return $decoded;
            }
        }

        return $this->secret;
    }
}
