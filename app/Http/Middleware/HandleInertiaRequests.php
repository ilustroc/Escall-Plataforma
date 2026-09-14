<?php
namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /** PHPUnit requests do not perform the initial HTML visit that supplies Vite's asset version. */
    public function version(Request $request): ?string
    {
        return app()->environment('testing') ? null : parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'appName'=>config('app.name'),
            'auth'=>['user'=>$request->user()?->only(['id','nombre','usuario','rol'])],
            'flash'=>['success'=>fn()=>$request->session()->get('success'), 'error'=>fn()=>$request->session()->get('error')],
        ];
    }
}
