<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';
require_login();

$error = '';
$today = date('Y-m-d');

// --- Dodanie uslugi ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    require_role('administrator', 'operator');
    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $type = trim($_POST['type'] ?? '');
    $start = $_POST['start_date'] ?? '';
    $expiry = $_POST['expiry_date'] ?? '';

    $stmt = $pdo->prepare('SELECT id FROM customers WHERE id = ?');
    $stmt->execute([$customer_id]);

    if (!$stmt->fetch()) {
        $error = 'Wybrany klient nie istnieje';
    } elseif ($expiry < $start) {
        $error = 'Data waznosci nie moze byc przed data startu';
    } else {
        $stmt = $pdo->prepare('INSERT INTO services (customer_id, type, start_date, expiry_date) VALUES (?, ?, ?, ?)');
        $stmt->execute([$customer_id, $type, $start, $expiry]);
        write_audit($pdo, $_SESSION['user_id'], 'create', 'service', $pdo->lastInsertId());
        redirect('services.php?ok=1');
    }
}

// --- Odnowienie uslugi ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'renew') {
    require_role('administrator', 'operator');
    $id = (int)($_POST['id'] ?? 0);
    $new_expiry = $_POST['new_expiry_date'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM services WHERE id = ?');
    $stmt->execute([$id]);
    $service = $stmt->fetch();

    if (!$service) {
        $error = 'Nie znaleziono uslugi';
    } elseif ($new_expiry <= $service['expiry_date']) {
        $error = 'Nowa data waznosci musi byc pozniejsza niz obecna';
    } else {
        // Zapisujemy odnowienie do historii i aktualizujemy date waznosci.
        $stmt = $pdo->prepare(
            'INSERT INTO renewals (service_id, old_expiry_date, new_expiry_date, renewal_date, registered_by)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$id, $service['expiry_date'], $new_expiry, $today, $_SESSION['user_id']]);

        $stmt = $pdo->prepare('UPDATE services SET expiry_date = ? WHERE id = ?');
        $stmt->execute([$new_expiry, $id]);
        write_audit($pdo, $_SESSION['user_id'], 'renew', 'service', $id);
        redirect('services.php?ok=1');
    }
}

$success = isset($_GET['ok']) ? 'Zapisano zmiany' : '';

// --- Filtr aktywne / wygasle ---
$filter = $_GET['filter'] ?? '';
$sql = 'SELECT s.*, c.name AS customer_name FROM services s JOIN customers c ON c.id = s.customer_id';
if ($filter === 'active') {
    $sql .= " WHERE s.expiry_date >= '$today'";
} elseif ($filter === 'expired') {
    $sql .= " WHERE s.expiry_date < '$today'";
}
$sql .= ' ORDER BY s.expiry_date ASC';
$list = $pdo->query($sql)->fetchAll();

$customers = $pdo->query('SELECT id, name FROM customers WHERE active = 1 ORDER BY name')->fetchAll();

$page = 'services';
require 'includes/header.php';
?>

<div class="page-head">
  <h1>Uslugi</h1>
  <?php if (can_edit()): ?>
    <a href="#" class="btn" onclick="openModal('modal'); return false;">+ Dodaj usluge</a>
  <?php endif; ?>
</div>

<?php if ($error): ?><div class="error-msg"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="ok-msg"><?= e($success) ?></div><?php endif; ?>

<form class="toolbar" method="get">
  <label style="display:inline; margin-right:6px;">Filtr:</label>
  <select name="filter" onchange="this.form.submit()" style="width:200px;">
    <option value="">wszystkie</option>
    <option value="active" <?= $filter === 'active' ? 'selected' : '' ?>>aktywne</option>
    <option value="expired" <?= $filter === 'expired' ? 'selected' : '' ?>>wygasle</option>
  </select>
</form>

<table>
  <thead>
    <tr><th>Klient</th><th>Typ</th><th>Start</th><th>Waznosc</th><th>Status</th><?php if (can_edit()): ?><th>Akcje</th><?php endif; ?></tr>
  </thead>
  <tbody>
    <?php foreach ($list as $s): ?>
      <?php $active = ($s['expiry_date'] >= $today); ?>
    <tr>
      <td><?= e($s['customer_name']) ?></td>
      <td><?= e($s['type']) ?></td>
      <td><?= e($s['start_date']) ?></td>
      <td><?= e($s['expiry_date']) ?></td>
      <td>
        <?php if ($active): ?><span class="badge badge-active">aktywna</span>
        <?php else: ?><span class="badge badge-expired">wygasla</span><?php endif; ?>
      </td>
      <?php if (can_edit()): ?>
      <td>
        <form method="post" style="display:flex; gap:6px;">
          <input type="hidden" name="action" value="renew">
          <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
          <input type="date" name="new_expiry_date" value="<?= e($s['expiry_date']) ?>" required style="width:150px;">
          <button class="btn btn-small" type="submit">Odnow</button>
        </form>
      </td>
      <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    <?php if (!$list): ?>
      <tr><td colspan="6" style="text-align:center; color:var(--muted);">Brak uslug</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<?php if (can_edit()): ?>
<div class="modal-bg" id="modal">
  <div class="modal">
    <h2>Nowa usluga</h2>
    <form method="post">
      <input type="hidden" name="action" value="add">
      <div class="form-row">
        <label>Klient</label>
        <select name="customer_id" required>
          <option value="">-- wybierz klienta --</option>
          <?php foreach ($customers as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-row"><label>Typ uslugi</label><input type="text" name="type" required></div>
      <div class="form-row"><label>Data startu</label><input type="date" name="start_date" required></div>
      <div class="form-row"><label>Data waznosci</label><input type="date" name="expiry_date" required></div>
      <div class="modal-actions">
        <a class="btn btn-secondary" href="services.php">Anuluj</a>
        <button type="submit" class="btn">Zapisz</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
