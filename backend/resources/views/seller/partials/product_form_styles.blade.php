{{-- Ortak satıcı ürün formu stilleri --}}
<style>
  .seller-product-form .spf-step {
    background: #fff;
    border: 1px solid #e8edf3;
    border-radius: 16px;
    margin-bottom: 1rem;
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
  }
  .seller-product-form .spf-step-head {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    border-bottom: 1px solid #e8edf3;
  }
  .seller-product-form .spf-step-num {
    width: 32px;
    height: 32px;
    border-radius: 999px;
    background: #0f172a;
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
    flex-shrink: 0;
  }
  .seller-product-form .spf-step-head h4 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: #0f172a;
  }
  .seller-product-form .spf-step-head p {
    margin: 2px 0 0;
    font-size: 12px;
    color: #64748b;
  }
  .seller-product-form .spf-step-body { padding: 18px; }
  .seller-product-form .spf-req {
    display: inline-block;
    margin-left: 6px;
    padding: 1px 7px;
    border-radius: 999px;
    background: #fee2e2;
    color: #b91c1c;
    font-size: 10px;
    font-weight: 700;
    vertical-align: middle;
  }
  .seller-product-form .spf-opt {
    display: inline-block;
    margin-left: 6px;
    padding: 1px 7px;
    border-radius: 999px;
    background: #e2e8f0;
    color: #475569;
    font-size: 10px;
    font-weight: 700;
    vertical-align: middle;
  }
  .seller-product-form .form-control,
  .seller-product-form .select2-container .select2-selection--single {
    min-height: 46px;
    font-size: 15px;
    border-radius: 10px;
  }
  .seller-product-form textarea.form-control { min-height: 96px; }
  .seller-product-form .spf-ai-card {
    border: 1px solid #c7d2fe;
    background: linear-gradient(135deg, #eef2ff 0%, #f8fafc 60%);
    border-radius: 16px;
    padding: 16px 18px;
    margin-bottom: 1rem;
  }
  .seller-product-form .spf-ai-card h5 {
    margin: 0 0 6px;
    font-size: 1rem;
    font-weight: 700;
    color: #312e81;
  }
  .seller-product-form .spf-hint {
    font-size: 12px;
    color: #64748b;
    margin-top: 4px;
  }
  .seller-product-form .seller-sticky-save {
    position: sticky;
    bottom: 12px;
    z-index: 20;
    padding-top: 8px;
  }
  .seller-product-form .seller-save-btn {
    border-radius: 12px;
    font-weight: 700;
    min-height: 52px;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.18);
  }
  .seller-product-form .color-row,
  .seller-product-form .size-row {
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    border-radius: 14px;
    padding: 14px;
    margin-bottom: 12px;
  }
  .seller-product-form .color-row img {
    width: 64px;
    height: 64px;
    object-fit: cover;
    border-radius: 10px;
    display: block;
  }
</style>
