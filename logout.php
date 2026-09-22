<?php
require_once 'auth.php';
// Kasujemy sesje i wracamy na logowanie.
session_destroy();
header('Location: login.php');
exit;
