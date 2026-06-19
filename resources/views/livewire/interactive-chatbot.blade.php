<div
    class="chatbot-wrapper"
    x-data="{
        open: false,
        ttsEnabled: true,
        isSpeaking: false,
        isPaused: false,
        scrollToBottom() {
            this.$nextTick(() => {
                const el = document.getElementById('chat-messages');
                if (el) el.scrollTop = el.scrollHeight;
            });
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
    x-on:chatbot-scrolled.window="scrollToBottom()"
    x-on:chatbot-speak.window="if (ttsEnabled) { window.speakText($event.detail.text); }"
    @tts-started.window="isSpeaking = true; isPaused = false"
    @tts-ended.window="isSpeaking = false; isPaused = false"
>
    {{-- ── Toggle Button ─────────────────────────── --}}
    <button
        @click="open = !open"
        class="chatbot-fab"
        title="Tanya Asisten Virtual"
        aria-label="Buka Chatbot"
    >
        <span x-show="!open" class="chatbot-fab-icon">💬</span>
        <span x-show="open"  class="chatbot-fab-icon">✕</span>
    </button>

    {{-- ── Chat Window ───────────────────────────── --}}
    <div
        x-show="open"
        x-transition:enter="chatbot-enter"
        x-transition:enter-start="chatbot-enter-start"
        x-transition:enter-end="chatbot-enter-end"
        x-transition:leave="chatbot-leave"
        x-transition:leave-start="chatbot-leave-end"
        x-transition:leave-end="chatbot-leave-start"
        class="chatbot-window"
        style="display:none"
    >
        {{-- Header --}}
        <div class="chatbot-header">
            <div class="chatbot-header-avatar">🤖</div>
            <div>
                <div class="chatbot-header-title">VISITA Assistant</div>
                <div class="chatbot-header-sub">AI · Selalu siap membantu</div>
            </div>
            {{-- TTS Controls: Stop / Pause-Resume (muncul hanya saat AI berbicara) --}}
            <div class="chatbot-speech-controls" x-show="isSpeaking" x-transition.opacity>
                <button
                    @click="stopSpeech()"
                    class="chatbot-ctrl-btn chatbot-ctrl-stop"
                    title="Hentikan suara"
                >🛑</button>
                <button
                    @click="pauseResumeSpeech()"
                    class="chatbot-ctrl-btn chatbot-ctrl-pause"
                    :title="isPaused ? 'Lanjutkan suara' : 'Jeda suara'"
                >
                    <span x-show="!isPaused">⏸️</span>
                    <span x-show="isPaused">▶️</span>
                </button>
            </div>
            <button
                @click="toggleTts()"
                class="chatbot-tts-btn"
                :title="ttsEnabled ? 'Matikan suara AI' : 'Nyalakan suara AI'"
            >
                <span x-show="ttsEnabled">🔊</span>
                <span x-show="!ttsEnabled">🔇</span>
            </button>
            <button
                wire:click="clearHistory"
                class="chatbot-clear-btn"
                title="Hapus riwayat"
            >🗑️</button>
        </div>

        {{-- Messages Area --}}
        <div id="chat-messages" class="chatbot-messages">

            {{-- Empty state --}}
            @if(empty($messages))
            <div class="chatbot-empty">
                <div class="chatbot-empty-icon">👋</div>
                <p>Halo! Saya VISITA Assistant.<br>Ada yang bisa saya bantu?</p>
            </div>
            @endif

            {{-- Chat bubbles --}}
            @foreach($messages as $msg)
            <div class="chatbot-msg-row chatbot-msg-row--{{ $msg['role'] }}">
                @if($msg['role'] === 'assistant')
                <div class="chatbot-avatar">🤖</div>
                @endif
                <div class="chatbot-bubble chatbot-bubble--{{ $msg['role'] }}">
                    @if($msg['role'] === 'assistant')
                        {{-- Render Markdown untuk respons AI --}}
                        <div class="chatbot-markdown"
                             x-data="{ md: @js($msg['content']) }"
                             x-html="window.marked ? marked.parse(md) : md"
                        ></div>
                    @else
                        {!! nl2br(e($msg['content'])) !!}
                    @endif
                </div>
                @if($msg['role'] === 'user')
                <div class="chatbot-avatar chatbot-avatar--user">👤</div>
                @endif
            </div>
            @endforeach

            {{-- Loading dots --}}
            @if($isLoading)
            <div class="chatbot-msg-row chatbot-msg-row--assistant">
                <div class="chatbot-avatar">🤖</div>
                <div class="chatbot-bubble chatbot-bubble--assistant chatbot-typing">
                    <span></span><span></span><span></span>
                </div>
            </div>
            @endif

            {{-- Error --}}
            @if($error)
            <div class="chatbot-error">⚠️ {{ $error }}</div>
            @endif
        </div>

        {{-- Input Area --}}
        <div class="chatbot-input-area">
            <textarea
                wire:model="inputMessage"
                wire:keydown.enter.prevent="sendMessage"
                class="chatbot-input"
                placeholder="Ketik pesan Anda..."
                rows="1"
                x-on:input="$el.style.height='auto'; $el.style.height=$el.scrollHeight+'px'"
                @if($isLoading) disabled @endif
            ></textarea>
            <button
                wire:click="sendMessage"
                wire:loading.attr="disabled"
                class="chatbot-send-btn"
                title="Kirim"
            >
                <span wire:loading.remove wire:target="sendMessage">➤</span>
                <span wire:loading wire:target="sendMessage" class="chatbot-spinner">⏳</span>
            </button>
        </div>
    </div>
    {{-- marked.js untuk render Markdown --}}
    <script src="https://cdn.jsdelivr.net/npm/marked@9/marked.min.js"></script>
    <script>
        if (window.marked) {
            marked.setOptions({ breaks: true, gfm: true });
        }

        /* ══════════════════════════════════════════════════════════
           TTS ENGINE — Web Speech API dengan State Management
        ══════════════════════════════════════════════════════════ */
        (function () {
            'use strict';

            let indoVoice = null;

            // Pre-load suara Indonesia
            function loadVoices() {
                const voices = window.speechSynthesis?.getVoices() ?? [];
                indoVoice = voices.find(v => v.lang === 'id-ID' || v.name.includes('Indonesia')) ?? null;
            }

            if ('speechSynthesis' in window) {
                window.speechSynthesis.getVoices();
                window.speechSynthesis.addEventListener('voiceschanged', loadVoices);
                loadVoices();
            }

            // ── Dispatch helper (Alpine mendengarkan via @tts-started / @tts-ended) ──
            function fireTtsEvent(name) {
                window.dispatchEvent(new CustomEvent(name));
            }

            /**
             * speakText(text) — Global TTS function
             * Rate 1.25 untuk artikulasi lebih cepat tapi tetap jelas.
             */
            window.speakText = function (text) {
                if (!text || !('speechSynthesis' in window)) return;

                window.speechSynthesis.cancel();

                // Strip markdown untuk TTS yang natural
                const plain = text
                    .replace(/[*_`#>~|\-]+/g, ' ')
                    .replace(/\n+/g, '. ')
                    .replace(/\s{2,}/g, ' ')
                    .trim();

                if (!plain) return;

                const utt  = new SpeechSynthesisUtterance(plain);
                utt.lang   = 'id-ID';
                utt.rate   = 1.25;   // Lebih cepat, artikulasi tetap jelas
                utt.pitch  = 1.0;
                utt.volume = 1.0;
                if (indoVoice) utt.voice = indoVoice;

                utt.onstart  = () => fireTtsEvent('tts-started');
                utt.onend    = () => fireTtsEvent('tts-ended');
                utt.onerror  = () => fireTtsEvent('tts-ended');
                utt.onpause  = () => {}; // state dikelola Alpine
                utt.onresume = () => {};

                window.speechSynthesis.speak(utt);
            };

            // ── Global Control Functions ──────────────────────────────
            window.stopAiSpeech = function () {
                window.speechSynthesis?.cancel();
                fireTtsEvent('tts-ended');
            };

            window.pauseAiSpeech = function () {
                window.speechSynthesis?.pause();
            };

            window.resumeAiSpeech = function () {
                window.speechSynthesis?.resume();
            };
        })();
    </script>

    {{-- Inline styles --}}
    <style>
/* ── Chatbot Layout ────────────────────── */
.chatbot-wrapper {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 9999;
    font-family: 'Poppins', sans-serif;
}

/* FAB Button */
.chatbot-fab {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    color: white;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(79, 70, 229, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    transition: transform 0.2s, box-shadow 0.2s;
    margin-left: auto;
}
.chatbot-fab:hover { transform: scale(1.1); box-shadow: 0 6px 28px rgba(79,70,229,.55); }
.chatbot-fab-icon { line-height: 1; }

/* Window */
.chatbot-window {
    position: absolute;
    bottom: 68px;
    right: 0;
    width: 360px;
    max-height: 520px;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,.18);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid #e5e7eb;
}

/* Transitions */
.chatbot-enter { transition: all .25s cubic-bezier(.4,0,.2,1); }
.chatbot-enter-start { opacity:0; transform: translateY(16px) scale(.97); }
.chatbot-enter-end   { opacity:1; transform: translateY(0) scale(1); }
.chatbot-leave { transition: all .2s ease; }
.chatbot-leave-end   { opacity:0; transform: translateY(16px) scale(.97); }

/* Header */
.chatbot-header {
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    color: white;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.chatbot-header-avatar { font-size: 26px; }
.chatbot-header-title  { font-weight: 600; font-size: 15px; }
.chatbot-header-sub    { font-size: 11px; opacity: .8; }
.chatbot-tts-btn,
.chatbot-clear-btn {
    margin-left: auto;
    background: rgba(255,255,255,.15);
    border: none;
    border-radius: 8px;
    padding: 4px 8px;
    cursor: pointer;
    font-size: 14px;
    color: white;
    transition: background .2s;
}
.chatbot-tts-btn { margin-left: auto; }
.chatbot-clear-btn { margin-left: 4px; }
.chatbot-tts-btn:hover,
.chatbot-clear-btn:hover { background: rgba(255,255,255,.28); }

/* Speech control buttons (Stop / Pause-Resume) */
.chatbot-speech-controls {
    display: flex;
    align-items: center;
    gap: 3px;
    margin-left: auto;
}
.chatbot-ctrl-btn {
    background: rgba(255,255,255,.18);
    border: none;
    border-radius: 6px;
    padding: 3px 7px;
    cursor: pointer;
    font-size: 13px;
    color: white;
    transition: background .2s, transform .1s;
    line-height: 1;
}
.chatbot-ctrl-btn:hover { background: rgba(255,255,255,.32); transform: scale(1.1); }
.chatbot-ctrl-stop:active { transform: scale(.9); }

/* Messages */
.chatbot-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px 14px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    scroll-behavior: smooth;
}
.chatbot-messages::-webkit-scrollbar { width: 4px; }
.chatbot-messages::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }

/* Empty state */
.chatbot-empty {
    text-align: center;
    color: #9ca3af;
    font-size: 13px;
    margin-top: 20px;
}
.chatbot-empty-icon { font-size: 36px; margin-bottom: 8px; }

/* Message rows */
.chatbot-msg-row {
    display: flex;
    align-items: flex-end;
    gap: 8px;
}
.chatbot-msg-row--user { flex-direction: row; justify-content: flex-end; }
.chatbot-msg-row--assistant { flex-direction: row; }

.chatbot-avatar { font-size: 20px; flex-shrink: 0; }
.chatbot-avatar--user { order: 1; }

/* Bubbles */
.chatbot-bubble {
    max-width: 76%;
    padding: 10px 14px;
    border-radius: 16px;
    font-size: 13.5px;
    line-height: 1.5;
    word-break: break-word;
}
.chatbot-bubble--user {
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    color: white;
    border-bottom-right-radius: 4px;
}
.chatbot-bubble--assistant {
    background: #f3f4f6;
    color: #111827;
    border-bottom-left-radius: 4px;
}

/* Markdown rendered content */
.chatbot-markdown { font-size: 13.5px; line-height: 1.6; }
.chatbot-markdown p  { margin: 0 0 6px; }
.chatbot-markdown p:last-child { margin-bottom: 0; }
.chatbot-markdown strong { font-weight: 700; }
.chatbot-markdown em { font-style: italic; }
.chatbot-markdown ul, .chatbot-markdown ol { padding-left: 18px; margin: 4px 0 6px; }
.chatbot-markdown li { margin-bottom: 2px; }
.chatbot-markdown code {
    background: #e5e7eb;
    border-radius: 4px;
    padding: 1px 5px;
    font-family: monospace;
    font-size: 12.5px;
}
.chatbot-markdown pre {
    background: #1f2937;
    color: #f9fafb;
    border-radius: 8px;
    padding: 10px 12px;
    overflow-x: auto;
    font-size: 12px;
    margin: 6px 0;
}
.chatbot-markdown pre code { background: none; padding: 0; color: inherit; }
.chatbot-markdown h1,.chatbot-markdown h2,.chatbot-markdown h3 {
    font-weight: 600;
    margin: 6px 0 4px;
    line-height: 1.3;
}
.chatbot-markdown h1 { font-size: 16px; }
.chatbot-markdown h2 { font-size: 14.5px; }
.chatbot-markdown h3 { font-size: 13.5px; }
.chatbot-markdown blockquote {
    border-left: 3px solid #d1d5db;
    padding-left: 10px;
    color: #6b7280;
    margin: 4px 0;
}
.chatbot-markdown a { color: #4f46e5; text-decoration: underline; }
.chatbot-markdown hr { border: none; border-top: 1px solid #e5e7eb; margin: 8px 0; }

/* Typing dots */
.chatbot-typing {
    display: flex;
    gap: 5px;
    align-items: center;
    padding: 12px 16px;
}
.chatbot-typing span {
    width: 7px; height: 7px;
    border-radius: 50%;
    background: #9ca3af;
    animation: chatbot-bounce 1.2s ease-in-out infinite;
}
.chatbot-typing span:nth-child(2) { animation-delay: .2s; }
.chatbot-typing span:nth-child(3) { animation-delay: .4s; }
@keyframes chatbot-bounce {
    0%,80%,100% { transform: translateY(0); }
    40%          { transform: translateY(-6px); }
}

/* Error */
.chatbot-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    border-radius: 10px;
    padding: 8px 12px;
    font-size: 12.5px;
}

/* Input area */
.chatbot-input-area {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    padding: 12px 14px;
    border-top: 1px solid #e5e7eb;
    background: #fafafa;
}
.chatbot-input {
    flex: 1;
    resize: none;
    border: 1.5px solid #e5e7eb;
    border-radius: 12px;
    padding: 9px 12px;
    font-size: 13.5px;
    font-family: inherit;
    outline: none;
    background: #fff;
    max-height: 100px;
    overflow-y: auto;
    transition: border-color .2s;
    line-height: 1.4;
}
.chatbot-input:focus { border-color: #7c3aed; }
.chatbot-input:disabled { background: #f3f4f6; }
.chatbot-send-btn {
    width: 38px;
    height: 38px;
    flex-shrink: 0;
    border-radius: 50%;
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    color: white;
    border: none;
    cursor: pointer;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform .15s, opacity .15s;
}
.chatbot-send-btn:hover  { transform: scale(1.1); }
.chatbot-send-btn:disabled { opacity: .5; cursor: not-allowed; transform: none; }
.chatbot-spinner { animation: spin .7s linear infinite; display: inline-block; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Mobile */
@media (max-width: 480px) {
    .chatbot-window { width: calc(100vw - 32px); right: 0; }
    .chatbot-wrapper { bottom: 16px; right: 16px; }
}
    </style>
</div>
