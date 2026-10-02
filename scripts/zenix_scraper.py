#!/usr/bin/env python3
"""
Zenix Kozmetik → Seyfibaba toplu ürün import Excel/CSV.

Kullanım:
  python scripts/zenix_scraper.py
  python scripts/zenix_scraper.py --limit 10

Çıktı: scripts/output/zenix_import.xlsx
Kaynak: https://zenixcosmetic.com/index.php?route=product/category&path=92
"""

from __future__ import annotations

import argparse
import csv
import re
import sys
import time
import unicodedata
from pathlib import Path
from urllib.parse import urljoin, urlparse, parse_qs

import requests
from bs4 import BeautifulSoup
from openpyxl import Workbook

BASE_URL = "https://zenixcosmetic.com"
CATEGORY_URL = f"{BASE_URL}/index.php?route=product/category&path=92&limit=100"
MARKUP = 1.20
HEADERS = {
    "User-Agent": (
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
        "AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36"
    ),
    "Accept-Language": "tr-TR,tr;q=0.9,en;q=0.8",
}
OUTPUT_DIR = Path(__file__).resolve().parent / "output"
IMAGES_DIR = OUTPUT_DIR / "zenix_images"

EXCEL_HEADERS = [
    "name",
    "short_name",
    "slug",
    "category",
    "sub_category",
    "child_category",
    "brand",
    "price",
    "offer_price",
    "qty",
    "short_description",
    "long_description",
    "sku",
    "weight",
    "tags",
    "image_url",
    "source_url",
    "source_price",
]

SELLER_HEADERS = [
    "name",
    "short_name",
    "slug",
    "category",
    "sub_category",
    "child_category",
    "brand",
    "price",
    "offer_price",
    "qty",
    "short_description",
    "long_description",
    "sku",
    "weight",
    "tags",
    "image_url",
]

# canli-kategoriler.md (Kozmetik id:3) — satıcı paneli birebir isim eşler
CATEGORY_RULES: list[tuple[list[str], str, str, str]] = [
    (["sakal"], "Kozmetik", "Erkek Bakım / Berber", "Sakal Bakım Serumu"),
    (["genital"], "Kozmetik", "Diğer Kozmetik", ""),
    (["güneş"], "Kozmetik", "Diğer Kozmetik", ""),
    (["btx", "botox"], "Kozmetik", "Saç Bakımı", "Saç Botoks"),
    (["havyar"], "Kozmetik", "Saç Bakımı", "Diğer Saç Bakımı"),
    (["saç bakım seti", "bakım seti", "keratin set"], "Kozmetik", "Saç Bakımı", "Keratin Seti"),
    (["krem kolonya"], "Kozmetik", "Tıraş Sonrası Bakım Ürünleri", "After Shave Balm"),
    (
        ["traş sonrası", "tıraş sonrası"],
        "Kozmetik",
        "Tıraş Sonrası Bakım Ürünleri",
        "Tıraş Kolonyası",
    ),
    (["tıraş jeli", "traş jeli"], "Kozmetik", "Tıraş Sonrası Bakım Ürünleri", "Diğer Tıraş Sonrası Bakım Ürünleri"),
    (["kolonya"], "Kozmetik", "Tıraş Sonrası Bakım Ürünleri", "Kolonya"),
    (["peeling", "scrub"], "Kozmetik", "Vücut Bakımı", "Vücut Peeling"),
    (
        ["kil maske", "yüz maskesi", "yüz kili", "kabarcık", "facial care mask", "soyulabilir"],
        "Kozmetik",
        "Tıraş Sonrası Bakım Ürünleri",
        "Yüz Maskesi",
    ),
    (
        ["micellar", "yüz temizleme", "temizleme jeli", "temizleme köpüğü"],
        "Kozmetik",
        "Tıraş Sonrası Bakım Ürünleri",
        "Diğer Tıraş Sonrası Bakım Ürünleri",
    ),
    (["cilt bakım kremi"], "Kozmetik", "Tıraş Sonrası Bakım Ürünleri", "Cilt Kremleri"),
    (["fön suyu"], "Kozmetik", "Saç Bakımı", "Fön Suyu"),
    (["mor şampuan", "gümüş", "silver", "gri ve beyaz"], "Kozmetik", "Saç Boyama", "Silver Şampuan"),
    (["saç serum", "bakım serum", "keratin oil"], "Kozmetik", "Saç Bakımı", "Saç Serumu"),
    (["hair mask", "saç bakım maskesi", "saç yumuşatıcı maske", "saç maskesi"], "Kozmetik", "Saç Bakımı", "Saç Maskesi"),
    (["yumuşatıcı"], "Kozmetik", "Saç Bakımı", "Saç Bakım Kremi"),
    (["iki fazlı saç kremi", "saç kremi"], "Kozmetik", "Saç Bakımı", "Saç Kremi"),
    (["saç bakım şampuanı", "saç bakım şampuan"], "Kozmetik", "Saç Bakımı", "Saç Bakım Şampuanı"),
    (["şampuan", "sampuan"], "Kozmetik", "Saç Bakımı", "Şampuan"),
    (["toz wax", "toz  wax", "pudra"], "Kozmetik", "Saç Şekillendirme", "Saç Pudrası"),
    (["şekillendirici krem", "krem wax", "krem ​​"], "Kozmetik", "Saç Şekillendirme", "Şekillendirici Krem"),
    (["köpük", "köpüğü"], "Kozmetik", "Saç Şekillendirme", "Saç Köpüğü"),
    (["hair jam", "fiber jam", "fiber wax", "wax"], "Kozmetik", "Saç Şekillendirme", "Wax"),
]


def slugify(text: str) -> str:
    text = unicodedata.normalize("NFKD", text)
    text = text.encode("ascii", "ignore").decode("ascii")
    text = re.sub(r"[^a-zA-Z0-9\s-]", "", text).lower()
    text = re.sub(r"[\s_-]+", "-", text).strip("-")
    return text or "urun"


def parse_price(value) -> float | None:
    if value is None:
        return None
    if isinstance(value, (int, float)):
        return float(value)

    cleaned = str(value).replace("TL", "").replace("₺", "").replace(" ", "").strip()
    if cleaned == "":
        return None
    if re.match(r"^\d{1,3}(\.\d{3})+(,\d+)?$", cleaned):
        cleaned = cleaned.replace(".", "").replace(",", ".")
    elif "," in cleaned and "." not in cleaned:
        cleaned = cleaned.replace(",", ".")
    try:
        return float(cleaned)
    except ValueError:
        return None


def fold_tr(text: str) -> str:
    text = unicodedata.normalize("NFKC", text)
    text = text.replace("İ", "i").replace("I", "i").replace("ı", "i")
    text = text.replace("Ş", "s").replace("ş", "s")
    text = text.replace("Ğ", "g").replace("ğ", "g")
    text = text.replace("Ü", "u").replace("ü", "u")
    text = text.replace("Ö", "o").replace("ö", "o")
    text = text.replace("Ç", "c").replace("ç", "c")
    return text.lower()


def classify_category(name: str) -> tuple[str, str, str]:
    haystack = fold_tr(name)
    for keywords, category, sub, child in CATEGORY_RULES:
        if any(fold_tr(keyword) in haystack for keyword in keywords):
            return category, sub, child
    return "Kozmetik", "Diğer Kozmetik", ""


def extract_weight(name: str) -> str:
    match = re.search(r"(\d+(?:[.,]\d+)?)\s*(ml|g|gr|kg|l|lt)\b", name, flags=re.I)
    if not match:
        return ""
    amount = match.group(1).replace(",", ".")
    unit = match.group(2).lower()
    if unit in {"gr"}:
        unit = "g"
    if unit in {"lt", "l"}:
        unit = "l"
    return f"{amount} {unit}"


def larger_image(url: str) -> str:
    if not url:
        return ""
    return re.sub(r"-(\d+)x(\d+)(\.[a-z0-9]+)$", r"-800x800\3", url, flags=re.I)


def product_id_from_url(url: str) -> str:
    qs = parse_qs(urlparse(url).query)
    values = qs.get("product_id") or []
    return values[0] if values else ""


def canonical_product_url(url: str) -> str:
    pid = product_id_from_url(url)
    if pid:
        return f"{BASE_URL}/index.php?route=product/product&product_id={pid}"
    return url.split("&limit=")[0]


def fetch_html(session: requests.Session, url: str) -> str:
    response = session.get(url, headers=HEADERS, timeout=45)
    response.raise_for_status()
    response.encoding = "utf-8"
    return response.text


def collect_product_urls(session: requests.Session) -> list[str]:
    html = fetch_html(session, CATEGORY_URL)
    soup = BeautifulSoup(html, "html.parser")
    urls: list[str] = []
    seen: set[str] = set()
    for thumb in soup.select(".product-thumb"):
        link = thumb.select_one("h4 a[href*='product_id='], .caption a[href*='product_id=']")
        if not link:
            link = thumb.select_one("a[href*='product_id=']")
        if not link:
            continue
        url = canonical_product_url(urljoin(BASE_URL, link["href"]))
        if url in seen:
            continue
        seen.add(url)
        urls.append(url)
    return urls


def table_value(soup: BeautifulSoup, label: str) -> str:
    for row in soup.select("table.product-info tr"):
        cells = row.find_all("td")
        if len(cells) < 2:
            continue
        if label.casefold() in cells[0].get_text(strip=True).casefold():
            return cells[1].get_text(strip=True)
    return ""


def scrape_product(session: requests.Session, url: str) -> dict | None:
    try:
        html = fetch_html(session, url)
    except requests.RequestException as exc:
        print(f"[warn] Ürün alınamadı {url}: {exc}", file=sys.stderr)
        return None

    soup = BeautifulSoup(html, "html.parser")
    name_el = soup.select_one("h1.product-name") or soup.select_one("h1.page-title") or soup.find("h1")
    name = re.sub(r"\s+", " ", name_el.get_text(" ", strip=True) if name_el else "").strip()
    if not name:
        return None

    price_el = soup.select_one("ul.product-price h2") or soup.select_one(".product-price h2")
    source_price = parse_price(price_el.get_text(strip=True) if price_el else None)
    if source_price is None:
        match = re.search(r"₺\s*([\d\.]+,\d{2})", html)
        source_price = parse_price(match.group(1) if match else None)
    if source_price is None:
        print(f"[warn] Fiyat bulunamadı: {url}", file=sys.stderr)
        return None

    brand = table_value(soup, "Marka") or "Zenix"
    sku = table_value(soup, "Ürün Kodu")
    stock_raw = table_value(soup, "Stok Durumu")
    qty = "10"
    if stock_raw and stock_raw.isdigit():
        qty = stock_raw if int(stock_raw) > 0 else "0"

    desc_el = soup.select_one("#tab-description")
    description = ""
    if desc_el:
        description = re.sub(r"\s+", " ", desc_el.get_text(" ", strip=True)).strip()
        if description.startswith(".banner-container"):
            description = name
    if not description:
        description = name

    img = soup.select_one(".product-image img, .thumbnails img")
    image_url = ""
    if img:
        image_url = img.get("data-zoom-image") or img.get("src") or ""
        image_url = larger_image(urljoin(BASE_URL, image_url))

    category, sub_category, child_category = classify_category(name)
    marked_up = round(source_price * MARKUP, 2)
    short_name = name[:60] + ("..." if len(name) > 60 else "")
    tags = ", ".join(part for part in [brand, child_category or sub_category, extract_weight(name)] if part)

    return {
        "name": name,
        "short_name": short_name,
        "slug": slugify(name),
        "category": category,
        "sub_category": sub_category,
        "child_category": child_category,
        "brand": brand,
        "price": f"{marked_up:.2f}",
        "offer_price": "",
        "qty": qty,
        "short_description": description[:250],
        "long_description": description,
        "sku": sku,
        "weight": extract_weight(name),
        "tags": tags,
        "image_url": image_url,
        "source_url": url,
        "source_price": f"{source_price:.2f}",
    }


def download_image(session: requests.Session, url: str, dest: Path) -> str:
    if not url or dest.exists():
        return str(dest) if dest.exists() else url
    try:
        response = session.get(url, headers=HEADERS, timeout=60)
        response.raise_for_status()
        dest.parent.mkdir(parents=True, exist_ok=True)
        dest.write_bytes(response.content)
        return str(dest)
    except requests.RequestException as exc:
        print(f"[warn] Görsel indirilemedi {url}: {exc}", file=sys.stderr)
        return url


def apply_live_categories(row: dict) -> dict:
    category, sub_category, child_category = classify_category(row.get("name") or "")
    row["category"] = category
    row["sub_category"] = sub_category
    row["child_category"] = child_category
    tags = [p for p in [row.get("brand"), child_category or sub_category, row.get("weight")] if p]
    row["tags"] = ", ".join(str(p) for p in tags)
    return row


def write_xlsx(rows: list[dict], path: Path) -> None:
    wb = Workbook()
    ws = wb.active
    ws.title = "Urunler"
    ws.append(EXCEL_HEADERS)
    for row in rows:
        ws.append([row.get(h, "") for h in EXCEL_HEADERS])

    guide = wb.create_sheet("Nasil Kullanilir")
    guide.append(["Seyfibaba Toplu Ürün Yükleme — Zenix Kozmetik aktarım dosyası"])
    guide.append([f"Kaynak: {CATEGORY_URL}"])
    guide.append([f"Fiyatlara %{int((MARKUP - 1) * 100)} marj uygulandı (source_price = kaynak fiyat)."])
    guide.append(["Kategoriler canli-kategoriler.md (Kozmetik) ağacına ürün adından eşlendi."])
    guide.append(["Zorunlu: name, category, price, qty"])

    path.parent.mkdir(parents=True, exist_ok=True)
    wb.save(path)


def write_seller_xlsx(rows: list[dict], path: Path) -> None:
    wb = Workbook()
    ws = wb.active
    ws.title = "Urunler"
    ws.append(SELLER_HEADERS)
    for row in rows:
        ws.append([row.get(h, "") for h in SELLER_HEADERS])

    guide = wb.create_sheet("Nasil Kullanilir")
    guide.append(["Satıcı paneli — Toplu Excel Yükle (Zenix)"])
    guide.append(["Kategori adları canlı ağaçla birebir: Kozmetik → alt → child"])
    guide.append(["Zorunlu: name, category, price, qty"])
    guide.append(["Görsel URL dolu olan ürünler yüklemede yayına alınır."])
    guide.append(["Fiyat = kaynak fiyat + %20 marj. offer_price boş."])

    path.parent.mkdir(parents=True, exist_ok=True)
    wb.save(path)


def write_csv(rows: list[dict], path: Path) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("w", newline="", encoding="utf-8-sig") as handle:
        writer = csv.DictWriter(handle, fieldnames=EXCEL_HEADERS)
        writer.writeheader()
        writer.writerows(rows)


def main() -> int:
    parser = argparse.ArgumentParser(description="Zenix Kozmetik ürün scraper → Seyfibaba Excel")
    parser.add_argument("--limit", type=int, default=0, help="Maksimum ürün (0 = hepsi)")
    parser.add_argument("--download-images", action="store_true")
    parser.add_argument("--from-csv", help="Mevcut scrape CSV'sini canlı kategorilere göre yeniden yaz")
    parser.add_argument("--output", default=str(OUTPUT_DIR / "zenix_import.xlsx"))
    parser.add_argument(
        "--seller-output",
        default=str(OUTPUT_DIR / "zenix_satici_yukle.xlsx"),
        help="Satıcı paneli Excel yolu",
    )
    args = parser.parse_args()

    if args.from_csv:
        csv_path = Path(args.from_csv)
        rows = list(csv.DictReader(csv_path.open(encoding="utf-8-sig")))
        for row in rows:
            apply_live_categories(row)
        seller_path = Path(args.seller_output)
        write_seller_xlsx(rows, seller_path)
        with seller_path.with_suffix(".csv").open("w", newline="", encoding="utf-8-sig") as handle:
            writer = csv.DictWriter(handle, fieldnames=SELLER_HEADERS, extrasaction="ignore")
            writer.writeheader()
            writer.writerows(rows)
        write_xlsx(rows, Path(args.output))
        write_csv(rows, Path(args.output).with_suffix(".csv"))
        print(f"Yeniden kategorilendi: {len(rows)} ürün")
        print(f"Satıcı Excel: {seller_path}")
        print(f"Satıcı CSV:   {seller_path.with_suffix('.csv')}")
        return 0

    session = requests.Session()
    print("Kategori listesi çekiliyor...")
    product_urls = collect_product_urls(session)
    if args.limit > 0:
        product_urls = product_urls[: args.limit]
    print(f"{len(product_urls)} ürün bulundu. Detaylar çekiliyor...\n")

    rows: list[dict] = []
    seen_slugs: set[str] = set()
    for index, url in enumerate(product_urls, start=1):
        row = scrape_product(session, url)
        if not row:
            continue
        base_slug = row["slug"]
        slug = base_slug
        counter = 2
        while slug in seen_slugs:
            slug = f"{base_slug}-{counter}"
            counter += 1
        row["slug"] = slug
        seen_slugs.add(slug)

        if args.download_images and row.get("image_url"):
            ext = Path(urlparse(row["image_url"]).path).suffix or ".jpg"
            local_path = download_image(session, row["image_url"], IMAGES_DIR / f"{slug}{ext}")
            row["image_url"] = local_path

        rows.append(row)
        cat = f"{row['sub_category']} / {row['child_category']}" if row["child_category"] else row["sub_category"]
        print(f"[{index}/{len(product_urls)}] {row['name'][:65]} | {row['source_price']} TL | {cat}")
        time.sleep(0.35)

    if not rows:
        print("Hiç ürün çekilemedi.", file=sys.stderr)
        return 1

    output_path = Path(args.output)
    write_xlsx(rows, output_path)
    write_csv(rows, output_path.with_suffix(".csv"))
    seller_path = Path(args.seller_output)
    write_seller_xlsx(rows, seller_path)
    with seller_path.with_suffix(".csv").open("w", newline="", encoding="utf-8-sig") as handle:
        writer = csv.DictWriter(handle, fieldnames=SELLER_HEADERS, extrasaction="ignore")
        writer.writeheader()
        writer.writerows(rows)
    print(f"\nTamamlandı: {len(rows)} ürün")
    print(f"Excel: {output_path}")
    print(f"CSV:   {output_path.with_suffix('.csv')}")
    print(f"Satıcı Excel: {seller_path}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
