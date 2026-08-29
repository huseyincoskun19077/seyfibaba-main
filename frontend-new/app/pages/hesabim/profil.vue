<script setup lang="ts">
import AccountSidebar from "~/components/account/AccountSidebar.vue";
import PageIntro from "~/components/common/PageIntro.vue";
</script>

<template>
  <main>
    <PageIntro
      eyebrow="Profil"
      title="Profil ve hesap ayarlari"
      description="Musteri bilgileri, parola, bildirim tercihleri ve kurumsal fatura ayarlari icin ekran."
    />

    <section class="page-section">
      <div class="container account-layout">
        <AccountSidebar />

        <div class="panel stack-md">
          <div>
            <h2 class="panel__title">Kullanici formu</h2>
            <p class="panel__muted">Ad soyad, telefon, e-posta, firma bilgisi ve parola guncelleme alani.</p>
          </div>

          <div class="form-grid">
            <label class="form-field">
              <span>Ad Soyad</span>
              <input type="text" value="Seyfibaba Musteri" />
            </label>
            <label class="form-field">
              <span>E-posta</span>
              <input type="email" value="musteri@seyfibaba.com" />
            </label>
            <label class="form-field">
              <span>Telefon</span>
              <input type="tel" value="+90 555 000 00 00" />
            </label>
            <label class="form-field">
              <span>Firma</span>
              <input type="text" value="Seyfibaba Studio" />
            </label>
          </div>

          <div class="detail-actions">
            <button class="button button--primary" type="button">Kaydet</button>
            <button class="button button--ghost" type="button">Parola degistir</button>
          </div>
        </div>
      </div>
    </section>
  </main>
</template>
