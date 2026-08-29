@extends('admin.master_layout')
@section('title')
<title>Kargo Kuralı Toplu Yükleme</title>
@endsection
@section('admin-content')
      <!-- Main Content -->
      <div class="main-content">
        <section class="section">
          <div class="section-header">
            <h1>Kargo Kuralı Toplu Yükleme</h1>

          </div>

          <div class="section-body">
            <a href="{{ route('admin.shipping.index') }}" class="btn btn-primary"><i class="fas fa-list"></i> Kargo Kuralları</a>

            <a href="{{ route('admin.shipping-export') }}" class="btn btn-success"><i class="fas fa-file-export"></i> Kural Listesini Dışa Aktar</a>

            <a href="{{ route('admin.shipping-demo-export') }}" class="btn btn-primary"><i class="fas fa-file-export"></i> Örnek Dosya İndir</a>

            <div class="row mt-4">
                <div class="col-12">
                  <div class="card">
                    <div class="card-body">
                        <form action="{{ route('admin.shipping-import') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="form-group col-12">
                                    <label>Dosya <span class="text-danger">*</span></label>
                                    <input type="file" id="name" class="form-control-file"  name="import_file" required>
                                </div>

                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <button class="btn btn-primary">Yükle</button>
                                </div>
                            </div>
                        </form>
                    </div>
                  </div>
                </div>
          </div>

          <div class="section-body">
            <div class="card">
                <div class="card-body">
                    <h3>Kullanım Talimatları</h3>
                    <table class="table table-bordered table-striped">

                        <tr>
                            <td><strong>Şehir ID (city_id)</strong></td>
                            <td>Zorunlu alan. Şehirler tablosundan geçerli bir şehir/ilçe ID'si giriniz. Tüm teslimat bölgeleri için geçerli bir kural tanımlamak istiyorsanız şehir ID olarak <strong>0 (sıfır)</strong> yazınız.</td>
                        </tr>

                        <tr>
                            <td><strong>Kargo Kuralı (shipping_rule)</strong></td>
                            <td>Zorunlu alan. Kargo kuralının adı — herhangi bir metin yazılabilir.</td>
                        </tr>

                        <tr>
                            <td><strong>Tip (type)</strong></td>
                            <td>Zorunlu alan. Yalnızca aşağıdaki 3 değerden biri kabul edilir. Farklı bir değer girilirse sistem hata verir:
                                <br>
                                1. <code>base_on_price</code> — Fiyata göre
                                <br>
                                2. <code>base_on_weight</code> — Ağırlığa göre
                                <br>
                                3. <code>base_on_qty</code> — Adete göre
                            </td>
                        </tr>


                        <tr>
                            <td><strong>Kargo Ücreti (shipping_fee)</strong></td>
                            <td>Zorunlu alan. Sadece sayısal değer giriniz. Ücretsiz kargo için <strong>0 (sıfır)</strong> yazınız.</td>
                        </tr>

                        <tr>
                            <td><strong>Koşul Başlangıcı (condition_from)</strong></td>
                            <td>Zorunlu alan. Sadece sayısal değer giriniz.</td>
                        </tr>

                        <tr>
                            <td><strong>Koşul Bitişi (condition_to)</strong></td>
                            <td>Zorunlu alan. Sadece sayısal değer giriniz. Üst limit olmadan sınırsız koşul tanımlamak için <strong>-1</strong> yazınız.</td>
                        </tr>

                    </table>
                </div>
            </div>
          </div>

        </section>
      </div>

@endsection
