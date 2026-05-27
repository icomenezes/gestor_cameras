<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AcessoCamerasConcedido;
use App\Mail\BoasVindas;
use App\Models\Camera;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    public function __construct(private NotificationService $notify) {}

    public function index()
    {
        $users = User::where('role', 'client')
            ->withCount('cameras')
            ->with('activeSubscription', 'activeSession')
            ->latest()
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $assigned     = $user->cameras()->pluck('cameras.id')->toArray();
        $cameras      = Camera::orderBy('name')->get();
        $subscription = $user->activeSubscription;
        $subscriptions = $user->subscriptions()->with('grantedBy')->limit(10)->get();
        $recentLogs   = $user->accessLogs()->with('camera')->limit(20)->get();

        return view('admin.users.show', compact(
            'user', 'cameras', 'assigned',
            'subscription', 'subscriptions', 'recentLogs'
        ));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'       => ['required', 'confirmed', Rules\Password::min(8)],
            'role'           => ['required', 'in:admin,client'],
            'clips_quota_mb' => ['nullable', 'integer', 'in:100,300,500,800'],
            'whatsapp'       => ['nullable', 'string', 'max:20'],
        ]);

        $user = User::create([
            'name'           => $request->name,
            'email'          => $request->email,
            'password'       => Hash::make($request->password),
            'role'           => $request->role,
            'clips_quota_mb' => $request->input('clips_quota_mb', 300),
            'whatsapp'       => $request->whatsapp ? preg_replace('/\D/', '', $request->whatsapp) : null,
        ]);

        Mail::to($user->email)->queue(new BoasVindas($user));
        $this->notify->boasVindas($user);

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Usuário criado com sucesso.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'Usuário removido.');
    }

    public function updateQuota(Request $request, User $user)
    {
        $request->validate([
            'clips_quota_mb' => ['required', 'integer', 'in:100,300,500,800'],
        ]);

        $user->update(['clips_quota_mb' => $request->clips_quota_mb]);

        return back()->with('success', "Quota de clipes alterada para {$request->clips_quota_mb} MB.");
    }

    public function grantAccess(Request $request, User $user, Camera $camera)
    {
        $expiresAt = $request->expires_at
            ? \Carbon\Carbon::parse($request->expires_at)->endOfDay()
            : null;

        $user->cameras()->syncWithoutDetaching([
            $camera->id => [
                'granted_at' => now(),
                'expires_at' => $expiresAt,
            ]
        ]);

        Mail::to($user->email)->queue(new AcessoCamerasConcedido($user, $camera));

        return back()->with('success', "Acesso à câmera \"{$camera->name}\" concedido.");
    }

    public function revokeAccess(User $user, Camera $camera)
    {
        $user->cameras()->detach($camera->id);
        return back()->with('success', "Acesso à câmera \"{$camera->name}\" revogado.");
    }
}
