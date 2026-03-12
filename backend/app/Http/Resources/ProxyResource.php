<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProxyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'host'              => $this->host,
            'port'              => $this->port,
            'protocol'          => $this->protocol,
            'username'          => $this->username,
            'connection_string' => $this->connection_string,
            'status'            => $this->status,
            'response_time_ms'  => $this->response_time_ms,
            'last_checked_at'   => $this->last_checked_at?->toIso8601String(),
            'created_at'        => $this->created_at->toIso8601String(),
            'updated_at'        => $this->updated_at->toIso8601String(),
        ];
    }
}

