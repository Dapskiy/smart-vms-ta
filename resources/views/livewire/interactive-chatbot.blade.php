<div
    class="chatbot-wrapper"
    x-data="{
        ttsEnabled: true,
        isSpeaking: false,
        isPaused: false,
        isListening: false,
        showKeyboard: false,
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

            recognition.onstart = () => { this.isListening = true; };
            recognition.onresult = (event) => {
                const transcript = event.results[0][0].transcript;
                this.$wire.set('inputMessage', transcript);
                // Auto submit when speaking ends
                this.$wire.sendMessage();
            };
            recognition.onerror = (e) => { console.error('Mic error:', e); this.isListening = false; };
            recognition.onend = () => { this.isListening = false; };
            recognition.start();
        },
        toggleTts() {
            this.ttsEnabled = !this.ttsEnabled;
            if (!this.ttsEnabled) {
                window.stopAiSpeech();
                this.isSpeaking = false;
                this.isPaused = false;
            }
        },
        stopSpeech() {
            window.stopAiSpeech();
            this.isSpeaking = false;
            this.isPaused = false;
        },
        pauseResumeSpeech() {
            if (this.isPaused) {
                window.resumeAiSpeech();
                this.isPaused = false;
            } else {
                window.pauseAiSpeech();
                this.isPaused = true;
            }
        }
    }"
    x-on:chatbot-speak.window="if (ttsEnabled) { window.speakText($event.detail.text); }"
    @tts-started.window="isSpeaking = true; isPaused = false"
    @tts-ended.window="isSpeaking = false; isPaused = false"
>
    <!-- AI ASSISTANT EMBEDDED LAYOUT -->
    <div class="chatbot-container">

        <!-- 1. STATUS BADGE -->
        <div class="chatbot-status-row">
            <div class="chatbot-status-badge" :class="{ 'speaking': isSpeaking && !isPaused, 'listening': isListening }">
                <span class="status-dot"></span>
                <span x-show="isSpeaking && !isPaused" x-cloak>VISITA AI sedang berbicara...</span>
                <span x-show="isListening" x-cloak>Mendengarkan suara Anda...</span>
                <span x-show="!isSpeaking && !isListening">VISITA AI siap membantu</span>
            </div>
        </div>

        <!-- 2. CONTROL BUTTONS (Mic, Keyboard, Utility) — compact row -->
        <div class="chatbot-controls">
            <!-- Mic -->
            <button
                type="button"
                @click="startDictation()"
                class="ctrl-btn ctrl-mic"
                :class="{ 'listening': isListening }"
                title="Bicara dengan AI"
                @if($isLoading) disabled @endif
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
                    <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                    <line x1="12" y1="19" x2="12" y2="23"/>
                    <line x1="8" y1="23" x2="16" y2="23"/>
                </svg>
            </button>

            <!-- Keyboard toggle -->
            <button
                type="button"
                @click="showKeyboard = !showKeyboard"
                class="ctrl-btn ctrl-kb"
                :class="{ 'active': showKeyboard }"
                title="Ketik Pesan"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="4" width="20" height="16" rx="2" ry="2"/>
                    <line x1="6" y1="8" x2="6" y2="8"/><line x1="10" y1="8" x2="10" y2="8"/>
                    <line x1="14" y1="8" x2="14" y2="8"/><line x1="18" y1="8" x2="18" y2="8"/>
                    <line x1="6" y1="12" x2="6" y2="12"/><line x1="10" y1="12" x2="18" y2="12"/>
                    <line x1="6" y1="16" x2="18" y2="16"/>
                </svg>
            </button>

            <!-- Mute / unmute -->
            <button @click="toggleTts()" class="ctrl-btn ctrl-util" :title="ttsEnabled ? 'Matikan Suara' : 'Aktifkan Suara'">
                <span x-show="ttsEnabled">🔊</span>
                <span x-show="!ttsEnabled">🔇</span>
            </button>

            <!-- Stop speaking -->
            <div x-show="isSpeaking" x-transition.opacity>
                <button @click="stopSpeech()" class="ctrl-btn ctrl-util ctrl-stop" title="Hentikan Suara">🛑</button>
            </div>

            <!-- Clear history -->
            @if(count($messages) > 0)
            <button wire:click="clearHistory" class="ctrl-btn ctrl-util" title="Hapus Riwayat">🗑️</button>
            @endif
        </div>

        <!-- 3. GREETING / LATEST MESSAGE — plain text, no card -->
        <div class="chatbot-greeting">
            @if($isLoading)
                <div class="chatbot-typing-inline">
                    <span></span><span></span><span></span>
                </div>
            @elseif($error)
                <div class="chatbot-error-inline">⚠️ {{ $error }}</div>
            @elseif(empty($messages))
                Halo! Saya <span class="brand-highlight">VISITA Assistant</span>. Ada yang bisa saya bantu?
            @else
                @php
                    $latestAssistantMsg = null;
                    foreach (array_reverse($messages) as $msg) {
                        if ($msg['role'] === 'assistant') { $latestAssistantMsg = $msg['content']; break; }
                    }
                @endphp
                @if($latestAssistantMsg)
                    <span
                        x-data="{ md: @js($latestAssistantMsg) }"
                        x-html="window.marked ? marked.parse(md) : md"
                        class="chatbot-md-inline"
                    ></span>
                @else
                    Halo! Saya <span class="brand-highlight">VISITA Assistant</span>. Ada yang bisa saya bantu?
                @endif
            @endif
        </div>
        
        <!-- 4. KEYBOARD TEXT INPUT (slide-down) -->
        <div
            x-show="showKeyboard"
            x-transition:enter="kb-enter"
            x-transition:enter-start="kb-enter-start"
            x-transition:enter-end="kb-enter-end"
            x-transition:leave="kb-leave"
            x-transition:leave-start="kb-leave-end"
            x-transition:leave-end="kb-leave-start"
            class="chatbot-text-input-container"
            style="display:none;"
        >
            <textarea
                x-ref="inputArea"
                wire:model="inputMessage"
                wire:keydown.enter.prevent="sendMessage"
                class="chatbot-textarea"
                placeholder="Ketik pesan Anda di sini..."
                rows="1"
                x-on:input="$el.style.height='auto'; $el.style.height=$el.scrollHeight+'px'"
                @if($isLoading) disabled @endif
            ></textarea>
            <button wire:click="sendMessage" wire:loading.attr="disabled" class="chatbot-send-msg-btn" title="Kirim">
                <span wire:loading.remove wire:target="sendMessage">➤</span>
                <span wire:loading wire:target="sendMessage">⏳</span>
            </button>
        </div>

    </div>

    <!-- marked.js untuk render Markdown -->
    <script src="https://cdn.jsdelivr.net/npm/marked@9/marked.min.js"></script>
    <script>
        if (window.marked) { marked.setOptions({ breaks: true, gfm: true }); }

        (function () {
            'use strict';
            let indoVoice = null;
            function loadVoices() {
                const voices = window.speechSynthesis?.getVoices() ?? [];
                indoVoice = voices.find(v => v.lang === 'id-ID' || v.name.includes('Indonesia')) ?? null;
            }
            if ('speechSynthesis' in window) {
                window.speechSynthesis.getVoices();
                window.speechSynthesis.addEventListener('voiceschanged', loadVoices);
                loadVoices();
            }
            function fireTtsEvent(name) { window.dispatchEvent(new CustomEvent(name)); }
            window.speakText = function (text) {
                if (!text || !('speechSynthesis' in window)) return;
                window.speechSynthesis.cancel();
                const plain = text.replace(/[*_`#>~|\-]+/g,' ').replace(/\n+/g,'. ').replace(/\s{2,}/g,' ').trim();
                if (!plain) return;
                const utt = new SpeechSynthesisUtterance(plain);
                utt.lang='id-ID'; utt.rate=1.25; utt.pitch=1.0; utt.volume=1.0;
                if (indoVoice) utt.voice = indoVoice;
                utt.onend = () => fireTtsEvent('tts-ended');
                utt.onerror = () => fireTtsEvent('tts-ended');
                fireTtsEvent('tts-started');
                window.speechSynthesis.speak(utt);
            };
            window.stopAiSpeech   = () => { window.speechSynthesis?.cancel(); fireTtsEvent('tts-ended'); };
            window.pauseAiSpeech  = () => window.speechSynthesis?.pause();
            window.resumeAiSpeech = () => window.speechSynthesis?.resume();
        })();
    </script>

    <style>
        /* ── Wrapper ── */
        .chatbot-wrapper {
            width: 100%;
            display: flex;
            align-items: flex-end; /* Align the controls and texts near bottom overlaying the avatar */
            justify-content: center;
            font-family: 'Poppins', sans-serif;
            z-index: 10;
            flex: 1;
            min-height: 0;
            padding-bottom: 2vh;
        }

        .chatbot-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.85rem;
            width: 100%;
            max-width: 560px;
            z-index: 12;
        }

        /* ── Status badge ── */
        .chatbot-status-row {
            display: flex;
            justify-content: center;
            width: 100%;
        }
        .chatbot-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.3rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            background: rgba(255,255,255,0.88);
            border: 1px solid rgba(79,70,229,0.10);
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            color: var(--text-secondary);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            transition: all 0.3s ease;
        }
        .chatbot-status-badge.speaking { border-color: rgba(99,102,241,0.3); color: var(--accent-primary); }
        .chatbot-status-badge.listening { border-color: rgba(244,63,94,0.3); color: var(--accent-rose); }

        .status-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: #22c55e;
            display: inline-block;
            flex-shrink: 0;
            transition: background 0.3s;
        }
        .chatbot-status-badge.speaking .status-dot  { background: var(--accent-primary); animation: dot-pulse 1.4s infinite; }
        .chatbot-status-badge.listening .status-dot { background: var(--accent-rose);    animation: dot-pulse 1.4s infinite; }
        @keyframes dot-pulse {
            0%,100% { transform: scale(1);   opacity: 1; }
            50%      { transform: scale(1.4); opacity: 0.4; }
        }

        /* ── Greeting — white card ── */
        .chatbot-greeting {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(79, 70, 229, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.05);
            border-radius: 1.25rem;
            padding: 1rem 1.75rem;
            text-align: center;
            font-size: clamp(0.98rem, 1.25vw, 1.18rem);
            font-weight: 500;
            color: var(--text-primary);
            line-height: 1.55;
            max-width: 520px;
            margin: 0.5rem auto;
        }
        .brand-highlight { color: var(--accent-primary); font-weight: 700; }

        .chatbot-md-inline p { margin: 0; display: inline; }
        .chatbot-md-inline ul,
        .chatbot-md-inline ol { text-align: left; margin: 0.25rem 0 0; padding-left: 1.25rem; }

        .chatbot-typing-inline {
            display: inline-flex; gap: 4px; align-items: center;
        }
        .chatbot-typing-inline span {
            width: 6px; height: 6px; border-radius: 50%;
            background: var(--text-muted);
            animation: chatbot-bounce 1.2s ease-in-out infinite;
        }
        .chatbot-typing-inline span:nth-child(2) { animation-delay: .2s; }
        .chatbot-typing-inline span:nth-child(3) { animation-delay: .4s; }
        @keyframes chatbot-bounce {
            0%,80%,100% { transform: translateY(0); }
            40% { transform: translateY(-5px); }
        }

        .chatbot-error-inline {
            color: #e11d48; font-size: 0.85rem;
        }

        /* ── Controls ── */
        .chatbot-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.65rem;
            flex-wrap: wrap;
        }

        .ctrl-btn {
            display: flex; align-items: center; justify-content: center;
            border: none; cursor: pointer;
            transition: all 0.22s cubic-bezier(0.34,1.56,0.64,1);
            flex-shrink: 0;
        }

        /* Mic — primary, larger */
        .ctrl-mic {
            width: 62px; height: 62px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-primary), #4f46e5);
            color: #fff;
            box-shadow: 0 6px 20px rgba(79,70,229,0.35);
        }
        .ctrl-mic svg { width: 26px; height: 26px; }
        .ctrl-mic:hover { transform: scale(1.07); box-shadow: 0 8px 28px rgba(79,70,229,0.45); }
        .ctrl-mic:active { transform: scale(0.94); }
        .ctrl-mic.listening {
            background: linear-gradient(135deg, var(--accent-rose), #e11d48);
            box-shadow: 0 6px 20px rgba(225,29,72,0.4);
            animation: mic-pulse 1.5s infinite;
        }
        @keyframes mic-pulse {
            0%   { box-shadow: 0 0 0 0   rgba(225,29,72,0.5); }
            70%  { box-shadow: 0 0 0 14px rgba(225,29,72,0); }
            100% { box-shadow: 0 0 0 0   rgba(225,29,72,0); }
        }

        /* Keyboard — secondary */
        .ctrl-kb {
            width: 48px; height: 48px;
            border-radius: 50%;
            background: #fff;
            border: 1px solid #cbd5e1 !important;
            color: var(--text-secondary);
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }
        .ctrl-kb svg { width: 20px; height: 20px; }
        .ctrl-kb:hover, .ctrl-kb.active {
            border-color: var(--accent-primary) !important;
            color: var(--accent-primary);
            box-shadow: 0 4px 14px rgba(79,70,229,0.15);
        }

        /* Utility (emoji) */
        .ctrl-util {
            width: 40px; height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.85);
            border: 1px solid #e2e8f0 !important;
            font-size: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            backdrop-filter: blur(4px);
        }
        .ctrl-util:hover { background: #f8fafc; border-color: #cbd5e1 !important; }
        .ctrl-stop { background: #ffe4e6 !important; border-color: #fecaca !important; }

        /* ── Keyboard text input ── */
        .chatbot-text-input-container {
            display: flex; gap: 0.5rem; width: 100%;
            background: #fff; border: 1px solid #e2e8f0;
            border-radius: 12px; padding: 0.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
        .kb-enter { transition: all 0.25s ease-out; }
        .kb-enter-start { opacity: 0; transform: translateY(-8px); }
        .kb-enter-end   { opacity: 1; transform: translateY(0); }
        .kb-leave { transition: all 0.2s ease-in; }
        .kb-leave-end { opacity: 0; transform: translateY(-8px); }

        .chatbot-textarea {
            flex: 1; resize: none; border: none; background: transparent;
            padding: 0.5rem; font-size: 0.95rem; font-family: inherit;
            outline: none; color: var(--text-primary);
            max-height: 80px; line-height: 1.4;
        }
        .chatbot-send-msg-btn {
            width: 40px; height: 40px; border-radius: 8px;
            background: var(--accent-primary); color: #fff; border: none;
            cursor: pointer; display: flex; align-items: center;
            justify-content: center; font-size: 16px; transition: background 0.2s;
        }
        .chatbot-send-msg-btn:hover { background: var(--accent-glow); }
    </style>
</div>
