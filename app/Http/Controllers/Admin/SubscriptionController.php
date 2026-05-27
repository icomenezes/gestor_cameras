<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AssinaturaAtivada;
use App\Models\Subscription;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SubscriptionController extends Controller
{
    public function __construct(private NotificationService $notify) {}

    public function store(Request $request, User $user)
    {
        $request->validate([
            'plan'       => ['required', 'in:monthly,quarterly,annual'],
            'starts_at'  => ['nullable', 'date'],
            'notes'      => ['nullable', 'string', 'max:500'],
        ]);

        $plan      = $request->plan;
        $startsAt  = $request->starts_at ? now()->parse($request->starts_at) : now();
        $expiresAt = $startsAt->copy()->addDays(Subscription::planDays($plan));

        // Suspende assinaturas ativas anteriores
        $user->subscriptions()->where('status', 'active')->update(['status' => 'suspended']);

        $subscription = $user->subscriptions()->create([
            'plan'       => $plan,
            'status'     => 'active',
            'starts_at'  => $startsAt,
            'expires_at' => $expiresAt,
            'granted_by' => auth()->id(),
            'notes'      => $request->notes,
        ]);

        Mail::to($user->email)->queue(new AssinaturaAtivada($user, $subscription));
        $this->notify->assinaturaAtivada($user, Subscription::planLabel($plan), $expiresAt->format('d/m/Y'));

        return back()->with('success', 'Assinatura ' . Subscription::planLabel($plan) . ' criada até ' . $expiresAt->format('d/m/Y') . '.');
    }

    public function renew(Request $request, User $user)
    {
        $request->validate([
            'plan' => ['required', 'in:monthly,quarterly,annual'],
        ]);

        $plan     = $request->plan;
        $current  = $user->activeSubscription;
        $startsAt = $current && $current->expires_at->isFuture()
            ? $current->expires_at
            : now();

        $expiresAt = $startsAt->copy()->addDays(Subscription::planDays($plan));

        $subscription = $user->subscriptions()->create([
            'plan'       => $plan,
            'status'     => 'active',
            'starts_at'  => $startsAt,
            'expires_at' => $expiresAt,
            'granted_by' => auth()->id(),
            'notes'      => 'Renovação',
        ]);

        Mail::to($user->email)->queue(new AssinaturaAtivada($user, $subscription));
        $this->notify->assinaturaAtivada($user, Subscription::planLabel($plan), $expiresAt->format('d/m/Y'));

        return back()->with('success', 'Assinatura renovada até ' . $expiresAt->format('d/m/Y') . '.');
    }

    public function suspend(User $user)
    {
        $user->subscriptions()->where('status', 'active')->update(['status' => 'suspended']);
        return back()->with('success', 'Assinatura suspensa. Aluno perde acesso imediatamente.');
    }

    public function index()
    {
        $expiringSoon = Subscription::with('user')
            ->active()
            ->where('expires_at', '<=', now()->addDays(7))
            ->orderBy('expires_at')
            ->get();

        $expired = Subscription::with('user')
            ->where('status', 'active')
            ->where('expires_at', '<', now())
            ->orderByDesc('expires_at')
            ->limit(50)
            ->get();

        $recent = Subscription::with(['user', 'grantedBy'])
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        return view('admin.subscriptions.index', compact('expiringSoon', 'expired', 'recent'));
    }
}
