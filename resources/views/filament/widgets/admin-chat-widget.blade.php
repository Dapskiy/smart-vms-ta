@php
    $adminName = auth()->user()?->name ?? 'Admin';
    $chatUrl   = route('admin.ai.chat');
    $hour = now()->setTimezone('Asia/Jakarta')->format('H');
    $greeting = 'Selamat Pagi';
    if ($hour >= 11 && $hour < 15) {
        $greeting = 'Selamat Siang';
    } elseif ($hour >= 15 && $hour < 18) {
        $greeting = 'Selamat Sore';
    } elseif ($hour >= 18) {
        $greeting = 'Selamat Malam';
    }
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
        {{-- Header — Premium Glassmorphism Design --}}
        <div class="relative flex-shrink-0 overflow-hidden">
            {{-- Layered gradient background --}}
            <div class="absolute inset-0 bg-gradient-to-br from-slate-900 via-indigo-950 to-violet-950"></div>
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_left,rgba(129,140,248,0.2),transparent_60%)]"></div>
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_bottom_right,rgba(167,139,250,0.15),transparent_60%)]"></div>

            {{-- Action buttons (absolute top-right) --}}
            <div class="absolute top-2.5 right-2.5 z-10 flex items-center gap-1">
                {{-- Speech Controls --}}
                <div id="aai-speech-controls" class="hidden items-center gap-1">
                    <button onclick="aaiUI.stopSpeech()" class="w-7 h-7 rounded-lg bg-white/10 backdrop-blur-sm hover:bg-white/20 flex items-center justify-center text-sm text-white/80 hover:text-white transition-all duration-200" title="Hentikan suara">🛑</button>
                    <button id="aai-pause-btn" onclick="aaiUI.pauseResumeSpeech()" class="w-7 h-7 rounded-lg bg-white/10 backdrop-blur-sm hover:bg-white/20 flex items-center justify-center text-sm text-white/80 hover:text-white transition-all duration-200" title="Jeda suara">⏸️</button>
                </div>
                {{-- TTS Toggle --}}
                <button id="aai-tts-btn" onclick="aaiUI.toggleTts()" title="Matikan/nyalakan suara" class="w-7 h-7 rounded-lg bg-white/10 backdrop-blur-sm hover:bg-white/20 flex items-center justify-center text-sm text-white/80 hover:text-white transition-all duration-200">🔊</button>
                {{-- Clear --}}
                <button onclick="aaiUI.clearHistory()" title="Hapus riwayat" class="w-7 h-7 rounded-lg bg-white/10 backdrop-blur-sm hover:bg-white/20 flex items-center justify-center text-sm text-white/80 hover:text-white transition-all duration-200">🗑️</button>
                {{-- Close --}}
                <button onclick="aaiUI.toggle()" title="Tutup" class="w-7 h-7 rounded-lg bg-white/10 backdrop-blur-sm hover:bg-red-500/40 flex items-center justify-center text-xs font-bold text-white/80 hover:text-white transition-all duration-200">✕</button>
            </div>

            {{-- Content --}}
            <div class="relative z-[1] flex items-center gap-4 px-5 py-4 pt-5">
                {{-- Avatar with glow ring --}}
                <div class="relative flex-shrink-0">
                    <div class="absolute -inset-1 rounded-2xl bg-gradient-to-br from-indigo-400/40 to-violet-500/40 blur-md"></div>
                    <div class="relative w-[80px] h-[80px] rounded-2xl bg-gradient-to-br from-white/15 to-white/5 backdrop-blur-sm border border-white/20 flex items-center justify-center p-1 shadow-xl">
                        <img id="aai-header-avatar" src="{{ asset('assets/images/chatbot/avatar-greeting-1.png') }}" alt="AI" class="w-full h-full object-contain drop-shadow-lg">
                    </div>
                </div>

                {{-- Text --}}
                <div class="flex flex-col gap-0.5 min-w-0">
                    <h3 class="text-white font-bold text-[16px] leading-tight tracking-tight">VISITA AI Assistant</h3>
                    <p class="text-indigo-300/80 text-[11px] leading-tight font-medium">Data real-time · Powered by Gemini</p>
                    <div class="flex items-center gap-1.5 mt-1.5">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
                        </span>
                        <span class="text-emerald-300/90 text-[10px] font-semibold uppercase tracking-wider">Online</span>
                    </div>
                </div>
            </div>

            {{-- Bottom accent line --}}
            <div class="h-[1px] bg-gradient-to-r from-transparent via-indigo-400/50 to-transparent"></div>
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
                    {{ $greeting }}, <strong class="text-gray-700 dark:text-gray-200">{{ $adminName }}</strong>!<br>
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
        class="w-14 h-14 rounded-full bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-white dark:text-gray-200 shadow-xl hover:shadow-2xl flex items-center justify-center overflow-hidden transition-all hover:scale-110 active:scale-95 border-2 border-indigo-500/30 dark:border-indigo-500/50"
        style="animation:aaiPulse 2.8s infinite"
    >
        <span id="aai-fab-icon" class="w-full h-full flex items-center justify-center text-3xl">🤖</span>
    </button>


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
            ? "{{ asset('assets/images/chatbot/avatar-speaking-1.png') }}" 
            : "{{ asset('assets/images/chatbot/avatar-greeting-1.png') }}";
    }

    let currentAdminAudio = null;

    function speakText(text) {
        if (!text) return;
        aaiUI.stopSpeech();
        const plain = text.replace(/[*_`#>~|\-]+/g,' ').replace(/\n+/g,'. ').replace(/\s{2,}/g,' ').trim();
        if (!plain) return;

        const ttsUrl = '/api/tts?text=' + encodeURIComponent(plain);
        const audio = new Audio(ttsUrl);
        currentAdminAudio = audio;

        audio.onplay = () => {
            isSpeaking = true; isPaused = false; showSpeechControls(true); updateAvatar();
        };
        audio.onended = () => {
            currentAdminAudio = null;
            isSpeaking = false; isPaused = false; showSpeechControls(false); updateAvatar();
        };
        audio.onerror = (e) => {
            console.warn("Admin Edge TTS failed, using fallback", e);
            currentAdminAudio = null;
            fallbackAdminSpeak(plain);
        };
        audio.play().catch(err => {
            console.warn("Admin Edge TTS play failed, using fallback", err);
            currentAdminAudio = null;
            fallbackAdminSpeak(plain);
        });
    }

    function fallbackAdminSpeak(plain) {
        if (!('speechSynthesis' in window)) return;
        window.speechSynthesis.cancel();
        const utt = new SpeechSynthesisUtterance(plain);
        utt.lang = 'id-ID';
        utt.rate = 1.0;
        utt.pitch = 1.0;
        const voices = window.speechSynthesis.getVoices();
        const voice = voices.find(v => v.name.includes('Gadis') || v.lang === 'id-ID' || v.name.includes('Indonesia'));
        if (voice) utt.voice = voice;

        utt.onstart = () => { isSpeaking = true; isPaused = false; showSpeechControls(true); updateAvatar(); };
        utt.onend = () => { isSpeaking = false; isPaused = false; showSpeechControls(false); updateAvatar(); };
        utt.onerror = () => { isSpeaking = false; isPaused = false; showSpeechControls(false); updateAvatar(); };

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
                icon.innerHTML = '<span class="text-2xl">✕</span>';
                fab.style.animation = 'none';
                setTimeout(() => $('aai-input')?.focus(), 160);
                scrollBottom();
            } else {
                panel.classList.add('hidden');
                panel.classList.remove('flex', 'flex-col');
                icon.innerHTML = '🤖';
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
                    body: JSON.stringify({ message: text })
                });

                if (!response.ok) throw new Error('Network error');
                
                const data = await response.json();
                this.removeTypingIndicator();
                
                if (data.status === 'success') {
                    this.addMessage(data.reply, 'bot');
                } else {
                    this.addMessage('Maaf, terjadi kesalahan: ' + data.message, 'bot');
                }
            } catch (err) {
                this.removeTypingIndicator();
                this.addMessage('Ups! Gagal terhubung ke AI server.', 'bot');
                console.error(err);
            } finally {
                isProcessing = false;
                input.focus();
            }
        },

        suggest(btn) {
            input.value = btn.innerText;
            this.sendMessage();
        },

        addMessage(text, sender) {
            const wrap = document.createElement('div');
            wrap.className = `flex ${sender === 'user' ? 'justify-end' : 'justify-start'} w-full`;
            
            const bubble = document.createElement('div');
            // Parse Markdown sederhana (bold, list) jika dari bot
            let parsedText = text;
            if (sender === 'bot') {
                parsedText = parsedText
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\n- (.*?)/g, '<br>• $1')
                    .replace(/\n/g, '<br>');
            }

            bubble.innerHTML = parsedText;
            
            if (sender === 'user') {
                bubble.className = `max-w-[85%] px-4 py-2.5 rounded-2xl rounded-tr-sm text-sm text-white font-medium shadow-sm leading-relaxed
                                    bg-gradient-to-br from-indigo-500 to-indigo-600`;
            } else {
                bubble.className = `relative max-w-[85%] px-4 py-3 rounded-2xl rounded-tl-sm text-sm shadow-sm leading-relaxed
                                    bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-100 dark:border-gray-700
                                    group`;
                
                // Tambahkan tombol Play/Speaker untuk pesan AI
                const playBtn = document.createElement('button');
                playBtn.innerHTML = `
                    <svg class="w-4 h-4 text-gray-400 hover:text-indigo-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5 12h4l4-8v16l-4-8H5z" />
                    </svg>
                `;
                playBtn.className = "absolute -right-8 bottom-1 p-1.5 opacity-0 group-hover:opacity-100 transition-opacity bg-white dark:bg-gray-800 rounded-full shadow-sm border border-gray-100 dark:border-gray-700";
                playBtn.title = "Bacakan teks ini";
                playBtn.onclick = () => this.speakText(text); // Gunakan text asli (bukan HTML parsed)
                
                wrap.appendChild(bubble);
                wrap.appendChild(playBtn);
                wrap.className += " relative items-end pr-8"; // Beri ruang untuk tombol play
            }
            
            if (sender === 'user') {
                wrap.appendChild(bubble);
            }

            // Hapus welcome dummy jika ada pesan baru
            const welcome = msgs.querySelector('.text-center.text-gray-500');
            if(welcome && sender === 'user') welcome.remove();

            msgs.appendChild(wrap);
            msgs.scrollTop = msgs.scrollHeight;
        },

        showTypingIndicator() {
            const id = 'aai-typing';
            if ($(id)) return;

            const wrap = document.createElement('div');
            wrap.id = id;
            wrap.className = 'flex justify-start w-full';
            wrap.innerHTML = `
                <div class="px-4 py-3 rounded-2xl rounded-tl-sm bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 flex gap-1.5 items-center h-[42px]">
                    <div class="w-2 h-2 bg-indigo-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                    <div class="w-2 h-2 bg-indigo-400 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                    <div class="w-2 h-2 bg-indigo-400 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                </div>
            `;
            msgs.appendChild(wrap);
            msgs.scrollTop = msgs.scrollHeight;
        },

        removeTypingIndicator() {
            const typing = $('aai-typing');
            if (typing) typing.remove();
        },

        // ── Voice Input (STT) ──
        toggleMic() {
            if (!recognition) {
                alert("Browser Anda tidak mendukung fitur Suara.");
                return;
            }
            if (isListening) {
                recognition.stop();
            } else {
                try {
                    recognition.start();
                } catch(e) {
                    console.error("Gagal memulai mic", e);
                }
            }
        },

        stopListening() {
            isListening = false;
            micBtn.classList.remove('text-rose-500', 'animate-pulse');
            input.placeholder = "Tanya sesuatu atau ketik '/'...";
        },

        // ── Text to Speech (TTS) ──
        speakText(text) {
            window.speakText(text);
        },

        // Helper TTS
        updateAvatar() {
            // Efek gelombang saat bicara
            const waves = $('aai-spk-waves');
            if (isSpeaking && !isPaused) {
                waves.classList.remove('hidden');
                spkName.textContent = "AI sedang berbicara...";
            } else {
                waves.classList.add('hidden');
                spkName.textContent = "VISITA AI";
            }
        },

        showSpeechControls(show) {
            if (show) {
                spkCtrl.classList.remove('hidden');
            } else {
                spkCtrl.classList.add('hidden');
            }
        },

        stopSpeech() {
            if (currentAdminAudio) {
                currentAdminAudio.pause();
                currentAdminAudio.currentTime = 0;
                currentAdminAudio = null;
            }
            window.speechSynthesis?.cancel();
            isSpeaking = false; 
            isPaused = false;
            this.showSpeechControls(false);
            this.updateAvatar();
        },
    };

    window.aaiUI = aaiUI;
    
    // Define helper functions in outer scope to be used internally
    function updateAvatar() {
        aaiUI.updateAvatar();
    }
    
    function showSpeechControls(show) {
        aaiUI.showSpeechControls(show);
    }
    
    function updatePauseBtn() {
        const pauseBtn = spkCtrl.querySelector('button');
        if (isPaused) {
            pauseBtn.innerHTML = `
                <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                </svg>
            `;
            pauseBtn.title = "Lanjutkan";
        } else {
            pauseBtn.innerHTML = `
                <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            `;
            pauseBtn.title = "Jeda";
        }
    }
})();
</script>

</div>
