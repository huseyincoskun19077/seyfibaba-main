@extends('admin.master_layout')
@section('title')
<title>Yeni Yasal Belge</title>
@endsection
@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Yeni Yasal Belge</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.legal-documents.index') }}">Yasal Belgeler</a></div>
        <div class="breadcrumb-item">Yeni</div>
      </div>
    </div>

    <div class="section-body">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-body">
              @include('admin.legal_documents._form', ['document' => null, 'action' => route('admin.legal-documents.store'), 'method' => 'POST'])
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection
