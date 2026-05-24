<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
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

        $user->subscriptions()->create([
            'plan'       => $plan,
            'status'     => 'active',
            'starts_at'  => $startsAt,
            'expires_at' => $expiresAt,
            'granted_by' => auth()->id(),
            'notes'      => $request->notes,
        ]);

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

        $user->subscriptions()->create([
            'plan'       => $plan,
            'status'     => 'active',
            'starts_at'  => $startsAt,
            'expires_at' => $expiresAt,
            'granted_by' => auth()->id(),
            'notes'      => 'Renovação',
        ]);

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
