{{-- Geriye uyumlu sarmalayıcı (edit / quick) --}}
<style>
  .color-row { background:#f8fafc; border:1px dashed #cbd5e1; border-radius:14px; padding:14px; margin-bottom:12px; }
  .color-row img { width:72px; height:72px; object-fit:cover; border-radius:10px; display:block; }
</style>
<div class="card mb-3" id="simpleColorsCard">
  <div class="card-header">
    <h4 class="mb-0">Renk varyantları <small class="text-muted">(opsiyonel)</small></h4>
  </div>
  <div class="card-body">
    @include('seller.partials.simple_color_variants_inner', ['colorRows' => $colorRows ?? []])
  </div>
</div>
