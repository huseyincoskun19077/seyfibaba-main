{{-- AI Content Generator - Header Button --}}
@if (isset($aiEnabled) && $aiEnabled)
<button type="button" class="btn btn-info" data-toggle="modal" data-target="#aiContentModal">
    <i class="fas fa-robot mr-1"></i> Yapay Zeka ile İçerik Üret
    <span class="badge badge-light ml-1" style="font-size:10px;">Beta</span>
</button>
@endif
