{{-- AI Content Generator - Header Button (Seller) --}}
@if (isset($aiEnabled) && $aiEnabled)
<button type="button" class="btn btn-info" data-toggle="modal" data-target="#aiContentModal">
    <i class="fas fa-robot mr-1"></i> Yapay zeka ile doldur
</button>
@endif
