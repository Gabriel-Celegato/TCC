<?php
// ============================================================================
// ARQUIVO: config.php
// DESCRIÇÃO: Configurações globais do sistema
// ============================================================================

// Carregar variáveis de ambiente
$env_file = __DIR__ . '/.env';
if(file_exists($env_file)) {
    require_once __DIR__ . '/backend/config/Env.php';
    try {
        Env::load();
    } catch(Exception $e) {
        // Fallback para valores padrão
    }
} else {
    // Valores padrão caso .env não exista
    define('SITE_NAME', 'Controle de Pragas');
    define('ADMIN_EMAIL', 'admin@sistema.com');
    define('APP_ENV', 'development');
    define('DEBUG_MODE', true);
}

// ============================================================================
// CONFIGURAÇÕES DE URL
// ============================================================================

// Detecta o protocolo (http ou https)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];

// URL base do site
define('BASE_URL', $protocol . $host . '/TCC/');

// Caminho físico absoluto no servidor
define('BASE_PATH', __DIR__ . '/');

// Caminhos físicos
define('BACKEND_PATH', BASE_PATH . 'backend/');
define('FRONTEND_PATH', BASE_PATH . 'frontend/');

// URLs
define('FRONTEND_URL', BASE_URL . 'frontend/');
define('BACKEND_URL', BASE_URL . 'backend/');
define('ADMIN_URL', FRONTEND_URL . 'admin/');
define('ASSETS_URL', FRONTEND_URL . 'assets/');

// ============================================================================
// CONFIGURAÇÕES DO SISTEMA
// ============================================================================

if(!defined('SITE_NAME')) {
    define('SITE_NAME', Env::get('SITE_NAME', 'Controle de Pragas'));
}
if(!defined('ADMIN_EMAIL')) {
    define('ADMIN_EMAIL', Env::get('ADMIN_EMAIL', 'admin@sistema.com'));
}
if(!defined('APP_ENV')) {
    define('APP_ENV', Env::get('APP_ENV', 'development'));
}
if(!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', Env::get('DEBUG_MODE', 'true') === 'true');
}

// Timezone
date_default_timezone_set(Env::get('TIMEZONE', 'America/Sao_Paulo'));

// Configurações de erro baseadas no ambiente
if(DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ============================================================================
// FUNÇÕES AUXILIARES
// ============================================================================

function url($path = '') {
    return FRONTEND_URL . ltrim($path, '/');
}

function back_url($path = '') {
    return BACKEND_URL . ltrim($path, '/');
}

function admin_url($path = '') {
    return ADMIN_URL . ltrim($path, '/');
}

function asset($path = '') {
    return ASSETS_URL . ltrim($path, '/');
}

function isActive($page, $exact = false) {
    $current = basename($_SERVER['PHP_SELF']);
    if($exact) {
        return $current === $page;
    }
    return strpos($_SERVER['PHP_SELF'], $page) !== false;
}

function redirect($url) {
    header("Location: " . url($url));
    exit();
}

function dd($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}
?>