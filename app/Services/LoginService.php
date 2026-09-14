<?php
namespace App\Services;

use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Str;

class LoginService
{
    public const ERROR = 'No se pudo iniciar sesión con las credenciales proporcionadas.';
    public function authenticate(LoginRequest $request): void
    {
        $userKey = 'login:'.hash('sha256', Str::transliterate(mb_strtolower(trim($request->string('usuario')->toString()))).'|'.$request->ip());
        $ipKey = 'login:ip:'.hash('sha256', $request->ip());
        Cache::lock($ipKey.':lock', 10)->block(5, function () use ($request,$userKey,$ipKey): void {
            if (RateLimiter::tooManyAttempts($userKey, 5) || RateLimiter::tooManyAttempts($ipKey, 30)) {
                $retry = max(RateLimiter::availableIn($userKey), RateLimiter::availableIn($ipKey), 1);
                throw new ThrottleRequestsException(self::ERROR, null, ['Retry-After'=>(string)$retry]);
            }
            RateLimiter::hit($userKey, 900);
            RateLimiter::hit($ipKey, 900);
            // Coste de verificación también para usuarios inexistentes/inactivos.
            password_verify($request->input('contrasena'), '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
            try {
                $authenticated = Auth::attempt(['usuario'=>$request->input('usuario'),'password'=>$request->input('contrasena')], false);
            } catch (\Throwable $e) {
                $authenticated = false;
            }
            if (!$authenticated) throw ValidationException::withMessages(['usuario'=>self::ERROR]);
            RateLimiter::clear($userKey);
            $request->session()->regenerate();
            $request->session()->regenerateToken();
        });
    }
}