<div
    class="chatbot-wrapper"
    x-data="{
        open: false,
        scrollToBottom() {
            this.$nextTick(() => {
                const el = document.getElementById('chat-messages');
                if (el) el.scrollTop = el.scrollHeight;
            });
        }
    }"
    x-on:chatbot-scrolled.window="scrollToBottom()"
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
                    {!! nl2br(e($msg['content'])) !!}
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
    {{-- Inline styles — harus di dalam root div (Livewire v3 hanya boleh 1 root element) --}}
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
.chatbot-clear-btn:hover { background: rgba(255,255,255,.28); }

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
