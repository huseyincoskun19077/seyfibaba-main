@if(!empty($method->description))
<div class="alert alert-primary" role="alert">
    {!! clean($method->description) !!}
</div>
@endif
