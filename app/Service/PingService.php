<?php

declare(strict_types=1);

namespace App\Service;

class PingService
{
    public function pong(): string
    {
        return 'ok';
    }
}
