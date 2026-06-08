<div id="admin-ai-widget" class="admin-ai-widget">

    {{-- ── Floating Action Button ─────────────────────────────── --}}
    <button
        id="admin-ai-fab"
        class="admin-ai-fab"
        title="VISITA AI Assistant"
        aria-label="Buka Admin AI Assistant"
        onclick="adminAI.toggle()"
    >
        <span id="admin-ai-fab-icon" class="admin-ai-fab-icon">🤖</span>
    </button>

    {{-- ── Chat Modal ──────────────────────────────────────────── --}}
    <div id="admin-ai-modal" class="admin-ai-modal" style="display:none" role="dialog" aria-label="Admin AI Assistant">

        {{-- Header --}}
        <div class="admin-ai-header">
            <div class="admin-ai-header-info">
                <span class="admin-ai-header-icon">🤖</span>
                <div>
                    <div class="admin-ai-header-title">VISITA AI Assistant</div>
                    <div class="admin-ai-header-sub">Data real-time · Powered by Gemini</div>
                </div>
            </div>
            <div class="admin-ai-header-actions">
                <button
                    id="admin-ai-tts-btn"
                    class="admin-ai-ctrl-btn"
                    title="Matikan/nyalakan suara"
                    onclick="adminAI.toggleTts()"
                >🔊</button>
                <button
                    class="admin-ai-ctrl-btn"
                    title="Hapus riwayat"
                    onclick="adminAI.clearHistory()"
                >🗑️</button>
                <button
                    class="admin-ai-ctrl-btn admin-ai-close-btn"
                    title="Tutup"
                    onclick="adminAI.toggle()"
                >✕</button>
            </div>
        </div>

        {{-- Messages --}}
        <div id="admin-ai-messages" class="admin-ai-messages">
            <div class="admin-ai-welcome">
                <div class="admin-ai-welcome-icon">👋</div>
                <p>Halo, <strong>{{ auth()->user()?->name ?? 'Admin' }}</strong>!<br>
                Tanya saya tentang kondisi kunjungan hari ini.</p>
                <div class="admin-ai-suggestions">
                    <button class="admin-ai-chip" onclick="adminAI.sendSuggestion(this)">Siapa yang sedang check-in?</button>
                    <button class="admin-ai-chip" onclick="adminAI.sendSuggestion(this)">Statistik hari ini</button>
                    <button class="admin-ai-chip" onclick="adminAI.sendSuggestion(this)">Berapa tamu aktif sekarang?</button>
                </div>
            </div>
        </div>

        {{-- Input --}}
        <div class="admin-ai-input-area">
            <textarea
                id="admin-ai-input"
                class="admin-ai-input"
                placeholder="Tanya tentang data sistem..."
                rows="1"
                oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px'"
                onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();adminAI.send();}"
            ></textarea>
            <button
                id="admin-ai-send-btn"
                class="admin-ai-send-btn"
                title="Kirim"
                onclick="adminAI.send()"
            >➤</button>
        </div>
    </div>
</div>

{{-- ── Styles ───────────────────────────────────────────────── --}}
<style>
/* Widget container */
.admin-ai-widget {
    position: fixed;
    bottom: 28px;
    right: 28px;
    z-index: 50000;
    font-family: 'Poppins', 'Inter', sans-serif;
}

/* FAB */
.admin-ai-fab {
    width: 58px;
    height: 58px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    color: white;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 24px rgba(79,70,229,.5), 0 0 0 0 rgba(79,70,229,.4);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    transition: transform .2s, box-shadow .2s;
    animation: admin-ai-pulse 2.5s infinite;
}
.admin-ai-fab:hover {
    transform: scale(1.12);
    box-shadow: 0 6px 32px rgba(79,70,229,.65);
    animation: none;
}
.admin-ai-fab-icon { line-height: 1; display: block; }
@keyframes admin-ai-pulse {
    0%,100% { box-shadow: 0 4px 24px rgba(79,70,229,.5), 0 0 0 0 rgba(79,70,229,.4); }
    50%      { box-shadow: 0 4px 24px rgba(79,70,229,.5), 0 0 0 10px rgba(79,70,229,0); }
}

/* Modal */
.admin-ai-modal {
    position: absolute;
    bottom: 70px;
    right: 0;
    width: 380px;
    max-height: 560px;
    background: #fff;
    border-radius: 22px;
    box-shadow: 0 24px 72px rgba(0,0,0,.2), 0 0 0 1px rgba(79,70,229,.08);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: admin-ai-slidein .25s cubic-bezier(.4,0,.2,1);
}
@keyframes admin-ai-slidein {
    from { opacity:0; transform:translateY(18px) scale(.97); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}

/* Header */
.admin-ai-header {
    background: linear-gradient(135deg, #4338ca 0%, #6d28d9 100%);
    color: white;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    flex-shrink: 0;
}
.admin-ai-header-info  { display:flex; align-items:center; gap:10px; }
.admin-ai-header-icon  { font-size:26px; }
.admin-ai-header-title { font-weight:700; font-size:14.5px; letter-spacing:-.2px; }
.admin-ai-header-sub   { font-size:11px; opacity:.75; margin-top:1px; }
.admin-ai-header-actions { display:flex; gap:6px; align-items:center; }
.admin-ai-ctrl-btn {
    background: rgba(255,255,255,.15);
    border: none;
    border-radius: 8px;
    padding: 5px 8px;
    cursor: pointer;
    font-size: 14px;
    color: white;
    transition: background .2s;
    line-height: 1;
}
.admin-ai-ctrl-btn:hover { background: rgba(255,255,255,.3); }
.admin-ai-close-btn { font-size: 13px; font-weight: 700; }

/* Messages area */
.admin-ai-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px 14px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    scroll-behavior: smooth;
}
.admin-ai-messages::-webkit-scrollbar { width:4px; }
.admin-ai-messages::-webkit-scrollbar-thumb { background:#d1d5db; border-radius:4px; }

/* Welcome screen */
.admin-ai-welcome {
    text-align:center;
    color:#6b7280;
    font-size:13px;
    padding: 10px 0;
}
.admin-ai-welcome-icon { font-size:36px; margin-bottom:8px; }
.admin-ai-welcome strong { color:#374151; }
.admin-ai-suggestions { display:flex; flex-wrap:wrap; gap:6px; justify-content:center; margin-top:12px; }
.admin-ai-chip {
    background: #ede9fe;
    color: #5b21b6;
    border: none;
    border-radius: 20px;
    padding: 5px 12px;
    font-size: 12px;
    cursor: pointer;
    font-family: inherit;
    transition: background .15s, transform .1s;
}
.admin-ai-chip:hover { background:#ddd6fe; transform:scale(1.03); }

/* Message row */
.admin-ai-msg-row {
    display:flex;
    align-items:flex-end;
    gap:8px;
}
.admin-ai-msg-row--user      { justify-content:flex-end; }
.admin-ai-msg-row--assistant { justify-content:flex-start; }
.admin-ai-avatar { font-size:20px; flex-shrink:0; }

/* Bubbles */
.admin-ai-bubble {
    max-width:78%;
    padding:10px 14px;
    border-radius:16px;
    font-size:13.5px;
    line-height:1.55;
    word-break:break-word;
    white-space:pre-wrap;
}
.admin-ai-bubble--user {
    background: linear-gradient(135deg,#4f46e5,#7c3aed);
    color:white;
    border-bottom-right-radius:4px;
}
.admin-ai-bubble--assistant {
    background:#f3f4f6;
    color:#111827;
    border-bottom-left-radius:4px;
}
/* Markdown-lite rendering */
.admin-ai-bubble--assistant b, .admin-ai-bubble--assistant strong { font-weight:700; }
.admin-ai-bubble--assistant em { font-style:italic; }

/* Typing animation */
.admin-ai-typing {
    display:flex;
    gap:5px;
    align-items:center;
    padding:12px 16px;
}
.admin-ai-typing span {
    width:7px; height:7px;
    border-radius:50%;
    background:#9ca3af;
    animation:admin-ai-bounce 1.2s ease-in-out infinite;
}
.admin-ai-typing span:nth-child(2) { animation-delay:.2s; }
.admin-ai-typing span:nth-child(3) { animation-delay:.4s; }
@keyframes admin-ai-bounce {
    0%,80%,100% { transform:translateY(0); }
    40%          { transform:translateY(-6px); }
}

/* Error bubble */
.admin-ai-error {
    background:#fef2f2;
    border:1px solid #fecaca;
    color:#dc2626;
    border-radius:10px;
    padding:8px 12px;
    font-size:12.5px;
}

/* Input area */
.admin-ai-input-area {
    display:flex;
    align-items:flex-end;
    gap:8px;
    padding:12px 14px;
    border-top:1px solid #e5e7eb;
    background:#fafafa;
    flex-shrink:0;
}
.admin-ai-input {
    flex:1;
    resize:none;
    border:1.5px solid #e5e7eb;
    border-radius:12px;
    padding:9px 12px;
    font-size:13.5px;
    font-family:inherit;
    outline:none;
    background:#fff;
    max-height:100px;
    overflow-y:auto;
    transition:border-color .2s;
    line-height:1.4;
}
.admin-ai-input:focus { border-color:#7c3aed; }
.admin-ai-input:disabled { background:#f3f4f6; }
.admin-ai-send-btn {
    width:38px; height:38px; flex-shrink:0;
    border-radius:50%;
    background:linear-gradient(135deg,#4f46e5,#7c3aed);
    color:white;
    border:none;
    cursor:pointer;
    font-size:16px;
    display:flex;
    align-items:center;
    justify-content:center;
    transition:transform .15s, opacity .15s;
}
.admin-ai-send-btn:hover   { transform:scale(1.1); }
.admin-ai-send-btn:disabled { opacity:.4; cursor:not-allowed; transform:none; }

/* Dark mode compatibility (Filament dark) */
@media (prefers-color-scheme:dark) {
    .admin-ai-modal    { background:#1f2937; color:#f9fafb; }
    .admin-ai-input    { background:#111827; color:#f9fafb; border-color:#374151; }
    .admin-ai-input-area { background:#111827; border-color:#374151; }
    .admin-ai-bubble--assistant { background:#374151; color:#f3f4f6; }
    .admin-ai-messages::-webkit-scrollbar-thumb { background:#4b5563; }
    .admin-ai-chip     { background:#312e81; color:#c4b5fd; }
    .admin-ai-chip:hover { background:#3730a3; }
    .admin-ai-welcome  { color:#9ca3af; }
    .admin-ai-welcome strong { color:#e5e7eb; }
}

/* Mobile */
@media (max-width:480px) {
    .admin-ai-modal  { width:calc(100vw - 40px); right:0; max-height:70vh; }
    .admin-ai-widget { bottom:16px; right:16px; }
}
</style>

{{-- ── Script ───────────────────────────────────────────────── --}}
<script>
(function () {
    const CHAT_ENDPOINT = '{{ route("admin.ai.chat") }}';
    const CSRF_TOKEN    = '{{ csrf_token() }}';

    let isOpen    = false;
    let isLoading = false;
    let ttsOn     = true;

    const adminAI = {
        /* ── Toggle modal ────────────────────── */
        toggle() {
            isOpen = !isOpen;
            const modal = document.getElementById('admin-ai-modal');
            const icon  = document.getElementById('admin-ai-fab-icon');
            if (isOpen) {
                modal.style.display = 'flex';
                modal.style.flexDirection = 'column';
                icon.textContent = '✕';
                setTimeout(() => document.getElementById('admin-ai-input')?.focus(), 150);
                this.scrollToBottom();
            } else {
                modal.style.display = 'none';
                icon.textContent = '🤖';
                window.speechSynthesis?.cancel();
            }
        },

        /* ── Send via suggestion chip ────────── */
        sendSuggestion(btn) {
            const text = btn.textContent.trim();
            document.getElementById('admin-ai-input').value = text;
            this.send();
        },

        /* ── Send message ────────────────────── */
        async send() {
            if (isLoading) return;
            const input  = document.getElementById('admin-ai-input');
            const message = input.value.trim();
            if (!message) return;

            input.value = '';
            input.style.height = 'auto';
            input.disabled = true;
            document.getElementById('admin-ai-send-btn').disabled = true;
            isLoading = true;

            // Remove welcome screen
            const welcome = document.querySelector('.admin-ai-welcome');
            if (welcome) welcome.remove();

            // Append user bubble
            this.appendBubble('user', message);

            // Show typing indicator
            const typingEl = this.showTyping();

            try {
                const res = await fetch(CHAT_ENDPOINT, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                    },
                    body: JSON.stringify({ message }),
                });

                typingEl.remove();

                const data = await res.json();

                if (res.ok && data.reply) {
                    this.appendBubble('assistant', data.reply);
                    // TTS: strip markdown sebelum dibacakan
                    if (ttsOn) {
                        const plain = data.reply
                            .replace(/[*_`#>~\-|]+/g, '')
                            .replace(/\n+/g, '. ')
                            .trim();
                        this.speak(plain);
                    }
                } else {
                    const errMsg = data.error ?? 'Terjadi kesalahan. Coba lagi.';
                    this.appendError(errMsg);
                }
            } catch (err) {
                typingEl.remove();
                this.appendError('Koneksi gagal: ' + err.message);
            } finally {
                isLoading = false;
                input.disabled = false;
                document.getElementById('admin-ai-send-btn').disabled = false;
                input.focus();
            }
        },

        /* ── DOM helpers ─────────────────────── */
        appendBubble(role, text) {
            const messages = document.getElementById('admin-ai-messages');
            const row = document.createElement('div');
            row.className = `admin-ai-msg-row admin-ai-msg-row--${role}`;

            if (role === 'assistant') {
                row.innerHTML = `
                    <span class="admin-ai-avatar">🤖</span>
                    <div class="admin-ai-bubble admin-ai-bubble--assistant">${this.renderMarkdown(text)}</div>`;
            } else {
                row.innerHTML = `
                    <div class="admin-ai-bubble admin-ai-bubble--user">${this.escapeHtml(text)}</div>
                    <span class="admin-ai-avatar">👤</span>`;
            }

            messages.appendChild(row);
            this.scrollToBottom();
        },

        appendError(msg) {
            const messages = document.getElementById('admin-ai-messages');
            const el = document.createElement('div');
            el.className = 'admin-ai-error';
            el.textContent = '⚠️ ' + msg;
            messages.appendChild(el);
            this.scrollToBottom();
        },

        showTyping() {
            const messages = document.getElementById('admin-ai-messages');
            const row = document.createElement('div');
            row.className = 'admin-ai-msg-row admin-ai-msg-row--assistant';
            row.innerHTML = `
                <span class="admin-ai-avatar">🤖</span>
                <div class="admin-ai-bubble admin-ai-bubble--assistant admin-ai-typing">
                    <span></span><span></span><span></span>
                </div>`;
            messages.appendChild(row);
            this.scrollToBottom();
            return row;
        },

        scrollToBottom() {
            const el = document.getElementById('admin-ai-messages');
            if (el) setTimeout(() => { el.scrollTop = el.scrollHeight; }, 30);
        },

        clearHistory() {
            const messages = document.getElementById('admin-ai-messages');
            messages.innerHTML = `
                <div class="admin-ai-welcome">
                    <div class="admin-ai-welcome-icon">👋</div>
                    <p>Riwayat dihapus. Ada yang bisa saya bantu?</p>
                    <div class="admin-ai-suggestions">
                        <button class="admin-ai-chip" onclick="adminAI.sendSuggestion(this)">Siapa yang sedang check-in?</button>
                        <button class="admin-ai-chip" onclick="adminAI.sendSuggestion(this)">Statistik hari ini</button>
                        <button class="admin-ai-chip" onclick="adminAI.sendSuggestion(this)">Berapa tamu aktif sekarang?</button>
                    </div>
                </div>`;
            window.speechSynthesis?.cancel();
        },

        /* ── Markdown renderer (lite) ─────────── */
        renderMarkdown(text) {
            if (window.marked) return marked.parse(text);
            // Fallback: basic formatting
            return text
                .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                .replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>')
                .replace(/\*(.+?)\*/g,'<em>$1</em>')
                .replace(/`(.+?)`/g,'<code>$1</code>')
                .replace(/\n/g,'<br>');
        },

        escapeHtml(text) {
            return text.replace(/&/g,'&amp;').replace(/</g,'&lt;')
                       .replace(/>/g,'&gt;').replace(/\n/g,'<br>');
        },

        /* ── TTS ─────────────────────────────── */
        toggleTts() {
            ttsOn = !ttsOn;
            const btn = document.getElementById('admin-ai-tts-btn');
            btn.textContent = ttsOn ? '🔊' : '🔇';
            btn.title = ttsOn ? 'Matikan suara' : 'Nyalakan suara';
            if (!ttsOn) window.speechSynthesis?.cancel();
        },

        speak(text) {
            if (!('speechSynthesis' in window)) return;
            window.speechSynthesis.cancel();
            const utt   = new SpeechSynthesisUtterance(text);
            utt.lang    = 'id-ID';
            utt.rate    = 1.0;
            utt.pitch   = 1.0;
            const voices = window.speechSynthesis.getVoices();
            const voice  = voices.find(v => v.lang === 'id-ID' || v.name.includes('Indonesia'));
            if (voice) utt.voice = voice;
            window.speechSynthesis.speak(utt);
        },
    };

    // Expose ke global scope
    window.adminAI = adminAI;

    // Pre-load voices
    if ('speechSynthesis' in window) {
        window.speechSynthesis.getVoices();
        window.speechSynthesis.onvoiceschanged = () => window.speechSynthesis.getVoices();
    }
})();
</script>
