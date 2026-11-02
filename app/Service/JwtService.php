<?php

declare(strict_types=1);

namespace App\Service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Hyperf\Contract\ConfigInterface;
use Random\RandomException;
use RuntimeException;

class JwtService
{
    public function __construct(private readonly ConfigInterface $config)
    {
    }

    /**
     * @return array{access_token: string, token_type: string, expires_in: int}
     * @throws RandomException
     */
    public function issue(int|string $userId): array
    {
        $now = time();
        $ttl = (int) $this->config->get('jwt.ttl', 3600);

        $token = JWT::encode([
            'iss' => $this->config->get('jwt.issuer', 'hyperfVueLearning'),
            'sub' => (string) $userId,
            'iat' => $now,
            'exp' => $now + $ttl,
            'jti' => bin2hex(random_bytes(16)),
            'purpose' => 'access',
        ], $this->secret(), 'HS256');

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $ttl,
        ];
    }

    public function issueEmailVerification(int|string $userId, string $email): string
    {
        $now = time();

        return JWT::encode([
            'iss' => $this->config->get('jwt.issuer', 'hyperfVueLearning'),
            'sub' => (string) $userId,
            'purpose' => 'email_verification',
            'email_hash' => hash('sha256', strtolower(trim($email))),
            'iat' => $now,
            'exp' => $now + 86400,
            'jti' => bin2hex(random_bytes(16)),
        ], $this->secret(), 'HS256');
    }

    public function decode(string $token): object
    {
        return JWT::decode($token, new Key($this->secret(), 'HS256'));
    }

    private function secret(): string
    {
        $secret = (string) $this->config->get('jwt.secret', '');

        if ($secret === '') {
            throw new RuntimeException('JWT_SECRET is not configured.');
        }

        return $secret;
    }
}