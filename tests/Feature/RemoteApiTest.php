<?php

use App\Support\RemoteApi;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    RemoteApi::forget();
    config([
        'services.zalo_api.base_url' => 'http://desde-entorno.test',
        'services.zalo_api.token' => 'token-entorno',
    ]);
});

afterEach(function () {
    Schema::dropIfExists('service_connections');
    RemoteApi::forget();
});

it('uses the environment when the panel has nothing', function () {
    expect(RemoteApi::url('zalo'))->toBe('http://desde-entorno.test')
        ->and(RemoteApi::token('zalo'))->toBe('token-entorno');
});

it('lets the panel override the environment and clears a blank token', function () {
    Schema::create('service_connections', function (Blueprint $table) {
        $table->id();
        $table->string('service')->unique();
        $table->string('base_url')->nullable();
        $table->text('token')->nullable();
        $table->timestamps();
    });
    RemoteApi::forget();

    RemoteApi::persist('zalo', 'http://desde-panel.test/', 'token-panel', false);

    expect(RemoteApi::url('zalo'))->toBe('http://desde-panel.test')
        ->and(RemoteApi::token('zalo'))->toBe('token-panel')
        ->and(RemoteApi::hint('zalo'))->toContain('panel')
        ->and(RemoteApi::hint('zalo'))->toContain('anel');

    expect(RemoteApi::formState()['zalo_base_url'])->toBe('http://desde-panel.test')
        ->and(RemoteApi::formState()['zalo_token'])->toBe('token-panel');

    RemoteApi::persist('zalo', 'http://desde-panel.test', '', false);

    expect(RemoteApi::token('zalo'))->toBe('token-entorno');

    RemoteApi::persist('zalo', '', '', true);

    expect(RemoteApi::url('zalo'))->toBe('http://desde-entorno.test')
        ->and(RemoteApi::token('zalo'))->toBe('token-entorno');
});
