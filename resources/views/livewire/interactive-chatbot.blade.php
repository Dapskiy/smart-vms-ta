<div
    class="chatbot-wrapper"
    x-data="{
        hasChatted: {{ empty($messages) ? 'false' : 'true' }},
        ttsEnabled: true,
        isSpeaking: false,
        isPaused: false,
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

            recognition.onstart = () => { 
                this.isListening = true; 
                this.hasChatted = true; 
            };
            recognition.onresult = (event) => {
                const transcript = event.results[0][0].transcript;
                this.$wire.set('inputMessage', transcript);
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
        },
        closeChatbot() {
            this.hasChatted = false;
            this.stopSpeech();
            this.$wire.clearHistory();
        }
    }"
    :class="{ 'is-chatting': hasChatted }"
    x-on:chatbot-speak.window="if (ttsEnabled) { window.speakText($event.detail.text); }"
    @tts-started.window="isSpeaking = true; isPaused = false"
    @tts-ended.window="isSpeaking = false; isPaused = false"
>
    <div class="kiosk-split-layout">
        
        <!-- CLOSE / BACK BUTTON (pojok kanan atas) -->
        <button
            class="chatbot-close-btn"
            @click="closeChatbot()"
            x-show="hasChatted"
            x-transition.opacity.duration.300ms
            title="Kembali ke Menu Utama"
            style="display:none;"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>

        <!-- LEFT PANEL: AI AVATAR -->
        <div class="kiosk-left-panel">
            <div class="left-panel-content">
                <!-- 1. Large Animated AI Avatar Box -->
                <div class="avatar-box">
                    <!-- Speech indicator ring around avatar -->
                    <div class="avatar-speech-ring" :class="{ 'speaking': isSpeaking && !isPaused, 'listening': isListening }"></div>
                    
                    <!-- GREETING SEQUENCE (Shown before chat starts) -->
                    <div 
                        class="avatar-video-element speaking-sequence"
                        :class="{ 'is-speaking': !hasChatted }"
                        x-show="!hasChatted"
                    >
                        <!-- Image 1 (Idle & First frame) -->
                        <img src="{{ asset('assets/images/chatbot/avatar-greeting-1.png') }}" class="avatar-seq img-1" alt="AI Avatar">
                        <!-- Image 2 (Second frame) -->
                        <img src="{{ asset('assets/images/chatbot/avatar-greeting-2.png') }}" class="avatar-seq img-2" alt="AI Avatar">
                    </div>

                    <!-- CHATTING SEQUENCE (Shown after chat starts) -->
                    <div 
                        class="avatar-video-element speaking-sequence-3"
                        :class="{ 'is-speaking': isSpeaking && !isPaused }"
                        x-show="hasChatted"
                        style="display:none;"
                    >
                        <img src="{{ asset('assets/images/chatbot/avatar-speaking-1.png') }}" class="avatar-seq img-3-1" alt="AI Avatar">
                        <img src="{{ asset('assets/images/chatbot/avatar-speaking-2.png') }}" class="avatar-seq img-3-2" alt="AI Avatar">
                        <img src="{{ asset('assets/images/chatbot/avatar-speaking-3.png') }}" class="avatar-seq img-3-3" alt="AI Avatar">
                    </div>
                </div>

                <!-- FLOATING SOUND CONTROLS (di bawah avatar) -->
                <div class="avatar-sound-controls" x-show="hasChatted" x-transition.opacity.duration.300ms style="display:none;">
                    <!-- Mute/Unmute -->
                    <button @click="toggleTts()" class="snd-ctrl-btn" :class="{ 'active': !ttsEnabled }" :title="ttsEnabled ? 'Bisukan Suara' : 'Aktifkan Suara'">
                        <svg x-show="ttsEnabled" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
                        <svg x-show="!ttsEnabled" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg>
                    </button>
                    <!-- Pause/Resume (only when speaking) -->
                    <button @click="pauseResumeSpeech()" class="snd-ctrl-btn" x-show="isSpeaking" x-cloak :title="isPaused ? 'Lanjutkan' : 'Jeda'">
                        <svg x-show="!isPaused" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                        <svg x-show="isPaused" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    </button>
                    <!-- Stop (only when speaking) -->
                    <button @click="stopSpeech()" class="snd-ctrl-btn snd-ctrl-stop" x-show="isSpeaking" x-cloak title="Hentikan Suara">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/></svg>
                    </button>
                </div>
                
                <!-- 2. Status Indicator Badge -->
                <div class="avatar-status-badge">
                    <span class="status-badge-dot" :class="{ 'speaking': isSpeaking && !isPaused, 'listening': isListening, 'thinking': $wire.isLoading }"></span>
                    <span x-show="isListening" x-cloak>Listening</span>
                    <span x-show="isSpeaking && !isPaused" x-cloak>Speaking</span>
                    <span x-show="$wire.isLoading" x-cloak>Thinking</span>
                    <span x-show="!isListening && !(isSpeaking && !isPaused) && !$wire.isLoading">Idle</span>
                </div>
                
                <!-- 3. Greeting Card (only shown initially) -->
                <div class="avatar-greeting-card" x-show="!hasChatted" x-transition>
                    @if($isLoading && !empty($messages))
                        <div class="chatbot-typing-inline">
                            <span></span><span><span></span>
                        </div>
                    @elseif($error)
                        <div class="chatbot-error-inline">⚠️ {{ $error }}</div>
                    @elseif(empty($messages))
                        <h2>Hello!</h2>
                        <p>I'm <span class="brand-highlight">Visita</span>, your AI Receptionist.</p>
                        <p class="greeting-subtitle">How may I help you today?</p>
                    @else
                        @php
                            $latestAssistantMsg = null;
                            foreach (array_reverse($messages) as $msg) {
                                if ($msg['role'] === 'assistant') { $latestAssistantMsg = $msg['content']; break; }
                            }
                        @endphp
                        @if($latestAssistantMsg)
                            <span
                                wire:key="ai-left-reply-{{ md5($latestAssistantMsg) }}"
                                x-data="{ md: @js($latestAssistantMsg) }"
                                x-init="md = @js($latestAssistantMsg)"
                                x-html="window.marked ? marked.parse(md) : md"
                                class="chatbot-md-inline"
                            ></span>
                        @else
                            <h2>Hello!</h2>
                            <p>I'm <span class="brand-highlight">Visita</span>, your AI Receptionist.</p>
                            <p class="greeting-subtitle">How may I help you today?</p>
                        @endif
                    @endif
                </div>
            </div>
        </div>
        
        <!-- RIGHT PANEL (The morphing input/chat box) -->
        <div class="kiosk-right-panel">
            <div class="chat-card-panel">
                
                <!-- Chat Conversation Area (Scrollable, hidden initially) -->
                <div class="chat-history-scroll" x-show="hasChatted" x-transition:enter="transition ease-out duration-400 delay-200" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" id="chat-history-container" x-init="$el.scrollTop = $el.scrollHeight" @chatbot-scrolled.window="setTimeout(() => { $el.scrollTop = $el.scrollHeight; }, 100)">
                    @if(empty($messages))
                        <div class="chat-empty-state">
                            <div class="empty-avatar-wave">👋</div>
                            <h3>Welcome!</h3>
                            <p>Touch the screen, select a quick suggestion, or press the mic to begin.</p>
                        </div>
                    @else
                        @foreach($messages as $msg)
                            <div class="chat-bubble-row {{ $msg['role'] === 'user' ? 'user-row' : 'assistant-row' }}">
                                @if($msg['role'] === 'assistant')
                                    <div class="chat-bubble assistant-bubble">
                                        <span
                                            wire:key="msg-{{ md5($msg['content']) }}"
                                            x-data="{ md: @js($msg['content']) }"
                                            x-html="window.marked ? window.marked.parse(md) : md"
                                        ></span>
                                    </div>
                                @else
                                    <div class="chat-bubble user-bubble">
                                        {{ $msg['content'] }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @endif
                    
                    @if($isLoading)
                        <div class="chat-bubble-row assistant-row">
                            <div class="chat-bubble assistant-bubble typing-bubble">
                                <div class="typing-indicator">
                                    <span></span><span></span><span></span>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    @if($error)
                        <div class="chat-bubble-row assistant-row">
                            <div class="chat-bubble error-bubble">
                                ⚠️ {{ $error }}
                            </div>
                        </div>
                    @endif
                </div>
                
                <!-- Registration Confirmation Card -->
                @if($showConfirmation && !empty($regData))
                <div class="reg-confirm-card" wire:key="reg-confirm">
                    <div class="reg-confirm-header">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        <span>Konfirmasi Pendaftaran</span>
                    </div>
                    <div class="reg-confirm-grid">
                        <div class="reg-item"><label>Nama</label><span>{{ $regData['name'] ?? '-' }}</span></div>
                        <div class="reg-item"><label>Perusahaan</label><span>{{ $regData['company'] ?? '-' }}</span></div>
                        <div class="reg-item"><label>Telepon</label><span>{{ $regData['phone'] ?? '-' }}</span></div>
                        <div class="reg-item"><label>Tujuan</label><span>{{ $regData['purpose'] ?? '-' }}</span></div>
                        <div class="reg-item"><label>Menemui</label><span>{{ $regData['pic_name'] ?? '-' }}</span></div>
                        <div class="reg-item"><label>Departemen</label><span>{{ $regData['department'] ?? '-' }}</span></div>
                        <div class="reg-item"><label>Tipe</label><span>{{ $regData['type'] === 'walk-in' ? 'Walk-In (Sekarang)' : 'Janji Temu' }}</span></div>
                        @if(($regData['type'] ?? '') === 'appointment')
                        <div class="reg-item"><label>Tanggal</label><span>{{ $regData['visit_date'] ?? '-' }}</span></div>
                        @endif
                    </div>
                    <div class="reg-confirm-actions">
                        <button type="button" class="reg-btn-confirm" wire:click="confirmRegistration">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><circle cx="12" cy="12" r="10" opacity=".2"/></svg>
                            Konfirmasi & Scan Wajah
                        </button>
                        <button type="button" class="reg-btn-cancel" wire:click="cancelRegistration">
                            Batal
                        </button>
                    </div>
                </div>
                @endif
                
                <!-- Suggested Chips -->
                <div class="chat-suggested-chips" :class="{ 'centered-chips': !hasChatted }">
                    <button type="button" class="chip-btn" @click="$wire.selectSuggestedChip('Meet someone'); hasChatted = true">Meet someone</button>
                    <button type="button" class="chip-btn" @click="$wire.selectSuggestedChip('Book appointment'); hasChatted = true">Book appointment</button>
                    <button type="button" class="chip-btn" @click="$wire.selectSuggestedChip('Find employee'); hasChatted = true">Find employee</button>
                    <button type="button" class="chip-btn" @click="$wire.selectSuggestedChip('Check today\'s schedule'); hasChatted = true" x-show="hasChatted">Check schedule</button>
                </div>
                
                <!-- Chat Input Row -->
                <div class="chat-input-row" :class="{ 'initial-input-row': !hasChatted }">
                    <textarea 
                        wire:model="inputMessage" 
                        class="chat-textarea-input" 
                        placeholder="Type your message..."
                        rows="1"
                        x-on:input="$el.style.height='auto'; $el.style.height=$el.scrollHeight+'px'"
                        x-on:keydown.enter.prevent="if (!$event.shiftKey) { $wire.sendMessage(); hasChatted = true; }"
                        @if($isLoading) disabled @endif
                    ></textarea>
                    
                    <div class="chat-input-actions">
                        <button type="button" class="action-btn-mic" @click="startDictation()" :class="{ 'is-listening': isListening }" @if($isLoading) disabled @endif>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/>
                                <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                                <line x1="12" y1="19" x2="12" y2="22"/>
                            </svg>
                        </button>
                        <button type="button" class="action-btn-send" @click="$wire.sendMessage(); hasChatted = true" @if($isLoading) disabled @endif>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="22" y1="2" x2="11" y2="13"/>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
            </div>
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
            window.stopAiSpeech    = () => { window.speechSynthesis?.cancel(); fireTtsEvent('tts-ended'); };
            window.pauseAiSpeech   = () => { window.speechSynthesis?.pause(); };
            window.resumeAiSpeech  = () => { window.speechSynthesis?.resume(); };
        })();
    </script>

    <style>
        [x-cloak] { display: none !important; }

        .chatbot-wrapper {
            width: 100%;
            flex: 1;
            display: flex;
            flex-direction: column;
            font-family: 'Poppins', sans-serif;
            z-index: 10;
            min-height: 0;
        }

        /* ── Close / Back Button ──────────────────────── */
        .chatbot-close-btn {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            z-index: 50;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(8px);
            color: #475569;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        .chatbot-close-btn:hover {
            background: #f1f5f9;
            color: #0f172a;
            transform: scale(1.08);
        }
        .chatbot-close-btn svg {
            width: 18px;
            height: 18px;
        }

        /* ── Floating Sound Controls ──────────────────── */
        .avatar-sound-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 0.25rem;
            margin-bottom: 0.25rem;
        }
        .snd-ctrl-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #475569;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
        .snd-ctrl-btn:hover {
            background: #f1f5f9;
            color: #0f172a;
            transform: scale(1.1);
        }
        .snd-ctrl-btn.active {
            background: #fef2f2;
            color: #ef4444;
            border-color: #fecaca;
        }
        .snd-ctrl-btn.snd-ctrl-stop:hover {
            background: #fef2f2;
            color: #ef4444;
            border-color: #fecaca;
        }
        .snd-ctrl-btn svg {
            width: 16px;
            height: 16px;
        }

        .kiosk-split-layout {
            position: relative;
            display: flex;
            flex-direction: row;
            width: 100%;
            height: 100%;
            min-height: 0;
            overflow: hidden;
        }

        /* LEFT PANEL styling */
        .kiosk-left-panel {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            height: 100%;
            padding: 1rem;
            padding-top: 3vh;
            transition: width 0.7s cubic-bezier(0.4, 0, 0.2, 1), padding 0.7s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .chatbot-wrapper.is-chatting .kiosk-left-panel {
            width: 55%;
            padding-top: 8vh;
        }

        .left-panel-content {
            width: 100%;
            max-width: 520px;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: all 0.6s ease;
        }

        .avatar-box {
            position: relative;
            width: 100%;
            height: 38vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: visible;
            transition: height 0.7s cubic-bezier(0.4, 0, 0.2, 1), max-height 0.7s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Enlarge avatar box when actively chatting */
        .chatbot-wrapper.is-chatting .avatar-box {
            height: 55vh;
            max-height: 550px;
        }

        /* Portrait Aspect Ratios */
        @media (max-aspect-ratio: 1/1) {
            .avatar-box {
                height: 38vh;
            }
            .chatbot-wrapper.is-chatting .avatar-box {
                height: 55vh;
            }
        }

        /* Landscape Aspect Ratios (Laptops/PCs) */
        @media (min-aspect-ratio: 1/1) {
            .avatar-box {
                height: 28vh;
                max-height: 260px;
            }
            .chatbot-wrapper.is-chatting .avatar-box {
                height: 48vh;
                max-height: 420px;
            }
            .avatar-greeting-card {
                padding: 0.75rem 1.25rem !important;
                min-height: 80px !important;
                margin: 0.25rem 0 !important;
            }
            .avatar-greeting-card h2 {
                font-size: 1.3rem !important;
                margin-bottom: 0.1rem !important;
            }
            .avatar-greeting-card p {
                font-size: 0.88rem !important;
            }
            .greeting-subtitle {
                margin-top: 0.25rem !important;
            }
            .avatar-status-badge {
                margin: 0.3rem auto !important;
                padding: 0.25rem 0.75rem !important;
                font-size: 0.78rem !important;
            }
        }

        .avatar-video-element {
            height: 100%;
            width: auto;
            max-width: 100%;
            object-fit: contain;
            mix-blend-mode: multiply;
            filter: brightness(1.12) contrast(1.1);
        }

        .avatar-video-element.speaking-sequence {
            position: relative;
            width: 100%;
        }

        .avatar-seq {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* Initial Idle state: Image 1 is visible, Image 2 is hidden */
        .avatar-seq.img-1 {
            opacity: 1;
        }
        .avatar-seq.img-2 {
            opacity: 0;
        }

        /* Speaking animation with empty gap (kosong) between fades */
        /* Total duration 12s: 5s hold -> 0.4s fade-out -> 0.2s gap -> 0.4s fade-in -> 5s hold -> 0.4s fade-out -> 0.2s gap -> 0.4s fade-in */
        .avatar-video-element.speaking-sequence.is-speaking .avatar-seq.img-1 {
            animation: avatar-img1 12s infinite linear;
        }
        .avatar-video-element.speaking-sequence.is-speaking .avatar-seq.img-2 {
            animation: avatar-img2 12s infinite linear;
        }

        @keyframes avatar-img1 {
            0%, 41.7%    { opacity: 1; }
            45%, 96.7%   { opacity: 0; }
            100%         { opacity: 1; }
        }

        @keyframes avatar-img2 {
            0%, 46.7%    { opacity: 0; }
            50%, 91.7%   { opacity: 1; }
            95%, 100%    { opacity: 0; }
        }

        /* 3-Frame Speaking Animation */
        .avatar-video-element.speaking-sequence-3 {
            position: relative;
            width: 100%;
        }

        .avatar-seq.img-3-1 { opacity: 1; }
        .avatar-seq.img-3-2 { opacity: 0; }
        .avatar-seq.img-3-3 { opacity: 0; }

        .avatar-video-element.speaking-sequence-3.is-speaking .avatar-seq.img-3-1 {
            animation: speak-frame-1 18s infinite linear;
        }
        .avatar-video-element.speaking-sequence-3.is-speaking .avatar-seq.img-3-2 {
            animation: speak-frame-2 18s infinite linear;
        }
        .avatar-video-element.speaking-sequence-3.is-speaking .avatar-seq.img-3-3 {
            animation: speak-frame-3 18s infinite linear;
        }

        @keyframes speak-frame-1 { 
            0%, 27.78%    { opacity: 1; }
            30%, 97.77%   { opacity: 0; }
            100%          { opacity: 1; }
        }
        @keyframes speak-frame-2 { 
            0%, 31.1%     { opacity: 0; }
            33.33%, 61.11%{ opacity: 1; }
            63.33%, 100%  { opacity: 0; }
        }
        @keyframes speak-frame-3 { 
            0%, 64.43%    { opacity: 0; }
            66.67%, 94.44%{ opacity: 1; }
            96.67%, 100%  { opacity: 0; }
        }

        .avatar-speech-ring {
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            border: 3px solid transparent;
            z-index: 1;
            pointer-events: none;
            transition: all 0.4s ease;
        }

        .avatar-speech-ring.speaking {
            border-color: rgba(37, 99, 235, 0.25);
            box-shadow: 0 0 40px rgba(37, 99, 235, 0.18);
            animation: ring-pulse 2s infinite ease-in-out;
        }

        .avatar-speech-ring.listening {
            border-color: rgba(244, 63, 94, 0.25);
            box-shadow: 0 0 40px rgba(244, 63, 94, 0.18);
            animation: ring-pulse 1.5s infinite ease-in-out;
        }

        @keyframes ring-pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.08); opacity: 1; }
        }

        /* Status Badge */
        .avatar-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1rem;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 500;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            color: #64748B;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            margin: 0.75rem auto;
        }

        .status-badge-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #94a3b8;
            transition: all 0.3s ease;
        }

        .status-badge-dot.speaking {
            background: #2563EB;
            animation: dot-pulse 1.4s infinite;
        }

        .status-badge-dot.listening {
            background: #f43f5e;
            animation: dot-pulse 1.4s infinite;
        }

        .status-badge-dot.thinking {
            background: #60A5FA;
            animation: dot-pulse 1.4s infinite;
        }

        @keyframes dot-pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.4); opacity: 0.4; }
        }

        /* Greeting Card */
        .avatar-greeting-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.04);
            margin: 0.5rem 0;
            width: 100%;
            min-height: 110px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .avatar-greeting-card h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }

        .avatar-greeting-card p {
            font-size: 0.95rem;
            color: #64748b;
            line-height: 1.5;
        }

        .greeting-subtitle {
            margin-top: 0.4rem;
            font-weight: 500;
            color: #0f172a !important;
        }

        .brand-highlight {
            color: #2563EB;
            font-weight: 700;
        }

        /* RIGHT PANEL / MORPHING CARD styling */
        .kiosk-right-panel {
            position: absolute;
            left: 50%;
            bottom: 1.5vh;
            transform: translateX(-50%);
            width: 520px;
            max-width: 90%;
            height: auto;
            display: flex;
            flex-direction: column;
            z-index: 20;
            animation: panel-rise 0.8s cubic-bezier(0.4, 0, 0.2, 1) 0.3s both;
            transition:
                left 0.7s cubic-bezier(0.4, 0, 0.2, 1),
                bottom 0.7s cubic-bezier(0.4, 0, 0.2, 1),
                width 0.7s cubic-bezier(0.4, 0, 0.2, 1),
                max-width 0.7s cubic-bezier(0.4, 0, 0.2, 1),
                height 0.7s cubic-bezier(0.4, 0, 0.2, 1),
                transform 0.7s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .chatbot-wrapper.is-chatting .kiosk-right-panel {
            left: 55%;
            width: calc(45% - 1.5rem);
            max-width: calc(45% - 1.5rem);
            height: 100%;
            bottom: 0;
            transform: translateX(0);
        }

        .chat-card-panel {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.04);
            padding: 1rem 1.25rem;
            min-height: 0;
            position: relative;
            transition:
                border-radius 0.7s cubic-bezier(0.4, 0, 0.2, 1),
                padding 0.7s cubic-bezier(0.4, 0, 0.2, 1),
                box-shadow 0.7s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .chatbot-wrapper.is-chatting .chat-card-panel {
            border-radius: 24px;
            padding: 1.5rem;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.06);
        }

        .chat-history-scroll {
            flex: 1;
            overflow-y: auto;
            padding-right: 0.5rem;
            margin-bottom: 1rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            min-height: 0;
        }

        .chat-bubble-row {
            display: flex;
            width: 100%;
            animation: bubble-slide-in 0.4s cubic-bezier(0.4, 0, 0.2, 1) both;
        }

        .user-row {
            justify-content: flex-end;
            animation-name: bubble-slide-right;
        }

        .assistant-row {
            justify-content: flex-start;
            animation-name: bubble-slide-left;
        }

        @keyframes bubble-slide-left {
            from { opacity: 0; transform: translateX(-16px) translateY(8px); }
            to   { opacity: 1; transform: translateX(0) translateY(0); }
        }

        @keyframes bubble-slide-right {
            from { opacity: 0; transform: translateX(16px) translateY(8px); }
            to   { opacity: 1; transform: translateX(0) translateY(0); }
        }

        .chat-bubble {
            max-width: 85%;
            padding: 1rem 1.25rem;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .user-bubble {
            background: #DBEAFE;
            color: #0f172a;
            border-radius: 18px 18px 2px 18px;
            font-weight: 500;
        }

        .assistant-bubble {
            background: #f8fafc;
            color: #0f172a;
            border-radius: 18px 18px 18px 2px;
            border: 1px solid #e2e8f0;
        }

        .error-bubble {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fee2e2;
            border-radius: 12px;
        }

        /* Suggested Chips */
        .chat-suggested-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
            transition: all 0.3s ease;
        }

        .centered-chips {
            justify-content: center;
        }

        .chip-btn {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 9999px;
            padding: 0.4rem 0.85rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .chip-btn:hover {
            background: #2563EB;
            color: #ffffff;
            border-color: #2563EB;
        }

        /* Input Area styling */
        .chat-input-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 0.5rem 0.75rem;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.01);
            transition: all 0.4s ease;
        }

        .initial-input-row {
            background: #ffffff;
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            border-radius: 20px;
        }

        .chat-textarea-input {
            flex: 1;
            border: none;
            background: transparent;
            resize: none;
            outline: none;
            font-family: inherit;
            font-size: 0.95rem;
            color: #0f172a;
            padding: 0.5rem;
            max-height: 80px;
            line-height: 1.4;
        }

        .chat-textarea-input::placeholder {
            color: #94a3b8;
        }

        .chat-input-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .action-btn-mic,
        .action-btn-send {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            flex-shrink: 0;
        }

        .action-btn-mic svg,
        .action-btn-send svg {
            width: 18px;
            height: 18px;
        }

        .action-btn-mic {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }

        .action-btn-mic:hover {
            background: #e2e8f0;
            color: #334155;
        }

        .action-btn-mic.is-listening {
            background: #fef2f2;
            color: #ef4444;
            border-color: #fecaca;
            animation: mic-pulse 1.4s infinite;
        }

        @keyframes mic-pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.3); }
            50% { box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
        }

        .action-btn-send {
            background: #2563EB;
            color: #ffffff;
        }

        .action-btn-send:hover {
            background: #1d4ed8;
            transform: scale(1.05);
        }

        .action-btn-send:active {
            transform: scale(0.95);
        }

        /* Typings */
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

        .typing-indicator {
            display: flex; gap: 4px;
        }
        .typing-indicator span {
            width: 6px; height: 6px; border-radius: 50%;
            background: #94a3b8;
            animation: chatbot-bounce 1.2s ease-in-out infinite;
        }
        .typing-indicator span:nth-child(2) { animation-delay: .2s; }
        .typing-indicator span:nth-child(3) { animation-delay: .4s; }

        @keyframes panel-rise {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Registration Confirmation Card */
        .reg-confirm-card {
            background: linear-gradient(135deg, #f0f9ff 0%, #ffffff 100%);
            border: 1px solid #bfdbfe;
            border-radius: 16px;
            padding: 1.25rem;
            margin-bottom: 0.75rem;
            animation: bubble-slide-left 0.4s cubic-bezier(0.4, 0, 0.2, 1) both;
        }

        .reg-confirm-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 1rem;
        }

        .reg-confirm-header svg {
            width: 18px;
            height: 18px;
            color: #2563eb;
        }

        .reg-confirm-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.6rem;
            margin-bottom: 1rem;
        }

        .reg-item {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.5rem 0.75rem;
        }

        .reg-item label {
            display: block;
            font-size: 0.65rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #2563eb;
            margin-bottom: 0.15rem;
        }

        .reg-item span {
            font-size: 0.85rem;
            font-weight: 600;
            color: #0f172a;
        }

        .reg-confirm-actions {
            display: flex;
            gap: 0.5rem;
        }

        .reg-btn-confirm {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.7rem 1rem;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .reg-btn-confirm:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .reg-btn-confirm svg {
            width: 16px;
            height: 16px;
        }

        .reg-btn-cancel {
            padding: 0.7rem 1.25rem;
            background: transparent;
            color: #64748b;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .reg-btn-cancel:hover {
            background: #fee2e2;
            color: #ef4444;
            border-color: #fca5a5;
        }
    </style>
</div>
