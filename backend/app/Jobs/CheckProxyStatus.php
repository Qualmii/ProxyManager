<?php

namespace App\Jobs;

use App\Models\Proxy;
use App\Services\ProxyCheckerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckProxyStatus implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 30;

    public function __construct(public readonly Proxy $proxy) {}

    public function handle(ProxyCheckerService $checker): void
    {
        $checker->check($this->proxy);
    }
}

