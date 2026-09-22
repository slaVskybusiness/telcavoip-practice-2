<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

if (is_logged_in()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'operator';

    // Przez rejestracje mozna zalozyc tylko operatora lub technika.
    if (!in_array($role, ['operator', 'technician'], true)) {
        $role = 'operator';
    }

    if (strlen($password) < 6) {
        $error = 'Haslo musi miec min. 6 znakow';
    } else {
        // Sprawdzamy czy email nie jest juz zajety.
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Taki email juz istnieje';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                'INSERT INTO users (name, email, password_hash, role, active)
                 VALUES (?, ?, ?, ?, 1)'
            );
            $stmt->execute([$name, $email, $hash, $role]);
            redirect('login.php');
        }
    }
}
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TelcaVoIP - Rejestracja</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="login-container">
  <div class="login-box card">
    <div class="login-logo">Telca<span>VoIP</span></div>
    <h2>Rejestracja konta</h2>

    <?php if ($error): ?><div class="error-msg"><?= e($error) ?></div><?php endif; ?>

    <form method="post">
      <label>Imie i nazwisko</label>
      <input type="text" name="name" required>
      <label style="margin-top:12px;">E-mail</label>
      <input type="email" name="email" required>
      <label style="margin-top:12px;">Haslo (min. 6 znakow)</label>
      <input type="password" name="password" minlength="6" required>
      <label style="margin-top:12px;">Rola</label>
      <select name="role">
        <option value="operator">Operator</option>
        <option value="technician">Technik</option>
      </select>
      <button type="submit" class="btn" style="width:100%; margin-top:15px;">Zarejestruj sie</button>
    </form>

    <p class="login-hint">Masz juz konto? <a href="login.php">Zaloguj sie</a></p>
  </div>
</div>
</body>
</html>
