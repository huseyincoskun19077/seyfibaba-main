@if(Auth::guard('web')->check() && Auth::guard('web')->user()->seller)
<style>
  .sai-fab {
    position: fixed; bottom: 24px; right: 24px; z-index: 1050;
    width: 58px; height: 58px; border-radius: 50%; border: none;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff; font-size: 1.4rem; cursor: pointer;
    box-shadow: 0 8px 28px rgba(99,102,241,.45);
    transition: transform .2s;
  }
  .sai-fab:hover { transform: scale(1.06); }
  .sai-panel {
    display: none; position: fixed; bottom: 94px; right: 24px; z-index: 1050;
    width: min(380px, calc(100vw - 32px)); height: min(520px, calc(100vh - 120px));
    background: #fff; border-radius: 20px;
    box-shadow: 0 16px 48px rgba(15,23,42,.18);
    flex-direction: column; overflow: hidden;
  }
  .sai-panel.open { display: flex; }
  .sai-head {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff; padding: 16px 18px; font-weight: 700;
    display: flex; align-items: center; justify-content: space-between;
  }
  .sai-head small { display: block; font-weight: 400; opacity: .9; font-size: .78rem; }
  .sai-msgs { flex: 1; overflow-y: auto; padding: 14px; background: #f8fafc; }
  .sai-msg { margin-bottom: 10px; max-width: 92%; padding: 10px 12px; border-radius: 14px; font-size: .9rem; line-height: 1.45; white-space: pre-wrap; }
  .sai-msg.user { margin-left: auto; background: #6366f1; color: #fff; border-bottom-right-radius: 4px; }
  .sai-msg.bot { background: #fff; border: 1px solid #e2e8f0; border-bottom-left-radius: 4px; }
  .sai-input-wrap { display: flex; gap: 8px; padding: 12px; border-top: 1px solid #e2e8f0; background: #fff; }
  .sai-input { flex: 1; border: 1px solid #e2e8f0; border-radius: 12px; padding: 10px 12px; font-size: .95rem; resize: none; max-height: 80px; }
  .sai-send { border: none; border-radius: 12px; padding: 0 16px; background: #6366f1; color: #fff; font-weight: 600; }
  .sai-send:disabled { opacity: .5; }
  @media (max-width: 576px) {
    .sai-fab { bottom: 16px; right: 16px; }
    .sai-panel { right: 16px; bottom: 84px; }
  }
</style>

<button type="button" class="sai-fab" id="saiFab" title="AI Asistan">
  <i class="fas fa-robot"></i>
</button>

<div class="sai-panel" id="saiPanel">
  <div class="sai-head">
    <div>
      AI Satıcı Asistanı
      <small>Fiyat, stok, açıklama — sor ve yaptır</small>
    </div>
    <button type="button" class="btn btn-sm btn-light" id="saiClose" style="border-radius:8px;">✕</button>
  </div>
  <div class="sai-msgs" id="saiMsgs">
    <div class="sai-msg bot">Merhaba! Ürün fiyatı, stok veya açıklama güncellemek için yazın. Örn: «Berber koltuğumun fiyatını 12500 yap»</div>
  </div>
  <div class="sai-input-wrap">
    <textarea class="sai-input" id="saiInput" rows="1" placeholder="Mesajınız…"></textarea>
    <button type="button" class="sai-send" id="saiSend">Gönder</button>
  </div>
</div>

<script>
(function () {
  var fab = document.getElementById('saiFab');
  var panel = document.getElementById('saiPanel');
  var closeBtn = document.getElementById('saiClose');
  var msgs = document.getElementById('saiMsgs');
  var input = document.getElementById('saiInput');
  var sendBtn = document.getElementById('saiSend');
  if (!fab) return;

  var history = [];
  var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  function appendMsg(text, role) {
    var el = document.createElement('div');
    el.className = 'sai-msg ' + (role === 'user' ? 'user' : 'bot');
    el.textContent = text;
    msgs.appendChild(el);
    msgs.scrollTop = msgs.scrollHeight;
  }

  fab.addEventListener('click', function () { panel.classList.toggle('open'); if (panel.classList.contains('open')) input.focus(); });
  closeBtn.addEventListener('click', function () { panel.classList.remove('open'); });

  function send() {
    var text = input.value.trim();
    if (!text) return;
    appendMsg(text, 'user');
    input.value = '';
    sendBtn.disabled = true;
    sendBtn.textContent = '…';

    fetch('{{ route('seller.ai-assistant.chat') }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
      },
      body: JSON.stringify({ message: text, history: history }),
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) {
          history = data.history || [];
          appendMsg(data.reply || 'Tamam.', 'bot');
        } else {
          appendMsg(data.message || 'Bir hata oluştu.', 'bot');
        }
      })
      .catch(function () { appendMsg('Bağlantı hatası. Tekrar deneyin.', 'bot'); })
      .finally(function () {
        sendBtn.disabled = false;
        sendBtn.textContent = 'Gönder';
      });
  }

  sendBtn.addEventListener('click', send);
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
  });
})();
</script>
@endif
