#!/bin/bash
# ============================================================================
# Shopo Marketplace Feature Smoke Test
# Usage:
#   bash tests/smoke/marketplace-feature-smoke.sh [BASE_URL] [SELLER_TOKEN] [ADMIN_TOKEN]
# Example:
#   bash tests/smoke/marketplace-feature-smoke.sh http://localhost:8000 "$SELLER" "$ADMIN"
# ============================================================================

set -euo pipefail

BASE_URL="${1:-http://localhost:8000}"
SELLER_TOKEN="${2:-}"
ADMIN_TOKEN="${3:-}"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

PASS=0
FAIL=0
WARN=0

TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

log_pass() { echo -e "${GREEN}[PASS]${NC} $1"; ((PASS++)); }
log_fail() { echo -e "${RED}[FAIL]${NC} $1"; ((FAIL++)); }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; ((WARN++)); }
log_info() { echo "       $1"; }

check_status() {
    local label="$1"
    local expected="$2"
    local actual="$3"

    if [ "$actual" = "$expected" ]; then
        log_pass "$label (HTTP $actual)"
    else
        log_fail "$label (expected $expected, got $actual)"
    fi
}

check_json_path_contains() {
    local label="$1"
    local file="$2"
    local pattern="$3"

    if grep -q "$pattern" "$file"; then
        log_pass "$label"
    else
        log_fail "$label"
        log_info "Response body:"
        cat "$file"
    fi
}

create_sample_csv() {
    cat > "$TMP_DIR/products.csv" <<'CSV'
name,short_name,slug,category,sub_category,child_category,brand,price,offer_price,qty,short_description,long_description,sku,weight,tags,status
"Smoke Product","Smoke","smoke-product","Elektronik","","","","1500.00","","10","Short description","Long description","SMOKE-001","0.5","smoke,test","1"
CSV
}

create_sample_pdf() {
    cat > "$TMP_DIR/kyc.pdf" <<'PDF'
%PDF-1.1
1 0 obj
<< /Type /Catalog /Pages 2 0 R >>
endobj
2 0 obj
<< /Type /Pages /Kids [3 0 R] /Count 1 >>
endobj
3 0 obj
<< /Type /Page /Parent 2 0 R /MediaBox [0 0 200 200] >>
endobj
trailer
<< /Root 1 0 R >>
%%EOF
PDF
}

echo ""
echo "========================================="
echo "PUBLIC ENDPOINTS"
echo "========================================="

BODY_FILE="$TMP_DIR/public-home.json"
STATUS=$(curl -s -o "$BODY_FILE" -w "%{http_code}" "$BASE_URL/api/website-setup")
check_status "website setup endpoint reachable" "200" "$STATUS"

echo ""
echo "========================================="
echo "SELLER ENDPOINTS"
echo "========================================="

if [ -z "$SELLER_TOKEN" ]; then
    log_warn "Seller token missing; skipping seller smoke checks"
else
    BODY_FILE="$TMP_DIR/seller-kyc-status.json"
    STATUS=$(curl -s -o "$BODY_FILE" -w "%{http_code}" \
        -H "Authorization: Bearer $SELLER_TOKEN" \
        "$BASE_URL/api/seller/kyc/status")
    check_status "seller KYC status endpoint" "200" "$STATUS"

    BODY_FILE="$TMP_DIR/seller-kyc-documents.json"
    STATUS=$(curl -s -o "$BODY_FILE" -w "%{http_code}" \
        -H "Authorization: Bearer $SELLER_TOKEN" \
        "$BASE_URL/api/seller/kyc/documents")
    check_status "seller KYC documents endpoint" "200" "$STATUS"

    BODY_FILE="$TMP_DIR/seller-bulk-template.csv"
    STATUS=$(curl -s -o "$BODY_FILE" -w "%{http_code}" \
        -H "Authorization: Bearer $SELLER_TOKEN" \
        "$BASE_URL/api/seller/products/bulk-import/template")
    check_status "seller bulk import template endpoint" "200" "$STATUS"
    check_json_path_contains "seller bulk template contains expected header" "$BODY_FILE" "name,short_name,slug,category"

    BODY_FILE="$TMP_DIR/seller-bulk-imports.json"
    STATUS=$(curl -s -o "$BODY_FILE" -w "%{http_code}" \
        -H "Authorization: Bearer $SELLER_TOKEN" \
        "$BASE_URL/api/seller/products/bulk-imports")
    check_status "seller bulk import history endpoint" "200" "$STATUS"

    BODY_FILE="$TMP_DIR/seller-low-stock.json"
    STATUS=$(curl -s -o "$BODY_FILE" -w "%{http_code}" \
        -H "Authorization: Bearer $SELLER_TOKEN" \
        "$BASE_URL/api/seller/products/low-stock")
    check_status "seller low stock endpoint" "200" "$STATUS"

    BODY_FILE="$TMP_DIR/seller-notifications.json"
    STATUS=$(curl -s -o "$BODY_FILE" -w "%{http_code}" \
        -H "Authorization: Bearer $SELLER_TOKEN" \
        "$BASE_URL/api/seller/notifications")
    check_status "seller notifications endpoint" "200" "$STATUS"

    create_sample_pdf
    BODY_FILE="$TMP_DIR/seller-kyc-upload.json"
    STATUS=$(curl -s -o "$BODY_FILE" -w "%{http_code}" \
        -X POST \
        -H "Authorization: Bearer $SELLER_TOKEN" \
        -F "document_type=identity_front" \
        -F "document=@$TMP_DIR/kyc.pdf;type=application/pdf" \
        "$BASE_URL/api/seller/kyc/upload")
    if [ "$STATUS" = "201" ] || [ "$STATUS" = "422" ]; then
        log_pass "seller KYC upload endpoint responds with expected mutation status (HTTP $STATUS)"
    else
        log_fail "seller KYC upload endpoint unexpected status (HTTP $STATUS)"
        cat "$BODY_FILE"
    fi

    create_sample_csv
    BODY_FILE="$TMP_DIR/seller-bulk-upload.json"
    STATUS=$(curl -s -o "$BODY_FILE" -w "%{http_code}" \
        -X POST \
        -H "Authorization: Bearer $SELLER_TOKEN" \
        -F "import_file=@$TMP_DIR/products.csv;type=text/csv" \
        "$BASE_URL/api/seller/products/bulk-import")
    if [ "$STATUS" = "201" ] || [ "$STATUS" = "422" ]; then
        log_pass "seller bulk import upload endpoint responds with expected mutation status (HTTP $STATUS)"
    else
        log_fail "seller bulk import upload endpoint unexpected status (HTTP $STATUS)"
        cat "$BODY_FILE"
    fi
fi

echo ""
echo "========================================="
echo "ADMIN ENDPOINTS"
echo "========================================="

if [ -z "$ADMIN_TOKEN" ]; then
    log_warn "Admin token missing; skipping admin smoke checks"
else
    BODY_FILE="$TMP_DIR/admin-kyc-pending.json"
    STATUS=$(curl -s -o "$BODY_FILE" -w "%{http_code}" \
        -H "Authorization: Bearer $ADMIN_TOKEN" \
        "$BASE_URL/api/admin/kyc/pending")
    check_status "admin pending KYC endpoint" "200" "$STATUS"

    BODY_FILE="$TMP_DIR/admin-stock-settings.json"
    STATUS=$(curl -s -o "$BODY_FILE" -w "%{http_code}" \
        -H "Authorization: Bearer $ADMIN_TOKEN" \
        "$BASE_URL/api/admin/stock-alerts/settings")
    check_status "admin stock alert settings endpoint" "200" "$STATUS"

    BODY_FILE="$TMP_DIR/admin-low-stock.json"
    STATUS=$(curl -s -o "$BODY_FILE" -w "%{http_code}" \
        -H "Authorization: Bearer $ADMIN_TOKEN" \
        "$BASE_URL/api/admin/products/low-stock")
    check_status "admin low stock endpoint" "200" "$STATUS"

    BODY_FILE="$TMP_DIR/admin-bulk-imports.json"
    STATUS=$(curl -s -o "$BODY_FILE" -w "%{http_code}" \
        -H "Authorization: Bearer $ADMIN_TOKEN" \
        "$BASE_URL/api/admin/products/bulk-imports")
    check_status "admin bulk import history endpoint" "200" "$STATUS"

    create_sample_csv
    BODY_FILE="$TMP_DIR/admin-bulk-upload.json"
    STATUS=$(curl -s -o "$BODY_FILE" -w "%{http_code}" \
        -X POST \
        -H "Authorization: Bearer $ADMIN_TOKEN" \
        -F "import_file=@$TMP_DIR/products.csv;type=text/csv" \
        "$BASE_URL/api/admin/products/bulk-import")
    if [ "$STATUS" = "201" ] || [ "$STATUS" = "422" ]; then
        log_pass "admin bulk import upload endpoint responds with expected mutation status (HTTP $STATUS)"
    else
        log_fail "admin bulk import upload endpoint unexpected status (HTTP $STATUS)"
        cat "$BODY_FILE"
    fi
fi

echo ""
echo "========================================="
echo "SUMMARY"
echo "========================================="
echo "PASS: $PASS"
echo "FAIL: $FAIL"
echo "WARN: $WARN"

if [ "$FAIL" -gt 0 ]; then
    exit 1
fi

exit 0
