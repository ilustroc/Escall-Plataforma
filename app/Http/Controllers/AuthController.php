<?php
namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\LoginService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLogin() { return Inertia::render('Auth/Login', ['recoveryUrl'=>config('platform.recovery_url')]); }
    public function login(LoginRequest $request, LoginService $service)
    {
        $service->authenticate($request);
        return redirect()->intended('/inicio');
    }
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}