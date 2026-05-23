export default function registerPlayback(_Alpine) {
    window.playbackDvr = (streamUrl, webrtcProxyUrl, stopUrl, clipStoreUrl, cameraId, csrfToken) => ({
        selectedDatetime: '',
        duration: 60,
        loading: false,
        playing: false,
        playingLabel: '',
        error: '',
        streamName: null,
        pc: null,

        // gravação ao vivo
        recording: false,
        recordingStart: null,   // timestamp DVR (ms) quando apertou gravar
        recordingElapsed: 0,    // segundos decorridos
        recordingTimer: null,
        clipSubmitting: false,
        clipSuccess: '',
        clipError: '',

        selectTime(datetime) {
            this.selectedDatetime = datetime;
            this.loadPlayback();
        },

        async loadPlayback() {
            if (!this.selectedDatetime) return;
            this.loading = true;
            this.error   = '';

            await this.stopPlayback();

            try {
                const resp = await fetch(streamUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ datetime: this.selectedDatetime, duration: this.duration }),
                });

                if (!resp.ok) {
                    const data = await resp.json().catch(() => ({}));
                    throw new Error(data.error || 'Erro ao iniciar stream');
                }

                const data = await resp.json();
                this.streamName  = data.stream_name;
                this.playingLabel = new Date(this.selectedDatetime).toLocaleString('pt-BR');
                this.playing     = true;

                this.$nextTick(() => this.connectWebRTC(data.webrtc_url));

            } catch (e) {
                this.error = e.message || 'Não foi possível conectar ao DVR.';
            } finally {
                this.loading = false;
            }
        },

        async connectWebRTC(apiUrl) {
            const video  = this.$refs.video;
            const status = this.$refs.status;

            try {
                this.pc = new RTCPeerConnection({ iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] });

                this.pc.ontrack = (e) => {
                    if (video.srcObject !== e.streams[0]) video.srcObject = e.streams[0];
                };

                this.pc.oniceconnectionstatechange = () => {
                    if (['disconnected', 'failed', 'closed'].includes(this.pc.iceConnectionState)) {
                        if (status) { status.innerHTML = '<span class="text-red-400">Conexão encerrada.</span>'; status.classList.remove('hidden'); }
                    }
                };

                ['video', 'audio'].forEach(kind =>
                    this.pc.addTransceiver(kind, { direction: 'recvonly' })
                );

                const offer = await this.pc.createOffer();
                await this.pc.setLocalDescription(offer);

                await new Promise(resolve => {
                    if (this.pc.iceGatheringState === 'complete') { resolve(); return; }
                    this.pc.addEventListener('icegatheringstatechange', function h() {
                        if (this.iceGatheringState === 'complete') {
                            this.removeEventListener('icegatheringstatechange', h);
                            resolve();
                        }
                    });
                    setTimeout(resolve, 2000);
                });

                const res = await fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/sdp',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: this.pc.localDescription.sdp,
                });

                if (!res.ok) throw new Error('go2rtc retornou ' + res.status);

                await this.pc.setRemoteDescription({ type: 'answer', sdp: await res.text() });
                video.onloadedmetadata = () => { if (status) status.classList.add('hidden'); };

            } catch (err) {
                this.error = `Falha WebRTC: ${err.message}`;
                if (status) status.innerHTML = `<span class="text-red-400">Falha: ${err.message}</span>`;
            }
        },

        // Retorna o timestamp DVR atual (Date) baseado no horário inicial + currentTime do vídeo
        currentDvrTime() {
            if (!this.selectedDatetime) return null;
            const base    = new Date(this.selectedDatetime).getTime();
            const elapsed = Math.floor(this.$refs.video?.currentTime ?? 0) * 1000;
            return new Date(base + elapsed);
        },

        startRecording() {
            this.recordingStart   = this.currentDvrTime();
            this.recording        = true;
            this.recordingElapsed = 0;
            this.clipSuccess      = '';
            this.clipError        = '';
            this.recordingTimer   = setInterval(() => { this.recordingElapsed++; }, 1000);
        },

        async stopRecording() {
            clearInterval(this.recordingTimer);
            this.recording = false;

            const end      = this.currentDvrTime();
            const duration = Math.round((end - this.recordingStart) / 1000);

            if (duration < 5) {
                this.clipError = 'Gravação muito curta (mínimo 5 segundos).';
                return;
            }

            if (duration > 600) {
                this.clipError = 'Máximo de 10 minutos por clipe.';
                return;
            }

            this.clipSubmitting = true;
            this.clipError      = '';

            // formata para datetime-local (YYYY-MM-DDTHH:mm:ss)
            const pad = n => String(n).padStart(2, '0');
            const d   = this.recordingStart;
            const startedAt = `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;

            try {
                const resp = await fetch(clipStoreUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        camera_id:  cameraId,
                        started_at: startedAt,
                        duration:   duration,
                        title:      null,
                    }),
                });

                if (!resp.ok) {
                    const data = await resp.json().catch(() => ({}));
                    throw new Error(data.message || data.error || 'Erro ao criar clipe.');
                }

                this.clipSuccess = `Clipe de ${duration}s criado! Processando em segundo plano.`;
            } catch (e) {
                this.clipError = e.message;
            } finally {
                this.clipSubmitting = false;
                this.recordingStart = null;
            }
        },

        get recordingLabel() {
            const m = Math.floor(this.recordingElapsed / 60);
            const s = this.recordingElapsed % 60;
            return m > 0
                ? `${m}:${String(s).padStart(2,'0')}`
                : `${s}s`;
        },

        async stopPlayback() {
            if (this.pc) { this.pc.close(); this.pc = null; }
            if (this.$refs.video) { this.$refs.video.srcObject = null; }
            this.playing = false;
            if (this.recording) { clearInterval(this.recordingTimer); this.recording = false; }

            if (this.streamName) {
                await fetch(stopUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ stream_name: this.streamName }),
                }).catch(() => {});
                this.streamName = null;
            }
        },
    });
}
