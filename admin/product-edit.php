<?php
/** BRAYVEN — Admin: add / edit a product (with secure image upload). */
require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$editing = $id > 0;

$categories = $pdo->query('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order, name')->fetchAll();

$product = [
    'category_id' => '', 'name' => '', 'slug' => '', 'sku' => '', 'description' => '',
    'price' => '', 'sale_price' => '', 'image' => '', 'sizes' => 'S,M,L,XL', 'colors' => '',
    'stock' => 0, 'department' => 'unisex', 'is_featured' => 0, 'is_new' => 0, 'is_active' => 1,
];
if ($editing) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) {
        flash_set('danger', 'Product not found.');
        redirect('admin/products.php');
    }
}

$errors = [];
$gallery = [];
if ($editing) {
    $g = $pdo->prepare('SELECT image FROM product_images WHERE product_id = ? ORDER BY sort_order, id');
    $g->execute([$id]);
    $gallery = $g->fetchAll(PDO::FETCH_COLUMN);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $product['category_id'] = (int)($_POST['category_id'] ?? 0);
    $product['name'] = trim((string)($_POST['name'] ?? ''));
    $product['slug'] = slugify(trim((string)($_POST['slug'] ?? '')) ?: $product['name']);
    $product['sku'] = strtoupper(trim((string)($_POST['sku'] ?? '')));
    $product['description'] = trim((string)($_POST['description'] ?? ''));
    $product['price'] = (float)($_POST['price'] ?? 0);
    $saleRaw = trim((string)($_POST['sale_price'] ?? ''));
    $product['sale_price'] = $saleRaw !== '' ? (float)$saleRaw : null;
    $product['sizes'] = implode(',', array_filter(array_map('trim', explode(',', (string)($_POST['sizes'] ?? '')))));
    $product['colors'] = implode(',', array_filter(array_map('trim', explode(',', (string)($_POST['colors'] ?? '')))));
    $product['stock'] = max(0, (int)($_POST['stock'] ?? 0));
    $product['department'] = in_array($_POST['department'] ?? '', ['men', 'women', 'unisex'], true) ? $_POST['department'] : 'unisex';
    $product['is_featured'] = isset($_POST['is_featured']) ? 1 : 0;
    $product['is_new'] = isset($_POST['is_new']) ? 1 : 0;
    $product['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    // Category must exist
    $catOk = false;
    foreach ($categories as $c) { if ((int)$c['id'] === $product['category_id']) { $catOk = true; break; } }
    if (!$catOk) { $errors['category_id'] = 'Choose a category.'; }
    if (mb_strlen($product['name']) < 2) { $errors['name'] = 'Product name is required.'; }
    if (mb_strlen($product['sku']) < 3) { $errors['sku'] = 'SKU must be at least 3 characters.'; }
    if (mb_strlen($product['description']) < 20) { $errors['description'] = 'Description must be at least 20 characters.'; }
    if ($product['price'] <= 0) { $errors['price'] = 'Price must be greater than zero.'; }
    if ($product['sale_price'] !== null && $product['sale_price'] >= $product['price']) { $errors['sale_price'] = 'Sale price must be lower than the regular price.'; }

    // Uniqueness
    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM products WHERE sku = ? AND id <> ?');
        $stmt->execute([$product['sku'], $id]);
        if ($stmt->fetch()) { $errors['sku'] = 'This SKU is already in use.'; }
        $stmt = $pdo->prepare('SELECT id FROM products WHERE slug = ? AND id <> ?');
        $stmt->execute([$product['slug'], $id]);
        if ($stmt->fetch()) { $product['slug'] .= '-' . substr(bin2hex(random_bytes(2)), 0, 4); }
    }

    // Secure image upload (main image)
    $newImage = save_uploaded_image('image_upload', $product['slug'] ?: 'product');

    if (!$errors) {
        try {
            if ($editing) {
                $stmt = $pdo->prepare(
                    'UPDATE products SET category_id=?, name=?, slug=?, sku=?, description=?, price=?, sale_price=?,
                     sizes=?, colors=?, stock=?, department=?, is_featured=?, is_new=?, is_active=? WHERE id=?'
                );
                $stmt->execute([
                    $product['category_id'], $product['name'], $product['slug'], $product['sku'], $product['description'],
                    $product['price'], $product['sale_price'], $product['sizes'], $product['colors'], $product['stock'],
                    $product['department'], $product['is_featured'], $product['is_new'], $product['is_active'], $id,
                ]);
                if ($newImage) {
                    $pdo->prepare('UPDATE products SET image = ? WHERE id = ?')->execute([$newImage, $id]);
                    $pdo->prepare('INSERT INTO product_images (product_id, image, sort_order) VALUES (?, ?, 0)')->execute([$id, $newImage]);
                }
                flash_set('success', 'Product updated.');
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO products (category_id, name, slug, sku, description, price, sale_price, image, sizes, colors, stock, department, is_featured, is_new, is_active)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $product['category_id'], $product['name'], $product['slug'], $product['sku'], $product['description'],
                    $product['price'], $product['sale_price'], $newImage, $product['sizes'], $product['colors'],
                    $product['stock'], $product['department'], $product['is_featured'], $product['is_new'], $product['is_active'],
                ]);
                $id = (int)$pdo->lastInsertId();
                if ($newImage) {
                    $pdo->prepare('INSERT INTO product_images (product_id, image, sort_order) VALUES (?, ?, 0)')->execute([$id, $newImage]);
                }
                flash_set('success', 'Product created.');
                redirect('admin/product-edit.php?id=' . $id);
            }
            redirect('admin/product-edit.php?id=' . $id);
        } catch (PDOException $e) {
            error_log('Product save failed: ' . $e->getMessage());
            $errors['general'] = 'Could not save the product. Check the values and try again.';
        }
    }
}

$pageTitle = $editing ? 'Edit Product' : 'Add Product';
$adminActive = 'products';
require __DIR__ . '/includes/admin-header.php';
?>

<?php if (isset($errors['general'])): ?>
  <div class="adm-card mb-3 border-danger"><div class="adm-card-body text-danger"><?= e($errors['general']) ?></div></div>
<?php endif; ?>

<form method="post" action="<?= e(url('admin/product-edit.php' . ($editing ? '?id=' . $id : ''))) ?>" enctype="multipart/form-data" class="row g-3">
  <?= csrf_field() ?>

  <div class="col-lg-8">
    <div class="adm-card mb-3">
      <div class="adm-card-head"><h2>Product Information</h2></div>
      <div class="adm-card-body">
        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label-adm" for="pName">Product name *</label>
            <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="pName" name="name" value="<?= e($product['name']) ?>" required>
            <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?>
          </div>
          <div class="col-md-4">
            <label class="form-label-adm" for="pSku">SKU *</label>
            <input class="form-control <?= isset($errors['sku']) ? 'is-invalid' : '' ?>" id="pSku" name="sku" value="<?= e($product['sku']) ?>" placeholder="BRV-XXX-000" required>
            <?php if (isset($errors['sku'])): ?><div class="invalid-feedback"><?= e($errors['sku']) ?></div><?php endif; ?>
          </div>
          <div class="col-12">
            <label class="form-label-adm" for="pDesc">Description *</label>
            <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" id="pDesc" name="description" rows="4" required><?= e($product['description']) ?></textarea>
            <?php if (isset($errors['description'])): ?><div class="invalid-feedback"><?= e($errors['description']) ?></div><?php endif; ?>
          </div>
          <div class="col-md-3">
            <label class="form-label-adm" for="pPrice">Price (<?= CURRENCY ?>) *</label>
            <input type="number" step="0.01" min="0.01" class="form-control <?= isset($errors['price']) ? 'is-invalid' : '' ?>" id="pPrice" name="price" value="<?= e((string)$product['price']) ?>" required>
            <?php if (isset($errors['price'])): ?><div class="invalid-feedback"><?= e($errors['price']) ?></div><?php endif; ?>
          </div>
          <div class="col-md-3">
            <label class="form-label-adm" for="pSale">Sale price (optional)</label>
            <input type="number" step="0.01" min="0" class="form-control <?= isset($errors['sale_price']) ? 'is-invalid' : '' ?>" id="pSale" name="sale_price" value="<?= $product['sale_price'] !== null ? e((string)$product['sale_price']) : '' ?>">
            <?php if (isset($errors['sale_price'])): ?><div class="invalid-feedback"><?= e($errors['sale_price']) ?></div><?php endif; ?>
          </div>
          <div class="col-md-3">
            <label class="form-label-adm" for="pStock">Stock quantity *</label>
            <input type="number" min="0" class="form-control" id="pStock" name="stock" value="<?= (int)$product['stock'] ?>" required>
          </div>
          <div class="col-md-3">
            <label class="form-label-adm" for="pDept">Department</label>
            <select class="form-select" id="pDept" name="department">
              <?php foreach (['unisex' => 'Unisex', 'men' => 'Men', 'women' => 'Women'] as $val => $label): ?>
                <option value="<?= $val ?>" <?= $product['department'] === $val ? 'selected' : '' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label-adm" for="pSizes">Sizes (comma separated)</label>
            <input class="form-control" id="pSizes" name="sizes" value="<?= e($product['sizes']) ?>" placeholder="S,M,L,XL or 28,30,32,34 or One Size">
          </div>
          <div class="col-md-6">
            <label class="form-label-adm" for="pColors">Colours (comma separated)</label>
            <input class="form-control" id="pColors" name="colors" value="<?= e($product['colors']) ?>" placeholder="Sand, Charcoal, Ivory">
          </div>
        </div>
      </div>
    </div>

    <?php if ($editing): ?>
      <div class="adm-card">
        <div class="adm-card-head"><h2>Gallery (<?= count($gallery) ?> images)</h2></div>
        <div class="adm-card-body d-flex flex-wrap gap-3">
          <?php foreach ($gallery as $img): ?>
            <figure class="m-0 text-center">
              <img src="<?= e(product_image_url($img)) ?>" alt="" width="72" height="90" style="object-fit:cover;background:#ECE9E3;border:1px solid var(--adm-border);">
              <figcaption class="mini-note" style="font-size:0.62rem;"><?= e(strlen($img) > 22 ? substr($img, -22) : $img) ?></figcaption>
            </figure>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="col-lg-4">
    <div class="adm-card mb-3">
      <div class="adm-card-head"><h2>Organisation</h2></div>
      <div class="adm-card-body">
        <label class="form-label-adm" for="pCat">Category *</label>
        <select class="form-select <?= isset($errors['category_id']) ? 'is-invalid' : '' ?>" id="pCat" name="category_id" required>
          <option value="">Choose…</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= (int)$product['category_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errors['category_id'])): ?><div class="invalid-feedback"><?= e($errors['category_id']) ?></div><?php endif; ?>

        <label class="form-label-adm mt-3" for="pSlug">URL slug (auto from name if blank)</label>
        <input class="form-control" id="pSlug" name="slug" value="<?= e($product['slug']) ?>" placeholder="essential-crew-tee">

        <div class="form-check form-switch mt-3">
          <input class="form-check-input" type="checkbox" id="pActive" name="is_active" <?= (int)$product['is_active'] === 1 ? 'checked' : '' ?>>
          <label class="form-check-label small" for="pActive">Active (visible in store)</label>
        </div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="pFeatured" name="is_featured" <?= (int)$product['is_featured'] === 1 ? 'checked' : '' ?>>
          <label class="form-check-label small" for="pFeatured">Featured (best sellers)</label>
        </div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="pNew" name="is_new" <?= (int)$product['is_new'] === 1 ? 'checked' : '' ?>>
          <label class="form-check-label small" for="pNew">New arrival badge</label>
        </div>
      </div>
    </div>

    <div class="adm-card mb-3">
      <div class="adm-card-head"><h2>Main Image</h2></div>
      <div class="adm-card-body">
        <?php if (!empty($product['image'])): ?>
          <img src="<?= e(product_image_url($product['image'])) ?>" alt="Current product image" width="120" height="150" class="d-block mb-3" style="object-fit:cover;background:#ECE9E3;border:1px solid var(--adm-border);">
        <?php endif; ?>
        <label class="form-label-adm" for="pImage">Upload new (JPG/PNG/WebP, max 3 MB)</label>
        <input type="file" class="form-control" id="pImage" name="image_upload" accept=".jpg,.jpeg,.png,.webp">
        <p class="mini-note mt-2 mb-0">Uploads are stored in <code>uploads/products/</code> with a randomised filename. Type and size are validated server-side.</p>
      </div>
    </div>

    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-adm"><?= $editing ? 'Save Changes' : 'Create Product' ?></button>
      <a class="btn btn-adm-outline text-decoration-none" href="<?= e(url('admin/products.php')) ?>">Cancel</a>
    </div>
  </div>
</form>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
