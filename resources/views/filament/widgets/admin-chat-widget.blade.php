@auth
@php
    $adminUser = auth()->user();
    $adminName = $adminUser?->name ?? 'Admin';
    $adminRoles = $adminUser ? $adminUser->getRoleNames()->implode(', ') : 'User';
    $currentPic = $adminUser ? \App\Models\Pic::where('user_id', $adminUser->id)->first() : null;
    $chatUrl   = route('admin.ai.chat');
    $recsUrl   = route('admin.ai.recommendations');
    $hour = now()->setTimezone('Asia/Jakarta')->format('H');
    $greeting = 'Selamat Pagi';
    if ($hour >= 11 && $hour < 15) {
        $greeting = 'Selamat Siang';
    } elseif ($hour >= 15 && $hour < 18) {
        $greeting = 'Selamat Sore';
    } elseif ($hour >= 18) {
        $greeting = 'Selamat Malam';
    }

    // Build RBAC-aware suggestion chips
    $isAdmin = $adminUser && ($adminUser->hasRole('super_admin') || $adminUser->hasRole('admin') || $adminUser->can('view_appointment'));
    $isPic = $currentPic !== null;

    $chips = [];
    if ($isAdmin) {
        $chips = [
            'Statistik hari ini',
            'Siapa yang sedang check-in?',
            'Berapa tamu aktif?',
            'Siapa yang sudah checkout?',
            'Daftar appointment pending',
        ];
    }
    if ($isPic) {
        $chips = array_merge($chips, [
            'Tamu saya hari ini',
            'Appointment pending saya',
            'Ubah status saya menjadi tersedia',
            'Update lokasi saya',
        ]);
    }
    if (empty($chips)) {
        $chips = ['Statistik hari ini', 'Bantuan'];
    }
    // Deduplicate
    $chips = array_unique($chips);
@endphp

{{-- ══════════════════════════════════════════════════════════
     VISITA Admin AI Assistant — Kiosk-Style Premium Chat Widget
     Redesigned to mirror the kiosk lobby chatbot experience
     with split-panel avatar, RBAC-aware features, and to-do list
     ══════════════════════════════════════════════════════════ --}}
<div
    id="aai-root"
    class="fixed bottom-7 right-7 z-[99999] flex flex-col items-end gap-3 font-sans"
    style="font-family:'Poppins','Inter',ui-sans-serif,sans-serif"
>

    {{-- ── Chat Panel ───────────────────────────────────────────── --}}
    <div
        id="aai-panel"
        class="hidden w-[94vw] md:w-[45vw] lg:w-[40vw] max-w-[500px] h-[85vh] max-h-[800px] bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 flex flex-col overflow-hidden origin-bottom-right"
        style="animation:aaiSlideIn .25s cubic-bezier(.4,0,.2,1) forwards"
        role="dialog"
        aria-label="VISITA AI Assistant"
    >
        {{-- Premium Header --}}
        <div class="relative flex-shrink-0 overflow-hidden pt-6 pb-4">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-900 via-indigo-950 to-violet-950"></div>
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(167,139,250,0.25),transparent_60%)]"></div>
            
            {{-- Header Actions (Top Right) --}}
            <div class="absolute top-2.5 right-2.5 z-10 flex items-center gap-1">
                <button onclick="aaiUI.clearHistory()" title="Hapus riwayat" class="w-7 h-7 rounded-lg bg-white/10 backdrop-blur-sm hover:bg-white/20 flex items-center justify-center text-xs text-white/80 hover:text-white transition-all duration-200">🗑️</button>
                <button onclick="aaiUI.toggle()" title="Tutup" class="w-7 h-7 rounded-lg bg-white/10 backdrop-blur-sm hover:bg-red-500/40 flex items-center justify-center text-xs font-bold text-white/80 hover:text-white transition-all duration-200">✕</button>
            </div>

            {{-- Sound Controls (Top Left) --}}
            <div id="aai-snd-ctrl" class="hidden absolute top-2.5 left-2.5 z-10 items-center gap-1">
                <button onclick="aaiUI.toggleTts()" id="aai-tts-toggle" class="w-7 h-7 rounded-md bg-white/10 backdrop-blur-sm hover:bg-white/20 flex items-center justify-center text-white/80 hover:text-white transition-all text-[11px]" title="Mute/Unmute">🔊</button>
                <button onclick="aaiUI.pauseResumeSpeech()" id="aai-pause-btn" class="w-7 h-7 rounded-md bg-white/10 backdrop-blur-sm hover:bg-white/20 flex items-center justify-center text-white/80 hover:text-white transition-all text-[11px]" title="Pause/Resume">⏸️</button>
                <button onclick="aaiUI.stopSpeech()" id="aai-stop-btn" class="w-7 h-7 rounded-md bg-white/10 backdrop-blur-sm hover:bg-red-500/40 flex items-center justify-center text-white/80 hover:text-white transition-all text-[11px]" title="Stop">⏹️</button>
            </div>

            {{-- Centered Content (Avatar + Text) --}}
            <div class="relative z-[1] flex flex-col items-center justify-center gap-2 px-4">
                
                {{-- AI Avatar Container (Profile Picture) --}}
                <div id="aai-header-avatar-container" class="relative w-[90px] h-[90px] md:w-[100px] md:h-[100px] pointer-events-none">
                    {{-- Avatar Speech Ring --}}
                    <div id="aai-speech-ring" class="absolute inset-0 rounded-full border-2 border-transparent z-[1] transition-all duration-400 scale-[0.85] md:scale-95"></div>
                    
                    {{-- 3-Frame Speaking Animation --}}
                    <div id="aai-avatar-container" class="absolute inset-0">
                        <img id="aai-avatar-1" src="{{ asset('assets/images/chatbot/avatar-speaking-1.png') }}" alt="AI Avatar" class="absolute inset-0 w-full h-full object-contain transition-opacity duration-300 drop-shadow-2xl" style="opacity:1">
                        <img id="aai-avatar-2" src="{{ asset('assets/images/chatbot/avatar-speaking-2.png') }}" alt="AI Avatar" class="absolute inset-0 w-full h-full object-contain transition-opacity duration-300 drop-shadow-2xl" style="opacity:0">
                        <img id="aai-avatar-3" src="{{ asset('assets/images/chatbot/avatar-speaking-3.png') }}" alt="AI Avatar" class="absolute inset-0 w-full h-full object-contain transition-opacity duration-300 drop-shadow-2xl" style="opacity:0">
                    </div>
                </div>

                {{-- Text Content --}}
                <div class="flex flex-col items-center text-center gap-0.5 w-full">
                    <h3 class="text-white font-bold text-[16px] leading-tight tracking-tight">VISITA AI Assistant</h3>
                    <p class="text-indigo-300/80 text-[11px] leading-tight font-medium max-w-[80%] truncate">{{ $adminName }} · {{ $adminRoles }}</p>
                    <div class="flex items-center gap-1.5 mt-1 bg-white/5 rounded-full px-2.5 py-0.5 border border-white/10">
                        <span class="relative flex h-1.5 w-1.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-400"></span>
                        </span>
                        <span class="text-emerald-300/90 text-[9px] font-semibold uppercase tracking-wider">Online</span>
                        <span class="text-gray-400/50 text-[10px] mx-0.5">•</span>
                        <span class="text-indigo-200/60 text-[9px] font-semibold uppercase tracking-wider">RBAC</span>
                        <span class="text-gray-400/50 text-[10px] mx-0.5">•</span>
                        <span id="aai-status-text" class="text-indigo-200/90 text-[9px] font-bold uppercase tracking-wider">Idle</span>
                    </div>
                </div>

            </div>
            
            {{-- Bottom Border Line --}}
            <div class="absolute bottom-0 left-0 w-full h-[1px] bg-gradient-to-r from-transparent via-indigo-400/50 to-transparent"></div>
        </div>

                {{-- Messages Area --}}
                <div
                    id="aai-messages"
                    class="flex-1 overflow-y-auto px-3.5 py-3 flex flex-col gap-2.5 scroll-smooth"
                    style="min-height:120px"
                >
                    {{-- Welcome State + To-Do Recommendations --}}
                    <div id="aai-welcome" class="flex flex-col text-gray-500 dark:text-gray-400 py-2 gap-3">
                        {{-- Greeting --}}
                        <div class="text-center">
                            <span class="text-3xl">👋</span>
                            <p class="text-[13px] leading-snug mt-1">
                                {{ $greeting }}, <strong class="text-gray-700 dark:text-gray-200">{{ $adminName }}</strong>!
                            </p>
                            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">{{ $adminRoles }} · Tanya saya tentang data sistem</p>
                        </div>

                        {{-- To-Do Recommendations Panel (loaded via JS) --}}
                        <div id="aai-todo-panel" class="bg-gradient-to-br from-indigo-50 via-white to-violet-50 dark:from-gray-800 dark:via-gray-800/80 dark:to-indigo-900/20 rounded-xl border border-indigo-100 dark:border-indigo-800/50 p-3 mx-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-sm">📋</span>
                                <span class="text-[12px] font-bold text-indigo-700 dark:text-indigo-300 uppercase tracking-wider">Rekomendasi Hari Ini</span>
                            </div>
                            <div id="aai-todo-list" class="flex flex-col gap-1.5">
                                <div class="flex items-center gap-2 text-[11px] text-gray-400 dark:text-gray-500">
                                    <span class="inline-block w-3 h-3 rounded-full bg-gray-200 dark:bg-gray-700 animate-pulse"></span>
                                    Memuat rekomendasi...
                                </div>
                            </div>
                        </div>

                        {{-- RBAC-Aware Suggestion Chips --}}
                        <div class="flex flex-wrap gap-1.5 justify-center px-1">
                            @foreach($chips as $chip)
                                <button onclick="aaiUI.suggest(this)" class="aai-chip">{{ $chip }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Input Area --}}
                <div class="flex items-end gap-2 px-3.5 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 flex-shrink-0">
                    <textarea
                        id="aai-input"
                        rows="1"
                        placeholder="Tanya tentang data sistem..."
                        class="flex-1 resize-none rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-100 text-[13px] px-3 py-2 outline-none focus:border-violet-500 dark:focus:border-violet-400 transition-colors max-h-24 overflow-y-auto leading-snug placeholder-gray-400"
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
        <span id="aai-fab-icon" class="w-full h-full flex items-center justify-center">
            <img src="{{ asset('assets/images/chatbot/avatar-greeting-1.png') }}" alt="AI" class="w-10 h-10 object-contain">
        </span>
    </button>

{{-- ── Notification Badge (on FAB) ── --}}
<span id="aai-fab-badge" class="hidden absolute -top-1 -right-1 w-5 h-5 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center shadow-lg animate-bounce">!</span>


<style>
.aai-chip {
    @apply bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300
           border border-indigo-200 dark:border-indigo-700 rounded-full
           px-3 py-1 text-[11px] cursor-pointer
           hover:bg-indigo-100 dark:hover:bg-indigo-800/50
           transition-colors select-none;
    font-family: inherit;
}
@keyframes aaiSlideIn {
    from { opacity:0; transform:translateY(14px) scale(.97); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}
@keyframes aaiSlideOut {
    from { opacity:1; transform:translateY(0) scale(1); }
    to   { opacity:0; transform:translateY(14px) scale(.97); }
}
@keyframes aaiPulse {
    0%,100% { box-shadow: 0 4px 24px rgba(79,70,229,.5), 0 0 0 0 rgba(79,70,229,.35); }
    55%     { box-shadow: 0 4px 24px rgba(79,70,229,.5), 0 0 0 9px rgba(79,70,229,0); }
}
#aai-messages::-webkit-scrollbar      { width: 3px; }
#aai-messages::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }

/* Avatar Speaking Animation (3-frame, matching kiosk) */
@keyframes aai-speak-1 {
    0%, 27.78%    { opacity: 1; }
    30%, 97.77%   { opacity: 0; }
    100%          { opacity: 1; }
}
@keyframes aai-speak-2 {
    0%, 31.1%     { opacity: 0; }
    33.33%, 61.11%{ opacity: 1; }
    63.33%, 100%  { opacity: 0; }
}
@keyframes aai-speak-3 {
    0%, 64.43%    { opacity: 0; }
    66.67%, 94.44%{ opacity: 1; }
    96.67%, 100%  { opacity: 0; }
}
#aai-avatar-container.speaking #aai-avatar-1 { animation: aai-speak-1 18s infinite linear; }
#aai-avatar-container.speaking #aai-avatar-2 { animation: aai-speak-2 18s infinite linear; }
#aai-avatar-container.speaking #aai-avatar-3 { animation: aai-speak-3 18s infinite linear; }

/* Speech ring pulse when speaking */
#aai-speech-ring.speaking {
    border-color: rgba(37, 99, 235, 0.25);
    box-shadow: 0 0 30px rgba(37, 99, 235, 0.18);
    animation: aai-ring-pulse 2s infinite ease-in-out;
}
#aai-speech-ring.listening {
    border-color: rgba(244, 63, 94, 0.25);
    box-shadow: 0 0 30px rgba(244, 63, 94, 0.18);
    animation: aai-ring-pulse 1.5s infinite ease-in-out;
}
@keyframes aai-ring-pulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.06); opacity: 1; }
}

/* Status dot animations */
#aai-status-dot.speaking { background: #2563EB; animation: aai-dot-pulse 1.4s infinite; }
#aai-status-dot.listening { background: #f43f5e; animation: aai-dot-pulse 1.4s infinite; }
#aai-status-dot.thinking { background: #60A5FA; animation: aai-dot-pulse 1.4s infinite; }
@keyframes aai-dot-pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.5); opacity: 0.4; }
}

/* To-Do item styles */
.aai-todo-item {
    @apply flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11.5px] font-medium cursor-pointer transition-all;
}
.aai-todo-item:hover {
    @apply bg-white/80 dark:bg-gray-700/50 shadow-sm transform -translate-y-px;
}
.aai-todo-item.warning { @apply text-amber-700 dark:text-amber-300; }
.aai-todo-item.info { @apply text-blue-700 dark:text-blue-300; }
.aai-todo-item.suggestion { @apply text-violet-700 dark:text-violet-300; }
</style>

{{-- ── marked.js for Markdown rendering ── --}}
<script src="/js/marked.min.js"></script>

{{-- ── JavaScript ───────────────────────────────────────────── --}}
<script>
(function () {
    /* ── Config ─────────────────────────────────────────────── */
    const ENDPOINT     = '{{ $chatUrl }}';
    const RECS_URL     = '{{ $recsUrl }}';

    let isOpen    = false;
    let isLoading = false;
    let ttsOn     = true;
    let isSpeaking = false;
    let isPaused   = false;
    let currentAdminAudio = null;
    let speakAnimFrame = null;

    /* ── Helpers ─────────────────────────────────────────────── */
    const $  = (id) => document.getElementById(id);
    const esc = (t) => t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');

    function renderMd(text) {
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

    /* ── Avatar Animation Control ───────────────────────────── */
    function setAvatarSpeaking(speaking) {
        const container = $('aai-avatar-container');
        const ring = $('aai-speech-ring');
        if (!container) return;

        if (speaking && !isPaused) {
            container.classList.add('speaking');
            ring?.classList.add('speaking');
            ring?.classList.remove('listening');
        } else {
            container.classList.remove('speaking');
            ring?.classList.remove('speaking');
        }
    }

    function setAvatarListening(listening) {
        const ring = $('aai-speech-ring');
        if (listening) {
            ring?.classList.add('listening');
            ring?.classList.remove('speaking');
        } else {
            ring?.classList.remove('listening');
        }
    }

    function updateStatus(status) {
        const text = $('aai-status-text');
        if (!text) return;

        switch(status) {
            case 'speaking':
                text.textContent = 'Speaking';
                break;
            case 'listening':
                text.textContent = 'Listening';
                break;
            case 'thinking':
                text.textContent = 'Thinking';
                break;
            default:
                text.textContent = 'Idle';
        }
    }

    function showSoundControls(show) {
        const ctrl = $('aai-snd-ctrl');
        const pauseBtn = $('aai-pause-btn');
        const stopBtn = $('aai-stop-btn');
        if (!ctrl) return;
        if (show) {
            ctrl.classList.remove('hidden');
            ctrl.classList.add('flex');
            pauseBtn?.classList.remove('hidden');
            stopBtn?.classList.remove('hidden');
        } else {
            ctrl.classList.add('hidden');
            ctrl.classList.remove('flex');
            pauseBtn?.classList.add('hidden');
            stopBtn?.classList.add('hidden');
        }
    }

    /* ── Bubble Factories ────────────────────────────────────── */
    function bubbleUser(text) {
        const row = document.createElement('div');
        row.className = 'flex justify-end items-end gap-2';
        row.innerHTML = `
            <div class="max-w-[85%] bg-gradient-to-br from-indigo-600 to-violet-600 text-white rounded-2xl rounded-br-sm px-3.5 py-2.5 text-[13px] leading-snug break-words shadow-sm">${esc(text)}</div>`;
        return row;
    }

    function bubbleAssistant(text) {
        const row = document.createElement('div');
        row.className = 'flex justify-start items-start gap-2 group';
        row.innerHTML = `
            <div class="max-w-[88%] bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-2xl rounded-bl-sm px-3.5 py-2.5 text-[13px] leading-snug break-words prose-sm dark:prose-invert shadow-sm">${renderMd(text)}</div>
            <button onclick="aaiUI.speakThis('${text.replace(/'/g, "\\'").replace(/\n/g, '\\n')}')" class="flex-shrink-0 w-7 h-7 rounded-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-600 flex items-center justify-center text-[11px] opacity-0 group-hover:opacity-100 transition-opacity hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:text-indigo-600" title="Bacakan">🔊</button>`;
        return row;
    }

    function bubbleTyping() {
        const row = document.createElement('div');
        row.className = 'flex justify-start items-end gap-2';
        row.id = 'aai-typing';
        row.innerHTML = `
            <div class="bg-gray-100 dark:bg-gray-700 rounded-2xl rounded-bl-sm px-4 py-3 flex gap-1.5 items-center shadow-sm">
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-bounce" style="animation-delay:0s"></span>
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-bounce" style="animation-delay:.18s"></span>
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-bounce" style="animation-delay:.36s"></span>
            </div>`;
        return row;
    }

    function bubbleError(msg) {
        const el = document.createElement('div');
        el.className = 'text-[12px] text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-xl px-3 py-2';
        el.textContent = '⚠️ ' + msg;
        return el;
    }

    /* ── TTS Engine (Edge TTS + Web Speech fallback) ───────── */
    let fallbackIndoVoice = null;

    function loadFallbackVoices() {
        const voices = window.speechSynthesis?.getVoices() ?? [];
        fallbackIndoVoice = voices.find(v => v.name.includes('Gadis') || v.lang === 'id-ID' || v.name.includes('Indonesia')) ?? null;
    }
    if ('speechSynthesis' in window) {
        window.speechSynthesis.getVoices();
        window.speechSynthesis.addEventListener('voiceschanged', loadFallbackVoices);
        loadFallbackVoices();
    }

    function speakText(text) {
        if (!text || !ttsOn) return;
        aaiUI.stopSpeech();
        const plain = text.replace(/<!--.*?-->/gs, '').replace(/[*_`#>~|\-]+/g,' ').replace(/\n+/g,'. ').replace(/\s{2,}/g,' ').trim();
        if (!plain) return;

        const ttsUrl = '/api/tts?text=' + encodeURIComponent(plain);
        const audio = new Audio(ttsUrl);
        currentAdminAudio = audio;

        isSpeaking = true; isPaused = false;
        setAvatarSpeaking(true);
        updateStatus('speaking');
        showSoundControls(true);

        audio.onended = () => {
            currentAdminAudio = null;
            isSpeaking = false; isPaused = false;
            setAvatarSpeaking(false);
            updateStatus('idle');
            showSoundControls(false);
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
        if (!('speechSynthesis' in window)) {
            isSpeaking = false; setAvatarSpeaking(false); updateStatus('idle'); showSoundControls(false);
            return;
        }
        window.speechSynthesis.cancel();
        const utt = new SpeechSynthesisUtterance(plain);
        utt.lang = 'id-ID'; utt.rate = 1.0; utt.pitch = 1.0;
        if (fallbackIndoVoice) utt.voice = fallbackIndoVoice;

        utt.onstart = () => { isSpeaking = true; isPaused = false; setAvatarSpeaking(true); updateStatus('speaking'); showSoundControls(true); };
        utt.onend = () => { isSpeaking = false; isPaused = false; setAvatarSpeaking(false); updateStatus('idle'); showSoundControls(false); };
        utt.onerror = () => { isSpeaking = false; isPaused = false; setAvatarSpeaking(false); updateStatus('idle'); showSoundControls(false); };

        window.speechSynthesis.speak(utt);
    }

    /* ── Load Recommendations (To-Do List) ──────────────────── */
    async function loadRecommendations() {
        try {
            const activeCsrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const res = await fetch(RECS_URL, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': activeCsrfToken,
                },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();
            renderRecommendations(data.recommendations || []);
        } catch (err) {
            console.warn('[AI-Recs] Failed to load:', err);
            const list = $('aai-todo-list');
            if (list) list.innerHTML = '<div class="text-[11px] text-gray-400">Gagal memuat rekomendasi</div>';
        }
    }

    function renderRecommendations(recs) {
        const list = $('aai-todo-list');
        if (!list) return;

        if (recs.length === 0) {
            list.innerHTML = '<div class="aai-todo-item info"><span>✨</span> Semua beres!</div>';
            return;
        }

        list.innerHTML = recs.map(r => `
            <div class="aai-todo-item ${r.type}" onclick="aaiUI.suggest(this)" data-text="${r.action}">
                <span class="text-sm flex-shrink-0">${r.icon}</span>
                <span class="flex-1">${r.text}</span>
                <svg class="w-3 h-3 opacity-40 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 5l7 7-7 7"/></svg>
            </div>
        `).join('');

        // Show badge on FAB if there are warning items
        const warnings = recs.filter(r => r.type === 'warning');
        const badge = $('aai-fab-badge');
        if (badge && warnings.length > 0 && !isOpen) {
            badge.textContent = warnings.length;
            badge.classList.remove('hidden');
        }
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
                setAvatarListening(true);
                updateStatus('listening');
                if (micBtn) {
                    micBtn.classList.add('bg-red-100', 'dark:bg-red-900/40', 'text-red-600', 'dark:text-red-400', 'animate-pulse');
                    micBtn.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
                }
            };

            recognition.onresult = (event) => {
                const transcript = event.results[0][0].transcript;
                const input = $('aai-input');
                input.value = transcript;
                input.style.height = 'auto';
                input.style.height = input.scrollHeight + 'px';
                // Auto-send after speech recognition
                this.send();
            };

            recognition.onerror = (e) => {
                console.error('Mic error:', e);
                this.isListening = false;
                setAvatarListening(false);
                updateStatus('idle');
            };

            recognition.onend = () => {
                this.isListening = false;
                setAvatarListening(false);
                updateStatus('idle');
                if (micBtn) {
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
            const badge = $('aai-fab-badge');

            if (isOpen) {
                panel.classList.remove('hidden');
                panel.style.display = 'flex';
                panel.style.animation = 'aaiSlideIn .25s cubic-bezier(.4,0,.2,1) forwards';
                icon.innerHTML = '<span class="text-2xl">✕</span>';
                fab.style.animation = 'none';
                badge?.classList.add('hidden');
                setTimeout(() => $('aai-input')?.focus(), 160);
                scrollBottom();
                // Load recommendations on first open
                loadRecommendations();
            } else {
                panel.style.animation = 'aaiSlideOut .2s cubic-bezier(.4,0,.2,1) forwards';
                setTimeout(() => {
                    panel.classList.add('hidden');
                    panel.style.display = 'none';
                    panel.style.animation = ''; // Reset animation
                }, 200);
                icon.innerHTML = `<img src="{{ asset('assets/images/chatbot/avatar-greeting-1.png') }}" alt="AI" class="w-10 h-10 object-contain">`;
                fab.style.animation = 'aaiPulse 2.8s infinite';
                this.stopSpeech();
            }
        },

        suggest(btn) {
            const text = btn.dataset?.text || btn.textContent?.trim();
            if (!text) return;
            $('aai-input').value = text;
            this.send();
        },

        async send() {
            if (isLoading) return;
            const input   = $('aai-input');
            const sendBtn = $('aai-send-btn');
            const message = input.value.trim();
            if (!message) return;

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

            updateStatus('thinking');

            try {
                const activeCsrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

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

                if (!res.ok) {
                    const errData = await res.json().catch(() => ({}));
                    throw new Error(errData.error || 'HTTP ' + res.status);
                }

                const data = await res.json();

                if (data.error) {
                    msgArea.appendChild(bubbleError(data.error));
                } else {
                    const reply = data.reply || '...';
                    msgArea.appendChild(bubbleAssistant(reply));
                    // Auto-speak response
                    if (ttsOn) speakText(reply);
                }
            } catch (err) {
                typing.remove();
                msgArea.appendChild(bubbleError(err.message || 'Gagal terhubung ke AI server.'));
                console.error('[AI-Chat]', err);
            } finally {
                isLoading = false;
                input.disabled = false;
                sendBtn.disabled = false;
                updateStatus('idle');
                input.focus();
                scrollBottom();
            }
        },

        clearHistory() {
            const msgArea = $('aai-messages');
            if (!msgArea) return;
            this.stopSpeech();
            // Rebuild welcome state
            msgArea.innerHTML = '';
            const welcome = document.createElement('div');
            welcome.id = 'aai-welcome';
            welcome.className = 'flex flex-col text-gray-500 dark:text-gray-400 py-2 gap-3';
            welcome.innerHTML = `
                <div class="text-center">
                    <span class="text-3xl">👋</span>
                    <p class="text-[13px] leading-snug mt-1">{{ $greeting }}, <strong class="text-gray-700 dark:text-gray-200">{{ $adminName }}</strong>!</p>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">{{ $adminRoles }} · Tanya saya tentang data sistem</p>
                </div>
                <div id="aai-todo-panel" class="bg-gradient-to-br from-indigo-50 via-white to-violet-50 dark:from-gray-800 dark:via-gray-800/80 dark:to-indigo-900/20 rounded-xl border border-indigo-100 dark:border-indigo-800/50 p-3 mx-1">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-sm">📋</span>
                        <span class="text-[12px] font-bold text-indigo-700 dark:text-indigo-300 uppercase tracking-wider">Rekomendasi Hari Ini</span>
                    </div>
                    <div id="aai-todo-list" class="flex flex-col gap-1.5">
                        <div class="flex items-center gap-2 text-[11px] text-gray-400">
                            <span class="inline-block w-3 h-3 rounded-full bg-gray-200 dark:bg-gray-700 animate-pulse"></span>
                            Memuat...
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-1.5 justify-center px-1">
                    @foreach($chips as $chip)
                        <button onclick="aaiUI.suggest(this)" class="aai-chip">{{ $chip }}</button>
                    @endforeach
                </div>
            `;
            msgArea.appendChild(welcome);
            loadRecommendations();
        },

        toggleTts() {
            ttsOn = !ttsOn;
            const btn = $('aai-tts-toggle');
            if (btn) btn.textContent = ttsOn ? '🔊' : '🔇';
            if (!ttsOn) this.stopSpeech();
        },

        speakThis(text) {
            speakText(text);
        },

        pauseResumeSpeech() {
            if (isPaused) {
                if (currentAdminAudio) {
                    currentAdminAudio.play().catch(() => {});
                } else if (window.speechSynthesis) {
                    window.speechSynthesis.resume();
                }
                isPaused = false;
                setAvatarSpeaking(true);
                updateStatus('speaking');
                const btn = $('aai-pause-btn');
                if (btn) btn.textContent = '⏸️';
            } else {
                if (currentAdminAudio) {
                    currentAdminAudio.pause();
                } else if (window.speechSynthesis) {
                    window.speechSynthesis.pause();
                }
                isPaused = true;
                setAvatarSpeaking(false);
                updateStatus('idle');
                const btn = $('aai-pause-btn');
                if (btn) btn.textContent = '▶️';
            }
        },

        stopSpeech() {
            if (currentAdminAudio) {
                currentAdminAudio.pause();
                currentAdminAudio.currentTime = 0;
                currentAdminAudio = null;
            }
            if (window.speechSynthesis) {
                window.speechSynthesis.cancel();
            }
            isSpeaking = false;
            isPaused = false;
            setAvatarSpeaking(false);
            updateStatus('idle');
            showSoundControls(false);
        },
    };

    window.aaiUI = aaiUI;

    // Auto-load recommendations for FAB badge
    setTimeout(loadRecommendations, 1500);
})();
</script>

</div>
@endauth
