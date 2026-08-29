@extends('admin.master_layout')
@section('title')
<title>AI Chat Ayarları - {{ __('admin.Admin Panel') }}</title>
@endsection
@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1><i class="fas fa-robot mr-2"></i>AI Chat Asistanı</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item">AI Chat</div>
      </div>
    </div>

    <div class="section-body">

      {{-- Status Cards --}}
      <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
          <div class="card card-statistic-1">
            <div class="card-icon bg-primary"><i class="fas fa-power-off"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Durum</h4></div>
              <div class="card-body">
                @if($setting->ai_chat_enabled)
                  <span class="text-success font-weight-bold">Aktif</span>
                @else
                  <span class="text-danger font-weight-bold">Pasif</span>
                @endif
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
          <div class="card card-statistic-1">
            <div class="card-icon bg-warning"><i class="fas fa-microchip"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Sağlayıcı</h4></div>
              <div class="card-body">{{ ucfirst($setting->ai_chat_provider ?? 'groq') }}</div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
          <div class="card card-statistic-1">
            <div class="card-icon bg-success"><i class="fas fa-book"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Bilgi Bankası</h4></div>
              <div class="card-body">{{ $knowledge->count() }} kayıt</div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
          <div class="card card-statistic-1">
            <div class="card-icon bg-info"><i class="fas fa-comments"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Konuşmalar</h4></div>
              <div class="card-body">{{ $conversations->count() }}</div>
            </div>
          </div>
        </div>
      </div>

      {{-- Settings Form --}}
      <form action="{{ route('admin.update-ai-chat-settings') }}" method="POST">
        @csrf
        @method('PUT')

        {{-- Sistem Prompt - Full Width --}}
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h4><i class="fas fa-brain mr-2"></i>Sistem Prompt (AI Davranış Talimatı)</h4>
              </div>
              <div class="card-body">
                <div class="form-group mb-0">
                  <textarea name="ai_chat_system_prompt" class="form-control" rows="30" style="font-family: 'Courier New', monospace; font-size: 14px; line-height: 1.8; background: #f8f9fa; border: 1px solid #e4e6fc; min-height: 600px; resize: vertical; padding: 20px; white-space: pre-wrap; word-wrap: break-word;" placeholder="AI asistanın davranış talimatlarını buraya yazın...">{{ $setting->ai_chat_system_prompt }}</textarea>
                  <div class="d-flex justify-content-between mt-2">
                    <small class="text-muted"><i class="fas fa-info-circle mr-1"></i>Boş bırakılırsa varsayılan Seyfibaba asistan promptu kullanılır. <strong>Güvenlik kuralları</strong> (prompt injection, altyapı sızıntısı) sunucu tarafında her zaman uygulanır ve bu alandan geçersiz kılınamaz.</small>
                    <small class="text-muted" id="promptCharCount"></small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          {{-- Genel Ayarlar --}}
          <div class="col-lg-6">
            <div class="card">
              <div class="card-header"><h4><i class="fas fa-cog mr-2"></i>Genel Ayarlar</h4></div>
              <div class="card-body">

                <div class="alert alert-info">
                  <i class="fas fa-info-circle mr-1"></i>
                  AI Chat, <a href="{{ route('admin.ai-settings') }}"><strong>AI Ayarları</strong></a> sayfasındaki API anahtarı ve model yapılandırmasını kullanır. Ayrıca yapılandırma gerekmez.
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <div class="control-label">AI Chat Durumu</div>
                      <label class="custom-switch mt-2">
                        <input type="checkbox" name="ai_chat_enabled" class="custom-switch-input" {{ $setting->ai_chat_enabled ? 'checked' : '' }}>
                        <span class="custom-switch-indicator"></span>
                        <span class="custom-switch-description">Aktif</span>
                      </label>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <div class="control-label">Misafir Kullanıcılar</div>
                      <label class="custom-switch mt-2">
                        <input type="checkbox" name="ai_chat_guest_enabled" class="custom-switch-input" {{ $setting->ai_chat_guest_enabled ? 'checked' : '' }}>
                        <span class="custom-switch-indicator"></span>
                        <span class="custom-switch-description">Giriş yapmadan chat</span>
                      </label>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>

          {{-- Model Ayarları --}}
          <div class="col-lg-6">
            <div class="card">
              <div class="card-header"><h4><i class="fas fa-sliders-h mr-2"></i>Chat Ayarları</h4></div>
              <div class="card-body">

                <div class="row">
                  <div class="col-6">
                    <div class="form-group">
                      <label>Max Token <i class="fas fa-question-circle text-muted" data-toggle="tooltip" title="AI'ın verebileceği maksimum yanıt uzunluğu. 500-1024 arası önerilir."></i></label>
                      <input type="number" name="ai_chat_max_tokens" class="form-control" value="{{ $setting->ai_chat_max_tokens }}" min="100" max="4096">
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="form-group">
                      <label>Temperature <i class="fas fa-question-circle text-muted" data-toggle="tooltip" title="0 = tutarlı yanıtlar, 1 = yaratıcı yanıtlar. Müşteri hizmeti için 0.3-0.5 önerilir."></i></label>
                      <input type="number" name="ai_chat_temperature" class="form-control" value="{{ $setting->ai_chat_temperature }}" min="0" max="2" step="0.1">
                    </div>
                  </div>
                </div>

                <div class="alert alert-light border mt-3">
                  <i class="fas fa-lightbulb text-warning mr-1"></i>
                  <strong>İpucu:</strong> Müşteri hizmeti için düşük temperature (0.3-0.5) ve 500-1024 arası max token önerilir.
                </div>

              </div>
            </div>
          </div>
        </div>

        <div class="text-right mb-4">
          <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save mr-2"></i>Ayarları Kaydet</button>
        </div>
      </form>

      {{-- Knowledge Base --}}
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h4><i class="fas fa-book mr-2"></i>Bilgi Bankası ({{ $knowledge->count() }} kayıt)</h4>
              <div class="card-header-action">
                <button class="btn btn-primary" data-toggle="modal" data-target="#addKnowledgeModal"><i class="fas fa-plus mr-1"></i> Yeni Ekle</button>
              </div>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                  <thead>
                    <tr>
                      <th style="width:50px">#</th>
                      <th style="width:120px">Kategori</th>
                      <th>Soru</th>
                      <th>Cevap</th>
                      <th style="width:60px">Sıra</th>
                      <th style="width:80px">Durum</th>
                      <th style="width:100px">İşlem</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($knowledge as $entry)
                    <tr>
                      <td>{{ $entry->id }}</td>
                      <td><span class="badge badge-info">{{ $entry->category }}</span></td>
                      <td title="{{ $entry->question }}">{{ Str::limit($entry->question, 50) }}</td>
                      <td title="{{ $entry->answer }}">{{ Str::limit($entry->answer, 50) }}</td>
                      <td class="text-center">{{ $entry->sort_order }}</td>
                      <td>
                        <form action="{{ route('admin.ai-chat-knowledge.toggle', $entry->id) }}" method="POST" class="d-inline">
                          @csrf @method('PUT')
                          <button type="submit" class="btn btn-sm btn-block {{ $entry->is_active ? 'btn-success' : 'btn-secondary' }}">
                            <i class="fas {{ $entry->is_active ? 'fa-check' : 'fa-times' }} mr-1"></i>{{ $entry->is_active ? 'Aktif' : 'Pasif' }}
                          </button>
                        </form>
                      </td>
                      <td>
                        <div class="btn-group">
                          <button class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editKnowledgeModal{{ $entry->id }}" title="Düzenle"><i class="fas fa-edit"></i></button>
                          <form action="{{ route('admin.ai-chat-knowledge.delete', $entry->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bu kaydı silmek istediğinize emin misiniz?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" title="Sil"><i class="fas fa-trash"></i></button>
                          </form>
                        </div>
                      </td>
                    </tr>

                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-inbox mr-1"></i>Henüz bilgi bankası kaydı eklenmemiş.</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Conversations --}}
      @if($conversations->count() > 0)
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header"><h4><i class="fas fa-history mr-2"></i>Son Konuşmalar ({{ $conversations->count() }})</h4></div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                  <thead>
                    <tr>
                      <th style="width:50px">#</th>
                      <th>Kullanıcı</th>
                      <th>Session</th>
                      <th style="width:100px">Mesaj</th>
                      <th style="width:140px">Tarih</th>
                      <th style="width:80px">İşlem</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($conversations as $conv)
                    <tr>
                      <td>{{ $conv->id }}</td>
                      <td>
                        @if($conv->user)
                          <i class="fas fa-user text-primary mr-1"></i>{{ $conv->user->name }}
                        @else
                          <i class="fas fa-user-secret text-muted mr-1"></i><span class="text-muted">Misafir</span>
                        @endif
                      </td>
                      <td><code class="text-xs">{{ Str::limit($conv->session_id, 16) }}</code></td>
                      <td class="text-center"><span class="badge badge-primary">{{ $conv->messages_count }}</span></td>
                      <td>{{ $conv->updated_at->format('d.m.Y H:i') }}</td>
                      <td>
                        <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#convModal{{ $conv->id }}" title="Konuşmayı Görüntüle"><i class="fas fa-eye"></i></button>
                      </td>
                    </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
      @endif

    </div>
  </section>
</div>

{{-- Edit Knowledge Modals (outside table for z-index) --}}
@foreach($knowledge as $entry)
<div class="modal fade" id="editKnowledgeModal{{ $entry->id }}" tabindex="-1" role="dialog" style="z-index:1050;">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form action="{{ route('admin.ai-chat-knowledge.update', $entry->id) }}" method="POST">
        @csrf @method('PUT')
        <div class="modal-header bg-warning text-white">
          <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Bilgi Düzenle #{{ $entry->id }}</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Kategori</label>
            <input type="text" name="category" class="form-control" value="{{ $entry->category }}" required>
          </div>
          <div class="form-group">
            <label>Soru</label>
            <textarea name="question" class="form-control" rows="3" required>{{ $entry->question }}</textarea>
          </div>
          <div class="form-group">
            <label>Cevap</label>
            <textarea name="answer" class="form-control" rows="5" required>{{ $entry->answer }}</textarea>
          </div>
          <div class="row">
            <div class="col-6">
              <div class="form-group">
                <label>Sıra</label>
                <input type="number" name="sort_order" class="form-control" value="{{ $entry->sort_order }}">
              </div>
            </div>
            <div class="col-6">
              <div class="form-group mt-4">
                <label class="custom-switch">
                  <input type="checkbox" name="is_active" class="custom-switch-input" {{ $entry->is_active ? 'checked' : '' }}>
                  <span class="custom-switch-indicator"></span>
                  <span class="custom-switch-description">Aktif</span>
                </label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Güncelle</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endforeach

{{-- Add Knowledge Modal --}}
<div class="modal fade" id="addKnowledgeModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form action="{{ route('admin.ai-chat-knowledge.store') }}" method="POST">
        @csrf
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title"><i class="fas fa-plus mr-2"></i>Yeni Bilgi Ekle</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Kategori</label>
            <select name="category" class="form-control selectric">
              <option value="genel">Genel</option>
              <option value="kargo">Kargo</option>
              <option value="iade">İade</option>
              <option value="odeme">Ödeme</option>
              <option value="urun">Ürün</option>
              <option value="satici">Satıcı</option>
              <option value="hesap">Hesap</option>
            </select>
            <small class="text-muted">Veya aşağıya özel kategori yazabilirsiniz</small>
          </div>
          <div class="form-group">
            <label>Soru</label>
            <textarea name="question" class="form-control" rows="3" required placeholder="Müşterinin sorabileceği soru..."></textarea>
          </div>
          <div class="form-group">
            <label>Cevap</label>
            <textarea name="answer" class="form-control" rows="5" required placeholder="AI'ın vereceği cevap..."></textarea>
          </div>
          <div class="row">
            <div class="col-6">
              <div class="form-group">
                <label>Sıra</label>
                <input type="number" name="sort_order" class="form-control" value="0">
              </div>
            </div>
            <div class="col-6">
              <div class="form-group mt-4">
                <label class="custom-switch">
                  <input type="checkbox" name="is_active" class="custom-switch-input" checked>
                  <span class="custom-switch-indicator"></span>
                  <span class="custom-switch-description">Aktif</span>
                </label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Ekle</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Conversation Detail Modals (inline, no AJAX) --}}
@foreach($conversations as $conv)
<div class="modal fade" id="convModal{{ $conv->id }}" tabindex="-1" role="dialog" style="z-index:1050;">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title"><i class="fas fa-comments mr-2"></i>Konuşma #{{ $conv->id }} — {{ $conv->user ? $conv->user->name : 'Misafir' }}</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body p-0" style="max-height:500px;overflow-y:auto;">
        @forelse($conv->messages as $msg)
        <div class="p-3" style="{{ $msg->role === 'user' ? 'background:#e3f2fd;border-left:3px solid #2196f3;' : 'background:#f5f5f5;border-left:3px solid #4caf50;' }}">
          <div class="d-flex justify-content-between mb-2">
            <small class="font-weight-bold">
              @if($msg->role === 'user')
                <i class="fas fa-user-circle text-primary mr-1"></i> Müşteri
              @else
                <i class="fas fa-robot text-success mr-1"></i> AI Asistan
              @endif
            </small>
            <small class="text-muted">{{ $msg->created_at->format('d.m.Y H:i:s') }}</small>
          </div>
          <div style="white-space:pre-wrap;font-size:14px;">{{ $msg->content }}</div>
        </div>
        @empty
        <p class="text-center text-muted py-4"><i class="fas fa-inbox mr-1"></i>Mesaj bulunamadı.</p>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endforeach

@endsection

@push('js')
<script>
// Prompt character count
var $prompt = $('textarea[name="ai_chat_system_prompt"]');
var $counter = $('#promptCharCount');
function updateCharCount() {
    $counter.text($prompt.val().length + ' karakter');
}
$prompt.on('input', updateCharCount);
updateCharCount();

// Tooltips
$('[data-toggle="tooltip"]').tooltip();
</script>
@endpush
