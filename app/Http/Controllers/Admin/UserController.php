<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::withCount('cameras')->latest()->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $assigned = $user->cameras()->pluck('cameras.id')->toArray();
        $cameras  = Camera::orderBy('name')->get();
        return view('admin.users.show', compact('user', 'cameras', 'assigned'));
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'Usuário removido.');
    }

    public function grantAccess(Request $request, User $user, Camera $camera)
    {
        $expiresAt = $request->expires_at ? \Carbon\Carbon::parse($request->expires_at)->endOfDay() : null;

        $user->cameras()->syncWithoutDetaching([
            $camera->id => [
                'granted_at' => now(),
                'expires_at' => $expiresAt,
            ]
        ]);

        return back()->with('success', "Acesso à câmera \"{$camera->name}\" concedido.");
    }

    public function revokeAccess(User $user, Camera $camera)
    {
        $user->cameras()->detach($camera->id);
        return back()->with('success', "Acesso à câmera \"{$camera->name}\" revogado.");
    }
}
