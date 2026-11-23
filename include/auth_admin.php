<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/connection.php';

if (!isset($_SESSION['idCliente'])) {
    header('Location: ' . BASE_URL . 'pages/login.php');
    exit;
}
