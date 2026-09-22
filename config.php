<?php
// Ustawienia polaczenia z baza danych MySQL.
// Pod OSPanel / XAMPP domyslnie root bez hasla.
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'telcavoip');
define('DB_USER', 'root');
define('DB_PASS', '');

// Dozwolone typy i maksymalny rozmiar zalacznikow
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB
$ALLOWED_FILE_TYPES = ['application/pdf', 'image/png', 'image/jpeg'];
