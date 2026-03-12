<?php

namespace App\Events;

use App\Http\Resources\ProxyResource;
use App\Models\Proxy;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProxyStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $proxy;

    public function __construct(Proxy $proxy)
    {
        $this->proxy = (new ProxyResource($proxy))->resolve();
    }

    /**
     * Публичный канал — все подписчики получат обновление
     */
    public function broadcastOn(): Channel
    {
        return new Channel('proxies');
    }

    public function broadcastAs(): string
    {
        return 'proxy.status.updated';
    }
}

