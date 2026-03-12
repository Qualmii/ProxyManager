<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proxies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('host');
            $table->unsignedSmallInteger('port');
            $table->enum('protocol', ['http', 'https', 'socks4', 'socks5'])->default('http');
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->enum('status', ['active', 'inactive', 'checking'])->default('inactive');
            $table->integer('response_time_ms')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proxies');
    }
};

