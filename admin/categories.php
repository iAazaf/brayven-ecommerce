<?php
/** BRAYVEN — Admin: category management. */
require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'create' || $action === 'update') {
        $id = (int)($_POST['category_id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $slug = slugify(trim((string)($_POST['slug'] ?? '')) ?: $name);
        $description = trim((string)($_POST['description'] ?? ''));
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        if (mb_strlen($name) < 2) {
            flash_set('danger', 'Category name must be at least 2 characters.');
        } else {
            $stmt = $pdo->prepare('SELECT id FROM categories WHERE slug = ? AND id <> ?');
            $stmt->execute([$slug, $id]);
            if ($stmt->fetch()) {
                $slug .= '-' . substr(bin2hex(random_bytes(2)), 0, 4);
            }
            if ($action === 'create') {
                $stmt = $pdo->prepare('INSERT INTO categories (name, slug, description, sort_order) VALUES (?, ?, ?, ?)');
                $stmt->execute([$name, $slug, $description !== '' ? $description : null, $sortOrder]);
                flash_set('success', 'Category created.');
            } else {
                $stmt = $pdo->prepare('UPDATE categories SET name = ?, slug = ?, description = ?, sort_order = ? WHERE id = ?');
                $stmt->execute([$name, $slug, $description !== '' ? $description : null, $sortOrder, $id]);
                flash_set('success', 'Category updated.');
            }
        }
    } elseif ($action === 'toggle') {
        $pdo->prepare('UPDATE categories SET is_active = 1 - is_active WHERE id = ?')->execute([(int)($_POST['category_id'] ?? 0)]);
        flash_set('success', 'Category visibility updated.');
    } elseif ($action === 'delete') {
        $id = (int)($_POST['category_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
        $stmt->execute([$id]);
        if ((int)$stmt->fetchColumn() > 0) {
            flash_set('danger', 'Cannot delete: products still belong to this category. Move or delete them first.');
        } else {
            $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            flash_set('success', 'Category deleted.');
        }
    }
    redirect('admin/categories.php');
}

$categories = $pdo->query(
    'SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
     FROM categories c ORDER BY c.sort_order, c.name'
)->fetchAll();

$pageTitle = 'Categories';
$adminActive = 'categories';
require __DIR__ . '/includes/admin-header.php';
?>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="adm-card">
      <div class="adm-card-head"><h2><?= count($categories) ?> categories</h2></div>
      <div class="table-responsive">
        <table class="table adm-table mb-0">
          <thead><tr><th>Category</th><th>Slug</th><th>Products</th><th>Order</th><th>Visibility</th><th class="text-end">Actions</th></tr></thead>
          <tbody>
            <?php foreach ($categories as $c): ?>
              <tr>
                <td class="fw-medium"><?= e($c['name']) ?></td>
                <td class="text-muted-bray"><?= e($c['slug']) ?></td>
                <td><?= (int)$c['product_count'] ?></td>
                <td><?= (int)$c['sort_order'] ?></td>
                <td><span class="chip <?= (int)$c['is_active'] === 1 ? 'chip-active' : 'chip-disabled' ?>"><?= (int)$c['is_active'] === 1 ? 'Visible' : 'Hidden' ?></span></td>
                <td class="text-end text-nowrap">
                  <button class="btn-adm-quiet" type="button" data-bs-toggle="modal" data-bs-target="#editCat-<?= (int)$c['id'] ?>">Edit</button>
                  <form class="d-inline" method="post" action="<?= e(url('admin/categories.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="category_id" value="<?= (int)$c['id'] ?>">
                    <button class="btn-adm-quiet" type="submit"><?= (int)$c['is_active'] === 1 ? 'Hide' : 'Show' ?></button>
                  </form>
                  <form class="d-inline" method="post" action="<?= e(url('admin/categories.php')) ?>" data-confirm="Delete category “<?= e($c['name']) ?>”?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="category_id" value="<?= (int)$c['id'] ?>">
                    <button class="btn-adm-danger" type="submit" <?= (int)$c['product_count'] > 0 ? 'disabled title="Move products out of this category first"' : '' ?>>Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="adm-card">
      <div class="adm-card-head"><h2>Add Category</h2></div>
      <div class="adm-card-body">
        <form method="post" action="<?= e(url('admin/categories.php')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="create">
          <div class="mb-3">
            <label class="form-label-adm" for="cName">Name *</label>
            <input class="form-control" id="cName" name="name" required placeholder="e.g. Knitwear">
          </div>
          <div class="mb-3">
            <label class="form-label-adm" for="cSlug">Slug (auto if blank)</label>
            <input class="form-control" id="cSlug" name="slug" placeholder="knitwear">
          </div>
          <div class="mb-3">
            <label class="form-label-adm" for="cDesc">Short description</label>
            <textarea class="form-control" id="cDesc" name="description" rows="2" maxlength="255"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label-adm" for="cSort">Sort order</label>
            <input type="number" class="form-control" id="cSort" name="sort_order" value="10">
          </div>
          <button type="submit" class="btn btn-adm w-100">Create Category</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php foreach ($categories as $c): ?>
  <div class="modal fade" id="editCat-<?= (int)$c['id'] ?>" tabindex="-1" aria-labelledby="editCatLabel-<?= (int)$c['id'] ?>" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content" style="border-radius:0;">
        <form method="post" action="<?= e(url('admin/categories.php')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="category_id" value="<?= (int)$c['id'] ?>">
          <div class="modal-header"><h5 class="modal-title" id="editCatLabel-<?= (int)$c['id'] ?>">Edit: <?= e($c['name']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
          <div class="modal-body">
            <div class="mb-3"><label class="form-label-adm" for="eName-<?= (int)$c['id'] ?>">Name</label><input class="form-control" id="eName-<?= (int)$c['id'] ?>" name="name" value="<?= e($c['name']) ?>" required></div>
            <div class="mb-3"><label class="form-label-adm" for="eSlug-<?= (int)$c['id'] ?>">Slug</label><input class="form-control" id="eSlug-<?= (int)$c['id'] ?>" name="slug" value="<?= e($c['slug']) ?>"></div>
            <div class="mb-3"><label class="form-label-adm" for="eDesc-<?= (int)$c['id'] ?>">Description</label><textarea class="form-control" id="eDesc-<?= (int)$c['id'] ?>" name="description" rows="2" maxlength="255"><?= e($c['description'] ?? '') ?></textarea></div>
            <div class="mb-3"><label class="form-label-adm" for="eSort-<?= (int)$c['id'] ?>">Sort order</label><input type="number" class="form-control" id="eSort-<?= (int)$c['id'] ?>" name="sort_order" value="<?= (int)$c['sort_order'] ?>"></div>
          </div>
          <div class="modal-footer"><button type="submit" class="btn btn-adm">Save Changes</button></div>
        </form>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
