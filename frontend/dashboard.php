<?php
session_start();
$pageTitle = "Dashboard - DevSystem";

// Verificar se usuário está logado
if(!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/header.php';
include 'includes/navbar.php';
require_once '../backend/auth/csrf.php'; 

// Conectar ao banco para buscar dados completos do usuário
require_once '../backend/config/database.php';
$database = new Database();
$db = $database->getConnection();

// Buscar dados do usuário logado
$query = "SELECT * FROM usuarios WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_SESSION['usuario_id']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Buscar endereços adicionais do usuário
$query_enderecos = "SELECT * FROM enderecos WHERE usuario_id = :id ORDER BY principal DESC, id";
$stmt_enderecos = $db->prepare($query_enderecos);
$stmt_enderecos->bindParam(':id', $_SESSION['usuario_id']);
$stmt_enderecos->execute();
$enderecos_adicionais = $stmt_enderecos->fetchAll(PDO::FETCH_ASSOC);

// Buscar últimos logs de login
$query_logs = "SELECT * FROM login_logs WHERE usuario_id = :id ORDER BY data_login DESC LIMIT 5";
$stmt_logs = $db->prepare($query_logs);
$stmt_logs->bindParam(':id', $_SESSION['usuario_id']);
$stmt_logs->execute();
$logs = $stmt_logs->fetchAll(PDO::FETCH_ASSOC);

// Funções auxiliares
function formatarTelefone($telefone) {
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    if(strlen($telefone) == 11) {
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
    }
    return $telefone;
}

function calcularIdade($data_nascimento) {
    $data = new DateTime($data_nascimento);
    $hoje = new DateTime();
    return $hoje->diff($data)->y;
}
?>

<main class="dashboard-container">
    <!-- HEADER DO DASHBOARD -->
    <div class="dashboard-header">
        <div class="header-content">
            <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
            <p>Bem-vindo de volta, <span class="highlight"><?php echo htmlspecialchars($user['nome']); ?></span>!</p>
        </div>
        <div class="header-actions">
            <a href="#editar-perfil" class="btn-edit-profile" onclick="openEditModal()">
                <i class="fas fa-edit"></i> Editar Perfil
            </a>
        </div>
    </div>

    <!-- STATS CARDS -->
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="stat-info">
                <h3>Membro desde</h3>
                <p><?php echo date('d/m/Y', strtotime($user['data_criacao'])); ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-info">
                <h3>Idade</h3>
                <p><?php echo calcularIdade($user['data_nascimento']); ?> anos</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-map-marker-alt"></i>
            </div>
            <div class="stat-info">
                <h3>Cidade</h3>
                <p><?php echo htmlspecialchars($user['cidade']); ?>/<?php echo $user['uf']; ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div class="stat-info">
                <h3>Status</h3>
                <p><span class="badge badge-<?php echo $user['status']; ?>"><?php echo ucfirst($user['status']); ?></span></p>
            </div>
        </div>
    </div>

    <!-- CONTEÚDO PRINCIPAL -->
    <div class="dashboard-content">
        <!-- COLUNA ESQUERDA - DADOS PESSOAIS -->
        <div class="content-card">
            <div class="card-header">
                <h2><i class="fas fa-user"></i> Dados Pessoais</h2>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-envelope"></i> Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-phone"></i> Telefone:</span>
                    <span class="info-value"><?php echo formatarTelefone($user['telefone']); ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-calendar"></i> Nascimento:</span>
                    <span class="info-value"><?php echo date('d/m/Y', strtotime($user['data_nascimento'])); ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-venus-mars"></i> Gênero:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['genero']); ?></span>
                </div>
            </div>
        </div>

        <!-- COLUNA DIREITA - ENDEREÇO PRINCIPAL -->
        <div class="content-card">
            <div class="card-header">
                <h2><i class="fas fa-map-marker-alt"></i> Endereço Principal</h2>
                <button class="btn-add-address" onclick="openAddAddressModal()">
                    <i class="fas fa-plus"></i> Novo Endereço
                </button>
            </div>
            
            <div class="address-card">
                <div class="address-header">
                    <span class="address-type">
                        <i class="fas fa-home"></i> Residencial
                    </span>
                    <span class="badge badge-primary">Principal</span>
                </div>
                
                <div class="address-body">
                    <p><strong><?php echo htmlspecialchars($user['logradouro']); ?>, <?php echo $user['numero']; ?></strong></p>
                    <?php if(!empty($user['complemento'])): ?>
                        <p><?php echo htmlspecialchars($user['complemento']); ?></p>
                    <?php endif; ?>
                    <p><?php echo htmlspecialchars($user['bairro']); ?></p>
                    <p><?php echo htmlspecialchars($user['cidade']); ?> - <?php echo $user['uf']; ?></p>
                    <p><strong>CEP:</strong> <?php echo $user['cep']; ?></p>
                </div>
            </div>

            <!-- ENDEREÇOS ADICIONAIS -->
            <?php if(count($enderecos_adicionais) > 0): ?>
                <h3 class="subtitle"><i class="fas fa-map-marker-alt"></i> Endereços Adicionais</h3>
                <?php foreach($enderecos_adicionais as $endereco): ?>
                    <div class="address-card additional">
                        <div class="address-header">
                            <span class="address-type">
                                <i class="fas fa-<?php echo $endereco['tipo'] == 'residencial' ? 'home' : 'briefcase'; ?>"></i> 
                                <?php echo ucfirst($endereco['tipo']); ?>
                            </span>
                            <div class="address-actions">
                                <button class="btn-icon" onclick="editAddress(<?php echo $endereco['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-icon" onclick="deleteAddress(<?php echo $endereco['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="address-body">
                            <p><strong><?php echo htmlspecialchars($endereco['logradouro']); ?>, <?php echo $endereco['numero']; ?></strong></p>
                            <?php if(!empty($endereco['complemento'])): ?>
                                <p><?php echo htmlspecialchars($endereco['complemento']); ?></p>
                            <?php endif; ?>
                            <p><?php echo htmlspecialchars($endereco['bairro']); ?></p>
                            <p><?php echo htmlspecialchars($endereco['cidade']); ?> - <?php echo $endereco['uf']; ?></p>
                            <p><strong>CEP:</strong> <?php echo $endereco['cep']; ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    
    </div>
</main>

<!-- MODAL DE EDIÇÃO DE PERFIL -->
<div id="editProfileModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-edit"></i> Editar Perfil</h2>
            <span class="close-modal" onclick="closeEditModal()">&times;</span>
        </div>
        
        <form id="editProfileForm" action="../backend/auth/update_profile.php" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
    <div class="modal-body">
                <!-- Dados Pessoais -->
                <h3>Dados Pessoais</h3>
                <div class="form-group">
                    <label for="edit_nome">Nome Completo</label>
                    <input type="text" id="edit_nome" name="nome" value="<?php echo htmlspecialchars($user['nome']); ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_telefone">Telefone</label>
                        <input type="text" id="edit_telefone" name="telefone" value="<?php echo htmlspecialchars($user['telefone']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_data_nascimento">Data Nascimento</label>
                        <input type="date" id="edit_data_nascimento" name="data_nascimento" value="<?php echo $user['data_nascimento']; ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_genero">Gênero</label>
                    <select id="edit_genero" name="genero">
                        <option value="M" <?php echo $user['genero'] == 'M' ? 'selected' : ''; ?>>Masculino</option>
                        <option value="F" <?php echo $user['genero'] == 'F' ? 'selected' : ''; ?>>Feminino</option>
                        <option value="Outro" <?php echo $user['genero'] == 'Outro' ? 'selected' : ''; ?>>Outro</option>
                        <option value="Prefiro não informar" <?php echo $user['genero'] == 'Prefiro não informar' ? 'selected' : ''; ?>>Prefiro não informar</option>
                    </select>
                </div>
                
                <h3>Alterar Senha (opcional)</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_senha_atual">Senha Atual</label>
                        <input type="password" id="edit_senha_atual" name="senha_atual">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_nova_senha">Nova Senha</label>
                        <input type="password" id="edit_nova_senha" name="nova_senha">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_confirmar_senha">Confirmar Senha</label>
                        <input type="password" id="edit_confirmar_senha" name="confirmar_senha">
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DE NOVO ENDEREÇO -->
<div id="addAddressModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-plus-circle"></i> Novo Endereço</h2>
            <span class="close-modal" onclick="closeAddAddressModal()">&times;</span>
        </div>
        
        <form id="addAddressForm" action="../backend/auth/add_address.php" method="POST" >
        <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">

            <div class="modal-body">
                <div class="form-group">
                    <label for="address_cep">CEP</label>
                    <div class="cep-input-group">
                        <input type="text" id="address_cep" name="cep" placeholder="00000-000" required maxlength="9">
                        <button type="button" class="btn-cep-small" onclick="buscarCepAddress()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address_logradouro">Logradouro</label>
                    <input type="text" id="address_logradouro" name="logradouro" required readonly>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="address_numero">Número</label>
                        <input type="text" id="address_numero" name="numero" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address_complemento">Complemento</label>
                        <input type="text" id="address_complemento" name="complemento">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address_bairro">Bairro</label>
                    <input type="text" id="address_bairro" name="bairro" required readonly>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="address_cidade">Cidade</label>
                        <input type="text" id="address_cidade" name="cidade" required readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="address_uf">UF</label>
                        <input type="text" id="address_uf" name="uf" required readonly maxlength="2">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address_tipo">Tipo de Endereço</label>
                    <select id="address_tipo" name="tipo" required>
                        <option value="residencial">Residencial</option>
                        <option value="comercial">Comercial</option>
                        <option value="entrega">Entrega</option>
                        <option value="cobrança">Cobrança</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="principal" value="1"> Definir como endereço principal
                    </label>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddAddressModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Adicionar Endereço</button>
            </div>
        </form>
    </div>
</div>

<!-- CSS ADICIONAL PARA O DASHBOARD -->
<style>
.dashboard-container {
    padding: 2rem;
    background: var(--light-color);
    min-height: 100vh;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    color: var(--dark-color);
}

.header-content h1 {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.header-content p {
    font-size: 1.1rem;
    opacity: 0.9;
}

.btn-edit-profile {
    background: var(--primary-color);
    color: white;
    padding: 0.8rem 1.5rem;
    border-radius: var(--radius-sm);
    text-decoration: none;
    font-family: var(--font-heading);
    font-weight: 400;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.btn-edit-profile:hover {
    background: var(--secondary-color);
    transform: translateY(-2px);
    border: var(--border-strong);
}

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--surface-color);
    border-radius: var(--radius-sm);
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    color: var(--dark-color);
    border: var(--border-strong);
    transition: all 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
}

.stat-icon i {
    font-size: 2.5rem;
    color: var(--primary-color);
}

.stat-info h3 {
    font-size: 0.9rem;
    opacity: 0.65;
    margin-bottom: 0.3rem;
}

.stat-info p {
    font-size: 1.2rem;
    font-weight: 600;
}

.dashboard-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
}

.content-card {
    background: white;
    border-radius: var(--radius-sm);
    padding: 1.5rem;
    border: var(--border-strong);
}

.content-card.full-width {
    grid-column: 1 / -1;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(46, 43, 37, 0.1);
}

.card-header h2 {
    font-size: 1.3rem;
    font-family: var(--font-heading);
    font-weight: 400;
    color: var(--dark-color);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-add-address {
    background: var(--primary-color);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: var(--radius-sm);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.btn-add-address:hover {
    background: var(--secondary-color);
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}

.info-label {
    font-size: 0.9rem;
    color: #666;
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.info-value {
    font-size: 1.1rem;
    font-weight: 500;
    color: var(--dark-color);
}

.address-card {
    background: var(--light-color);
    border-radius: var(--radius-sm);
    padding: 1rem;
    margin-bottom: 1rem;
    border-left: 4px solid var(--primary-color);
}

.address-card.additional {
    border-left-color: var(--secondary-color);
    opacity: 0.9;
}

.address-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.8rem;
}

.address-type {
    font-weight: 600;
    color: var(--dark-color);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.address-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-icon {
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    padding: 0.3rem;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.btn-icon:hover {
    background: var(--surface-color);
    color: var(--primary-color);
}

.address-body p {
    margin: 0.3rem 0;
    color: #555;
}

.badge {
    padding: 0.3rem 0.9rem;
    border-radius: var(--radius-pill);
    font-size: 0.8rem;
    font-weight: 600;
}

.badge-primary {
    background: var(--primary-color);
    color: white;
}

.badge-success {
    background: var(--success-color);
    color: white;
}

.badge-error {
    background: var(--error-color);
    color: white;
}

.badge-ativo {
    background: var(--success-color);
    color: white;
}

.badge-inativo {
    background: #999;
    color: white;
}

.badge-bloqueado {
    background: var(--error-color);
    color: white;
}

.subtitle {
    margin: 1.5rem 0 1rem;
    font-size: 1.1rem;
    color: var(--dark-color);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.logs-table {
    overflow-x: auto;
}

.logs-table table {
    width: 100%;
    border-collapse: collapse;
}

.logs-table th {
    text-align: left;
    padding: 1rem;
    background: #f8f9fa;
    font-weight: 600;
    color: var(--dark-color);
}

.logs-table td {
    padding: 1rem;
    border-bottom: 1px solid #f0f0f0;
}

.user-agent {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.text-center {
    text-align: center;
    padding: 2rem;
    color: #999;
}

/* MODAL STYLES */
.modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    animation: fadeIn 0.3s ease;
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 2rem;
    border-radius: var(--radius-sm);
    width: 90%;
    max-width: 600px;
    max-height: 80vh;
    overflow-y: auto;
    animation: slideDown 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideDown {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(46, 43, 37, 0.1);
}

.modal-header h2 {
    font-size: 1.5rem;
    font-family: var(--font-heading);
    font-weight: 400;
    color: var(--dark-color);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.close-modal {
    font-size: 2rem;
    cursor: pointer;
    color: #999;
    transition: color 0.3s ease;
}

.close-modal:hover {
    color: var(--error-color);
}

.modal-body {
    margin-bottom: 1.5rem;
}

.modal-body h3 {
    margin: 1.5rem 0 1rem;
    color: var(--dark-color);
    font-size: 1.1rem;
}

.modal-body h3:first-of-type {
    margin-top: 0;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.cep-input-group {
    display: flex;
    gap: 0.5rem;
}

.btn-cep-small {
    padding: 0.8rem 1rem;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-cep-small:hover {
    background: var(--secondary-color);
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding-top: 1rem;
    border-top: 2px solid #f0f0f0;
}

@media (max-width: 768px) {
    .dashboard-container {
        padding: 1rem;
    }
    
    .dashboard-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .dashboard-content {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        margin: 10% auto;
        padding: 1rem;
    }
}
</style>

<!-- SCRIPTS DO DASHBOARD -->
<script>
// Funções do Modal de Edição
function openEditModal() {
    document.getElementById('editProfileModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editProfileModal').style.display = 'none';
}

// Funções do Modal de Endereço
function openAddAddressModal() {
    document.getElementById('addAddressModal').style.display = 'block';
}

function closeAddAddressModal() {
    document.getElementById('addAddressModal').style.display = 'none';
    document.getElementById('addAddressForm').reset();
}

// Buscar CEP no modal de endereço
function buscarCepAddress() {
    const cep = document.getElementById('address_cep').value.replace(/\D/g, '');
    
    if(cep.length !== 8) {
        alert('CEP inválido!');
        return;
    }
    
    fetch(`../backend/api/viacep.php?cep=${cep}`)
        .then(response => response.json())
        .then(data => {
            if(data.erro) {
                alert(data.erro);
            } else {
                document.getElementById('address_logradouro').value = data.logradouro || '';
                document.getElementById('address_bairro').value = data.bairro || '';
                document.getElementById('address_cidade').value = data.cidade || '';
                document.getElementById('address_uf').value = data.uf || '';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao buscar CEP');
        });
}

// Editar endereço
function editAddress(id) {
    // Implementar edição de endereço
    alert('Editar endereço ' + id);
}

// Deletar endereço
function deleteAddress(id) {
    if(confirm('Tem certeza que deseja excluir este endereço?')) {
        fetch(`../backend/auth/delete_address.php?id=${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-Token': '<?php echo csrfToken(); ?>' }
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                location.reload();
            } else {
                alert('Erro ao excluir endereço');
            }
        });
    }
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}

// Máscara de telefone no modal de edição
document.getElementById('edit_telefone')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 11) value = value.substring(0, 11);
    
    if (value.length > 6) {
        value = `(${value.substring(0, 2)}) ${value.substring(2, 7)}-${value.substring(7, 11)}`;
    } else if (value.length > 2) {
        value = `(${value.substring(0, 2)}) ${value.substring(2)}`;
    } else if (value.length > 0) {
        value = `(${value}`;
    }
    
    e.target.value = value;
});

// Máscara de CEP no modal de endereço
document.getElementById('address_cep')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 5) {
        value = value.substring(0, 5) + '-' + value.substring(5, 8);
    }
    e.target.value = value;
});
</script>

<?php include 'includes/footer.php'; ?>