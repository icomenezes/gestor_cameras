@extends('admin.layout')

@section('title', 'Analytics')

@section('content')
<div class="space-y-6">

    {{-- KPIs --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach([
            ['label' => 'Clientes ativos', 'value' => $totals['clients'], 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
            ['label' => 'Assinaturas ativas', 'value' => $totals['active_subs'], 'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z'],
            ['label' => 'Câmeras ativas', 'value' => $totals['cameras'], 'icon' => 'M15 10l4.553-2.069A1 1 0 0121 8.871v6.258a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'],
            ['label' => 'Streams este mês', 'value' => $totals['streams_month'], 'icon' => 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ] as $kpi)
            <div class="bg-gray-900 border border-gray-800 rounded-lg p-5 flex items-center gap-4">
                <div class="w-11 h-11 bg-blue-900/40 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $kpi['icon'] }}"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ number_format($kpi['value']) }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $kpi['label'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid lg:grid-cols-2 gap-6">

        {{-- Gráfico de logins (últimos 30 dias) --}}
        <div class="bg-gray-900 border border-gray-800 rounded-lg p-5">
            <h3 class="text-sm font-semibold text-gray-300 mb-4">Logins por dia — últimos 30 dias</h3>
            <div class="flex items-end gap-0.5 h-32" id="login-chart">
                @php $max = max($days30->values()->max(), 1); @endphp
                @foreach($days30 as $day => $cnt)
                    <div class="flex-1 flex flex-col items-center gap-1 group" title="{{ \Carbon\Carbon::parse($day)->format('d/m') }}: {{ $cnt }}">
                        <div class="w-full rounded-sm bg-blue-600 hover:bg-blue-400 transition-colors"
                             style="height: {{ round($cnt / $max * 100) }}%; min-height: 2px;"></div>
                    </div>
                @endforeach
            </div>
            <div class="flex justify-between text-xs text-gray-600 mt-1">
                <span>{{ $days30->keys()->first() ? \Carbon\Carbon::parse($days30->keys()->first())->format('d/m') : '' }}</span>
                <span>Hoje</span>
            </div>
        </div>

        {{-- Ranking câmeras mais assistidas --}}
        <div class="bg-gray-900 border border-gray-800 rounded-lg p-5">
            <h3 class="text-sm font-semibold text-gray-300 mb-4">Câmeras mais assistidas — mês atual</h3>
            @if($streamsByCamera->isEmpty())
                <p class="text-sm text-gray-500 py-8 text-center">Sem dados de streaming este mês.</p>
            @else
                <div class="space-y-3">
                    @php $maxStreams = $streamsByCamera->first()->starts; @endphp
                    @foreach($streamsByCamera as $row)
                        <div>
                            <div class="flex justify-between text-xs text-gray-300 mb-1">
                                <span>{{ $row->camera?->name ?? 'Câmera #'.$row->camera_id }}</span>
                                <span class="font-mono text-gray-400">{{ $row->starts }} streams</span>
                            </div>
                            <div class="h-1.5 bg-gray-800 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-600 rounded-full"
                                     style="width: {{ round($row->starts / $maxStreams * 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

    <div class="grid lg:grid-cols-3 gap-6">

        {{-- Heatmap por hora/dia da semana --}}
        <div class="lg:col-span-2 bg-gray-900 border border-gray-800 rounded-lg p-5">
            <h3 class="text-sm font-semibold text-gray-300 mb-4">Horário de pico — últimos 30 dias</h3>
            @php
                $days   = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
                $maxHeat = max(1, $heatmap->max('cnt'));
            @endphp
            <div class="overflow-x-auto">
                <table class="text-xs text-gray-500 border-collapse">
                    <thead>
                        <tr>
                            <th class="pr-2 text-right w-8"></th>
                            @for($h = 0; $h < 24; $h++)
                                <th class="w-5 text-center font-normal">{{ $h % 3 === 0 ? $h.'h' : '' }}</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($days as $di => $dname)
                            <tr>
                                <td class="pr-2 text-right py-0.5">{{ $dname }}</td>
                                @for($h = 0; $h < 24; $h++)
                                    @php
                                        $key   = ($di + 1) . '_' . $h;
                                        $cnt   = $heatmap->get($key)?->cnt ?? 0;
                                        $alpha = $cnt > 0 ? max(0.1, $cnt / $maxHeat) : 0;
                                    @endphp
                                    <td class="w-5 h-5 rounded-sm"
                                        title="{{ $dname }} {{ $h }}h: {{ $cnt }}"
                                        style="background: rgba(59,130,246,{{ number_format($alpha, 2) }})">
                                    </td>
                                @endfor
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Métricas adicionais --}}
        <div class="space-y-4">
            <div class="bg-gray-900 border border-gray-800 rounded-lg p-5">
                <p class="text-xs text-gray-400 mb-1">Tempo médio de sessão</p>
                <p class="text-3xl font-bold text-white">
                    {{ $avgSession?->avg_min ? round($avgSession->avg_min) : '—' }}
                    @if($avgSession?->avg_min)<span class="text-lg font-normal text-gray-400">min</span>@endif
                </p>
                <p class="text-xs text-gray-500 mt-1">Últimos 30 dias ({{ $avgSession?->cnt ?? 0 }} sessões)</p>
            </div>

            <div class="bg-gray-900 border border-gray-800 rounded-lg p-5">
                <p class="text-xs text-gray-400 mb-1">Taxa de renovação</p>
                <p class="text-3xl font-bold text-white">{{ $renewalRate }}<span class="text-lg font-normal text-gray-400">%</span></p>
                <p class="text-xs text-gray-500 mt-1">Clientes que renovaram após vencimento</p>
                <div class="h-1.5 bg-gray-800 rounded-full mt-3">
                    <div class="h-full bg-green-500 rounded-full" style="width: {{ $renewalRate }}%"></div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
