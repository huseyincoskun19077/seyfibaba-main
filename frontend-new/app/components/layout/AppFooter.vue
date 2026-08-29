<template>
  <footer class="app-footer">
    <div class="container">
      <div class="app-footer__newsletter">
        <div>
          <span class="section-heading__eyebrow">Bulten ve Teklifler</span>
          <h3>Yeni koleksiyon ve kampanyalari kacirmayin</h3>
          <p>Salon projeleri, indirimli urunler ve yeni kategori acilislarini e-posta ile alin.</p>
        </div>
        <div class="app-footer__newsletter-form">
          <input type="email" placeholder="E-posta adresiniz" />
          <button type="button" class="button button--primary">Kaydol</button>
        </div>
      </div>
    </div>

    <div class="container app-footer__grid">
      <div>
        <h3>Seyfibaba</h3>
        <p>
          Berber, kuafor ve guzellik salonlari icin profesyonel ekipmanlari modern bir storefront ile
          sunan yeni Nuxt arayuzu.
        </p>
      </div>

      <div>
        <h4>Magaza</h4>
        <ul>
          <li><NuxtLink to="/products">Tum Urunler</NuxtLink></li>
          <li><NuxtLink to="/favoriler">Favoriler</NuxtLink></li>
          <li><NuxtLink to="/karsilastir">Karsilastirma</NuxtLink></li>
          <li><NuxtLink to="/sepet">Sepet</NuxtLink></li>
        </ul>
      </div>

      <div>
        <h4>Hesap</h4>
        <ul>
          <li><NuxtLink to="/hesabim">Hesabim</NuxtLink></li>
          <li><NuxtLink to="/siparisler">Siparislerim</NuxtLink></li>
          <li><NuxtLink to="/hesabim/adresler">Adresler</NuxtLink></li>
          <li><NuxtLink to="/hesabim/profil">Profil</NuxtLink></li>
        </ul>
      </div>

      <div>
        <h4>Iletisim</h4>
        <ul>
          <li><a href="tel:+905555555555">+90 555 555 55 55</a></li>
          <li><a href="mailto:destek@seyfibaba.com">destek@seyfibaba.com</a></li>
          <li>Istanbul / Turkiye</li>
          <li>Hafta ici 09:00 - 18:00 destek</li>
        </ul>
      </div>
    </div>

    <div class="container app-footer__bottom">
      <p>Nuxt storefront skeleton - Seyfibaba</p>
      <div class="app-footer__legal">
        <span>KVKK</span>
        <span>Mesafeli Satis</span>
        <span>Iade Kosullari</span>
      </div>
    </div>
  </footer>
</template>
