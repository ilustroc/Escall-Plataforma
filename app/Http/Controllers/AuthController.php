<?php
namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\LoginService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }
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
        if ($request->header('X-Inertia')) {
            return Inertia::location(route('login'));
        }
        return redirect('/login');
    }
}
