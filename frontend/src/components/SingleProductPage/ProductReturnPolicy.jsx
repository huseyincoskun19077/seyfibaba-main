import Link from "next/link";

const STEPS = [
  {
    title: "İade talebinizi oluşturun",
    body: (
      <>
        Cayma hakkının bulunmadığı ürünler dışındaki tüm ürünler için{" "}
        <strong>8 günlük</strong> cayma hakkı süresi içinde iade talebi
        oluşturabilirsiniz.{" "}
        <Link href="/profile#order" className="font-700 text-[#04334a] underline underline-offset-2 hover:text-qyellow">
          Siparişlerim
        </Link>{" "}
        sayfasından iade etmek istediğiniz ürünü bulun ve{" "}
        <strong>İade oluştur</strong>&apos;a tıklayın. İade etmek istediğiniz
        miktarı, iade nedeninizi ve talebinizi seçerek iade talebinizi
        oluşturun.
      </>
    ),
  },
  {
    title: "İade kargo kodunuzu alın ve not edin",
    body: (
      <>
        Yönlendirmeleri tamamlayın. İade talebinize en geç{" "}
        <strong>2 iş günü</strong> içinde yanıt gelecek ve satıcının tercihine
        göre ürünleri göndermeniz için bir kargo kodu üretilecektir. Ürünler
        geri gönderilemeyecek durumdaysa ya da satıcı ürünleri geri istemezse
        iade talebiniz, iade kargo kodu hiç oluşmadan da sonuçlanabilir.
      </>
    ),
  },
  {
    title: "Vergi mükellefi iseniz iade faturanızı kesin",
    body: (
      <>
        Vergi mükellefi iseniz para iadenizi alabilmek için satıcı adına
        kurumsal iade faturası düzenlemeniz gerekmektedir. İade faturanızı iade
        talebinizi oluştururken belge olarak yükleyebilir ya da{" "}
        <a
          href="mailto:info@kuafortedarik.com"
          className="font-700 text-[#04334a] underline underline-offset-2 hover:text-qyellow"
        >
          info@kuafortedarik.com
        </a>{" "}
        adresimize gönderebilirsiniz.
      </>
    ),
  },
  {
    title: "Ürünleri kargoya teslim edin",
    body: (
      <>
        Ürünü tüm aparatlarıyla eksiksiz bir şekilde paketleyerek kargo
        kodunuzun ait olduğu Kuaför Tedarik anlaşmalı kargo firmasından{" "}
        <strong>ücretsiz</strong> gönderin. Kuaför Tedarik anlaşmalı kargo firması
        dışında başka bir kargo firması ile gönderim yaparsanız iadeniz
        gerçekleştirilemez.
      </>
    ),
  },
  {
    title: "İadeniz onaylanır ve iade talebiniz yerine getirilir",
    body: (
      <>
        İade ettiğiniz ürünün ücret iade süreci, ürünün satıcıya ulaştığı
        takdirde <strong>2 iş günü</strong> içinde onaylanır ve bankanıza bağlı
        olarak <strong>2–8 iş günü</strong> içinde ödeme yapmış olduğunuz
        kartınıza yansır. İade talebiniz onaylandığında paranız, ödemeyi ilk
        yaptığınız yöntemle otomatik olarak bankanıza aktarılır. Kuaför Tedarik para
        iadenizi tek seferde toplu olarak yapar; ancak taksitli alışverişlerinizde
        bankanız iadenizi size taksitler halinde yansıtabilir.
      </>
    ),
  },
];

export default function ProductReturnPolicy() {
  return (
    <div className="space-y-6 text-sm leading-7 text-[#04334a]/70">
      <div>
        <h3 className="mb-3 text-lg font-800 text-[#04334a]">İade Koşulları</h3>
        <div className="space-y-3 rounded-xl border border-[#04334a]/10 bg-[#F4F6F7] p-4 text-[#04334a]/75">
          <p>
            Kuaför Tedarik Pazaryeri&apos;nde sipariş tutarları satıcılara doğrudan
            aktarılmaz ve bir havuzda bekletilir. Yasal iade süreniz dolduktan
            sonra ödemeniz satıcıya aktarılır.
          </p>
          <p>
            Ürünleri teslim aldığınız tarihten itibaren en geç{" "}
            <strong className="text-[#04334a]">8 gün</strong> içinde{" "}
            <Link
              href="/profile#order"
              className="font-700 text-[#04334a] underline underline-offset-2 hover:text-qyellow"
            >
              Hesabım &gt; Siparişlerim
            </Link>{" "}
            sayfasında ilgili siparişinize girebilir, siparişte yer alan
            ürünleriniz için iade talebinde bulunabilirsiniz.
          </p>
          <p>
            Kuaför Tedarik Pazaryeri, ticari nitelikli satış sözleşmesi hükümlerine
            göre satış ve iade işlemlerini yürütmektedir. Türk Ticaret Kanunu
            hükümlerine göre ayıpsız ürünlerin iadesi kazanılmış bir hak olmayıp;
            ayıpsız ürünlerin iade taleplerine satıcının ihtiyari takdiri ile
            karar verilir. Anlaşmalı kargo firması ile ürünleri gönderebilmeniz
            için üretilen kodu kullanarak ürünleri satıcıya geri gönderebilirsiniz.
            Ürünler satıcıya geri ulaştıktan sonra para iadeniz yapılır.
            Bankanızın ödemeyi hesabınıza yansıtması birkaç gün sürebilir.
          </p>
        </div>
      </div>

      <div>
        <h4 className="mb-3 text-base font-800 text-[#04334a]">
          İade adımları
        </h4>
        <ol className="space-y-3">
          {STEPS.map((step, index) => (
            <li
              key={step.title}
              className="rounded-xl border border-[#04334a]/10 bg-white p-4"
            >
              <p className="mb-1.5 font-800 text-[#04334a]">
                <span className="mr-2 inline-flex h-6 w-6 items-center justify-center rounded-full bg-qyellow text-xs font-800 text-[#04334a]">
                  {index + 1}
                </span>
                {step.title}
              </p>
              <div className="pl-8">{step.body}</div>
            </li>
          ))}
        </ol>
      </div>

      <div className="rounded-xl border border-[#04334a]/10 bg-[#FFF8E8] p-4">
        <h4 className="mb-2 text-base font-800 text-[#04334a]">
          İade faturası düzenlerken dikkat edin
        </h4>
        <ul className="list-disc space-y-1.5 pl-5">
          <li>Fatura tipi <strong>iade</strong> olmalıdır.</li>
          <li>
            Fatura açıklama bölümüne düzenlenen faturanın hangi siparişe ait
            olduğu belirtilmelidir.
          </li>
          <li>Fatura oluşturma tarihi güncel olmalıdır.</li>
          <li>
            İade talebinizi oluştururken talebinizde yer alan ürün miktar ve
            fiyatlarına göre örnek bir iade faturası gösterilir; aynı bilgileri
            kullanarak iade faturanızı kesebilirsiniz.
          </li>
        </ul>
      </div>

      <div>
        <h4 className="mb-3 text-base font-800 text-[#04334a]">
          Sık sorulanlar
        </h4>
        <div className="space-y-3">
          <details className="group rounded-xl border border-[#04334a]/10 bg-white open:shadow-sm">
            <summary className="cursor-pointer list-none px-4 py-3 font-700 text-[#04334a] marker:content-none [&::-webkit-details-marker]:hidden">
              <span className="flex items-center justify-between gap-3">
                Farklı iadelerimi aynı kargoda yollayabilir miyim?
                <span className="text-[#04334a]/35 transition group-open:rotate-180">
                  ▾
                </span>
              </span>
            </summary>
            <div className="space-y-2 border-t border-[#04334a]/8 px-4 py-3">
              <p>
                İadeye yolladığınız her bir ürün sistemde takip edilir ve
                satıcıya ulaşıp ulaşmadığı izlenir. İade sisteminin sağlıklı
                çalışabilmesi için her ürünü size o ürün için verilen kargo
                kodu ile göndermelisiniz.
              </p>
              <p>
                <strong>Farklı satıcıların</strong> ürünlerini aynı kargoyla
                göndermeyin. Her satıcı için ayrı iade talebi ve farklı iade
                kodları oluşur; ürünleri doğru satıcının iade koduyla
                gönderdiğinizden emin olun.
              </p>
              <p>
                Aynı satıcıdan birden fazla ürün için tek seferde iade talebi
                oluşturduğunuzda tüm ürünler için bir iade kodu oluşur; bu
                durumda aynı kod ile tek kargoda gönderebilirsiniz. Farklı
                zamanlarda oluşturulan taleplerde farklı kodlar üretilebilir.
              </p>
            </div>
          </details>

          <details className="group rounded-xl border border-[#04334a]/10 bg-white open:shadow-sm">
            <summary className="cursor-pointer list-none px-4 py-3 font-700 text-[#04334a] marker:content-none [&::-webkit-details-marker]:hidden">
              <span className="flex items-center justify-between gap-3">
                İade kargo kodumu kaybettim
                <span className="text-[#04334a]/35 transition group-open:rotate-180">
                  ▾
                </span>
              </span>
            </summary>
            <div className="border-t border-[#04334a]/8 px-4 py-3">
              <p>
                <Link
                  href="/profile#order"
                  className="font-700 text-[#04334a] underline underline-offset-2 hover:text-qyellow"
                >
                  Hesabım &gt; Siparişlerim
                </Link>{" "}
                (veya iade talepleri) sayfasında iade kargo kodunuzu tekrar
                görebilirsiniz.
              </p>
            </div>
          </details>
        </div>
      </div>

      <p className="text-xs text-[#04334a]/45">
        Detaylı metinler için{" "}
        <Link
          href="/legal/delivery-return"
          className="underline underline-offset-2 hover:text-[#04334a]"
        >
          Teslimat ve İade Şartları
        </Link>{" "}
        sayfasını da inceleyebilirsiniz.
      </p>
    </div>
  );
}
