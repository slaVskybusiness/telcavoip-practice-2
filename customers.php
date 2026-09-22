<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';
require_login();

$error = '';
$success = '';

// --- Obsluga zapisu (dodanie lub edycja) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    require_role('administrator', 'operator');

    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $vat = trim($_POST['vat'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Sprawdzenie duplikatow: ten sam NIP, email lub telefon
    // (pomijamy samego siebie przy edycji).
    $stmt = $pdo->prepare(
        'SELECT id FROM customers
         WHERE (vat = ? OR email = ? OR phone = ?) AND id <> ?'
    );
    $stmt->execute([$vat, $email, $phone, $id]);

    if ($stmt->fetch()) {
        $error = 'Klient z takim NIP / email / telefonem juz istnieje';
    } else {
        if ($id > 0) {
            $stmt = $pdo->prepare(
                'UPDATE customers SET name=?, vat=?, email=?, phone=?, address=? WHERE id=?'
            );
            $stmt->execute([$name, $vat, $email, $phone, $address, $id]);
            write_audit($pdo, $_SESSION['user_id'], 'update', 'customer', $id);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO customers (name, vat, email, phone, address) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$name, $vat, $email, $phone, $address]);
            write_audit($pdo, $_SESSION['user_id'], 'create', 'customer', $pdo->lastInsertId());
        }
        redirect('customers.php?ok=1');
    }
}

// --- Deaktywacja klienta ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'deactivate') {
    require_role('administrator', 'operator');
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $pdo->prepare('UPDATE customers SET active = 0 WHERE id = ?');
    $stmt->execute([$id]);
    write_audit($pdo, $_SESSION['user_id'], 'deactivate', 'customer', $id);
    redirect('customers.php?ok=1');
}

if (isset($_GET['ok'])) {
    $success = 'Zapisano zmiany';
}

// --- Wyszukiwanie ---
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $like = '%' . $q . '%';
    $stmt = $pdo->prepare(
        'SELECT * FROM customers
         WHERE name LIKE ? OR email LIKE ? OR vat LIKE ? OR phone LIKE ?
         ORDER BY created_at DESC'
    );
    $stmt->execute([$like, $like, $like, $like]);
    $list = $stmt->fetchAll();
} else {
    $list = $pdo->query('SELECT * FROM customers ORDER BY created_at DESC')->fetchAll();
}

// --- Dane do edycji (jak kliknieto Edytuj) ---
$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

$page = 'customers';
require 'includes/header.php';
?>

<div class="page-head">
  <h1>Klienci</h1>
  <?php if (can_edit()): ?>
    <a href="customers.php" class="btn" onclick="openModal('modal'); return false;">+ Dodaj klienta</a>
  <?php endif; ?>
</div>

<?php if ($error): ?><div class="error-msg"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="ok-msg"><?= e($success) ?></div><?php endif; ?>

<form class="toolbar" method="get">
  <input type="text" name="q" value="<?= e($q) ?>" placeholder="Szukaj po nazwie, NIP, email, telefon" style="max-width:340px;">
  <button class="btn btn-secondary" type="submit">Szukaj</button>
</form>

<table>
  <thead>
    <tr>
      <th>Nazwa</th><th>NIP</th><th>E-mail</th><th>Telefon</th><th>Status</th>
      <?php if (can_edit()): ?><th>Akcje</th><?php endif; ?>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($list as $c): ?>
    <tr>
      <td><?= e($c['name']) ?></td>
      <td><?= e($c['vat']) ?></td>
      <td><?= e($c['email']) ?></td>
      <td><?= e($c['phone']) ?></td>
      <td>
        <?php if ($c['active']): ?><span class="badge badge-active">aktywny</span>
        <?php else: ?><span class="badge badge-closed">wylaczony</span><?php endif; ?>
      </td>
      <?php if (can_edit()): ?>
      <td>
        <a class="btn btn-small btn-secondary" href="customers.php?edit=<?= (int)$c['id'] ?>">Edytuj</a>
        <?php if ($c['active']): ?>
        <form method="post" style="display:inline;" onsubmit="return confirm('Wylaczyc tego klienta?');">
          <input type="hidden" name="action" value="deactivate">
          <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
          <button class="btn btn-small btn-danger" type="submit">Wylacz</button>
        </form>
        <?php endif; ?>
      </td>
      <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    <?php if (!$list): ?>
      <tr><td colspan="6" style="text-align:center; color:var(--muted);">Brak klientow</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<?php if (can_edit()): ?>
<!-- Okno formularza (dodawanie / edycja) -->
<div class="modal-bg <?= $edit ? 'show' : '' ?>" id="modal">
  <div class="modal">
    <h2><?= $edit ? 'Edytuj klienta' : 'Nowy klient' ?></h2>
    <form method="post">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= $edit ? (int)$edit['id'] : 0 ?>">
      <div class="form-row"><label>Nazwa</label><input type="text" name="name" value="<?= e($edit['name'] ?? '') ?>" required></div>
      <div class="form-row"><label>NIP</label><input type="text" name="vat" value="<?= e($edit['vat'] ?? '') ?>" required></div>
      <div class="form-row"><label>E-mail</label><input type="email" name="email" value="<?= e($edit['email'] ?? '') ?>" required></div>
      <div class="form-row"><label>Telefon</label><input type="text" name="phone" value="<?= e($edit['phone'] ?? '') ?>" required></div>
      <div class="form-row"><label>Adres</label><input type="text" name="address" value="<?= e($edit['address'] ?? '') ?>"></div>
      <div class="modal-actions">
        <a class="btn btn-secondary" href="customers.php">Anuluj</a>
        <button type="submit" class="btn">Zapisz</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
