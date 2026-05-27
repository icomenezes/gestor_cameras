@extends('client.layout')

@section('title', 'Mosaico')

@section('content')
<div x-data="mosaic()" x-init="init()" class="space-y-4">

    {{-- Barra de controles --}}
    <div class="flex flex-wrap items-center gap-3 bg-gray-900 border border-gray-800 rounded-lg px-4 py-3">
        <span class="text-sm font-medium text-gray-300">Grade:</span>
        <div class="flex gap-1">
            @foreach(['2x2' => '2×2', '3x3' => '3×3', '1+5' => '1+5'] as $val => $label)
                <button @click="setGrid('{{ $val }}')"
                        :class="grid === '{{ $val }}' ? 'bg-blue-600 text-white' : 'bg-gray-800 text-gray-400 hover:text-white'"
                        class="px-3 py-1.5 rounded text-sm font-medium transition-colors">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <span class="text-sm font-medium text-gray-300 ml-4">Rotação:</span>
        <select x-model="rotationSecs" @change="startRotation()"
                class="bg-gray-800 border border-gray-700 rounded px-2 py-1.5 text-sm text-white focus:outline-none">
            <option value="">Desativada</option>
            <option value="5">5 s</option>
            <option value="10">10 s</option>
            <option value="15">15 s</option>
            <option value="30">30 s</option>
            <option value="60">60 s</option>
        </select>

        <button @click="save()" class="ml-auto px-4 py-1.5 bg-blue-600 hover:bg-blue-500 text-white text-sm rounded transition-colors">
            Salvar layout
        </button>

        <a href="{{ route('dashboard') }}" class="text-sm text-gray-400 hover:text-white transition-colors">← Voltar</a>
    </div>

    {{-- Grade de câmeras --}}
    <div :class="gridClass" class="gap-2">
        <template x-for="(slot, idx) in slots" :key="idx">
            <div class="relative bg-gray-900 border border-gray-800 rounded-lg overflow-hidden aspect-video flex flex-col"
                 :class="idx === 0 && grid === '1+5' ? 'col-span-2 row-span-2' : ''">

                {{-- Seletor de câmera --}}
                <div class="absolute top-2 left-2 z-10">
                    <select x-model="slotCameras[idx]" @change="assignCamera(idx, $event.target.value)"
                            class="bg-black/60 border border-gray-700 rounded text-xs text-white px-2 py-1 focus:outline-none max-w-[150px] truncate">
                        <option value="">— Vazio —</option>
                        @foreach($cameras as $cam)
                            <option value="{{ $cam->id }}">{{ $cam->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Player --}}
                <div class="flex-1" x-show="slotCameras[idx]">
                    <template x-if="slotCameras[idx]">
                        <div class="w-full h-full" :id="'player-' + idx"
                             x-init="$nextTick(() => mountPlayer(idx))">
                        </div>
                    </template>
                </div>

                {{-- Placeholder --}}
                <div class="flex-1 flex items-center justify-center" x-show="!slotCameras[idx]">
                    <svg class="w-12 h-12 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M15 10l4.553-2.069A1 1 0 0121 8.871v6.258a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
        </template>
    </div>

    <p x-show="saved" x-transition class="text-center text-sm text-green-400">Layout salvo!</p>
</div>

@push('scripts')
<script>
const GO2RTC_URL = '{{ rtrim(config("cameras.go2rtc_public_url", config("cameras.go2rtc_url", "http://localhost:1984")), "/") }}';
const CAMERA_KEYS = @json($cameras->pluck('id')->mapWithKeys(fn($id) => [$id => 'cam'.$id]));
const SAVED_LAYOUT = {
    grid:     '{{ $layout->grid }}',
    cameras:  @json($layout->camera_ids ?? []),
    rotation: {{ $layout->rotation_seconds ?? 'null' }},
};
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

function mosaic() {
    return {
        grid: SAVED_LAYOUT.grid,
        slots: [],
        slotCameras: [],
        rotationSecs: SAVED_LAYOUT.rotation ? String(SAVED_LAYOUT.rotation) : '',
        rotationTimer: null,
        rotationIdx: 0,
        saved: false,
        players: {},

        get gridClass() {
            const map = { '2x2': 'grid grid-cols-2', '3x3': 'grid grid-cols-3', '1+5': 'grid grid-cols-3' };
            return map[this.grid] || 'grid grid-cols-2';
        },

        init() {
            this.rebuildSlots();
            // Carrega câmeras salvas
            SAVED_LAYOUT.cameras.forEach((camId, idx) => {
                if (idx < this.slotCameras.length) this.slotCameras[idx] = camId ? String(camId) : '';
            });
            this.$nextTick(() => {
                this.slotCameras.forEach((camId, idx) => { if (camId) this.mountPlayer(idx); });
                if (this.rotationSecs) this.startRotation();
            });
        },

        rebuildSlots() {
            const counts = { '2x2': 4, '3x3': 9, '1+5': 6 };
            const n = counts[this.grid] || 4;
            this.slots = Array(n).fill(null);
            // Preserva atribuições existentes
            const prev = [...this.slotCameras];
            this.slotCameras = Array(n).fill('');
            prev.forEach((v, i) => { if (i < n) this.slotCameras[i] = v; });
        },

        setGrid(g) {
            this.destroyAllPlayers();
            this.grid = g;
            this.rebuildSlots();
            this.$nextTick(() => {
                this.slotCameras.forEach((camId, idx) => { if (camId) this.mountPlayer(idx); });
            });
        },

        assignCamera(idx, camId) {
            this.destroyPlayer(idx);
            this.slotCameras[idx] = camId;
            if (camId) this.$nextTick(() => this.mountPlayer(idx));
        },

        mountPlayer(idx) {
            const camId = this.slotCameras[idx];
            if (!camId || !CAMERA_KEYS[camId]) return;
            const container = document.getElementById('player-' + idx);
            if (!container) return;

            const streamKey = CAMERA_KEYS[camId];
            const video = document.createElement('video');
            video.autoplay = true;
            video.muted    = true;
            video.playsInline = true;
            video.style.cssText = 'width:100%;height:100%;object-fit:cover;background:#000';

            container.innerHTML = '';
            container.appendChild(video);

            const pc = new RTCPeerConnection({ iceServers: [] });
            pc.addTransceiver('video', { direction: 'recvonly' });
            pc.addTransceiver('audio', { direction: 'recvonly' });

            pc.ontrack = (e) => { video.srcObject = e.streams[0]; };

            pc.createOffer().then(offer => pc.setLocalDescription(offer)).then(() => {
                return fetch('/go2rtc/webrtc', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': CSRF },
                    body: 'offer=' + encodeURIComponent(pc.localDescription.sdp) + '&src=' + streamKey,
                });
            }).then(r => r.text()).then(sdp => {
                pc.setRemoteDescription({ type: 'answer', sdp });
            }).catch(() => {});

            this.players[idx] = pc;
        },

        destroyPlayer(idx) {
            if (this.players[idx]) {
                this.players[idx].close();
                delete this.players[idx];
            }
            const el = document.getElementById('player-' + idx);
            if (el) el.innerHTML = '';
        },

        destroyAllPlayers() {
            Object.keys(this.players).forEach(idx => this.destroyPlayer(Number(idx)));
        },

        startRotation() {
            clearInterval(this.rotationTimer);
            if (!this.rotationSecs) return;

            const camIds = {{ $cameras->pluck('id') }};
            if (camIds.length <= this.slots.length) return;

            this.rotationTimer = setInterval(() => {
                const slotsCount = this.slots.length;
                const offset = this.rotationIdx % (camIds.length - slotsCount + 1);
                camIds.slice(offset, offset + slotsCount).forEach((id, i) => {
                    if (this.slotCameras[i] !== String(id)) {
                        this.assignCamera(i, String(id));
                    }
                });
                this.rotationIdx++;
            }, Number(this.rotationSecs) * 1000);
        },

        save() {
            fetch('{{ route("mosaic.save") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({
                    grid: this.grid,
                    camera_ids: this.slotCameras.map(v => v ? Number(v) : null),
                    rotation_seconds: this.rotationSecs || null,
                }),
            }).then(r => r.json()).then(() => {
                this.saved = true;
                setTimeout(() => { this.saved = false; }, 3000);
            });
        },
    };
}
</script>
@endpush
@endsection
