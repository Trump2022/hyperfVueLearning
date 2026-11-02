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

namespace App\Controller;
use Hyperf\Coroutine\Coroutine;
use function Hyperf\Coroutine\parallel;
use App\Service\PingService;
use function Hyperf\Coroutine\go;
use Hyperf\Redis\RedisFactory;
use App\Model\User;

class IndexController extends AbstractController
{
    public function __construct(private readonly PingService $pingService, private readonly RedisFactory $redisFactory)
    {
    }

    public function index(): array
    {
        $user = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();

        return [
            'method' => $method,
            'message' => "Hello {$user}.",
        ];
    }

    public function dbDemo(): array
    {
        $user = User::query()->select('id')->first();
        return ['user_id' => $user->id];
    }

    public function redisDemo()
    {
        $redis = $this->redisFactory->get('default');
        $key = 'hyperfVueLearning:lesson:redis';

        $redis->setex($key, 60, 'hello-redis');

        return [
            'key' => $key,
            'value' => $redis->get($key),
        ];
    }

    public function protected(): array
    {
        return ['data' => 'private'];
    }

    public function ping(): array
    {
        return ['pong' => $this->pingService->pong()];
    }

    public function parallel(): array
    {

        $startedAt = microtime(true);

        $results = parallel([
            'first' => static function (): string {
                Coroutine::sleep(1);
                return 'first';
            },
            'second' => static function (): string {
                Coroutine::sleep(1);
                return 'second';
            },
        ], 2);

        return [
            'results' => $results,
            'elapsed_ms' => round( (microtime(true) - $startedAt) * 1000),
        ];
    }

    public function goDemo(): array
    {
        $started = go(static function (){
            Coroutine::sleep(1);
        });
        return ['started' => $started !== false];
    }
}
