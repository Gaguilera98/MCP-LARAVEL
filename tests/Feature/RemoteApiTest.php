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
        ->and(RemoteApi::token('zalo'))->toBe('token-entorno')
        ->and(RemoteApi::persist('zalo', 'http://otra.test', 'otra', false))->toBeFalse();
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

    // El formulario trae la URL, pero nunca el token.
    expect(RemoteApi::formState()['zalo_base_url'])->toBe('http://desde-panel.test')
        ->and(RemoteApi::formState()['zalo_token'])->toBe('');

    RemoteApi::persist('zalo', 'http://desde-panel.test', '', false);

    expect(RemoteApi::token('zalo'))->toBe('token-entorno');

    RemoteApi::persist('zalo', '', '', true);

    expect(RemoteApi::url('zalo'))->toBe('http://desde-entorno.test')
        ->and(RemoteApi::token('zalo'))->toBe('token-entorno');

    expect(RemoteApi::persist('zalo', 'http://desde-entorno.test/', 'token-entorno', false))->toBeTrue()
        ->and(RemoteApi::hasPanelToken('zalo'))->toBeFalse()
        ->and(RemoteApi::hint('zalo'))->toContain('entorno')
        ->and(\App\Models\ServiceConnection::query()->where('service', 'zalo')->exists())->toBeFalse();

    RemoteApi::persist('zalo', 'http://otra.test', 'token-entorno', false);

    expect(RemoteApi::url('zalo'))->toBe('http://otra.test')
        ->and(RemoteApi::token('zalo'))->toBe('token-entorno')
        ->and(RemoteApi::hasPanelToken('zalo'))->toBeFalse()
        ->and(RemoteApi::hint('zalo'))->toContain('URL del panel');

    expect(fn () => RemoteApi::persist('zalo', 'no-es-url', 'token-panel', false))
        ->toThrow(InvalidArgumentException::class);
});

it('never sends a token to the form, from the panel or from the environment', function () {
    config(['services.heraldo_api.token' => 'token-heraldo-entorno']);

    $state = RemoteApi::formState();

    foreach (array_keys(RemoteApi::SERVICES) as $service) {
        expect($state[$service.'_token'])->toBe('')
            ->and($state[$service.'_clear_token'])->toBeFalse();
    }
    expect(json_encode($state))->not->toContain('token-entorno')
        ->and(json_encode($state))->not->toContain('token-heraldo-entorno')
        ->and(RemoteApi::hint('zalo'))->toContain('torn')
        ->and(RemoteApi::hint('zalo'))->not->toContain('token-entorno');
});

it('keeps the saved token when the field comes back empty, and clears it only when asked', function () {
    Schema::create('service_connections', function (Blueprint $table) {
        $table->id();
        $table->string('service')->unique();
        $table->string('base_url')->nullable();
        $table->text('token')->nullable();
        $table->timestamps();
    });
    RemoteApi::forget();
    RemoteApi::persist('zalo', null, 'token-panel', false);

    // Guardar el formulario sin tocar el token: queda el del panel.
    RemoteApi::persist('zalo', 'http://otra.test', RemoteApi::tokenToSave('zalo', '', false), false);
    expect(RemoteApi::token('zalo'))->toBe('token-panel')
        ->and(RemoteApi::url('zalo'))->toBe('http://otra.test');

    // Uno nuevo lo reemplaza.
    RemoteApi::persist('zalo', 'http://otra.test', RemoteApi::tokenToSave('zalo', ' token-nuevo ', false), false);
    expect(RemoteApi::token('zalo'))->toBe('token-nuevo');

    // Borrar vuelve al del entorno, aunque se haya escrito algo.
    RemoteApi::persist('zalo', 'http://otra.test', RemoteApi::tokenToSave('zalo', 'ignorado', true), true);
    expect(RemoteApi::token('zalo'))->toBe('token-entorno')
        ->and(RemoteApi::hasPanelToken('zalo'))->toBeFalse();

    // Sin token en el panel, vacío sigue con el del entorno.
    expect(RemoteApi::tokenToSave('zalo', '', false))->toBeNull();
});
