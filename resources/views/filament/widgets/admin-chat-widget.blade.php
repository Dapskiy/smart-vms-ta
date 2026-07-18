@php
    $adminName = auth()->user()?->name ?? 'Admin';
    $chatUrl   = route('admin.ai.chat');
@endphp

{{-- ══════════════════════════════════════════════════════════
     VISITA Admin AI Assistant — Floating Chat Widget
     Styling: Tailwind CSS (bundled with Filament v3)
     ══════════════════════════════════════════════════════════ --}}
<div
    id="aai-root"
    class="fixed bottom-7 right-7 z-[99999] flex flex-col items-end gap-3 font-sans"
    style="font-family:'Poppins','Inter',ui-sans-serif,sans-serif"
>

    {{-- ── Chat Panel ───────────────────────────────────────────── --}}
    <div
        id="aai-panel"
        class="hidden w-[92vw] md:w-[45vw] max-w-[600px] h-[85vh] max-h-[850px] bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 flex flex-col overflow-hidden"
        style="animation:aaiSlideIn .22s cubic-bezier(.4,0,.2,1)"
        role="dialog"
        aria-label="VISITA AI Assistant"
    >
        {{-- Header --}}
        <div class="relative flex flex-col items-center gap-2 px-4 pt-6 pb-5 bg-gradient-to-br from-indigo-600 to-violet-600 flex-shrink-0 text-center">
            {{-- Action buttons (absolute top-right) --}}
            <div class="absolute top-3 right-3 flex items-center gap-1.5">
                {{-- Speech Controls --}}
                <div id="aai-speech-controls" class="hidden items-center gap-1.5">
                    <button onclick="aaiUI.stopSpeech()" class="w-7 h-7 rounded-lg bg-white/15 hover:bg-white/30 flex items-center justify-center text-sm text-white transition-colors" title="Hentikan suara">🛑</button>
                    <button id="aai-pause-btn" onclick="aaiUI.pauseResumeSpeech()" class="w-7 h-7 rounded-lg bg-white/15 hover:bg-white/30 flex items-center justify-center text-sm text-white transition-colors" title="Jeda suara">⏸️</button>
                </div>
                {{-- TTS Toggle --}}
                <button id="aai-tts-btn" onclick="aaiUI.toggleTts()" title="Matikan/nyalakan suara" class="w-7 h-7 rounded-lg bg-white/15 hover:bg-white/30 flex items-center justify-center text-sm text-white transition-colors">🔊</button>
                {{-- Clear --}}
                <button onclick="aaiUI.clearHistory()" title="Hapus riwayat" class="w-7 h-7 rounded-lg bg-white/15 hover:bg-white/30 flex items-center justify-center text-sm text-white transition-colors">🗑️</button>
                {{-- Close --}}
                <button onclick="aaiUI.toggle()" title="Tutup" class="w-7 h-7 rounded-lg bg-white/15 hover:bg-white/30 flex items-center justify-center text-xs font-bold text-white transition-colors">✕</button>
            </div>

            <div class="w-[200px] h-[200px] rounded-full bg-white flex items-center justify-center overflow-hidden shadow-lg border-4 border-white/20">
                <img id="aai-header-avatar" src="{{ asset('assets/images/chatbot/avatar-idle.gif') }}" alt="AI" class="w-full h-full object-cover">
            </div>
            <div>
                <p class="text-white font-semibold text-[17px] leading-tight mb-1">VISITA AI Assistant</p>
                <p class="text-indigo-200 text-xs leading-tight">Data real-time · Powered by Gemini</p>
            </div>
        </div>

        {{-- Messages Area --}}
        <div
            id="aai-messages"
            class="flex-1 overflow-y-auto px-3.5 py-3 flex flex-col gap-2.5 scroll-smooth"
            style="min-height:200px"
        >
            {{-- Welcome state --}}
            <div id="aai-welcome" class="flex flex-col items-center text-center text-gray-500 dark:text-gray-400 py-4 gap-2">
                <span class="text-4xl">👋</span>
                <p class="text-[13px] leading-snug">
                    Halo, <strong class="text-gray-700 dark:text-gray-200">{{ $adminName }}</strong>!<br>
                    Tanya saya tentang kondisi kunjungan hari ini.
                </p>
                {{-- Suggestion chips --}}
                <div class="flex flex-wrap gap-1.5 justify-center mt-1">
                    <button onclick="aaiUI.suggest(this)" class="aai-chip">Siapa yang sedang check-in?</button>
                    <button onclick="aaiUI.suggest(this)" class="aai-chip">Statistik hari ini</button>
                    <button onclick="aaiUI.suggest(this)" class="aai-chip">Berapa tamu aktif?</button>
                    <button onclick="aaiUI.suggest(this)" class="aai-chip">Siapa yang sudah checkout?</button>
                </div>
            </div>
        </div>

        {{-- Speech Controls: muncul saat AI berbicara --}}
        <div
            id="aai-speech-controls"
            class="hidden items-center justify-center gap-2 px-3.5 py-1.5 border-t border-gray-100 dark:border-gray-700 bg-indigo-50 dark:bg-indigo-900/20 flex-shrink-0"
        >
            <button
                onclick="aaiUI.stopSpeech()"
                title="Hentikan suara"
                class="px-2.5 py-1 rounded-lg bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-300 text-[11px] font-semibold hover:bg-red-200 dark:hover:bg-red-800/50 transition-colors"
            >🛑 Stop</button>
            <button
                id="aai-pause-btn"
                onclick="aaiUI.pauseResume()"
                title="Jeda / Lanjutkan"
                class="px-2.5 py-1 rounded-lg bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-300 text-[11px] font-semibold hover:bg-indigo-200 dark:hover:bg-indigo-800/50 transition-colors"
            >⏸️ Jeda</button>
            <span class="text-[10px] text-gray-400 dark:text-gray-500 ml-1">AI sedang berbicara…</span>
        </div>

        {{-- Input Area --}}
        <div class="flex items-end gap-2 px-3.5 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 flex-shrink-0">
            <textarea
                id="aai-input"
                rows="1"
                placeholder="Tanya tentang data sistem..."
                class="flex-1 resize-none rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-100 text-[13.5px] px-3 py-2 outline-none focus:border-violet-500 dark:focus:border-violet-400 transition-colors max-h-24 overflow-y-auto leading-snug placeholder-gray-400"
                oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px'"
                onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();aaiUI.send();}"
            ></textarea>
            <button
                id="aai-mic-btn"
                onclick="aaiUI.startDictation()"
                title="Input Suara"
                class="w-9 h-9 flex-shrink-0 rounded-full bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 flex items-center justify-center text-base transition-all disabled:opacity-40"
            >🎙️</button>
            <button
                id="aai-send-btn"
                onclick="aaiUI.send()"
                title="Kirim"
                class="w-9 h-9 flex-shrink-0 rounded-full bg-gradient-to-br from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 disabled:opacity-40 disabled:cursor-not-allowed text-white flex items-center justify-center text-base transition-all hover:scale-105 active:scale-95 shadow-md"
            >➤</button>
        </div>
    </div>

    {{-- ── Floating Action Button ───────────────────────────────── --}}
    <button
        id="aai-fab"
        onclick="aaiUI.toggle()"
        title="VISITA AI Assistant"
        aria-label="Buka Admin AI Assistant"
        class="w-14 h-14 rounded-full bg-white hover:bg-gray-50 text-white shadow-xl hover:shadow-2xl flex items-center justify-center overflow-hidden transition-all hover:scale-110 active:scale-95 border-2 border-indigo-500/30"
        style="animation:aaiPulse 2.8s infinite"
    >
        <span id="aai-fab-icon" class="w-full h-full"><img src="{{ asset('assets/images/chatbot/avatar-idle.gif') }}" alt="AI" class="w-full h-full object-cover"></span>
    </button>

</div>

{{-- ── Suggestion Chip Styles (minimal, Tailwind-compatible) ── --}}
<style>
.aai-chip {
    @apply bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300
           border border-indigo-200 dark:border-indigo-700 rounded-full
           px-3 py-1 text-[11.5px] cursor-pointer
           hover:bg-indigo-100 dark:hover:bg-indigo-800/50
           transition-colors select-none;
    font-family: inherit;
}
@keyframes aaiSlideIn {
    from { opacity:0; transform:translateY(14px) scale(.97); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}
@keyframes aaiPulse {
    0%,100% { box-shadow: 0 4px 24px rgba(79,70,229,.5), 0 0 0 0 rgba(79,70,229,.35); }
    55%     { box-shadow: 0 4px 24px rgba(79,70,229,.5), 0 0 0 9px rgba(79,70,229,0); }
}
#aai-messages::-webkit-scrollbar      { width: 3px; }
#aai-messages::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
</style>

{{-- ── JavaScript ───────────────────────────────────────────── --}}
<script>
(function () {
    /* ── Config ─────────────────────────────────────────────── */
    const ENDPOINT   = '{{ $chatUrl }}';

    let isOpen    = false;
    let isLoading = false;
    let ttsOn     = true;

    /* ── Helpers ─────────────────────────────────────────────── */
    const $  = (id) => document.getElementById(id);
    const esc = (t) => t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');

    function renderMd(text) {
        // Gunakan marked.js jika tersedia (dimuat di kiosk blade), fallback ke mini-render
        if (window.marked) {
            try { return marked.parse(text); } catch (_) {}
        }
        return text
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>')
            .replace(/\*(.+?)\*/g,'<em>$1</em>')
            .replace(/`(.+?)`/g,'<code class="bg-gray-100 dark:bg-gray-700 px-1 rounded text-xs">$1</code>')
            .replace(/\n/g,'<br>');
    }

    function scrollBottom() {
        const el = $('aai-messages');
        if (el) setTimeout(() => { el.scrollTop = el.scrollHeight; }, 25);
    }

    /* ── Bubble Factories ────────────────────────────────────── */
    function bubbleUser(text) {
        const row = document.createElement('div');
        row.className = 'flex justify-end items-end gap-2';
        row.innerHTML = `
            <div class="max-w-[85%] bg-gradient-to-br from-indigo-600 to-violet-600 text-white rounded-2xl rounded-br-sm px-3.5 py-2.5 text-[13px] leading-snug break-words">${esc(text)}</div>`;
        return row;
    }

    function bubbleAssistant(text) {
        const row = document.createElement('div');
        row.className = 'flex justify-start items-end gap-2';
        row.innerHTML = `
            <div class="max-w-[88%] bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-2xl rounded-bl-sm px-3.5 py-2.5 text-[13px] leading-snug break-words prose-sm dark:prose-invert">${renderMd(text)}</div>`;
        return row;
    }

    function bubbleTyping() {
        const row = document.createElement('div');
        row.className = 'flex justify-start items-end gap-2';
        row.id = 'aai-typing';
        row.innerHTML = `
            <div class="bg-gray-100 dark:bg-gray-700 rounded-2xl rounded-bl-sm px-4 py-3 flex gap-1.5 items-center">
                <span class="w-1.5 h-1.5 rounded-full bg-gray-400 animate-bounce" style="animation-delay:0s"></span>
                <span class="w-1.5 h-1.5 rounded-full bg-gray-400 animate-bounce" style="animation-delay:.18s"></span>
                <span class="w-1.5 h-1.5 rounded-full bg-gray-400 animate-bounce" style="animation-delay:.36s"></span>
            </div>`;
        return row;
    }

    function bubbleError(msg) {
        const el = document.createElement('div');
        el.className = 'text-[12px] text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-xl px-3 py-2';
        el.textContent = '⚠️ ' + msg;
        return el;
    }

    /* ── TTS ───────────────────────────────────────────────────── */
    let isSpeaking = false;
    let isPaused   = false;

    function showSpeechControls(show) {
        const bar = $('aai-speech-controls');
        if (!bar) return;
        if (show) { bar.classList.remove('hidden'); bar.classList.add('flex'); }
        else      { bar.classList.add('hidden');    bar.classList.remove('flex'); }
    }

    function updatePauseBtn() {
        const btn = $('aai-pause-btn');
        if (!btn) return;
        btn.innerHTML = isPaused ? '▶️ Lanjut' : '⏸️ Jeda';
        btn.title     = isPaused ? 'Lanjutkan suara' : 'Jeda suara';
    }

    function updateAvatar() {
        const avatar = $('aai-header-avatar');
        if (!avatar) return;
        avatar.src = (isSpeaking && !isPaused) 
            ? "{{ asset('assets/images/chatbot/avatar-speaking.gif') }}" 
            : "{{ asset('assets/images/chatbot/avatar-idle.gif') }}";
    }

    function speakText(text) {
        if (!('speechSynthesis' in window)) return;
        window.speechSynthesis.cancel();
        const plain = text.replace(/[*_`#>~|\-]+/g,' ').replace(/\n+/g,'. ').replace(/\s{2,}/g,' ').trim();
        if (!plain) return;

        const utt   = new SpeechSynthesisUtterance(plain);
        utt.lang    = 'id-ID';
        utt.rate    = 1.25;
        utt.pitch   = 1.0;
        utt.volume  = 1.0;
        const voices = window.speechSynthesis.getVoices();
        const voice  = voices.find(v => v.lang === 'id-ID' || v.name.includes('Indonesia'));
        if (voice) utt.voice = voice;

        utt.onstart  = () => { isSpeaking = true; isPaused = false; showSpeechControls(true); updateAvatar(); };
        utt.onend    = () => { isSpeaking = false; isPaused = false; showSpeechControls(false); updateAvatar(); };
        utt.onerror  = () => { isSpeaking = false; isPaused = false; showSpeechControls(false); updateAvatar(); };

        window.speechSynthesis.speak(utt);
    }

    if ('speechSynthesis' in window) {
        window.speechSynthesis.getVoices();
        window.speechSynthesis.onvoiceschanged = () => window.speechSynthesis.getVoices();
    }

    /* ── Main UI Object ──────────────────────────────────────── */
    const aaiUI = {
        isListening: false,
        
        startDictation() {
            if (this.isListening) return;
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SpeechRecognition) {
                alert('Browser Anda tidak mendukung fitur Input Suara.');
                return;
            }
            
            const recognition = new SpeechRecognition();
            recognition.lang = 'id-ID';
            recognition.interimResults = false;
            recognition.maxAlternatives = 1;
            
            const micBtn = $('aai-mic-btn');
            
            recognition.onstart = () => {
                this.isListening = true;
                if(micBtn) {
                    micBtn.classList.add('bg-red-100', 'dark:bg-red-900/40', 'text-red-600', 'dark:text-red-400', 'animate-pulse');
                    micBtn.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
                }
            };
            
            recognition.onresult = (event) => {
                const transcript = event.results[0][0].transcript;
                const input = $('aai-input');
                const currentText = input.value;
                input.value = currentText ? currentText + ' ' + transcript : transcript;
                input.style.height = 'auto';
                input.style.height = input.scrollHeight + 'px';
            };
            
            recognition.onerror = (e) => {
                console.error('Mic error:', e);
                this.isListening = false;
            };
            
            recognition.onend = () => {
                this.isListening = false;
                if(micBtn) {
                    micBtn.classList.remove('bg-red-100', 'dark:bg-red-900/40', 'text-red-600', 'dark:text-red-400', 'animate-pulse');
                    micBtn.classList.add('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
                }
            };
            
            recognition.start();
        },

        toggle() {
            isOpen = !isOpen;
            const panel = $('aai-panel');
            const icon  = $('aai-fab-icon');
            const fab   = $('aai-fab');

            if (isOpen) {
                panel.classList.remove('hidden');
                panel.classList.add('flex', 'flex-col');
                icon.innerHTML = '<span class="text-2xl text-gray-800">✕</span>';
                fab.style.animation = 'none';
                setTimeout(() => $('aai-input')?.focus(), 160);
                scrollBottom();
            } else {
                panel.classList.add('hidden');
                panel.classList.remove('flex', 'flex-col');
                icon.innerHTML = '<img src="{{ asset('assets/images/chatbot/avatar-idle.gif') }}" alt="AI" class="w-full h-full object-cover">';
                fab.style.animation = 'aaiPulse 2.8s infinite';
                window.speechSynthesis?.cancel();
                isSpeaking = false; isPaused = false;
                showSpeechControls(false);
            }
        },

        suggest(btn) {
            $('aai-input').value = btn.textContent.trim();
            this.send();
        },

        async send() {
            if (isLoading) return;
            const input   = $('aai-input');
            const sendBtn = $('aai-send-btn');
            const message = input.value.trim();
            if (!message) return;

            // Clear input
            input.value = '';
            input.style.height = 'auto';
            input.disabled  = true;
            sendBtn.disabled = true;
            isLoading        = true;

            // Remove welcome screen on first message
            $('aai-welcome')?.remove();

            const msgArea = $('aai-messages');
            msgArea.appendChild(bubbleUser(message));
            const typing = bubbleTyping();
            msgArea.appendChild(typing);
            scrollBottom();

            try {
                // Ambil CSRF token terupdate dari DOM agar tidak stale setelah session change/login-logout
                const activeCsrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                                     || '{{ csrf_token() }}';

                const res = await fetch(ENDPOINT, {
                    method: 'POST',
                    headers: {
                        'Content-Type' : 'application/json',
                        'Accept'       : 'application/json',
                        'X-CSRF-TOKEN' : activeCsrfToken,
                    },
                    body: JSON.stringify({ message }),
                });

                typing.remove();
                const data = await res.json();

                if (res.ok && data.reply) {
                    msgArea.appendChild(bubbleAssistant(data.reply));
                    if (ttsOn) speakText(data.reply);
                } else {
                    msgArea.appendChild(bubbleError(data.error ?? 'Terjadi kesalahan, coba lagi.'));
                }
            } catch (err) {
                typing.remove();
                $('aai-messages').appendChild(bubbleError('Koneksi gagal: ' + err.message));
            } finally {
                isLoading        = false;
                input.disabled   = false;
                sendBtn.disabled = false;
                input.focus();
                scrollBottom();
            }
        },

        toggleTts() {
            ttsOn = !ttsOn;
            const btn = $('aai-tts-btn');
            btn.textContent = ttsOn ? '🔊' : '🔇';
            btn.title = ttsOn ? 'Matikan suara' : 'Nyalakan suara';
            if (!ttsOn) {
                window.speechSynthesis?.cancel();
                isSpeaking = false; isPaused = false;
                showSpeechControls(false);
                updateAvatar();
            }
        },

        stopSpeech() {
            window.speechSynthesis?.cancel();
            isSpeaking = false; isPaused = false;
            showSpeechControls(false);
            updateAvatar();
        },

        pauseResumeSpeech() {
            if (isPaused) {
                window.speechSynthesis?.resume();
                isPaused = false;
            } else {
                window.speechSynthesis?.pause();
                isPaused = true;
            }
            updatePauseBtn();
            updateAvatar();
        },

        clearHistory() {
            const msgArea = $('aai-messages');
            msgArea.innerHTML = `
                <div class="flex flex-col items-center text-center text-gray-500 dark:text-gray-400 py-4 gap-2">
                    <span class="text-4xl">✨</span>
                    <p class="text-[13px]">Riwayat dihapus. Ada yang bisa saya bantu?</p>
                    <div class="flex flex-wrap gap-1.5 justify-center mt-1">
                        <button onclick="aaiUI.suggest(this)" class="aai-chip">Siapa yang sedang check-in?</button>
                        <button onclick="aaiUI.suggest(this)" class="aai-chip">Statistik hari ini</button>
                        <button onclick="aaiUI.suggest(this)" class="aai-chip">Berapa tamu aktif?</button>
                    </div>
                </div>`;
            window.speechSynthesis?.cancel();
            isSpeaking = false; isPaused = false;
            showSpeechControls(false);
        },
    };

    window.aaiUI = aaiUI;
})();
</script>
