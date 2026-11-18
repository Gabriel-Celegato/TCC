<?php
// frontend/includes/header.php

// Carregar configurações globais
$config_file = __DIR__ . '/../../config.php';
if (file_exists($config_file)) {
    require_once $config_file;
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/TCC/');
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Controle de Pragas'; ?></title>
    
    <!-- CSS Principal -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>frontend/css/style.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>