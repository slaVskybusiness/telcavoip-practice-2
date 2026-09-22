<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';
require_login();

$error = '';

// --- Dodanie interwencji (moga wszyscy zalogowani) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    require_login();
    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $type = trim($_POST['type'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    $stmt = $pdo->prepare('SELECT id FROM customers WHERE id = ?');
    $stmt->execute([$customer_id]);
    if (!$stmt->fetch()) {
        $error = 'Wybrany klient nie istnieje';
    } else {
        $stmt = $pdo->prepare('INSERT INTO interventions (customer_id, type, notes) VALUES (?, ?, ?)');
        $stmt->execute([$customer_id, $type, $notes]);
        write_audit($pdo, $_SESSION['user_id'], 'create', 'intervention', $pdo->lastInsertId());
        redirect('interventions.php?ok=1');
    }
}

// --- Zamkniecie interwencji ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'close') {
    require_login();
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE interventions SET status = 'closed', closed_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
    write_audit($pdo, $_SESSION['user_id'], 'close', 'intervention', $id);
    redirect('interventions.php?ok=1');
}

// --- Wgranie zalacznika ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload') {
    require_login();
    $id = (int)($_POST['id'] ?? 0);
    $file = $_FILES['file'] ?? null;

    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Blad przy wgrywaniu pliku';
    } elseif (!in_array($file['type'], $ALLOWED_FILE_TYPES, true)) {
        // Sprawdzamy typ pliku - tylko PDF/PNG/JPG.
        $error = 'Niedozwolony typ pliku (dozwolone: PDF, PNG, JPG)';
    } elseif ($file['size'] > MAX_FILE_SIZE) {
        // Za duzy plik odrzucamy i nie zapisujemy.
        $error = 'Plik jest za duzy (max 5 MB)';
    } else {
        $stored = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file['name']);
        move_uploaded_file($file['tmp_name'], __DIR__ . '/uploads/' . $stored);
        $stmt = $pdo->prepare(
            'INSERT INTO attachments (intervention_id, filename, stored_name, mime_type, size, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$id, $file['name'], $stored, $file['type'], $file['size'], $_SESSION['user_id']]);
        redirect('interventions.php?ok=1');
    }
}

$success = isset($_GET['ok']) ? 'Zapisano zmiany' : '';

// --- Filtr open / closed ---
$filter = $_GET['filter'] ?? '';
$sql = 'SELECT i.*, c.name AS customer_name,
        (SELECT COUNT(*) FROM attachments a WHERE a.intervention_id = i.id) AS files
        FROM interventions i JOIN customers c ON c.id = i.customer_id';
if (in_array($filter, ['open', 'closed'], true)) {
    $sql .= " WHERE i.status = '$filter'";
}
$sql .= ' ORDER BY i.opened_at DESC';
$list = $pdo->query($sql)->fetchAll();

$customers = $pdo->query('SELECT id, name FROM customers WHERE active = 1 ORDER BY name')->fetchAll();

$page = 'interventions';
require 'includes/header.php';
?>

<div class="page-head">
  <h1>Interwencje techniczne</h1>
  <a href="#" class="btn" onclick="openModal('modal'); return false;">+ Nowa interwencja</a>
</div>

<?php if ($error): ?><div class="error-msg"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="ok-msg"><?= e($success) ?></div><?php endif; ?>

<form class="toolbar" method="get">
  <label style="display:inline; margin-right:6px;">Filtr:</label>
  <select name="filter" onchange="this.form.submit()" style="width:200px;">
    <option value="">wszystkie</option>
    <option value="open" <?= $filter === 'open' ? 'selected' : '' ?>>otwarte</option>
    <option value="closed" <?= $filter === 'closed' ? 'selected' : '' ?>>zamkniete</option>
  </select>
</form>

<table>
  <thead>
    <tr><th>Klient</th><th>Typ</th><th>Notatki</th><th>Pliki</th><th>Status</th><th>Akcje</th></tr>
  </thead>
  <tbody>
    <?php foreach ($list as $i): ?>
    <tr>
      <td><?= e($i['customer_name']) ?></td>
      <td><?= e($i['type']) ?></td>
      <td><?= e($i['notes']) ?></td>
      <td><?= (int)$i['files'] ?></td>
      <td>
        <?php if ($i['status'] === 'open'): ?><span class="badge badge-open">otwarta</span>
        <?php else: ?><span class="badge badge-closed">zamknieta</span><?php endif; ?>
      </td>
      <td>
        <?php if ($i['status'] === 'open'): ?>
        <form method="post" style="display:inline;">
          <input type="hidden" name="action" value="close">
          <input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
          <button class="btn btn-small" type="submit">Zamknij</button>
        </form>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data" style="display:inline;">
          <input type="hidden" name="action" value="upload">
          <input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
          <label class="btn btn-small btn-secondary">
            Plik
            <input type="file" name="file" style="display:none;" onchange="this.form.submit()">
          </label>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$list): ?>
      <tr><td colspan="6" style="text-align:center; color:var(--muted);">Brak interwencji</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<div class="modal-bg" id="modal">
  <div class="modal">
    <h2>Nowa interwencja</h2>
    <form method="post">
      <input type="hidden" name="action" value="add">
      <div class="form-row">
        <label>Klient</label>
        <select name="customer_id" required>
          <option value="">-- wybierz klienta --</option>
          <?php foreach ($customers as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-row"><label>Typ</label><input type="text" name="type" required></div>
      <div class="form-row"><label>Notatki</label><textarea name="notes" rows="3"></textarea></div>
      <div class="modal-actions">
        <a class="btn btn-secondary" href="interventions.php">Anuluj</a>
        <button type="submit" class="btn">Zapisz</button>
      </div>
    </form>
  </div>
</div>

<?php require 'includes/footer.php'; ?>
