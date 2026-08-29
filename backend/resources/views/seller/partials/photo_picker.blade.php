{{--
  Mobil dostu fotoğraf seçici.
  Params:
    $inputName (string)  — form name, e.g. thumb_image or images[]
    $inputId (string)    — unique id
    $multiple (bool)     — default false
    $previewId (string|null) — img element id for single preview
    $required (bool)     — default false
    $label (string)      — optional heading
--}}
@php
  $multiple = $multiple ?? false;
  $required = $required ?? false;
  $previewId = $previewId ?? null;
  $label = $label ?? null;
  $accept = 'image/jpeg,image/jpg,image/png,image/webp,image/*';
@endphp
<div class="seller-photo-picker" data-target="{{ $inputId }}" data-multiple="{{ $multiple ? '1' : '0' }}" @if($previewId) data-preview="{{ $previewId }}" @endif>
  @if($label)
    <label class="d-block font-weight-bold mb-2">{{ $label }} @if($required)<span class="text-danger">*</span>@endif</label>
  @endif

  <input
    type="file"
    name="{{ $inputName }}"
    id="{{ $inputId }}"
    class="d-none seller-photo-main"
    accept="{{ $accept }}"
    @if($multiple) multiple @endif
    @if($required) required @endif
  >

  <div class="row">
    <div class="col-6 pr-1">
      <button type="button" class="btn btn-primary btn-lg btn-block seller-photo-camera mb-2">
        <i class="fas fa-camera d-block mb-1"></i>
        <span class="seller-photo-btn-text">Fotoğraf Çek</span>
      </button>
      <input type="file" class="d-none seller-photo-camera-input" accept="{{ $accept }}" capture="environment" @if($multiple) multiple @endif>
    </div>
    <div class="col-6 pl-1">
      <button type="button" class="btn btn-outline-primary btn-lg btn-block seller-photo-gallery mb-2">
        <i class="fas fa-images d-block mb-1"></i>
        <span class="seller-photo-btn-text">Galeriden Seç</span>
      </button>
      <input type="file" class="d-none seller-photo-gallery-input" accept="{{ $accept }}" @if($multiple) multiple @endif>
    </div>
  </div>

  <p class="text-muted small mb-2">JPEG, PNG veya WEBP · en fazla 5 MB</p>
  <div class="seller-photo-previews row"></div>
</div>
