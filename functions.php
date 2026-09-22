<?php
// Pomocnicze funkcje uzywane w calym projekcie.

// Skrot do bezpiecznego wypisania tekstu w HTML (ochrona przed XSS).
function e($text)
{
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

// Przekierowanie na inna strone.
function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

// Dopisanie wpisu do dziennika audytu.
// Wpisy tylko dodajemy - nigdzie w kodzie nie ma edycji ani usuwania audytu.
function write_audit($pdo, $actor_id, $action, $entity, $entity_id)
{
    $stmt = $pdo->prepare(
        'INSERT INTO audit_logs (actor_id, action, entity, entity_id)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$actor_id, $action, $entity, $entity_id]);
}

// Polskie nazwy rol do wyswietlania.
function role_name($role)
{
    $names = [
        'administrator' => 'Administrator',
        'operator' => 'Operator',
        'technician' => 'Technik',
    ];
    return $names[$role] ?? $role;
}
