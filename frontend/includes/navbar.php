<?php
// ============================================================================
// ARQUIVO: navbar.php
// ============================================================================

// Carregar configurações globais
$config_file = __DIR__ . '/../../config.php';
if (file_exists($config_file)) {
    require_once $config_file;
} else {
    // Fallback se o config não existir
    define('BASE_URL', '/TCC/');
    define('BACKEND_PATH', __DIR__ . '/../../backend/');
    define('FRONTEND_PATH', __DIR__ . '/../');
    define('ADMIN_URL', BASE_URL . 'frontend/admin/');
}

// Iniciar sessão apenas se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================================
// VERIFICAÇÕES BÁSICAS
// ============================================================================
$loggedIn = isset($_SESSION['usuario_id']);
$userName = $_SESSION['usuario_nome'] ?? 'Visitante';

// ============================================================================
// VERIFICAÇÃO DE ADMIN
// ============================================================================
$isAdmin = false;

if($loggedIn) {
    try {
        // Usar caminho absoluto para o database.php
        $db_file = __DIR__ . '/../../backend/config/database.php';
        if (file_exists($db_file)) {
            require_once $db_file;
            
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "SELECT tipo FROM usuarios WHERE id = :id AND tipo = 'admin'";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_SESSION['usuario_id']);
            $stmt->execute();
            
            $isAdmin = $stmt->rowCount() > 0;
        }
    } catch(Exception $e) {
        $isAdmin = false;
        error_log("Erro ao verificar admin: " . $e->getMessage());
    }
}

// ============================================================================
// DETECTAR PÁGINA ATUAL
// ============================================================================
$current_page = basename($_SERVER['PHP_SELF']);
$is_admin_page = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
?>

<nav class="navbar">
    <div class="nav-container">
        
        <!-- LOGO -->
        <div class="logo">
            <a href="<?php echo BASE_URL . 'frontend/index.php'; ?>">
                <span class="logo-mark" aria-hidden="true"></span>
                <span class="logo-text">
                    <span class="logo-title">Controle de Pragas</span>
                    <span class="logo-subtitle">Gestão &amp; Denúncia de Pragas</span>
                </span>
            </a>
        </div>
        
        <!-- MENU DE NAVEGAÇÃO -->
        <div class="nav-links">
            
            <!-- HOME -->
            <a href="<?php echo BASE_URL . 'frontend/index.php'; ?>" 
               class="<?php echo $current_page == 'index.php' && !$is_admin_page ? 'active' : ''; ?>">
                Início
            </a>
            
            <?php if($loggedIn): ?>
                
                <!-- DASHBOARD USUÁRIO -->
                <a href="<?php echo BASE_URL . 'frontend/dashboard.php'; ?>" 
                   class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    Dashboard
                </a>
                
                <!-- NOVA DENÚNCIA -->
                <a href="<?php echo BASE_URL . 'frontend/nova_denuncia.php'; ?>" 
                   class="<?php echo $current_page == 'nova_denuncia.php' ? 'active' : ''; ?>">
                    Nova denúncia
                </a>
                
                <!-- MINHAS DENÚNCIAS -->
                <a href="<?php echo BASE_URL . 'frontend/minhas_denuncias.php'; ?>" 
                   class="<?php echo $current_page == 'minhas_denuncias.php' ? 'active' : ''; ?>">
                    Minhas denúncias
                </a>
                
                <!-- PAINEL ADMIN (apenas para admin) -->
                <?php if($isAdmin): ?>
                    <a href="<?php echo BASE_URL . 'frontend/admin/dashboard.php'; ?>" 
                       class="<?php echo $is_admin_page ? 'active' : ''; ?>">
                        Painel admin
                    </a>
                <?php endif; ?>
                
                <!-- LOGOUT -->
                <a href="<?php echo BASE_URL . 'backend/auth/logout.php'; ?>" class="btn-logout">
                    Sair
                </a>
                
                <!-- SAUDAÇÃO -->
                <span class="user-greeting">
                    Olá, <?php echo htmlspecialchars($userName); ?>
                </span>
                
            <?php else: ?>
                
                <!-- LOGIN -->
                <a href="<?php echo BASE_URL . 'frontend/login.php'; ?>" 
                   class="<?php echo $current_page == 'login.php' ? 'active' : ''; ?>">
                    Entrar
                </a>
                
                <!-- CADASTRO -->
                <a href="<?php echo BASE_URL . 'frontend/register.php'; ?>" class="btn-nav-cadastro">
                    Cadastrar
                </a>
                
            <?php endif; ?>
        </div>
        
        <!-- MENU MOBILE -->
        <div class="mobile-menu">
            <i class="fas fa-bars"></i>
        </div>
        
    </div>
</nav>

<!-- O toggle do menu mobile e os estilos da navbar agora moram só em main.js e style.css -->