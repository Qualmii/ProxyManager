<?php

use App\Jobs\CheckProxyStatus;
use App\Models\Proxy;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    Proxy::all()->each(function (Proxy $proxy) {
        $proxy->update(['status' => 'checking']);
        CheckProxyStatus::dispatch($proxy);
    });
})->everyFiveMinutes()->name('check-all-proxies')->withoutOverlapping();
