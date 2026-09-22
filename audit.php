<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';
require_role('administrator'); // dziennik widzi tylko administrator

// Dziennik jest tylko do odczytu - nie ma tu zadnego dodawania,
// edycji ani usuwania wpisow. Dlatego audyt jest niezmienialny.
$list = $pdo->query(
    'SELECT a.*, u.name AS actor_name
     FROM audit_logs a
     LEFT JOIN users u ON u.id = a.actor_id
     ORDER BY a.created_at DESC
     LIMIT 200'
)->fetchAll();

$page = 'audit';
require 'includes/header.php';
?>

<h1>Dziennik audytu</h1>
<p style="color:var(--muted); margin-top:-5px;">Ostatnie 200 zdarzen. Wpisow nie mozna edytowac ani usuwac.</p>

<table>
  <thead>
    <tr><th>Data</th><th>Uzytkownik</th><th>Akcja</th><th>Obiekt</th><th>ID obiektu</th></tr>
  </thead>
  <tbody>
    <?php foreach ($list as $a): ?>
    <tr>
      <td><?= e($a['created_at']) ?></td>
      <td><?= e($a['actor_name'] ?? ('#' . $a['actor_id'])) ?></td>
      <td><?= e($a['action']) ?></td>
      <td><?= e($a['entity']) ?></td>
      <td><?= (int)$a['entity_id'] ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$list): ?>
      <tr><td colspan="5" style="text-align:center; color:var(--muted);">Dziennik jest pusty</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<?php require 'includes/footer.php'; ?>
