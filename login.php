<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';

// Jak juz zalogowany - od razu do panelu.
if (is_logged_in()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $error = 'Zly email lub haslo';
    } elseif (!$user['active']) {
        $error = 'Konto jest nieaktywne';
    } else {
        // Zapamietujemy dane w sesji.
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        redirect('index.php');
    }
}
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TelcaVoIP - Logowanie</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="login-container">
  <div class="login-box card">
    <div class="login-logo">Telca<span>VoIP</span></div>
    <h2>Logowanie do panelu</h2>

    <?php if ($error): ?><div class="error-msg"><?= e($error) ?></div><?php endif; ?>

    <form method="post">
      <label>E-mail</label>
      <input type="email" name="email" required>
      <label style="margin-top:12px;">Haslo</label>
      <input type="password" name="password" required>
      <button type="submit" class="btn" style="width:100%; margin-top:15px;">Zaloguj sie</button>
    </form>

    <p class="login-hint">Nie masz konta? <a href="register.php">Zarejestruj sie</a></p>
  </div>
</div>
</body>
</html>
