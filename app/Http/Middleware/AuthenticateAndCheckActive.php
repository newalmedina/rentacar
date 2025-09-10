<?php

namespace App\Http\Middleware;

use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Illuminate\Database\Eloquent\Model;

class AuthenticateAndCheckActive extends FilamentAuthenticate
{
    /**
     * @param  array<string>  $guards
     */
    protected function authenticate($request, array $guards): void
    {
        $guard = Filament::auth();

        if (! $guard->check()) {
            $this->unauthenticated($request, $guards);
            return;
        }

        $this->auth->shouldUse(Filament::getAuthGuard());

        /** @var Model $user */
        $user = $guard->user();

        $panel = Filament::getCurrentPanel();

        abort_if(
            $user instanceof FilamentUser ?
                (! $user->canAccessPanel($panel)) : (config('app.env') !== 'local'),
            403,
        );

        if (!$user->active) {
            $guard->logout();

            abort(403, 'Tu cuenta está desactivada.');
        }

        $center = $user->center;

        if (!$user->center_id) {
            $guard->logout();
            abort(403, 'No tienes un centro asignado. Contacta con la administración.');
        } elseif (! $center->active) {
            $guard->logout();
            abort(403, 'El centro asignado no está activo. Contacta con la administración.');
        }

        // ✅ VERIFICACIÓN DE ACCESO AL PANEL ADMIN
        // Nueva verificación:
        if ($panel->getId() == "admin" && !$user->can_admin_panel) {
            abort(403, 'No tienes permiso para acceder al panel de administración.');
        }
    }
}
