<?php
$pageTitle = "Login - Controle de Pragas";
include 'includes/header.php';
include 'includes/navbar.php';
require_once '../backend/auth/csrf.php'; 

if(isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>

<main class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <i class="fas fa-spider auth-icon"></i>
            <h2>Login</h2>
            <p>Sistema de Controle de Pragas</p>
        </div>
        
        <form id="loginForm" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email</label>
                <input type="email" id="email" name="email" placeholder="seu@email.com" autocomplete="email" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Senha</label>
                <div class="password-input">
                    <input type="password" id="senha" name="senha" placeholder="Sua senha" autocomplete="current-password" required>
                    <i class="fas fa-eye toggle-password" tabindex="0" role="button" aria-label="Mostrar senha"></i>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-sign-in-alt"></i> Entrar
            </button>
        </form>
        
        <div class="auth-footer">
            <p>Não tem cadastro? <a href="register.php">Cadastre-se</a></p>
        </div>
    </div>
</main>

<script>
// Suporte a teclado pro toggle de senha (o clique já funciona via main.js,
// isso só cobre quem navega com Tab + Enter/Espaço)
const toggleSenha = document.querySelector('.toggle-password');
if (toggleSenha) {
    toggleSenha.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            this.click();
        }
    });
}

document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    
    submitBtn.classList.add('btn-loading');
    submitBtn.disabled = true;
    
    try {
        const response = await fetch('../backend/auth/login_process.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if(data.success) {
            mostrarNotificacao(data.message, 'success');
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 1500);
        } else {
            mostrarNotificacao(data.message, 'error');
        }
    } catch(error) {
        mostrarNotificacao('Erro ao conectar ao servidor', 'error');
    } finally {
        submitBtn.classList.remove('btn-loading');
        submitBtn.disabled = false;
    }
});

function mostrarNotificacao(mensagem, tipo) {
    const container = document.getElementById('notificacao-container') || criarContainer();
    
    const notificacao = document.createElement('div');
    notificacao.className = `notificacao ${tipo}`;
    notificacao.innerHTML = `
        <i class="fas ${tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
        <span>${mensagem}</span>
    `;
    
    container.appendChild(notificacao);
    
    setTimeout(() => {
        notificacao.classList.add('fade-out');
        setTimeout(() => notificacao.remove(), 300);
    }, 3000);
}

function criarContainer() {
    const container = document.createElement('div');
    container.id = 'notificacao-container';
    container.className = 'notificacao-container';
    document.body.appendChild(container);
    return container;
}
</script>

<?php include 'includes/footer.php'; ?>