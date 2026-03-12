<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proxy extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'host',
        'port',
        'protocol',
        'username',
        'password',
        'status',
        'response_time_ms',
        'last_checked_at',
    ];

    protected $attributes = [
        'status' => 'inactive',
    ];

    protected $casts = [
        'port'             => 'integer',
        'response_time_ms' => 'integer',
        'last_checked_at'  => 'datetime',
    ];

    protected $hidden = [
        'password',
    ];

    public function getConnectionStringAttribute(): string
    {
        return "{$this->protocol}://{$this->host}:{$this->port}";
    }
}

