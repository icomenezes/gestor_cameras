<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Models\Camera;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        $now    = now();
        $month  = $now->copy()->startOfMonth();
        $prev   = $now->copy()->subMonth()->startOfMonth();

        // ── Sessões por câmera (mês atual) ─────────────────────────────────
        $streamsByCamera = AccessLog::select('camera_id', DB::raw('count(*) as starts'))
            ->where('event', 'stream_start')
            ->where('created_at', '>=', $month)
            ->whereNotNull('camera_id')
            ->groupBy('camera_id')
            ->with('camera')
            ->orderByDesc('starts')
            ->limit(10)
            ->get();

        // ── Heatmap: acessos por hora/dia da semana (últimos 30 dias) ───────
        $heatmap = AccessLog::select(
                DB::raw('DAYOFWEEK(created_at) as dow'),
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('count(*) as cnt')
            )
            ->where('event', 'stream_start')
            ->where('created_at', '>=', $now->copy()->subDays(30))
            ->groupBy('dow', 'hour')
            ->get()
            ->keyBy(fn ($r) => "{$r->dow}_{$r->hour}");

        // ── Tempo médio de sessão por usuário (últimos 30 dias) ─────────────
        // Estima duração: stream_start → stream_stop por user+camera no mesmo dia
        $avgSession = AccessLog::select(
                DB::raw('AVG(duration_minutes) as avg_min'),
                DB::raw('count(*) as cnt')
            )
            ->fromSub(
                AccessLog::select(
                    'user_id',
                    DB::raw('TIMESTAMPDIFF(MINUTE, MIN(created_at), MAX(created_at)) as duration_minutes')
                )
                ->whereIn('event', ['stream_start', 'stream_stop'])
                ->where('created_at', '>=', $now->copy()->subDays(30))
                ->groupBy('user_id', DB::raw('DATE(created_at)'), 'camera_id')
                ->having('duration_minutes', '>', 0),
                'sessions'
            )
            ->first();

        // ── Taxa de renovação de assinaturas ────────────────────────────────
        $totalExpired  = Subscription::where('status', 'expired')->count();
        $renewed       = Subscription::whereIn('user_id',
            Subscription::select('user_id')->where('status', 'expired')->groupBy('user_id')
        )->where('status', 'active')->distinct('user_id')->count('user_id');
        $renewalRate   = $totalExpired > 0 ? round($renewed / $totalExpired * 100) : 0;

        // ── Logins por dia (últimos 30 dias) para gráfico ───────────────────
        $loginsByDay = AccessLog::select(
                DB::raw('DATE(created_at) as day'),
                DB::raw('count(*) as cnt')
            )
            ->where('event', 'login')
            ->where('created_at', '>=', $now->copy()->subDays(29))
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('cnt', 'day');

        // Preenche dias sem acesso com 0
        $days30 = collect();
        for ($i = 29; $i >= 0; $i--) {
            $d = $now->copy()->subDays($i)->format('Y-m-d');
            $days30->put($d, $loginsByDay->get($d, 0));
        }

        // ── Totais gerais ────────────────────────────────────────────────────
        $totals = [
            'clients'      => User::where('role', 'client')->count(),
            'active_subs'  => Subscription::active()->count(),
            'cameras'      => Camera::where('is_active', true)->count(),
            'streams_month'=> AccessLog::where('event', 'stream_start')->where('created_at', '>=', $month)->count(),
        ];

        return view('admin.analytics.index', compact(
            'streamsByCamera', 'heatmap', 'avgSession',
            'renewalRate', 'days30', 'totals'
        ));
    }
}
