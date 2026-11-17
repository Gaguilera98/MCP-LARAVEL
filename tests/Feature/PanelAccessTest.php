<?php

use App\Models\User;
use Filament\Facades\Filament;

test('el usuario de prueba no entra al panel y el resto sí', function () {
    $panel = Filament::getPanel('admin');

    expect((new User(['email' => 'equipo@god.ai']))->canAccessPanel($panel))->toBeTrue()
        ->and((new User(['email' => 'test@example.com']))->canAccessPanel($panel))->toBeFalse();
});
