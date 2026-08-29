@extends('admin.master_layout')
@section('title')
<title>{{ $document->title }} — Düzenle</title>
@endsection
@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>{{ $document->title }}</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.legal-documents.index') }}">Yasal Belgeler</a></div>
        <div class="breadcrumb-item">Düzenle</div>
      </div>
    </div>

    <div class="section-body">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-body">
              @include('admin.legal_documents._form', ['document' => $document, 'action' => route('admin.legal-documents.update', $document->id), 'method' => 'PUT'])
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection
