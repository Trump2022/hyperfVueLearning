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



