@php
    $isLocal = \App\Helpers\KioskHelper::isKioskLocal();
@endphp
<div
    class="chatbot-wrapper"
    data-init-chatted="{{ empty($messages) ? 'false' : 'true' }}"
    x-data="{
        hasChatted: false,
        ttsEnabled: true,
        isSpeaking: false,
        isPaused: false,
        isListening: false,
        init() {
            this.hasChatted = this.$el.dataset.initChatted === 'true';
        },
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
            // Delay server history clearance to allow shrink animation (0.7s) to finish without DOM interruption
            setTimeout(() => {
                this.$wire.clearHistory();
            }, 700);
        }
    }"
    :class="{ 'is-chatting': hasChatted }"
    @chatbot-trigger-action.window="
        const actionType = $event.detail.type;
        if (!window.isKioskLocal && (actionType === 'checkout' || actionType === 'attendance')) {
            if (window.showOffsiteRestriction) {
                window.showOffsiteRestriction();
            }
            return;
        }
        if (actionType === 'checkout') {
            if (typeof openCheckoutFaceScan === 'function') openCheckoutFaceScan();
        } else if (actionType === 'attendance') {
            if (typeof openAttendanceModal === 'function') openAttendanceModal();
        } else {
            if (typeof handleCheckin === 'function') handleCheckin(actionType);
        }
    "
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
            style="{{ empty($messages) ? 'display: none;' : '' }}"
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
                

                <!-- Action Cards (Moved from Welcome) -->
                <div class="cards-grid" @if(!$isLocal) style="grid-template-columns: 1fr;" @endif>
                    @if($isLocal)
                        <!-- Card 1: Sudah Ada Janji (Check-In) -->
                        <div class="checkin-card card-appointment" onclick="handleCheckin('appointment')" role="button" tabindex="0" aria-label="Check-in dengan janji temu">
                            <div class="card-icon-wrap">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="3" y="3" width="7" height="7" rx="1"/>
                                    <rect x="14" y="3" width="7" height="7" rx="1"/>
                                    <rect x="3" y="14" width="7" height="7" rx="1"/>
                                    <path d="M14 14h.01M18 14h.01M14 18h.01M18 18h.01M16 16v.01"/>
                                </svg>
                            </div>
                            <div class="card-body">
                                <div class="card-title" data-lang-id="Sudah Ada Janji" data-lang-en="Have Appointment">{{ $lang === 'en' ? 'Have Appointment' : 'Sudah Ada Janji' }}</div>
                                <div class="card-sub" data-lang-id="Scan QR Code" data-lang-en="Scan QR Code">{{ $lang === 'en' ? 'Scan QR Code' : 'Scan QR Code' }}</div>
                            </div>
                        </div>

                        <!-- Card 2: Check-Out Mandiri -->
                        <div class="checkin-card card-checkout" onclick="openCheckoutFaceScan()" role="button" tabindex="0" aria-label="Check-out mandiri via wajah">
                            <div class="card-icon-wrap">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 16l4-4m0 0l-4-4m4 4H7"/>
                                    <path d="M3 12a9 9 0 1 0 18 0 9 9 0 0 0-18 0" opacity=".3"/>
                                </svg>
                            </div>
                            <div class="card-body">
                                <div class="card-title" data-lang-id="Check-Out" data-lang-en="Check-Out">{{ $lang === 'en' ? 'Check-Out' : 'Check-Out' }}</div>
                                <div class="card-sub" data-lang-id="Check-out mandiri" data-lang-en="Self check-out">{{ $lang === 'en' ? 'Self check-out' : 'Check-out mandiri' }}</div>
                            </div>
                        </div>

                        <!-- Card 3: Tamu Baru / Walk-in -->
                        <div class="checkin-card card-walkin" onclick="handleCheckin('walkin')" role="button" tabindex="0" aria-label="Registrasi tamu baru walk-in">
                            <div class="card-icon-wrap">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <line x1="19" y1="8" x2="19" y2="14"/>
                                    <line x1="22" y1="11" x2="16" y2="11"/>
                                </svg>
                            </div>
                            <div class="card-body">
                                <div class="card-title" data-lang-id="Tamu Baru" data-lang-en="New Visitor">{{ $lang === 'en' ? 'New Visitor' : 'Tamu Baru' }}</div>
                                <div class="card-sub" data-lang-id="Walk-in" data-lang-en="Walk-in">{{ $lang === 'en' ? 'Walk-in' : 'Walk-in' }}</div>
                            </div>
                        </div>

                        <!-- Card 4: Absensi Karyawan (PIC) -->
                        <div class="checkin-card card-attendance" onclick="openAttendanceModal()" role="button" tabindex="0" aria-label="Absensi Karyawan">
                            <div class="card-icon-wrap">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="8.5" cy="7" r="4"></circle>
                                    <polyline points="17 11 19 13 23 9"></polyline>
                                </svg>
                            </div>
                            <div class="card-body">
                                <div class="card-title" data-lang-id="Absen Masuk / Keluar" data-lang-en="Staff Check-In / Out">{{ $lang === 'en' ? 'Staff Check-In / Out' : 'Absen Masuk / Keluar' }}</div>
                                <div class="card-sub" data-lang-id="Khusus Karyawan" data-lang-en="Staff Only">{{ $lang === 'en' ? 'Staff Only' : 'Khusus Karyawan' }}</div>
                            </div>
                        </div>
                    @else
                        <!-- Only 1 Card visible when offsite: Buat Janji Temu (Appointment) -->
                        <div class="checkin-card card-walkin" onclick="handleCheckin('walkin')" role="button" tabindex="0" aria-label="Buat Janji Temu Baru" style="border: 2px solid var(--accent-primary); background: rgba(99, 102, 241, 0.05);">
                            <div class="card-icon-wrap" style="color: var(--accent-primary);">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                            </div>
                            <div class="card-body">
                                <div class="card-title" style="color: var(--accent-primary);" data-lang-id="Buat Janji Temu" data-lang-en="Make Appointment">{{ $lang === 'en' ? 'Make Appointment' : 'Buat Janji Temu' }}</div>
                                <div class="card-sub" data-lang-id="Appointment Kunjungan" data-lang-en="Visit Appointment">{{ $lang === 'en' ? 'Visit Appointment' : 'Appointment Kunjungan' }}</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- RIGHT PANEL (The morphing input/chat box) -->
        <div class="kiosk-right-panel">
            
            <!-- Greeting Card (Moved from left panel, no box, large text) -->
            <div class="right-panel-greeting">
                @if($lang === 'en')
                    <h2>Hello!</h2>
                    <p>I am <span class="brand-highlight">Visita</span>, your AI Assistant</p>
                    <p class="greeting-subtitle">I can help you make an appointment with the PIC that best suits your needs</p>
                @else
                    <h2>Hola!</h2>
                    <p>Saya <span class="brand-highlight">Visita</span>, AI Assistant Anda</p>
                    <p class="greeting-subtitle">Saya bisa membantu Anda membuat pertemuan dengan PIC yang sesuai dengan kebutuhan Anda</p>
                @endif
            </div>

            <div class="chat-card-panel">
                
                <!-- Chat Conversation Area (Scrollable, hidden initially) -->
                <div class="chat-history-scroll" x-show="hasChatted" style="{{ empty($messages) ? 'display: none;' : '' }}" x-transition:enter="transition ease-out duration-400 delay-200" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" id="chat-history-container" x-init="$el.scrollTop = $el.scrollHeight" @chatbot-scrolled.window="setTimeout(() => { $el.scrollTop = $el.scrollHeight; }, 100)">
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
                    <button type="button" class="chip-btn" @click="$wire.selectSuggestedChip(window.appLang === 'en' ? 'I want to meet an employee' : 'Saya ingin bertemu karyawan'); hasChatted = true" x-show="hasChatted" data-lang-id="Bertemu Karyawan" data-lang-en="Meet Employee">{{ $lang === 'en' ? 'Meet Employee' : 'Bertemu Karyawan' }}</button>
                    <button type="button" class="chip-btn" @click="$wire.selectSuggestedChip(window.appLang === 'en' ? 'How do I check-in?' : 'Bagaimana cara check-in?'); hasChatted = true" x-show="hasChatted" data-lang-id="Cara Check-in" data-lang-en="How to Check-in">{{ $lang === 'en' ? 'How to Check-in' : 'Cara Check-in' }}</button>
                    <button type="button" class="chip-btn" @click="$wire.selectSuggestedChip(window.appLang === 'en' ? 'Make a new appointment' : 'Buat janji temu baru'); hasChatted = true" x-show="hasChatted" data-lang-id="Buat Janji Temu" data-lang-en="Make Appointment">{{ $lang === 'en' ? 'Make Appointment' : 'Buat Janji Temu' }}</button>
                    <button type="button" class="chip-btn" @click="$wire.selectSuggestedChip(window.appLang === 'en' ? 'Please explain the visit rules here' : 'Tolong jelaskan aturan kunjungan di sini'); hasChatted = true" x-show="hasChatted" data-lang-id="Aturan Kunjungan" data-lang-en="Visit Rules">{{ $lang === 'en' ? 'Visit Rules' : 'Aturan Kunjungan' }}</button>
                </div>
                
                <!-- Chat Input Row -->
                <div class="chat-input-row" :class="{ 'initial-input-row': !hasChatted }" x-data="{ ready: false }" x-init="$nextTick(() => { ready = true })">
                    <textarea 
                        wire:model="inputMessage" 
                        class="chat-textarea-input" 
                        :placeholder="ready ? '{{ $lang === 'en' ? 'Type your message...' : 'Ketik pesan Anda...' }}' : '{{ $lang === 'en' ? 'System loading...' : 'Sistem sedang memuat...' }}'"
                        rows="1"
                        x-on:input="$el.style.height='auto'; $el.style.height=$el.scrollHeight+'px'"
                        x-on:keydown.enter.prevent="if (!$event.shiftKey) { $wire.sendMessage(); hasChatted = true; }"
                        x-bind:disabled="!ready || {{ $isLoading ? 'true' : 'false' }}"
                        disabled
                    ></textarea>
                    
                    <div class="chat-input-actions">
                        <button type="button" class="action-btn-mic" @click="startDictation()" :class="{ 'is-listening': isListening }" x-bind:disabled="!ready || {{ $isLoading ? 'true' : 'false' }}" disabled>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/>
                                <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                                <line x1="12" y1="19" x2="12" y2="22"/>
                            </svg>
                        </button>
                        <button type="button" class="action-btn-send" @click="$wire.sendMessage(); hasChatted = true" wire:loading.attr="disabled" wire:target="sendMessage" x-bind:disabled="!ready" disabled>
                            <svg wire:loading.remove wire:target="sendMessage" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="22" y1="2" x2="11" y2="13"/>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                            </svg>
                            <svg wire:loading wire:target="sendMessage" class="animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
            </div>
        </div>
        
    </div>

    <!-- marked.js untuk render Markdown (offline-resilient) -->
    <script src="/js/marked.min.js"></script>
    <script>
        document.addEventListener('livewire:initialized', () => {
            const loc = localStorage.getItem('kiosk-location-name') || '';
            @this.set('kioskLocation', loc);
        });

        if (window.marked) { marked.setOptions({ breaks: true, gfm: true }); }

        (function () {
            'use strict';
            let currentAudio = null;
            let currentAbortController = null;
            let currentBlobUrl = null;
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

            function fireTtsEvent(name) { window.dispatchEvent(new CustomEvent(name)); }

            function cleanupBlobUrl() {
                if (currentBlobUrl) {
                    URL.revokeObjectURL(currentBlobUrl);
                    currentBlobUrl = null;
                }
            }

            window.speakText = function (text) {
                if (!text) return;

                // Hentikan suara yang sedang berjalan
                window.stopAiSpeech();

                const plain = text.replace(/<!--.*?-->/gs, '').replace(/[*_`#>~|\-]+/g, ' ').replace(/\n+/g, '. ').replace(/\s{2,}/g, ' ').trim();
                if (!plain) return;

                // Fire event immediately so buttons appear and avatar starts moving
                fireTtsEvent('tts-started');

                // Gunakan Web Speech API (fallback) secara langsung
                fallbackSpeak(plain);
            };

            let currentUttId = 0;

            function fallbackSpeak(plain) {
                if (!('speechSynthesis' in window)) {
                    fireTtsEvent('tts-ended');
                    return;
                }
                currentUttId++;
                const thisUttId = currentUttId;
                
                window.speechSynthesis.cancel();
                const utt = new SpeechSynthesisUtterance(plain);
                utt.lang = 'id-ID';
                utt.rate = 1.15;
                utt.pitch = 1.0;
                if (fallbackIndoVoice) utt.voice = fallbackIndoVoice;

                utt.onend = () => { if (currentUttId === thisUttId) fireTtsEvent('tts-ended'); };
                utt.onerror = () => { if (currentUttId === thisUttId) fireTtsEvent('tts-ended'); };
                
                fireTtsEvent('tts-started');
                window.speechSynthesis.speak(utt);
            }

            window.stopAiSpeech = () => {
                currentUttId++; // Invalidate any ongoing fallback TTS events
                // Cancel in-flight fetch request
                if (currentAbortController) {
                    currentAbortController.abort();
                    currentAbortController = null;
                }
                if (currentAudio) {
                    currentAudio.pause();
                    currentAudio.currentTime = 0;
                    currentAudio = null;
                }
                cleanupBlobUrl();
                if (window.speechSynthesis) {
                    window.speechSynthesis.cancel();
                }
                fireTtsEvent('tts-ended');
            };

            window.pauseAiSpeech = () => {
                if (currentAudio) {
                    currentAudio.pause();
                } else if (window.speechSynthesis) {
                    window.speechSynthesis.pause();
                }
            };

            window.resumeAiSpeech = () => {
                if (currentAudio) {
                    currentAudio.play().catch(() => {});
                } else if (window.speechSynthesis) {
                    window.speechSynthesis.resume();
                }
            };
        })();
    </script>

    <style>
        [x-cloak] { display: none !important; }

        .card-disabled-offsite {
            opacity: 0.55;
            cursor: not-allowed !important;
            position: relative;
            pointer-events: auto;
            transition: all 0.2s ease;
        }
        .card-disabled-offsite:hover {
            opacity: 0.8;
            transform: translateY(-2px);
            border-color: #f59e0b !important;
        }
        .card-disabled-offsite::after {
            content: "🔒";
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            font-size: 1rem;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }

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
            position: absolute;
            bottom: 12rem; /* Geser agak ke atas agar tidak tumpang tindih dengan card */
            left: 5%;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            z-index: 10;
        }
        .snd-ctrl-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 2px solid #6366f1;
            background: linear-gradient(135deg, #eef2ff, #e0e7ff);
            color: #4338ca;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 3px 12px rgba(99, 102, 241, 0.25), 0 0 0 3px rgba(99, 102, 241, 0.08);
        }
        .snd-ctrl-btn:hover {
            background: linear-gradient(135deg, #c7d2fe, #a5b4fc);
            color: #312e81;
            transform: scale(1.15);
            box-shadow: 0 4px 18px rgba(99, 102, 241, 0.4), 0 0 0 4px rgba(99, 102, 241, 0.12);
        }
        .snd-ctrl-btn.active {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            color: #dc2626;
            border-color: #f87171;
            box-shadow: 0 3px 12px rgba(239, 68, 68, 0.3), 0 0 0 3px rgba(239, 68, 68, 0.1);
        }
        .snd-ctrl-btn.snd-ctrl-stop {
            border-color: #f87171;
            background: linear-gradient(135deg, #fff1f2, #ffe4e6);
            color: #dc2626;
        }
        .snd-ctrl-btn.snd-ctrl-stop:hover {
            background: linear-gradient(135deg, #fecaca, #fca5a5);
            color: #991b1b;
            border-color: #ef4444;
            box-shadow: 0 4px 18px rgba(239, 68, 68, 0.4), 0 0 0 4px rgba(239, 68, 68, 0.12);
        }
        .snd-ctrl-btn svg {
            width: 20px;
            height: 20px;
            stroke-width: 2.5;
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

        /* LEFT PANEL styling overriding (cleaning up old redundant styles) */
        .kiosk-left-panel {
            flex: 0 0 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.5);
            padding: 2rem;
            border-right: 1px solid rgba(226, 232, 240, 0.6);
            backdrop-filter: blur(10px);
            z-index: 2;
            transition: flex 0.7s cubic-bezier(0.4, 0, 0.2, 1), padding 0.7s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .chatbot-wrapper.is-chatting .kiosk-left-panel {
            flex: 0 0 40%;
            padding-top: 1rem;
        }

        .left-panel-content {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: all 0.6s ease;
            position: relative;
        }

        .avatar-box {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
        }

        .avatar-box::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 20%;
            background: linear-gradient(to top, rgba(255, 255, 255, 1) 0%, rgba(255, 255, 255, 0) 100%);
            z-index: 2;
            pointer-events: none;
        }

        /* Removed legacy landscape aspect ratio restrictions to allow full maximization of the avatar */

        .avatar-video-element {
            height: 100%;
            width: 100%;
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
            position: absolute;
            top: 0.75rem;
            left: 2.5%;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1rem;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 500;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(226, 232, 240, 0.8);
            color: #64748B;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            z-index: 10;
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

        /* 4 Cards Grid - 4 Columns Layout */
        .cards-grid {
            position: absolute;
            bottom: 1.5rem;
            left: 50%;
            transform: translateX(-50%);
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.8rem;
            width: 95%;
            z-index: 10;
        }
        
        .cards-grid .checkin-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1.5px solid #2563EB;
            border-radius: 16px;
            padding: 1rem 0.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
            height: 100%;
        }
        
        .cards-grid .checkin-card:hover {
            transform: translateY(-5px);
            background: #ffffff;
            border-color: #1D4ED8;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.25);
        }
        
        .cards-grid .card-icon-wrap {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 12px;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #475569;
        }
        
        .cards-grid .card-icon-wrap svg {
            width: 1.25rem;
            height: 1.25rem;
        }
        
        .cards-grid .card-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.2;
        }
        
        .cards-grid .card-sub {
            font-size: 0.7rem;
            color: #64748b;
            line-height: 1.3;
        }
        
        .cards-grid .card-cta {
            display: none;
        }

        .brand-highlight {
            color: #2563EB;
            font-weight: 700;
        }

        /* RIGHT PANEL GREETING ANIMATION */
        .right-panel-greeting {
            padding: 1rem 0;
            text-align: left;
            margin: 0 0 1rem 0;
            width: 100%;
            transition: all 0.7s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            max-height: 400px;
            opacity: 1;
            transform: translateY(0);
        }

        .chatbot-wrapper.is-chatting .right-panel-greeting {
            max-height: 0;
            padding: 0;
            margin: 0;
            opacity: 0;
            transform: translateY(-2rem);
        }
        
        .right-panel-greeting h2 {
            font-size: 3.5rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 0.5rem;
            letter-spacing: -0.03em;
        }

        .right-panel-greeting p {
            font-size: 1.4rem;
            color: #475569;
            margin: 0.2rem 0;
            font-weight: 500;
        }

        .right-panel-greeting .greeting-subtitle {
            font-size: 1.1rem;
            color: #64748b;
            margin-top: 0.8rem;
            line-height: 1.5;
        }

        /* RIGHT PANEL styling */
        .kiosk-right-panel {
            flex: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem 2.5rem;
            z-index: 20;
            background: rgba(255, 255, 255, 0.3);
            transition: padding 0.7s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .chatbot-wrapper.is-chatting .kiosk-right-panel {
            padding: 2rem 2.5rem;
        }

        .chat-card-panel {
            width: 100%;
            flex-grow: 0;
            flex-shrink: 1;
            flex-basis: auto;
            display: flex;
            flex-direction: column;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.04);
            padding: 1.5rem;
            min-height: 0;
            position: relative;
            transition:
                flex-grow 0.7s cubic-bezier(0.4, 0, 0.2, 1),
                border-radius 0.7s cubic-bezier(0.4, 0, 0.2, 1),
                padding 0.7s cubic-bezier(0.4, 0, 0.2, 1),
                box-shadow 0.7s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .chatbot-wrapper.is-chatting .chat-card-panel {
            flex-grow: 1;
            border-radius: 24px;
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

        .assistant-bubble p {
            margin: 0.35rem 0;
            padding: 0;
            line-height: 1.5;
        }

        .assistant-bubble p:first-child {
            margin-top: 0;
        }

        .assistant-bubble p:last-child {
            margin-bottom: 0;
        }

        .assistant-bubble ul,
        .assistant-bubble ol {
            margin: 0.35rem 0;
            padding-left: 1.1rem;
            list-style-position: outside;
        }

        .assistant-bubble ul {
            list-style-type: disc;
        }

        .assistant-bubble ol {
            list-style-type: decimal;
        }

        .assistant-bubble li {
            margin: 0.2rem 0;
            padding-left: 0.1rem;
            line-height: 1.45;
        }

        .assistant-bubble strong {
            font-weight: 600;
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
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .action-btn-mic svg,
        .action-btn-send svg {
            width: 20px;
            height: 20px;
        }

        .action-btn-mic {
            background: #e2e8f0;
            color: #475569;
            border: 2px solid #cbd5e1;
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
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
        }

        .action-btn-send:hover:not(:disabled) {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            transform: scale(1.08);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.6);
        }

        .action-btn-send:active:not(:disabled) {
            transform: scale(0.95);
        }

        .action-btn-send:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .animate-spin {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
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

        /* ============================================================
           DARK MODE OVERRIDES FOR CHATBOT
        ============================================================ */
        html.dark-mode .kiosk-left-panel {
            background: rgba(30, 41, 59, 0.5);
            border-right-color: rgba(51, 65, 85, 0.6);
        }

        html.dark-mode .kiosk-right-panel {
            background: rgba(30, 41, 59, 0.3);
        }

        /* Avatar: disable multiply blend & white-fade gradient in dark mode */
        html.dark-mode .avatar-video-element {
            mix-blend-mode: normal;
            filter: brightness(1) contrast(1);
        }

        html.dark-mode .avatar-box::after {
            background: linear-gradient(to top, rgba(30, 41, 59, 0.8) 0%, transparent 100%);
        }

        /* Chat card panel */
        html.dark-mode .chat-card-panel {
            background: #1E293B;
            border-color: #334155;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
        }

        /* Chat bubbles */
        html.dark-mode .assistant-bubble {
            background: #334155;
            color: #F1F5F9;
            border-color: #475569;
        }

        html.dark-mode .user-bubble {
            background: #1e40af;
            color: #F1F5F9;
        }

        html.dark-mode .error-bubble {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border-color: rgba(239, 68, 68, 0.3);
        }

        /* Input area */
        html.dark-mode .chat-input-row {
            background: #334155;
            border-color: #475569;
        }

        html.dark-mode .initial-input-row {
            background: #1E293B;
            border-color: #475569;
        }

        html.dark-mode .chat-textarea-input {
            color: #F1F5F9;
        }

        html.dark-mode .chat-textarea-input::placeholder {
            color: #64748B;
        }

        /* Mic button */
        html.dark-mode .action-btn-mic {
            background: #334155;
            color: #cbd5e1;
            border: 2px solid #475569;
        }

        html.dark-mode .action-btn-mic:hover {
            background: #475569;
            color: #ffffff;
            border-color: #64748b;
        }
        
        /* Send button */
        html.dark-mode .action-btn-send {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            box-shadow: 0 4px 14px rgba(59, 130, 246, 0.4);
        }
        
        html.dark-mode .action-btn-send:hover:not(:disabled) {
            background: linear-gradient(135deg, #60a5fa, #3b82f6);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.6);
        }

        /* Chip buttons */
        html.dark-mode .chip-btn {
            background: #334155;
            border-color: #475569;
            color: #94A3B8;
        }

        html.dark-mode .chip-btn:hover {
            background: #2563EB;
            color: #ffffff;
            border-color: #2563EB;
        }

        /* Greeting text */
        html.dark-mode .right-panel-greeting h2 {
            color: #F1F5F9;
        }

        html.dark-mode .right-panel-greeting p {
            color: #94A3B8;
        }

        html.dark-mode .right-panel-greeting .greeting-subtitle {
            color: #64748B;
        }

        /* Avatar status badge */
        html.dark-mode .avatar-status-badge {
            background: rgba(30, 41, 59, 0.85);
            border-color: rgba(51, 65, 85, 0.8);
            color: #94A3B8;
        }

        /* Sound control buttons */
        html.dark-mode .snd-ctrl-btn {
            background: linear-gradient(135deg, #312e81, #3730a3);
            border-color: #818cf8;
            color: #c7d2fe;
            box-shadow: 0 3px 14px rgba(129, 140, 248, 0.35), 0 0 0 3px rgba(129, 140, 248, 0.12);
        }

        html.dark-mode .snd-ctrl-btn:hover {
            background: linear-gradient(135deg, #3730a3, #4338ca);
            color: #e0e7ff;
            border-color: #a5b4fc;
            box-shadow: 0 4px 20px rgba(129, 140, 248, 0.5), 0 0 0 4px rgba(129, 140, 248, 0.18);
        }

        html.dark-mode .snd-ctrl-btn.active {
            background: linear-gradient(135deg, #7f1d1d, #991b1b);
            border-color: #f87171;
            color: #fecaca;
            box-shadow: 0 3px 14px rgba(248, 113, 113, 0.35), 0 0 0 3px rgba(248, 113, 113, 0.12);
        }

        html.dark-mode .snd-ctrl-btn.snd-ctrl-stop {
            background: linear-gradient(135deg, #7f1d1d, #991b1b);
            border-color: #f87171;
            color: #fecaca;
            box-shadow: 0 3px 14px rgba(248, 113, 113, 0.35), 0 0 0 3px rgba(248, 113, 113, 0.12);
        }

        html.dark-mode .snd-ctrl-btn.snd-ctrl-stop:hover {
            background: linear-gradient(135deg, #991b1b, #b91c1c);
            border-color: #fca5a5;
            color: #fee2e2;
            box-shadow: 0 4px 20px rgba(248, 113, 113, 0.5), 0 0 0 4px rgba(248, 113, 113, 0.18);
        }

        /* Close button */
        html.dark-mode .chatbot-close-btn {
            background: rgba(30, 41, 59, 0.92);
            border-color: #334155;
            color: #94A3B8;
        }

        html.dark-mode .chatbot-close-btn:hover {
            background: #334155;
            color: #F1F5F9;
        }

        /* Cards grid */
        html.dark-mode .cards-grid .checkin-card {
            background: rgba(30, 41, 59, 0.9);
            border-color: #2563EB;
        }

        html.dark-mode .cards-grid .checkin-card:hover {
            background: rgba(30, 41, 59, 1);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
        }

        html.dark-mode .cards-grid .card-icon-wrap {
            background: rgba(255, 255, 255, 0.08);
        }

        html.dark-mode .cards-grid .card-title {
            color: #F1F5F9;
        }

        html.dark-mode .cards-grid .card-sub {
            color: #94A3B8;
        }

        /* Registration confirmation card */
        html.dark-mode .reg-confirm-card {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            border-color: #3b82f6;
        }

        html.dark-mode .reg-item {
            background: rgba(15, 23, 42, 0.6);
            border-color: #475569;
        }

        html.dark-mode .reg-item span {
            color: #F1F5F9;
        }

        html.dark-mode .reg-btn-cancel {
            color: #94A3B8;
            border-color: #475569;
        }

        html.dark-mode .reg-btn-cancel:hover {
            background: rgba(239, 68, 68, 0.15);
        }

        /* Greeting card (if used) */
        html.dark-mode .avatar-greeting-card {
            background: #1E293B;
            border-color: #334155;
        }

        html.dark-mode .avatar-greeting-card h2 {
            color: #F1F5F9;
        }

        html.dark-mode .avatar-greeting-card p {
            color: #94A3B8;
        }

        /* ============================================================
           MOBILE RESPONSIVE STYLES (Screen Width <= 768px)
           Improves mobile chatbot layout without affecting PC view
        ============================================================ */
        @media (max-width: 768px) {
            .kiosk-split-layout {
                flex-direction: column !important;
                height: auto !important;
                overflow: visible !important;
                gap: 0 !important;
            }

            .kiosk-left-panel {
                flex: none !important;
                width: 100% !important;
                min-height: 200px !important;
                max-height: 320px !important;
                height: auto !important;
                padding: 0.75rem 0.75rem 0.5rem !important;
                border-right: none !important;
                border-bottom: 1px solid rgba(226, 232, 240, 0.6) !important;
                position: relative !important;
            }

            .chatbot-wrapper.is-chatting .kiosk-left-panel {
                flex: none !important;
                width: 100% !important;
                min-height: 120px !important;
                max-height: 200px !important;
                padding: 0.5rem 0.5rem 0.25rem !important;
            }

            .left-panel-content {
                position: relative !important;
            }

            .avatar-box {
                position: relative !important;
                height: 150px !important;
                width: 100% !important;
            }

            .chatbot-wrapper.is-chatting .avatar-box {
                height: 90px !important;
            }

            .avatar-box::after {
                height: 15% !important;
            }

            .avatar-speech-ring {
                width: 120px !important;
                height: 120px !important;
            }

            .chatbot-wrapper.is-chatting .avatar-speech-ring {
                width: 80px !important;
                height: 80px !important;
            }

            .avatar-status-badge {
                top: 0.25rem !important;
                left: 0.25rem !important;
                padding: 0.2rem 0.55rem !important;
                font-size: 0.7rem !important;
                gap: 0.35rem !important;
            }

            /* Sound controls - position below avatar area, not overlapping */
            .avatar-sound-controls {
                position: relative !important;
                bottom: auto !important;
                left: auto !important;
                justify-content: center !important;
                margin-top: 0.25rem !important;
                margin-bottom: 0.25rem !important;
            }

            .snd-ctrl-btn {
                width: 36px !important;
                height: 36px !important;
            }

            .snd-ctrl-btn svg {
                width: 16px !important;
                height: 16px !important;
            }

            /* Cards grid - 2x2 on mobile */
            .cards-grid {
                position: relative !important;
                bottom: auto !important;
                left: auto !important;
                transform: none !important;
                width: 100% !important;
                margin-top: 0.5rem !important;
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 0.5rem !important;
            }

            .cards-grid .checkin-card {
                height: auto !important;
                min-height: 60px !important;
                padding: 0.5rem 0.35rem !important;
                border-radius: 12px !important;
                gap: 0.3rem !important;
            }

            .cards-grid .card-icon-wrap {
                width: 1.8rem !important;
                height: 1.8rem !important;
                border-radius: 8px !important;
            }

            .cards-grid .card-icon-wrap svg {
                width: 0.95rem !important;
                height: 0.95rem !important;
            }

            .cards-grid .card-title {
                font-size: 0.7rem !important;
                line-height: 1.15 !important;
            }

            .cards-grid .card-sub {
                font-size: 0.6rem !important;
            }

            /* Right panel */
            .kiosk-right-panel {
                flex: none !important;
                width: 100% !important;
                height: auto !important;
                padding: 0.5rem 0.5rem 0 !important;
            }

            .chatbot-wrapper.is-chatting .kiosk-right-panel {
                padding: 0.5rem 0.5rem 0 !important;
                flex: 1 !important;
            }

            .right-panel-greeting {
                margin-bottom: 0.3rem !important;
                padding: 0.25rem 0 !important;
            }

            .right-panel-greeting h2 {
                font-size: 1.5rem !important;
                margin-bottom: 0.15rem !important;
            }

            .right-panel-greeting p {
                font-size: 0.9rem !important;
            }

            .right-panel-greeting .greeting-subtitle {
                font-size: 0.78rem !important;
                margin-top: 0.2rem !important;
            }

            /* Chat panel */
            .chat-card-panel {
                padding: 0.75rem !important;
                border-radius: 14px !important;
            }

            .chat-history-scroll {
                max-height: 45vh !important;
                gap: 0.65rem !important;
                margin-bottom: 0.5rem !important;
            }

            .chat-bubble {
                max-width: 92% !important;
                font-size: 0.82rem !important;
                padding: 0.6rem 0.8rem !important;
            }

            .reg-confirm-grid {
                grid-template-columns: 1fr !important;
                gap: 0.35rem !important;
            }

            /* Suggested chips */
            .chat-suggested-chips {
                gap: 0.3rem !important;
                margin-bottom: 0.5rem !important;
            }

            .chip-btn {
                padding: 0.25rem 0.6rem !important;
                font-size: 0.72rem !important;
            }

            /* Input area */
            .chat-input-row {
                padding: 0.3rem 0.5rem !important;
                border-radius: 12px !important;
                gap: 0.4rem !important;
            }

            .chat-textarea-input {
                font-size: 0.82rem !important;
                padding: 0.35rem !important;
            }

            .action-btn-mic,
            .action-btn-send {
                width: 36px !important;
                height: 36px !important;
            }

            .action-btn-mic svg,
            .action-btn-send svg {
                width: 16px !important;
                height: 16px !important;
            }

            /* Close button */
            .chatbot-close-btn {
                top: 0.35rem !important;
                right: 0.35rem !important;
                width: 30px !important;
                height: 30px !important;
                border-radius: 8px !important;
            }

            .chatbot-close-btn svg {
                width: 14px !important;
                height: 14px !important;
            }
        }

        /* ============================================================
           EXTRA SMALL SCREENS (Screen Width <= 400px)
        ============================================================ */
        @media (max-width: 400px) {
            .kiosk-left-panel {
                min-height: 160px !important;
                max-height: 260px !important;
            }

            .avatar-box {
                height: 120px !important;
            }

            .chatbot-wrapper.is-chatting .avatar-box {
                height: 70px !important;
            }

            .cards-grid .card-title {
                font-size: 0.65rem !important;
            }

            .right-panel-greeting h2 {
                font-size: 1.3rem !important;
            }

            .chat-history-scroll {
                max-height: 40vh !important;
            }
        }

    </style>
</div>
