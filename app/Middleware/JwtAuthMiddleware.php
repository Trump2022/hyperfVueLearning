<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Model\User;
use App\Service\JwtService;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as ResponseFactory;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JwtAuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly ResponseFactory $response, private readonly JwtService $jwt, private readonly ConfigInterface $config)
    {

    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): PsrResponse
    {
        $authorization = $request->getHeaderLine('Authorization');

        if (! preg_match('/^Bearer\s+(\S+)$/i', $authorization, $matches)) {
            return $this->unauthorized();
        }

        try {
            $claims = $this->jwt->decode($matches[1]);
        } catch (\Throwable) {
            return $this->unauthorized();
        }

        $userId = filter_var($claims->sub ?? null, FILTER_VALIDATE_INT);
        $expectedIssuer = (string) $this->config->get('jwt.issuer', 'hyperfVueLearning');

        if (
            ($claims->iss ?? null) !== $expectedIssuer
            || $userId === false
            || $userId < 1
        ) {
            return $this->unauthorized();
        }

        $user = User::query()->find($userId);

        if ($user === null) {
            return $this->unauthorized();
        }

        return $handler->handle($request->withAttribute('auth_user', $user));
    }

    private function unauthorized(): PsrResponse
    {
        return $this->response
            ->json(['message' => '未登录或登录已过期。'])
            ->withStatus(401);
    }
}