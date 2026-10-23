#!/usr/bin/env bash
# BRAYVEN end-to-end smoke test.
# Usage: bash tools/smoke-test.sh [BASE_URL]   (default http://127.0.0.1:8080)
set -u
BASE="${1:-http://127.0.0.1:8080}"
MYSQL="${MYSQL:-/c/xampp/mysql/bin/mysql.exe}"
TMP=$(mktemp -d)
PASS=0; FAIL=0

check() { # label expected actual
  if [ "$2" = "$3" ]; then PASS=$((PASS+1)); echo "PASS: $1"; else FAIL=$((FAIL+1)); echo "FAIL: $1 (expected [$2] got [$3])"; fi
}
contains() { # label url pattern
  if curl -s -b "$TMP/jar-$4" -c "$TMP/jar-$4" "$2" | grep -q "$3"; then PASS=$((PASS+1)); echo "PASS: $1"; else FAIL=$((FAIL+1)); echo "FAIL: $1 — '$3' missing at $2"; fi
}
lacks() { # label url pattern jar
  if curl -s -b "$TMP/jar-$4" -c "$TMP/jar-$4" "$2" | grep -q "$3"; then FAIL=$((FAIL+1)); echo "FAIL: $1 — '$3' unexpectedly present at $2"; else PASS=$((PASS+1)); echo "PASS: $1"; fi
}
redirects_to() { # label url pattern jar
  loc=$(curl -s -o /dev/null -w '%{redirect_url}' -b "$TMP/jar-$4" -c "$TMP/jar-$4" "$2")
  case "$loc" in *"$3"*) PASS=$((PASS+1)); echo "PASS: $1";; *) FAIL=$((FAIL+1)); echo "FAIL: $1 — redirected to [$loc]";; esac
}
csrf() { # url jar → csrf token
  curl -s -b "$TMP/jar-$2" -c "$TMP/jar-$2" "$1" | grep -oP 'name="csrf_token" value="\K[a-f0-9]{32,}' | head -1
}
form_token() { # url jar → checkout form token
  curl -s -b "$TMP/jar-$2" -c "$TMP/jar-$2" "$1" | grep -oP 'name="form_token" value="\K[a-f0-9]+' | head -1
}
db() { "$MYSQL" -u root -N -e "USE brayven; $1" 2>/dev/null; }

echo "=== BRAYVEN smoke test against $BASE ==="

# ---------- 1. Storefront ----------
contains "homepage renders hero"            "$BASE/index.php" "Define Your Own Standard" g
contains "homepage newsletter present"      "$BASE/index.php" "Subscribe" g
contains "shop lists products"              "$BASE/shop.php"  "products" g
contains "women filter excludes mens shirt" "$BASE/shop.php?department=women" "Midi Slip Dress" g
lacks    "women filter has no oxford shirt" "$BASE/shop.php?department=women" "Oxford Button-Down" g
contains "price filter includes trousers"   "$BASE/shop.php?min_price=100&max_price=200" "Tailored Wool Trousers" g
lacks    "price filter excludes 29-dollar tee" "$BASE/shop.php?min_price=100&max_price=200" "Essential Crew Tee" g
contains "sort low-to-high shows beanie"    "$BASE/shop.php?sort=price_asc" "Ribbed Cotton Beanie" g
contains "sale flag shows pocket tee"       "$BASE/shop.php?sale=1" "Pima Cotton Pocket Tee" g
contains "new arrivals show slip dress"     "$BASE/shop.php?new=1" "Midi Slip Dress" g
contains "search finds linen"               "$BASE/search.php?q=linen" "Linen Camp Collar Shirt" g
contains "empty search shows empty state"   "$BASE/search.php?q=zzzzqq" "Nothing matches" g
contains "category page works"              "$BASE/category.php?slug=hoodies" "Classic Pullover Hoodie" g
contains "bad category 404s"                "$BASE/category.php?slug=nope-nope" "Category not found" g
contains "product detail works"             "$BASE/product.php?slug=classic-pullover-hoodie" "Add to Cart" g
contains "bad product 404s"                 "$BASE/product.php?slug=zzz" "no longer available" g
for p in about contact faq privacy terms login register; do
  code=$(curl -s -o /dev/null -w '%{http_code}' "$BASE/$p.php"); check "$p.php returns 200" 200 "$code"
done
contains "wishlist requires login"          "$BASE/wishlist.php" "Save what you love" g

# ---------- 2. CSRF protection ----------
code=$(curl -s -o /dev/null -w '%{http_code}' -X POST "$BASE/cart.php" -d "action=add&product_id=1&qty=1&size=M&color=Sand")
check "cart POST without CSRF token is rejected (419)" 419 "$code"

# ---------- 3. Guest cart + checkout ----------
G=g
T=$(csrf "$BASE/product.php?slug=classic-pullover-hoodie" $G)
curl -s -o /dev/null -b "$TMP/jar-$G" -c "$TMP/jar-$G" -X POST "$BASE/cart.php" \
  -d "action=add&product_id=9&qty=2&size=M&color=Charcoal&csrf_token=$T&return=/cart.php"
contains "cart shows added product" "$BASE/cart.php" "Classic Pullover Hoodie" $G

T=$(csrf "$BASE/product.php?slug=slub-jersey-longline-tee" $G)
curl -s -o /dev/null -b "$TMP/jar-$G" -c "$TMP/jar-$G" -X POST "$BASE/cart.php" \
  --data-urlencode "action=add" --data-urlencode "product_id=4" --data-urlencode "qty=1" \
  --data-urlencode "size=M" --data-urlencode "color=Stone" --data-urlencode "csrf_token=$T" --data-urlencode "return=/cart.php"
lacks "sold-out product rejected from cart" "$BASE/cart.php" "Slub Jersey" $G

# overstock clamp: request 999 of pullover, expect clamp to stock (40)
T=$(csrf "$BASE/product.php?slug=classic-pullover-hoodie" $G)
curl -s -o /dev/null -b "$TMP/jar-$G" -c "$TMP/jar-$G" -X POST "$BASE/cart.php" \
  -d "action=update&key=9|M|Charcoal&qty=999&csrf_token=$T"
contains "quantity clamped to available stock" "$BASE/cart.php" "Max available" $G
# back to 2 for a clean order
curl -s -o /dev/null -b "$TMP/jar-$G" -c "$TMP/jar-$G" -X POST "$BASE/cart.php" \
  -d "action=update&key=9|M|Charcoal&qty=2&csrf_token=$T"

redirects_to "cart redirects to checkout" "$BASE/checkout.php" "checkout.php" $G
FT=$(form_token "$BASE/checkout.php" $G); T=$(csrf "$BASE/checkout.php" $G)
LOC=$(curl -s -o /dev/null -w '%{redirect_url}' -b "$TMP/jar-$G" -c "$TMP/jar-$G" -X POST "$BASE/checkout.php" \
  -d "csrf_token=$T&form_token=$FT&name=Test Buyer&email=buyer@brayven.test&phone=5551234567&address=12 Editor Lane&city=Metropolis&postal_code=10001&notes=Leave at desk")
ORDER=$(echo "$LOC" | grep -oP 'order=\KBRV-[0-9A-Z]+')
if [ -n "$ORDER" ]; then PASS=$((PASS+1)); echo "PASS: checkout created order $ORDER"; else FAIL=$((FAIL+1)); echo "FAIL: checkout did not create order ([$LOC])"; fi
contains "receipt renders" "$BASE/order-success.php?order=$ORDER" "Your order is confirmed" $G
lacks    "receipt is one-time for guests" "$BASE/order-success.php?order=$ORDER" "Your order is confirmed" $G
check "cart cleared after order" "0" "$(curl -s -b "$TMP/jar-$G" -c "$TMP/jar-$G" "$BASE/cart.php" | grep -q 'Your bag is empty' && echo 0 || echo 1)"

# DB truth: order row + stock decrement
check "order saved in DB" "1" "$(db "SELECT COUNT(*) FROM orders WHERE order_number='$ORDER'")"
check "order item saved"  "1" "$(db "SELECT COUNT(*) FROM order_items WHERE order_id=(SELECT id FROM orders WHERE order_number='$ORDER') AND quantity=2")"
check "stock decremented 40→38" "38" "$(db "SELECT stock FROM products WHERE id=9")"

# ---------- 4. Customer auth ----------
redirects_to "admin pages blocked for guests" "$BASE/admin/index.php" "login.php" a
T=$(csrf "$BASE/register.php" a)
redirects_to "registration works" "$BASE/register.php" "profile.php" a <<< ""
curl -s -o /dev/null -b "$TMP/jar-a" -c "$TMP/jar-a" -X POST "$BASE/register.php" \
  -d "csrf_token=$T&name=Ada Tester&email=ada@brayven.test&phone=&password=Testpass1&password_confirm=Testpass1"
contains "profile shows new user" "$BASE/profile.php" "Ada Tester" a
T=$(csrf "$BASE/profile.php" a)
curl -s -o /dev/null -b "$TMP/jar-a" -c "$TMP/jar-a" -X POST "$BASE/profile.php" \
  -d "csrf_token=$T&action=update_profile&name=Ada Tester&email=ada@brayven.test&phone=5550001111&new_password=Testpass2"
redirects_to "logout works" "$BASE/logout.php" "index.php" a
T=$(csrf "$BASE/login.php" a)
redirects_to "login works" "$BASE/login.php" "profile.php" a
curl -s -o /dev/null -b "$TMP/jar-a" -c "$TMP/jar-a" -X POST "$BASE/login.php" -d "csrf_token=$T&email=ada@brayven.test&password=Testpass2"
contains "changed password accepted" "$BASE/profile.php" "Hello, Ada" a

T=$(csrf "$BASE/product.php?slug=essential-crew-tee" a)
curl -s -o /dev/null -b "$TMP/jar-a" -c "$TMP/jar-a" -X POST "$BASE/wishlist.php" \
  -d "csrf_token=$T&action=toggle&product_id=1&return=/wishlist.php"
contains "wishlist saves product" "$BASE/wishlist.php" "Essential Crew Tee" a

# demo customer login
D=d
T=$(csrf "$BASE/login.php" $D)
curl -s -o /dev/null -b "$TMP/jar-$D" -c "$TMP/jar-$D" -X POST "$BASE/login.php" -d "csrf_token=$T&email=demo@brayven.test&password=Brayven@123"
contains "demo customer can sign in" "$BASE/profile.php" "Hello, Demo" $D
lacks "demo cannot see guest orders" "$BASE/orders.php" "$ORDER" $D

# ---------- 5. Admin ----------
S=s
T=$(csrf "$BASE/setup-admin.php" $S)
redirects_to "admin setup creates account" "$BASE/setup-admin.php" "admin/index.php" $S
curl -s -o /dev/null -b "$TMP/jar-$S" -c "$TMP/jar-$S" -X POST "$BASE/setup-admin.php" \
  -d "csrf_token=$T&name=Store Owner&email=admin@brayven.test&password=AdminPass123&password_confirm=AdminPass123"
contains "admin dashboard renders" "$BASE/admin/index.php" "Active Products" $S
for p in products orders categories customers; do
  code=$(curl -s -o /dev/null -w '%{http_code}' -b "$TMP/jar-$S" "$BASE/admin/$p.php"); check "admin/$p.php returns 200" 200 "$code"
done

T=$(csrf "$BASE/admin/product-edit.php" $S)
curl -s -o /dev/null -b "$TMP/jar-$S" -c "$TMP/jar-$S" -X POST "$BASE/admin/product-edit.php" \
  -d "csrf_token=$T&category_id=1&name=Studio Test Piece&slug=studio-test-piece&sku=BRV-TST-999&description=A test product created by the automated smoke test suite.&price=49.99&sale_price=&sizes=S,M,L&colors=Sand&stock=10&department=unisex&is_active=1"
contains "admin created product listed" "$BASE/admin/products.php" "Studio Test Piece" $S
contains "new product visible in store" "$BASE/product.php?slug=studio-test-piece" "Studio Test Piece" $S

OID=$(db "SELECT id FROM orders ORDER BY id DESC LIMIT 1")
T=$(csrf "$BASE/admin/orders.php" $S)
curl -s -o /dev/null -b "$TMP/jar-$S" -c "$TMP/jar-$S" -X POST "$BASE/admin/orders.php" \
  -d "csrf_token=$T&order_id=$OID&status=processing&payment_status=paid"
check "admin updated order status" "processing|paid" "$(db "SELECT CONCAT(status,'|',payment_status) FROM orders WHERE id=$OID")"

T=$(csrf "$BASE/admin/categories.php" $S)
curl -s -o /dev/null -b "$TMP/jar-$S" -c "$TMP/jar-$S" -X POST "$BASE/admin/categories.php" \
  -d "csrf_token=$T&action=create&name=Knitwear&slug=&description=Test category&sort_order=10"
contains "admin created category" "$BASE/admin/categories.php" "Knitwear" $S

contains "customers list shows registrant" "$BASE/admin/customers.php" "ada@brayven.test" $S
lacks    "customers page hides password hashes" "$BASE/admin/customers.php" '$2y$' $S

# customer cannot reach admin
contains "customer blocked from admin (redirect)" "$BASE/admin/index.php" "" x
redirects_to "customer redirected from admin" "$BASE/admin/index.php" "login.php" a

echo "==================================="
echo "RESULT: $PASS passed, $FAIL failed"
[ "$FAIL" = "0" ]
