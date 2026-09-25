"use client";
import Image from "next/image";
import Link from "next/link";
import { getProductImageProps } from "@/utils/productImage";
import { buildProductPath } from "@/utils/url";
import { resolveCartLineUnitPrice } from "@/utils/variantPricing";
import PriceDisplay from "@/components/Shared/PriceDisplay";

const calculateItemPrice = (item) => {
  if (!item) return 0;
  return Number(resolveCartLineUnitPrice(item) || 0);
};

const calculateTotalPrice = (item) => {
  if (!item) return 0;
  return calculateItemPrice(item) * Number(item.qty || 1);
};

function TrashIcon() {
  return (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden>
      <path
        d="M4 7h16M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2m-9 0v12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V7M10 11v6M14 11v6"
        stroke="currentColor"
        strokeWidth="1.7"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </svg>
  );
}

function QtyControl({ qty, onInc, onDec }) {
  return (
    <div className="inline-flex h-9 items-center rounded-md border border-[#04334a]/15 bg-white overflow-hidden">
      <button
        type="button"
        onClick={onDec}
        disabled={qty <= 1}
        className="h-full w-9 flex items-center justify-center text-qyellow font-800 text-lg hover:bg-[#04334a]/[0.04] disabled:opacity-40 disabled:cursor-not-allowed"
        aria-label="Azalt"
      >
        −
      </button>
      <span className="min-w-[36px] text-center text-sm font-800 text-[#04334a] tabular-nums border-x border-[#04334a]/10 px-1">
        {qty}
      </span>
      <button
        type="button"
        onClick={onInc}
        className="h-full w-9 flex items-center justify-center text-qyellow font-800 text-lg hover:bg-[#04334a]/[0.04]"
        aria-label="Artır"
      >
        +
      </button>
    </div>
  );
}

function VariantsLine({ variants }) {
  if (!Array.isArray(variants) || !variants.length) return null;
  const text = variants
    .map((v) => {
      const vi = v?.variant_item;
      if (!vi) return null;
      const g = vi.product_variant_name || "Seçenek";
      return `${g}: ${vi.name}`;
    })
    .filter(Boolean)
    .join(" · ");
  if (!text) return null;
  return <p className="text-[12px] text-[#04334a]/55 mt-0.5">{text}</p>;
}

function CartItemCard({ item, deleteItem, incrementQty, decrementQty }) {
  const product = item?.product || {};
  const productId = product.id ?? item.product_id;
  const qty = Math.max(1, parseInt(item.qty, 10) || 1);
  const code = product.barcode || product.sku || null;
  const saleUnit = Math.max(1, parseInt(product.sale_unit_qty, 10) || 1);
  const maxInstallment = Math.max(1, parseInt(product.max_installment, 10) || 1);
  const categoryName = String(product.category_name || "").trim();

  const unit = calculateItemPrice(item);
  const lineTotal = calculateTotalPrice(item);
  const listUnit = Number(product.price || 0);
  const offerUnit =
    product.offer_price != null && Number(product.offer_price) > 0
      ? Number(product.offer_price)
      : null;
  // Güncel satır tutarı her zaman unit*qty; liste/indirim varsa PriceDisplay
  const listLine =
    listUnit > 0 ? listUnit * qty : lineTotal;
  const offerLine =
    offerUnit != null && listUnit > offerUnit ? offerUnit * qty : null;
  // Sunucu unit_price (indirimli) ile liste farklıysa onu kullan
  const displayOffer =
    offerLine != null
      ? offerLine
      : listUnit > 0 && unit < listUnit
        ? unit * qty
        : null;
  const displayList = displayOffer != null ? listLine : lineTotal;

  return (
    <article className="w-full bg-white border border-[#04334a]/10 rounded-xl px-3 py-4 md:px-5 md:py-5">
      <div className="flex flex-col md:flex-row md:items-center gap-4 md:gap-5">
        {/* Image + details */}
        <div className="flex flex-1 gap-3 md:gap-4 min-w-0">
          <Link
            href={buildProductPath(product.slug)}
            className="relative shrink-0 w-[72px] h-[72px] md:w-[88px] md:h-[88px] rounded-lg border border-[#04334a]/12 bg-neutral-50 overflow-hidden"
          >
            {product.thumb_image ? (
              <Image
                fill
                sizes="88px"
                {...getProductImageProps(product.thumb_image)}
                alt={product.name || "Ürün"}
                className="object-contain p-1"
              />
            ) : null}
          </Link>

          <div className="min-w-0 flex-1">
            <Link href={buildProductPath(product.slug)}>
              <h3 className="text-[14px] md:text-[15px] font-800 text-[#04334a] leading-snug hover:underline line-clamp-2 notranslate">
                {product.name}
              </h3>
            </Link>

            {code ? (
              <p className="mt-1 text-[12px] text-[#04334a]/50 tabular-nums">
                {code}
              </p>
            ) : null}

            <p className="mt-1 text-[12px] text-[#04334a]/70">
              <span className="font-700 text-[#04334a]">İçindeki miktar:</span>{" "}
              {saleUnit} adet
              {qty > 1 ? (
                <span className="text-[#04334a]/50">
                  {" "}
                  · Sepette {qty} × {saleUnit} = {qty * saleUnit} adet
                </span>
              ) : null}
            </p>

            <div className="mt-2 space-y-0.5 text-[12px] leading-relaxed">
              <p className="text-[#04334a]/70">
                <span className="font-800 text-qyellow">Bireysel kart:</span>{" "}
                {maxInstallment > 1
                  ? `${maxInstallment} taksite kadar`
                  : "Tek çekim"}
                {categoryName ? (
                  <span className="text-[#04334a]/45">
                    {" "}
                    ({categoryName})
                  </span>
                ) : null}
              </p>
              <p className="text-[#04334a]/70">
                <span className="font-800 text-qyellow">Ticari kart:</span>{" "}
                <span className="text-[#04334a]/35">—</span>
              </p>
            </div>

            <VariantsLine variants={item.variants} />
          </div>
        </div>

        {/* Qty + delete + price */}
        <div className="flex items-center justify-between md:justify-end gap-3 md:gap-5 shrink-0 md:pl-2">
          <div className="flex items-center gap-2.5">
            <QtyControl
              qty={qty}
              onInc={() => incrementQty(productId)}
              onDec={() => decrementQty(productId)}
            />
            <button
              type="button"
              onClick={() => deleteItem(item.product_id ?? productId)}
              className="inline-flex h-9 w-9 items-center justify-center rounded-md text-qyellow hover:bg-qyellow/15 transition"
              aria-label="Ürünü kaldır"
            >
              <TrashIcon />
            </button>
          </div>

          <PriceDisplay
            price={displayList}
            offerPrice={displayOffer}
            size="md"
            layout="stack"
            className="items-end text-right min-w-[100px]"
          />
        </div>
      </div>
    </article>
  );
}

export default function ProductsTable({
  className,
  cartItems,
  deleteItem,
  incrementQty,
  decrementQty,
}) {
  return (
    <div className={`w-full space-y-3 ${className || ""}`}>
      {cartItems?.map((item, index) => (
        <CartItemCard
          key={`${item.product_id}-${index}`}
          item={item}
          deleteItem={deleteItem}
          incrementQty={incrementQty}
          decrementQty={decrementQty}
        />
      ))}
    </div>
  );
}
