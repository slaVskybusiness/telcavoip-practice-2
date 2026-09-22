<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';
require_role('administrator'); // tylko administrator

$error = '';
$roles = ['administrator' => 'Administrator', 'operator' => 'Operator', 'technician' => 'Technik'];

// --- Utworzenie uzytkownika ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'operator';

    if (!isset($roles[$role])) {
        $error = 'Nieznana rola';
    } elseif (strlen($password) < 6) {
        $error = 'Haslo musi miec min. 6 znakow';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Taki email juz istnieje';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, active) VALUES (?, ?, ?, ?, 1)');
            $stmt->execute([$name, $email, $hash, $role]);
            write_audit($pdo, $_SESSION['user_id'], 'create', 'user', $pdo->lastInsertId());
            redirect('users.php?ok=1');
        }
    }
}

// --- Deaktywacja uzytkownika ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'deactivate') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id === (int)$_SESSION['user_id']) {
        $error = 'Nie mozna wylaczyc wlasnego konta';
    } else {
        $stmt = $pdo->prepare('UPDATE users SET active = 0 WHERE id = ?');
        $stmt->execute([$id]);
        write_audit($pdo, $_SESSION['user_id'], 'deactivate', 'user', $id);
        redirect('users.php?ok=1');
    }
}

$success = isset($_GET['ok']) ? 'Zapisano zmiany' : '';
$list = $pdo->query('SELECT * FROM users ORDER BY id')->fetchAll();

$page = 'users';
require 'includes/header.php';
?>

<div class="page-head">
  <h1>Uzytkownicy</h1>
  <a href="#" class="btn" onclick="openModal('modal'); return false;">+ Dodaj uzytkownika</a>
</div>

<?php if ($error): ?><div class="error-msg"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="ok-msg"><?= e($success) ?></div><?php endif; ?>

<table>
  <thead>
    <tr><th>Imie i nazwisko</th><th>E-mail</th><th>Rola</th><th>Status</th><th>Akcje</th></tr>
  </thead>
  <tbody>
    <?php foreach ($list as $u): ?>
    <tr>
      <td><?= e($u['name']) ?></td>
      <td><?= e($u['email']) ?></td>
      <td><?= e($roles[$u['role']] ?? $u['role']) ?></td>
      <td>
        <?php if ($u['active']): ?><span class="badge badge-active">aktywny</span>
        <?php else: ?><span class="badge badge-closed">wylaczony</span><?php endif; ?>
      </td>
      <td>
        <?php if ($u['active'] && (int)$u['id'] !== (int)$_SESSION['user_id']): ?>
        <form method="post" style="display:inline;" onsubmit="return confirm('Wylaczyc tego uzytkownika?');">
          <input type="hidden" name="action" value="deactivate">
          <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
          <button class="btn btn-small btn-danger" type="submit">Wylacz</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<div class="modal-bg" id="modal">
  <div class="modal">
    <h2>Nowy uzytkownik</h2>
    <form method="post">
      <input type="hidden" name="action" value="add">
      <div class="form-row"><label>Imie i nazwisko</label><input type="text" name="name" required></div>
      <div class="form-row"><label>E-mail</label><input type="email" name="email" required></div>
      <div class="form-row"><label>Haslo (min. 6 znakow)</label><input type="password" name="password" minlength="6" required></div>
      <div class="form-row">
        <label>Rola</label>
        <select name="role">
          <?php foreach ($roles as $key => $label): ?><option value="<?= $key ?>"><?= $label ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="modal-actions">
        <a class="btn btn-secondary" href="users.php">Anuluj</a>
        <button type="submit" class="btn">Zapisz</button>
      </div>
    </form>
  </div>
</div>

<?php require 'includes/footer.php'; ?>
