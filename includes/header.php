<?php
// Wspolny naglowek z bocznym menu. Kazda strona panelu docza go na gorze.
// Zmienna $page ustawia, ktory link menu jest podswietlony.
if (!isset($page)) { $page = ''; }
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TelcaVoIP - Panel</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="sidebar-logo">Telca<span>VoIP</span></div>
    <nav>
      <a href="index.php" class="<?= $page === 'dashboard' ? 'active' : '' ?>">Pulpit</a>
      <a href="customers.php" class="<?= $page === 'customers' ? 'active' : '' ?>">Klienci</a>
      <a href="voip.php" class="<?= $page === 'voip' ? 'active' : '' ?>">Konta VoIP</a>
      <a href="services.php" class="<?= $page === 'services' ? 'active' : '' ?>">Uslugi</a>
      <a href="interventions.php" class="<?= $page === 'interventions' ? 'active' : '' ?>">Interwencje</a>
      <?php if (current_role() === 'administrator'): ?>
        <a href="users.php" class="<?= $page === 'users' ? 'active' : '' ?>">Uzytkownicy</a>
        <a href="audit.php" class="<?= $page === 'audit' ? 'active' : '' ?>">Audyt</a>
      <?php endif; ?>
    </nav>
  </aside>

  <div class="main">
    <header class="topbar">
      <div class="topbar-title">Panel zarzadzania</div>
      <div class="topbar-user">
        <span><?= e($_SESSION['name']) ?> <small>(<?= e(role_name(current_role())) ?>)</small></span>
        <a href="logout.php" class="btn btn-secondary btn-small">Wyloguj</a>
      </div>
    </header>
    <div class="content">
