<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['buscar' => ['nullable', 'string', 'max:255']]);
        $users = User::query()
            ->when($filters['buscar'] ?? null, fn ($q, $term) => $q->where(fn ($nested) => $nested->where('nombre', 'like', "%{$term}%")->orWhere('usuario', 'like', "%{$term}%")))
            ->orderBy('nombre')->paginate(15)->withQueryString();

        return Inertia::render('Admin/Index', ['users' => $users, 'filters' => $filters]);
    }

    public function store(UserRequest $request, UserService $users)
    {
        $users->save($request->validated(), $request->user());
        return $this->success($request, 'Usuario creado.');
    }

    public function update(UserRequest $request, UserService $users)
    {
        $target = User::findOrFail($request->validated('id'));
        $users->save($request->validated(), $request->user(), $target);
        return $this->success($request, 'Usuario actualizado.');
    }

    public function toggle(Request $request, UserService $users)
    {
        $data = $request->validate(['id' => ['required', 'integer', 'exists:usuarios,id']]);
        $users->toggle(User::findOrFail($data['id']), $request->user());
        return $this->success($request, 'Estado actualizado.');
    }

    public function destroy(Request $request, UserService $users)
    {
        $data = $request->validate(['id' => ['required', 'integer', 'exists:usuarios,id']]);
        $users->delete(User::findOrFail($data['id']), $request->user());
        return $this->success($request, 'Usuario eliminado.');
    }

    private function success(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }
}
