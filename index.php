<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';
require_login();

$today = date('Y-m-d');
$soon = date('Y-m-d', strtotime('+30 days'));

// Liczby na pulpit.
$customers = $pdo->query('SELECT COUNT(*) FROM customers WHERE active = 1')->fetchColumn();
$active = $pdo->query("SELECT COUNT(*) FROM services WHERE expiry_date >= '$today'")->fetchColumn();
$expired = $pdo->query("SELECT COUNT(*) FROM services WHERE expiry_date < '$today'")->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM services WHERE expiry_date >= ? AND expiry_date <= ?');
$stmt->execute([$today, $soon]);
$expiring = $stmt->fetchColumn();

$open = $pdo->query("SELECT COUNT(*) FROM interventions WHERE status = 'open'")->fetchColumn();

$page = 'dashboard';
require 'includes/header.php';
?>

<h1>Pulpit</h1>

<div class="tiles">
  <div class="card tile" style="border-top-color:#0a1a4a;">
    <div class="tile-value"><?= (int)$customers ?></div>
    <div class="tile-label">Klienci</div>
  </div>
  <div class="card tile" style="border-top-color:#8cc63f;">
    <div class="tile-value"><?= (int)$active ?></div>
    <div class="tile-label">Aktywne uslugi</div>
  </div>
  <div class="card tile" style="border-top-color:#e0a800;">
    <div class="tile-value"><?= (int)$expiring ?></div>
    <div class="tile-label">Wygasajace (30 dni)</div>
  </div>
  <div class="card tile" style="border-top-color:#d64545;">
    <div class="tile-value"><?= (int)$expired ?></div>
    <div class="tile-label">Uslugi wygasle</div>
  </div>
  <div class="card tile" style="border-top-color:#1b2f66;">
    <div class="tile-value"><?= (int)$open ?></div>
    <div class="tile-label">Otwarte interwencje</div>
  </div>
</div>

<?php require 'includes/footer.php'; ?>
