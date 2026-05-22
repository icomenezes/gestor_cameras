export default function registerPlayback(_Alpine) {
    window.playbackDvr = (streamUrl, webrtcProxyUrl, stopUrl) => ({
        selectedDatetime: '',
        duration: 60,
        loading: false,
        playing: false,
        playingLabel: '',
        error: '',
        streamName: null,
        pc: null,

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

        async stopPlayback() {
            if (this.pc) { this.pc.close(); this.pc = null; }
            if (this.$refs.video) { this.$refs.video.srcObject = null; }
            this.playing = false;

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
