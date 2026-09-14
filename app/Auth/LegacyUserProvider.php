<?php
namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class LegacyUserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials['usuario'])) return null;
        return $this->createModel()->newQuery()
            ->whereRaw('LOWER(usuario) = ?', [mb_strtolower(trim($credentials['usuario']), 'UTF-8')])
            ->where(fn($query) => $query->where('activo', 1)->orWhereNull('activo'))->first();
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        $plain = $credentials['password'] ?? '';
        $stored = $user->getAuthPassword();
        if (!is_string($plain) || !is_string($stored) || $stored === '') return false;
        if (password_get_info($stored)['algo'] !== null) return password_verify($plain, $stored);
        // Transición opt-in: el provider Laravel rehashea tras autenticar correctamente.
        // Nunca se migran credenciales ni se consulta producción durante el desarrollo.
        return config('auth.legacy_passwords', false) && hash_equals($stored, $plain);
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false)
    {
        if (password_get_info($user->getAuthPassword())['algo'] === null) $force = true;
        parent::rehashPasswordIfRequired($user, $credentials, $force);
    }
}