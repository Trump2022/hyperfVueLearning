<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
use Hyperf\HttpServer\Router\Router;

Router::addRoute(['GET', 'POST', 'HEAD'], '/', 'App\Controller\IndexController@index');
Router::get('/api/ping', 'App\Controller\IndexController@ping');

Router::get('/favicon.ico', function () {
    return '';
});

Router::get(
    '/api/protected',
    [\App\Controller\IndexController::class, 'protected'],
    ['middleware' => [\App\Middleware\DemoTokenMiddleware::class]]
);

Router::get('/api/parallel', 'App\Controller\IndexController@parallel');
Router::get('/api/go', 'App\Controller\IndexController@goDemo');
Router::get('/api/redis-demo', 'App\Controller\IndexController@redisDemo');
Router::get('/api/db-demo', 'App\Controller\IndexController@dbDemo');

Router::post('/api/auth/register', [\App\Controller\AuthController::class, 'register']);
Router::post('/api/auth/login', [\App\Controller\AuthController::class, 'login']);


Router::get('/api/auth/me', [\App\Controller\AuthController::class, 'me'], [
    'middleware' => [\App\Middleware\JwtAuthMiddleware::class],
]);
Router::patch('/api/auth/me', [\App\Controller\AuthController::class, 'updateProfile'], [
    'middleware' => [\App\Middleware\JwtAuthMiddleware::class],
]);
Router::patch('/api/auth/me/password', [\App\Controller\AuthController::class, 'updatePassword'], [
    'middleware' => [\App\Middleware\JwtAuthMiddleware::class],
]);

Router::get('/api/auth/verify-email', [\App\Controller\AuthController::class, 'verifyEmail']);
Router::post('/api/auth/email/verification-notification', [\App\Controller\AuthController::class, 'resendVerification'], [
    'middleware' => [\App\Middleware\JwtAuthMiddleware::class],
]);

Router::post('/api/auth/forgot-password', [
    \App\Controller\AuthController::class,
    'sendPasswordResetLink',
]);

Router::post('/api/auth/reset-password', [
    \App\Controller\AuthController::class,
    'resetPassword',
]);








