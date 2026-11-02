<?php

declare(strict_types=1);

namespace App\Middleware;

// use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as ResponseFactory;
use Psr\Http\Message\ResponseInterface as PsrResponse;

class DemoTokenMiddleware implements MiddlewareInterface
{
    // public function __construct(protected ContainerInterface $container)
    // {
    // }
    //
    // public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    // {
    //     return $handler->handle($request);
    // }

    public function __construct(private ResponseFactory $response)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): PsrResponse
    {
        if ($request->getHeaderLine('Authorization') !== 'Bearer learning-token') {
            return $this->response
                ->json(['message' => 'Unauthorized'])
                ->withStatus(401);
        }

        return $handler->handle($request);
    }
}
