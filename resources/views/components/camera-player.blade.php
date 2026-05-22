@props(['url', 'autoplay' => true])

@php
    $isWebRTC = str_contains($url, '/api/webrtc?src=');
    $isHLS    = str_ends_with($url, '.m3u8') || str_contains($url, '/api/stream.m3u8');
    $isMp4    = preg_match('/\.(mp4|webm|ogg)(\?|$)/i', $url);
    $isMjpeg  = preg_match('/\.(jpg|mjpg|mjpeg)(\?|$)/i', $url) || str_contains($url, 'mjpeg');
    $isRtsp   = str_starts_with($url, 'rtsp://');
    $playerId = 'player-' . uniqid();
@endphp

<div class="relative w-full h-full bg-black flex items-center justify-center" id="{{ $playerId }}-wrap">

    {{-- WebRTC (go2rtc via proxy Laravel) --}}
    @if($isWebRTC)
        @php
            // extrai o parâmetro src= da URL e monta a rota proxy (mesma origem, sem CORS)
            preg_match('/[?&]src=([^&]+)/', $url, $m);
            $proxyUrl = route('go2rtc.webrtc') . '?src=' . ($m[1] ?? '');
        @endphp
        <video id="{{ $playerId }}" class="w-full h-full" autoplay muted playsinline controls></video>
        <div id="{{ $playerId }}-status" class="absolute inset-0 flex flex-col items-center justify-center bg-black/80 text-gray-400 text-sm gap-2">
            <svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
            Conectando via WebRTC...
        </div>
        <script>
        (function() {
            const apiUrl = @json($proxyUrl);
            const video  = document.getElementById(@json($playerId));
            const status = document.getElementById(@json($playerId) + '-status');

            async function connect() {
                try {
                    const pc = new RTCPeerConnection({
                        iceServers: [{ urls: 'stun:stun.l.google.com:19302' }]
                    });

                    pc.ontrack = (e) => {
                        if (video.srcObject !== e.streams[0]) {
                            video.srcObject = e.streams[0];
                        }
                    };

                    pc.oniceconnectionstatechange = () => {
                        if (['disconnected','failed','closed'].includes(pc.iceConnectionState)) {
                            status.innerHTML = '<span class="text-red-400">Conexão perdida. Reconectando...</span>';
                            status.classList.remove('hidden');
                            setTimeout(connect, 3000);
                        }
                    };

                    ['video','audio'].forEach(kind =>
                        pc.addTransceiver(kind, { direction: 'recvonly' })
                    );

                    const offer = await pc.createOffer();
                    await pc.setLocalDescription(offer);

                    // aguarda ICE gathering
                    await new Promise(resolve => {
                        if (pc.iceGatheringState === 'complete') { resolve(); return; }
                        pc.addEventListener('icegatheringstatechange', function handler() {
                            if (pc.iceGatheringState === 'complete') {
                                pc.removeEventListener('icegatheringstatechange', handler);
                                resolve();
                            }
                        });
                        setTimeout(resolve, 2000);
                    });

                    const res = await fetch(apiUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/sdp',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                        body: pc.localDescription.sdp,
                    });

                    if (!res.ok) throw new Error('go2rtc retornou ' + res.status);

                    const sdp = await res.text();
                    await pc.setRemoteDescription({ type: 'answer', sdp });

                    video.onloadedmetadata = () => status.classList.add('hidden');

                } catch (err) {
                    console.error('WebRTC error:', err);
                    status.innerHTML = `
                        <svg class="w-8 h-8 text-red-500 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-red-400 font-medium">Falha no WebRTC</span>
                        <span class="text-xs text-gray-500 mt-1">Verifique se o go2rtc está rodando</span>
                        <button onclick="location.reload()" class="mt-3 px-3 py-1.5 bg-gray-700 hover:bg-gray-600 rounded-md text-xs text-white transition-colors">
                            Tentar novamente
                        </button>`;
                }
            }

            connect();
        })();
        </script>

    {{-- HLS --}}
    @elseif($isHLS)
        <video id="{{ $playerId }}" class="w-full h-full" {{ $autoplay ? 'autoplay' : '' }} muted playsinline controls></video>
        <script>
        (function() {
            const url   = @json($url);
            const video = document.getElementById(@json($playerId));

            function loadHLS() {
                if (typeof Hls !== 'undefined' && Hls.isSupported()) {
                    const hls = new Hls({ liveSyncDurationCount: 1, liveMaxLatencyDurationCount: 3 });
                    hls.loadSource(url);
                    hls.attachMedia(video);
                    hls.on(Hls.Events.ERROR, (e, data) => {
                        if (data.fatal) setTimeout(loadHLS, 5000);
                    });
                } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                    video.src = url;
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', loadHLS);
            } else {
                loadHLS();
            }
        })();
        </script>

    {{-- MP4 / WebM nativo --}}
    @elseif($isMp4)
        <video class="w-full h-full" {{ $autoplay ? 'autoplay' : '' }} muted playsinline controls>
            <source src="{{ $url }}">
        </video>

    {{-- MJPEG --}}
    @elseif($isMjpeg)
        <img src="{{ $url }}" class="w-full h-full object-contain" alt="Stream MJPEG">

    {{-- RTSP sem proxy --}}
    @elseif($isRtsp)
        <div class="text-center p-8 max-w-sm">
            <svg class="w-12 h-12 mx-auto mb-4 text-yellow-500/60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <p class="text-white font-medium mb-2">Stream RTSP</p>
            <p class="text-gray-400 text-xs leading-relaxed mb-4">
                Browsers não suportam RTSP direto. Use o <strong class="text-white">go2rtc</strong> para converter para WebRTC/HLS.
            </p>
            <div class="bg-gray-800 rounded-lg p-3 text-left text-xs font-mono text-gray-300 break-all mb-3">
                {{ $url }}
            </div>
            <p class="text-xs text-gray-500">
                Adicione ao <code class="bg-gray-800 px-1 rounded-lg">go2rtc.yaml</code>:<br>
                <code class="text-blue-400">streams: camera1: {{ $url }}</code>
            </p>
        </div>

    {{-- Iframe / YouTube / embed genérico --}}
    @else
        <iframe src="{{ $url }}" class="w-full h-full" allowfullscreen allow="autoplay"></iframe>
    @endif

</div>

{{-- hls.js lazy-load (só se precisar) --}}
@if($isHLS)
<script>
    if (typeof Hls === 'undefined') {
        const s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/hls.js@1/dist/hls.min.js';
        document.head.appendChild(s);
    }
</script>
@endif
