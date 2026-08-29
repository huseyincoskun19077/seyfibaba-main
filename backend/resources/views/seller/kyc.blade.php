@extends('seller.master_layout')
@section('title')<title>Hesap Doğrulama (KYC)</title>@endsection
@section('seller-content')
@php
  $onboarding = app(\App\Services\SellerIyzicoOnboardingService::class);
  $rawType = old('seller_type', $seller->seller_type ?? 'limited_company');
  $sellerType = $onboarding->normalizeSellerType($rawType === 'corporate' ? 'limited_company' : $rawType);
  $userEmail = optional(auth()->user())->email;
  $hasRealEmail = $onboarding->hasValidContactEmail($userEmail);
  $typeLabels = [
    'identity_front' => 'Kimlik Ön Yüz',
    'identity_back' => 'Kimlik Arka Yüz',
    'tax_certificate' => 'Vergi Levhası',
    'address_proof' => 'Adres Belgesi',
    'bank_statement' => 'Banka Hesap Özeti',
    'iban_document' => 'IBAN Belgesi',
  ];
  $sellerTypeLabels = [
    'sole_proprietorship' => 'Şahıs Şirketi',
    'limited_company' => 'Ltd / A.Ş.',
  ];
  $requiredDocTypes = $onboarding->requiredDocumentTypes($sellerType);
  $approvedTypes = $documents->where('status', 'approved')->pluck('document_type')->unique();
  $pendingTypes = $documents->where('status', 'pending')->pluck('document_type')->unique();
  $rejectedDocs = $documents->where('status', 'rejected')->sortByDesc('reviewed_at');
  $missingDocTypes = collect($requiredDocTypes)->filter(function ($type) use ($approvedTypes, $pendingTypes) {
      return ! $approvedTypes->contains($type) && ! $pendingTypes->contains($type);
  });
  $infoChecklist = [
    ['label' => 'E-posta (Iyzico)', 'ok' => $hasRealEmail],
    ['label' => 'Satıcı tipi seçildi', 'ok' => filled($seller->seller_type)],
    ['label' => 'Adres bilgisi', 'ok' => filled($seller->address) && $seller->address !== 'Adres bilgisi sonra tamamlanacak'],
    ['label' => 'IBAN', 'ok' => filled($seller->iban)],
  ];
  if ($sellerType === 'sole_proprietorship') {
    $infoChecklist[] = ['label' => 'TC Kimlik No', 'ok' => filled($seller->tc_identity)];
  }
  if ($sellerType === 'limited_company') {
    $infoChecklist[] = ['label' => 'Yetkili TC Kimlik No', 'ok' => filled($seller->tc_identity)];
    $infoChecklist[] = ['label' => 'Vergi No', 'ok' => filled($seller->tax_number)];
  }
  if ($sellerType === 'sole_proprietorship' || $sellerType === 'limited_company') {
    $infoChecklist[] = ['label' => 'Ticari unvan', 'ok' => filled($seller->legal_company_title)];
    $infoChecklist[] = ['label' => 'Vergi dairesi', 'ok' => filled($seller->tax_office)];
  }
@endphp
<div class="main-content">
  <section class="section">
    <div class="section-header"><h1>Hesap Doğrulama (KYC)</h1></div>
    <div class="section-body">

      <div class="row mb-4">
        <div class="col-md-4">
          <div class="card card-statistic-1">
            <div class="card-icon {{ $seller->kyc_status == 'approved' ? 'bg-success' : ($seller->kyc_status == 'pending' ? 'bg-warning' : ($seller->kyc_status == 'rejected' ? 'bg-danger' : 'bg-secondary')) }}">
              <i class="fas fa-id-card"></i>
            </div>
            <div class="card-wrap">
              <div class="card-header"><h4>Doğrulama Durumu</h4></div>
              <div class="card-body">
                @switch($seller->kyc_status)
                  @case('approved') <span class="text-success">Onaylı</span> @break
                  @case('pending') <span class="text-warning">Onay Bekliyor</span> @break
                  @case('rejected') <span class="text-danger">Reddedildi</span> @break
                  @default <span class="text-secondary">Belge Yüklenmedi</span>
                @endswitch
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card card-statistic-1">
            <div class="card-icon bg-info"><i class="fas fa-file-alt"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Yüklenen Belgeler</h4></div>
              <div class="card-body">{{ $documents->count() }}</div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card card-statistic-1">
            <div class="card-icon {{ $seller->iyzico_sub_merchant_key ? 'bg-success' : 'bg-secondary' }}"><i class="fas fa-credit-card"></i></div>
            <div class="card-wrap">
              <div class="card-header"><h4>Ödeme Alma</h4></div>
              <div class="card-body">{{ $seller->iyzico_sub_merchant_key ? 'Aktif' : 'Pasif' }}</div>
            </div>
          </div>
        </div>
      </div>

      @if($seller->kyc_status == 'approved')
        @if($seller->iyzico_sub_merchant_key)
          <div class="alert alert-success">
            <i class="fas fa-check-circle mr-1"></i> Hesabınız doğrulanmış ve ödeme alma aktif. Ürün ekleyip satış yapabilirsiniz.
          </div>
        @else
          <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            KYC onaylı ancak Iyzico alt üye işyeri henüz oluşmadı. Aşağıdaki bilgilerin eksiksiz olduğundan emin olun; admin panelinden tekrar oluşturulabilir.
            @php $iyzicoMissing = $onboarding->missingFields($seller); @endphp
            @if(count($iyzicoMissing))
              <div class="mt-2"><strong>Eksik:</strong> {{ implode(', ', $iyzicoMissing) }}</div>
            @endif
          </div>
        @endif
      @elseif($seller->kyc_status == 'rejected')
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-triangle mr-1"></i> <strong>Hesap doğrulamanız reddedildi.</strong>
          Aşağıdaki “Eksik / Bekleyen Adımlar” listesinden hangi bilginin veya belgenin sorunlu olduğunu görün, düzeltip yeniden yükleyin.
          @php
            $rejectNote = $documents->where('status', 'rejected')->sortByDesc('reviewed_at')->first()?->admin_note;
          @endphp
          @if($rejectNote)
            <p class="mb-0 mt-2"><strong>Son red gerekçesi:</strong> {{ $rejectNote }}</p>
          @endif
        </div>
      @elseif($seller->kyc_status == 'pending')
        <div class="alert alert-warning">
          <i class="fas fa-clock mr-1"></i> Belgeleriniz inceleniyor. Onaylandığında ürün eklemeye başlayabilirsiniz.
        </div>
      @endif

      @if($seller->kyc_status != 'approved' || empty($seller->iyzico_sub_merchant_key))
      <div class="card border-warning mb-4">
        <div class="card-header bg-light"><h4 class="mb-0"><i class="fas fa-tasks mr-1"></i> Eksik / Bekleyen Adımlar</h4></div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <h6>Bilgi kontrol listesi</h6>
              <ul class="list-unstyled seller-kyc-checklist mb-0">
                @foreach($infoChecklist as $item)
                  <li>
                    @if($item['ok'])
                      <i class="fas fa-check-circle text-success mr-1"></i>
                    @else
                      <i class="fas fa-times-circle text-danger mr-1"></i>
                    @endif
                    {{ $item['label'] }}
                    @unless($item['ok']) <span class="badge badge-danger">Eksik</span> @endunless
                  </li>
                @endforeach
              </ul>
            </div>
            <div class="col-md-6 mb-3">
              <h6>Zorunlu belgeler ({{ $sellerTypeLabels[$sellerType] ?? $sellerType }})</h6>
              <ul class="list-unstyled seller-kyc-checklist mb-0">
                @foreach($requiredDocTypes as $docType)
                  @php
                    $isApproved = $approvedTypes->contains($docType);
                    $isPending = $pendingTypes->contains($docType);
                    $isRejected = $rejectedDocs->where('document_type', $docType)->isNotEmpty() && ! $isApproved && ! $isPending;
                  @endphp
                  <li>
                    @if($isApproved)
                      <i class="fas fa-check-circle text-success mr-1"></i> {{ $typeLabels[$docType] ?? $docType }}
                      <span class="badge badge-success">Onaylı</span>
                    @elseif($isPending)
                      <i class="fas fa-clock text-warning mr-1"></i> {{ $typeLabels[$docType] ?? $docType }}
                      <span class="badge badge-warning">İnceleniyor</span>
                    @elseif($isRejected)
                      <i class="fas fa-exclamation-circle text-danger mr-1"></i> {{ $typeLabels[$docType] ?? $docType }}
                      <span class="badge badge-danger">Yeniden yükleyin</span>
                    @else
                      <i class="fas fa-times-circle text-danger mr-1"></i> {{ $typeLabels[$docType] ?? $docType }}
                      <span class="badge badge-danger">Eksik</span>
                    @endif
                  </li>
                @endforeach
              </ul>
            </div>
          </div>

          @if($rejectedDocs->count())
            <div class="alert alert-danger mb-0 mt-2">
              <strong>Reddedilen belgeler:</strong>
              <ul class="mb-0 mt-2">
                @foreach($rejectedDocs as $doc)
                  <li>
                    {{ $typeLabels[$doc->document_type] ?? $doc->document_type }}
                    @if($doc->admin_note)
                      — <em>{{ $doc->admin_note }}</em>
                    @endif
                  </li>
                @endforeach
              </ul>
              <p class="mb-0 mt-2 small">Reddedilen belgeyi aynı türde yeniden yükleyin; ardından inceleme tekrar başlar.</p>
            </div>
          @elseif($missingDocTypes->count() || collect($infoChecklist)->contains(fn ($i) => ! $i['ok']))
            <div class="alert alert-info mb-0 mt-2">
              Eksikleri tamamlayıp belgeleri yükledikten sonra durum <strong>Onay Bekliyor</strong> olur. Onay sonrası Iyzico alt üye işyeri otomatik oluşur ve satış yapabilirsiniz.
            </div>
          @endif
        </div>
      </div>
      @endif

      <div class="row mb-3">
        <div class="col-lg-12">
          <div class="card border-primary">
            <div class="card-header bg-light"><h4 class="mb-0"><i class="fas fa-clipboard-list mr-1"></i> Gerekli Bilgiler ve Belge</h4></div>
            <div class="card-body">
              <p class="text-muted mb-3">Şimdilik yalnızca <strong>vergi levhası</strong> yüklenir. E-posta, IBAN ve adres Iyzico ödeme alımı için zorunludur; kimlik belgesi istenmez.</p>
              <div class="row">
                <div class="col-md-6">
                  <h6 class="text-primary">Şahıs Şirketi</h6>
                  <ul class="mb-0 pl-3">
                    <li>Sahip TC Kimlik No</li>
                    <li>Ticari unvan + vergi dairesi</li>
                    <li>IBAN + adres + e-posta</li>
                    <li>Vergi levhası</li>
                  </ul>
                </div>
                <div class="col-md-6">
                  <h6 class="text-primary">Ltd / A.Ş.</h6>
                  <ul class="mb-0 pl-3">
                    <li>Vergi no + vergi dairesi + unvan</li>
                    <li>Yetkili TC Kimlik No</li>
                    <li>IBAN + adres + e-posta</li>
                    <li>Vergi levhası</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header"><h4><i class="fas fa-user-edit mr-1"></i> Satıcı Bilgileri (Iyzico)</h4></div>
            <div class="card-body">
              @if($errors->any())
                <div class="alert alert-danger">
                  @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
              @endif
              <form action="{{ route('seller.kyc.update-info') }}" method="POST">
                @csrf
                <div class="row">
                  @unless($hasRealEmail)
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>E-posta <span class="text-danger">*</span></label>
                      <input type="email" class="form-control" name="email" id="seller_email"
                        value="{{ old('email') }}"
                        required
                        autocomplete="email"
                        placeholder="ornek@sirket.com">
                      <small class="text-muted">Iyzico alt üye işyeri kaydı için zorunludur. Profil düzenleme sayfasından da ekleyebilirsiniz.</small>
                    </div>
                  </div>
                  @endunless
                  <div class="col-md-{{ $hasRealEmail ? '12' : '6' }}">
                    <div class="form-group">
                      <label>Satıcı Tipi <span class="text-danger">*</span></label>
                      <select name="seller_type" id="seller_type" class="form-control" required>
                        <option value="limited_company" {{ $sellerType === 'limited_company' ? 'selected' : '' }}>Ltd / A.Ş. (Kurumsal)</option>
                        <option value="sole_proprietorship" {{ $sellerType === 'sole_proprietorship' ? 'selected' : '' }}>Şahıs Şirketi</option>
                      </select>
                      <small class="text-muted">Tip, Iyzico alt üye işyeri kaydını belirler. Yanlış tip seçilirse ödeme alınamaz.</small>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-4 seller-type-field seller-type-needs-tc">
                    <div class="form-group">
                      <label id="tc_label">TC Kimlik No <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" name="tc_identity" id="tc_identity"
                        value="{{ old('tc_identity', $seller->tc_identity) }}"
                        maxlength="11"
                        pattern="\d{11}"
                        title="11 haneli TC Kimlik Numarası">
                      <small class="text-muted" id="tc_hint">11 haneli, sadece rakam</small>
                    </div>
                  </div>
                  <div class="col-md-5 seller-type-field">
                    <div class="form-group">
                      <label>IBAN <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" name="iban" id="iban_input"
                        value="{{ old('iban', $seller->iban) }}"
                        maxlength="26"
                        pattern="TR[0-9]{24}"
                        title="TR ile başlayan 26 karakterli IBAN"
                        required>
                      <small class="text-muted">TR + 24 rakam, boşluksuz</small>
                    </div>
                  </div>
                  <div class="col-md-3 seller-type-field seller-type-limited">
                    <div class="form-group">
                      <label>Vergi No <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" name="tax_number" id="tax_number" value="{{ old('tax_number', $seller->tax_number) }}">
                    </div>
                  </div>
                  <div class="col-md-3 seller-type-field seller-type-sole">
                    <div class="form-group">
                      <label>Vergi No</label>
                      <input type="text" class="form-control" name="tax_number_sole" id="tax_number_sole" value="{{ old('tax_number', $seller->tax_number) }}" disabled>
                      <small class="text-muted">Boş bırakılırsa TC kullanılır</small>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6 seller-type-field">
                    <div class="form-group">
                      <label>Adres <span class="text-danger">*</span></label>
                      <textarea class="form-control" name="address" rows="2" required
                        placeholder="Cadde, Mahalle, İlçe, İl (Iyzico için zorunlu)">{{ old('address', $seller->address) }}</textarea>
                      <small class="text-muted">Iyzico alt üye işyeri kaydı için tam adres zorunludur.</small>
                    </div>
                  </div>
                  <div class="col-md-3 seller-type-field seller-type-company">
                    <div class="form-group">
                      <label>Vergi Dairesi <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" name="tax_office" id="tax_office" value="{{ old('tax_office', $seller->tax_office) }}">
                    </div>
                  </div>
                  <div class="col-md-3 seller-type-field seller-type-company">
                    <div class="form-group">
                      <label>Ticari Unvan <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" name="legal_company_title" id="legal_company_title" value="{{ old('legal_company_title', $seller->legal_company_title) }}">
                    </div>
                  </div>
                </div>
                <button type="submit" class="btn btn-success">
                  <i class="fas fa-save mr-1"></i> Bilgileri Kaydet
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-6">
          <div class="card">
            <div class="card-header"><h4>Belge Yükle</h4></div>
            <div class="card-body">
              @unless($hasRealEmail)
                <div class="alert alert-warning">
                  <i class="fas fa-envelope mr-1"></i>
                  Belge yüklemeden önce yukarıdaki <strong>Satıcı Bilgileri</strong> bölümünden geçerli bir e-posta adresi kaydedin. Iyzico alt üye işyeri e-posta olmadan oluşturulamaz.
                </div>
              @endunless
              <form action="{{ route('seller.kyc.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="form-group">
                  <label>Belge Türü <span class="text-danger">*</span></label>
                  <select name="document_type" id="document_type" class="form-control" required>
                    <option value="tax_certificate" selected>Vergi Levhası</option>
                  </select>
                  <small class="text-muted" id="document_type_hint">Şimdilik yalnızca vergi levhası yeterlidir. Kimlik belgesi istenmez.</small>
                </div>

                <div class="form-group">
                  <label>Belge Dosyası <span class="text-danger">*</span></label>
                  <input type="file" class="form-control-file" name="document" accept=".pdf,.jpg,.jpeg,.png,image/*,application/pdf" required>
                  <small class="text-muted">PDF, JPG veya PNG (max 5MB). Telefonda kamera veya galeri seçebilirsiniz.</small>
                </div>

                <button type="submit" class="btn btn-primary" @unless($hasRealEmail) disabled title="Önce e-posta adresinizi kaydedin" @endunless>
                  <i class="fas fa-upload mr-1"></i> Belgeyi Yükle
                </button>
              </form>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="card">
            <div class="card-header"><h4>Yüklenen Belgeler</h4></div>
            <div class="card-body">
              @if($documents->count() > 0)
                <div class="table-responsive">
                  <table class="table table-sm table-striped">
                    <thead>
                      <tr><th>Belge</th><th>Dosya</th><th>Durum</th><th>Tarih</th></tr>
                    </thead>
                    <tbody>
                      @foreach($documents as $doc)
                        <tr>
                          <td>{{ $typeLabels[$doc->document_type] ?? $doc->document_type }}</td>
                          <td class="small">{{ Str::limit($doc->original_name, 25) }}</td>
                          <td>
                            @switch($doc->status)
                              @case('approved') <span class="badge badge-success">Onaylı</span> @break
                              @case('pending') <span class="badge badge-warning">Bekliyor</span> @break
                              @case('rejected') <span class="badge badge-danger">Reddedildi</span> @break
                            @endswitch
                          </td>
                          <td class="small">{{ $doc->created_at->format('d.m.Y') }}</td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              @else
                <p class="text-muted text-center py-4">Henüz belge yüklenmedi.</p>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<script>
  const ibanInput = document.getElementById('iban_input');
  if (ibanInput) {
    ibanInput.addEventListener('input', function () {
      this.value = this.value.toUpperCase().replace(/\s+/g, '');
    });
    ibanInput.addEventListener('blur', function () {
      let val = this.value.toUpperCase().replace(/\s+/g, '');
      if (val && !val.startsWith('TR')) val = 'TR' + val;
      this.value = val;
      if (val.length > 0 && val.length !== 26) {
        this.setCustomValidity('Türkiye IBAN\'ı TR ile başlayan 26 karakter olmalıdır. Şu an: ' + val.length + ' karakter.');
      } else {
        this.setCustomValidity('');
      }
    });
  }
  const tcInput = document.getElementById('tc_identity');
  if (tcInput) {
    tcInput.addEventListener('input', function () {
      this.value = this.value.replace(/\D/g, '');
    });
  }

  function syncSellerTypeFields() {
    var typeEl = document.getElementById('seller_type');
    if (!typeEl) return;

    var type = typeEl.value;
    var isSole = type === 'sole_proprietorship';
    var isLimited = type === 'limited_company';
    var isCompany = isSole || isLimited;

    document.querySelectorAll('.seller-type-needs-tc').forEach(function (el) {
      el.style.display = '';
    });
    document.querySelectorAll('.seller-type-company').forEach(function (el) {
      el.style.display = isCompany ? '' : 'none';
    });
    document.querySelectorAll('.seller-type-limited').forEach(function (el) {
      el.style.display = isLimited ? '' : 'none';
    });
    document.querySelectorAll('.seller-type-sole').forEach(function (el) {
      el.style.display = isSole ? '' : 'none';
    });

    var taxLimited = document.getElementById('tax_number');
    var taxSole = document.getElementById('tax_number_sole');
    if (taxLimited) {
      taxLimited.disabled = !isLimited;
      taxLimited.name = isLimited ? 'tax_number' : 'tax_number_unused';
    }
    if (taxSole) {
      taxSole.disabled = !isSole;
      taxSole.name = isSole ? 'tax_number' : 'tax_number_unused_sole';
    }

    var tcLabel = document.getElementById('tc_label');
    var tcHint = document.getElementById('tc_hint');
    var hint = document.getElementById('document_type_hint');
    if (tcLabel) {
      tcLabel.innerHTML = isLimited
        ? 'Yetkili TC Kimlik No <span class="text-danger">*</span>'
        : 'TC Kimlik No <span class="text-danger">*</span>';
    }
    if (tcHint) {
      tcHint.textContent = isLimited
        ? 'Ödeme onayı için şirket yetkilisinin 11 haneli TC’si zorunludur'
        : '11 haneli, sadece rakam';
    }
    if (hint) {
      hint.textContent = isSole
        ? 'Şahıs şirketi: vergi levhası yeterlidir. Kimlik belgesi istenmez.'
        : 'Ltd / A.Ş.: vergi levhası yeterlidir. Kimlik belgesi istenmez.';
    }
  }

  var sellerTypeSelect = document.getElementById('seller_type');
  if (sellerTypeSelect) {
    sellerTypeSelect.addEventListener('change', syncSellerTypeFields);
    syncSellerTypeFields();
  }
</script>
@endsection
