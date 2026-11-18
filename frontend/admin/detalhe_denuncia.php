<?php
// ============================================================================
// ARQUIVO: detalhe_denuncia.php (Admin - Visualizar Denúncia)
// ============================================================================

// Carregar configurações globais
require_once __DIR__ . '/../../config.php';

// Verificar se é admin
require_once BACKEND_PATH . 'auth/check_admin.php';
requireAdmin();

$pageTitle = "Detalhes da Denúncia - " . SITE_NAME;

include FRONTEND_PATH . 'includes/header.php';
include FRONTEND_PATH . 'includes/navbar.php';

// ============================================================================
// CONEXÃO COM BANCO
// ============================================================================
require_once BACKEND_PATH . 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// ============================================================================
// PEGAR ID DA DENÚNCIA
// ============================================================================
$denuncia_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($denuncia_id <= 0) {
    header("Location: " . admin_url('dashboard.php?error=invalid_id'));
    exit();
}

// ============================================================================
// BUSCAR DETALHES DA DENÚNCIA
// ============================================================================
$query = "SELECT d.*, 
          u.nome as usuario_nome, 
          u.email as usuario_email,
          u.telefone as usuario_telefone,
          u.logradouro, u.numero, u.bairro, u.cidade, u.uf, u.cep,
          tp.nome as tipo_praga,
          tp.nivel_risco as tipo_risco,
          tp.descricao as tipo_descricao
          FROM denuncias d
          JOIN usuarios u ON d.usuario_id = u.id
          JOIN tipos_praga tp ON d.tipo_praga_id = tp.id
          WHERE d.id = :id";

$stmt = $db->prepare($query);
$stmt->bindParam(':id', $denuncia_id);
$stmt->execute();

$denuncia = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$denuncia) {
    header("Location: " . admin_url('dashboard.php?error=not_found'));
    exit();
}

// ============================================================================
// BUSCAR HISTÓRICO DE ALTERAÇÕES
// ============================================================================
$query_historico = "SELECT h.*, u.nome as alterado_por_nome
                    FROM historico_denuncia h
                    JOIN usuarios u ON h.alterado_por = u.id
                    WHERE h.denuncia_id = :id
                    ORDER BY h.created_at DESC";

$stmt_historico = $db->prepare($query_historico);
$stmt_historico->bindParam(':id', $denuncia_id);
$stmt_historico->execute();
$historico = $stmt_historico->fetchAll(PDO::FETCH_ASSOC);

// ============================================================================
// BUSCAR COMENTÁRIOS
// ============================================================================
$query_comentarios = "SELECT c.*, u.nome as usuario_nome
                      FROM comentarios_denuncia c
                      JOIN usuarios u ON c.usuario_id = u.id
                      WHERE c.denuncia_id = :id
                      ORDER BY c.created_at DESC";

$stmt_comentarios = $db->prepare($query_comentarios);
$stmt_comentarios->bindParam(':id', $denuncia_id);
$stmt_comentarios->execute();
$comentarios = $stmt_comentarios->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .denuncia-detalhes-container {
        padding: 2rem;
        max-width: 1200px;
        margin: 0 auto;
        margin-top: 70px;
    }

    .back-button {
        margin-bottom: 1.5rem;
    }

    .back-button a {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: #6c5ce7;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 5px;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .back-button a:hover {
        background: #5a4ad1;
        transform: translateX(-5px);
    }

    .detalhes-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .card h2 {
        color: #2d3436;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border-bottom: 2px solid #6c5ce7;
        padding-bottom: 0.5rem;
    }

    .info-row {
        display: flex;
        margin-bottom: 1rem;
        padding: 0.5rem;
        border-bottom: 1px solid #f0f0f0;
    }

    .info-label {
        width: 120px;
        font-weight: 600;
        color: #666;
    }

    .info-value {
        flex: 1;
        color: #2d3436;
    }

    .foto-detalhe {
        text-align: center;
        margin-top: 1rem;
    }

    .foto-detalhe img {
        max-width: 100%;
        max-height: 400px;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }

    .status-badge {
        display: inline-block;
        padding: 0.3rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .status-pendente { background: #fdcb6e; color: white; }
    .status-em_andamento { background: #0984e3; color: white; }
    .status-resolvido { background: #00b894; color: white; }
    .status-falso_alarme { background: #636e72; color: white; }

    .historico-item {
        padding: 1rem;
        border-left: 3px solid #6c5ce7;
        margin-bottom: 1rem;
        background: #f8f9fa;
        border-radius: 5px;
    }

    .comentario-item {
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 10px;
        margin-bottom: 1rem;
    }

    .comentario-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        color: #666;
        font-size: 0.9rem;
    }

    .btn-editar-status {
        background: #6c5ce7;
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 1rem;
    }

    .btn-editar-status:hover {
        background: #5a4ad1;
        transform: translateY(-2px);
    }

    @media (max-width: 768px) {
        .detalhes-grid {
            grid-template-columns: 1fr;
        }
        
        .info-row {
            flex-direction: column;
        }
        
        .info-label {
            width: 100%;
            margin-bottom: 0.3rem;
        }
    }

    /* ============================================================================
   NOTIFICAÇÕES FLUTUANTES
   ============================================================================ */
.notification {
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 1rem 1.5rem;
    border-radius: 10px;
    background: white;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    gap: 0.8rem;
    z-index: 10000;
    transform: translateX(400px);
    transition: transform 0.3s ease;
    max-width: 350px;
    animation: slideIn 0.3s ease forwards;
}

.notification.show {
    transform: translateX(0);
}

.notification-success {
    border-left: 4px solid #00b894;
}

.notification-success i {
    color: #00b894;
}

.notification-error {
    border-left: 4px solid #d63031;
}

.notification-error i {
    color: #d63031;
}

@keyframes slideIn {
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOut {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(400px);
        opacity: 0;
    }
}

.notification-removing {
    animation: slideOut 0.3s ease forwards;
}
</style>

<div class="denuncia-detalhes-container">
    <div class="back-button">
        <a href="<?php echo admin_url('dashboard.php'); ?>">
            <i class="fas fa-arrow-left"></i> Voltar ao Painel
        </a>
    </div>

    <div class="detalhes-grid">
        <!-- Informações da Denúncia -->
        <div class="card">
            <h2><i class="fas fa-bug"></i> Informações da Denúncia</h2>
            
            <div class="info-row">
                <div class="info-label">ID:</div>
                <div class="info-value">#<?php echo $denuncia['id']; ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Tipo de Praga:</div>
                <div class="info-value">
                    <strong><?php echo htmlspecialchars($denuncia['tipo_praga']); ?></strong>
                    <span class="status-badge" style="background: 
                        <?php echo $denuncia['tipo_risco'] == 'crítico' ? '#d63031' : 
                                ($denuncia['tipo_risco'] == 'alto' ? '#e17055' :
                                ($denuncia['tipo_risco'] == 'médio' ? '#fdcb6e' : '#00b894')); ?>">
                        <?php echo ucfirst($denuncia['tipo_risco']); ?>
                    </span>
                </div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Quantidade:</div>
                <div class="info-value"><?php echo $denuncia['quantidade']; ?> unidade(s)</div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Local:</div>
                <div class="info-value"><?php echo htmlspecialchars($denuncia['local_denuncia']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Data Ocorrência:</div>
                <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($denuncia['data_ocorrencia'])); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Urgência:</div>
                <div class="info-value">
                    <span class="status-badge" style="background: 
                        <?php echo $denuncia['urgencia'] == 'urgente' ? '#d63031' :
                                ($denuncia['urgencia'] == 'alta' ? '#e17055' :
                                ($denuncia['urgencia'] == 'média' ? '#fdcb6e' : '#00b894')); ?>">
                        <?php echo ucfirst($denuncia['urgencia']); ?>
                    </span>
                </div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">
                    <span class="status-badge status-<?php echo $denuncia['status']; ?>">
                        <?php 
                        $status_nomes = [
                            'pendente' => 'Pendente',
                            'em_andamento' => 'Em Andamento',
                            'resolvido' => 'Resolvido',
                            'falso_alarme' => 'Falso Alarme'
                        ];
                        echo $status_nomes[$denuncia['status']];
                        ?>
                    </span>
                </div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Descrição:</div>
                <div class="info-value"><?php echo nl2br(htmlspecialchars($denuncia['descricao'])); ?></div>
            </div>
            
            <?php if($denuncia['foto_path']): ?>
            <div class="foto-detalhe">
                <img src="<?php echo BASE_URL . $denuncia['foto_path']; ?>" alt="Foto da denúncia">
            </div>
            <?php endif; ?>
            
            <button class="btn-editar-status" onclick="abrirModalStatus(<?php echo $denuncia['id']; ?>)">
                <i class="fas fa-edit"></i> Alterar Status
            </button>
        </div>
        
        <!-- Informações do Denunciante -->
        <div class="card">
            <h2><i class="fas fa-user"></i> Informações do Denunciante</h2>
            
            <div class="info-row">
                <div class="info-label">Nome:</div>
                <div class="info-value"><?php echo htmlspecialchars($denuncia['usuario_nome']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value"><?php echo htmlspecialchars($denuncia['usuario_email']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Telefone:</div>
                <div class="info-value"><?php echo $denuncia['usuario_telefone'] ?: 'Não informado'; ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Endereço:</div>
                <div class="info-value">
                    <?php echo htmlspecialchars($denuncia['logradouro'] ?? 'Não informado'); ?>,
                    <?php echo $denuncia['numero'] ?? 's/n'; ?><br>
                    <?php echo htmlspecialchars($denuncia['bairro'] ?? ''); ?> -
                    <?php echo htmlspecialchars($denuncia['cidade'] ?? ''); ?>/<?php echo $denuncia['uf'] ?? ''; ?>
                    <?php if($denuncia['cep']): ?>CEP: <?php echo $denuncia['cep']; ?><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Histórico de Alterações -->
    <div class="card" style="margin-bottom: 2rem;">
        <h2><i class="fas fa-history"></i> Histórico de Alterações</h2>
        
        <?php if(count($historico) > 0): ?>
            <?php foreach($historico as $h): ?>
                <div class="historico-item">
                    <strong><?php echo date('d/m/Y H:i', strtotime($h['created_at'])); ?></strong><br>
                    Status: <strong><?php echo ucfirst(str_replace('_', ' ', $h['status_anterior'])); ?></strong> → 
                    <strong><?php echo ucfirst(str_replace('_', ' ', $h['status_novo'])); ?></strong><br>
                    Alterado por: <?php echo htmlspecialchars($h['alterado_por_nome']); ?><br>
                    <?php if($h['observacao']): ?>
                        Observação: <?php echo htmlspecialchars($h['observacao']); ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: #666;">Nenhuma alteração registrada ainda.</p>
        <?php endif; ?>
    </div>
    
    <!-- Comentários -->
    <div class="card">
        <h2><i class="fas fa-comments"></i> Comentários</h2>
        
        <?php if(count($comentarios) > 0): ?>
            <?php foreach($comentarios as $c): ?>
                <div class="comentario-item">
                    <div class="comentario-header">
                        <strong><i class="fas fa-user"></i> <?php echo htmlspecialchars($c['usuario_nome']); ?></strong>
                        <span><?php echo date('d/m/Y H:i', strtotime($c['created_at'])); ?></span>
                    </div>
                    <p><?php echo nl2br(htmlspecialchars($c['comentario'])); ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: #666;">Nenhum comentário ainda.</p>
        <?php endif; ?>
        
        <!-- Formulário para adicionar comentário -->
        <form id="comentarioForm" style="margin-top: 1.5rem;">
            <textarea id="novoComentario" rows="3" placeholder="Adicionar um comentário..." style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 10px; resize: vertical;"></textarea>
            <button type="submit" class="btn-editar-status" style="margin-top: 0.5rem;">
                <i class="fas fa-paper-plane"></i> Enviar Comentário
            </button>
        </form>
    </div>
</div>

<!-- MODAL DE EDIÇÃO DE STATUS -->
<div id="statusModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Alterar Status da Denúncia</h3>
            <span class="close-modal" onclick="fecharStatusModal()">&times;</span>
        </div>
        
        <form id="statusForm" onsubmit="salvarStatus(event)">
            <input type="hidden" id="statusDenunciaId">
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Novo Status:</label>
                <select id="novoStatus" class="form-control" required style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 5px;">
                    <option value="pendente">Pendente</option>
                    <option value="em_andamento">Em Andamento</option>
                    <option value="resolvido">Resolvido</option>
                    <option value="falso_alarme">Falso Alarme</option>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Observação:</label>
                <textarea id="statusObservacao" rows="3" class="form-control" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 5px;"></textarea>
            </div>
            
            <button type="submit" class="btn-editar-status" style="width: 100%;">
                <i class="fas fa-save"></i> Salvar Alteração
            </button>
        </form>
    </div>
</div>

<style>
.modal {
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.8);
    display: none;
    justify-content: center;
    align-items: center;
}

.modal-content {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    width: 90%;
    max-width: 500px;
    animation: slideDown 0.3s ease;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.close-modal {
    font-size: 2rem;
    cursor: pointer;
    color: #999;
}

.close-modal:hover {
    color: #d63031;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
<script>
// ============================================================================
// VARIÁVEIS GLOBAIS
// ============================================================================
let statusModalTimeout = null;

// ============================================================================
// FUNÇÃO PARA MOSTRAR NOTIFICAÇÃO
// ============================================================================
function showNotification(message, type = 'success') {
    // Criar elemento de notificação
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    // Adicionar ao corpo
    document.body.appendChild(notification);
    
    // Animar entrada
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Remover após 3 segundos
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// ============================================================================
// FUNÇÕES DO MODAL DE STATUS
// ============================================================================
function abrirModalStatus(id) {
    document.getElementById('statusDenunciaId').value = id;
    document.getElementById('statusModal').style.display = 'flex';
}

function fecharStatusModal() {
    document.getElementById('statusModal').style.display = 'none';
}

function salvarStatus(event) {
    event.preventDefault();
    
    const id = document.getElementById('statusDenunciaId').value;
    const status = document.getElementById('novoStatus').value;
    const observacao = document.getElementById('statusObservacao').value;
    
    const submitBtn = event.submitter;
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
    submitBtn.disabled = true;
    
    fetch('<?php echo BACKEND_URL; ?>admin/atualizar_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            denuncia_id: id,
            status: status,
            observacao: observacao
        })
    })
    .then(async response => {
        const text = await response.text();
        console.log('Resposta status:', text);
        
        // Limpar warnings
        let jsonString = text;
        const firstBrace = text.indexOf('{');
        if(firstBrace > 0) {
            jsonString = text.substring(firstBrace);
        }
        
        try {
            return JSON.parse(jsonString);
        } catch(e) {
            throw new Error('Resposta inválida');
        }
    })
    .then(data => {
        if(data.success) {
            showNotification('Status atualizado com sucesso!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message || 'Erro ao atualizar status', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showNotification('Erro ao conectar ao servidor', 'error');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}
// ============================================================================
// FUNÇÃO PARA ADICIONAR COMENTÁRIO (VERSÃO SIMPLIFICADA E CORRIGIDA)
// ============================================================================
document.getElementById('comentarioForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const comentario = document.getElementById('novoComentario').value.trim();
    const denunciaId = <?php echo $denuncia_id; ?>;
    
    if(!comentario) {
        showNotification('Por favor, digite um comentário', 'error');
        return;
    }
    
    // Desabilitar botão e mostrar loading
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
    submitBtn.disabled = true;
    
    fetch('<?php echo BACKEND_URL; ?>admin/adicionar_comentario.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            denuncia_id: denunciaId,
            comentario: comentario
        })
    })
    .then(async response => {
        const text = await response.text();
        console.log('Resposta comentário:', text);
        
        // Limpar warnings (tudo antes do primeiro {)
        let jsonString = text;
        const firstBrace = text.indexOf('{');
        if(firstBrace > 0) {
            jsonString = text.substring(firstBrace);
        }
        
        try {
            return JSON.parse(jsonString);
        } catch(e) {
            console.error('Erro ao parsear JSON:', e);
            throw new Error('Resposta inválida do servidor');
        }
    })
    .then(data => {
        if(data.success) {
            // Limpar textarea
            document.getElementById('novoComentario').value = '';
            
            // Encontrar o CARD de comentários (o último card da página)
            const cards = document.querySelectorAll('.card');
            const comentariosCard = cards[cards.length - 1]; // Último card é o de comentários
            
            // Encontrar o formulário dentro do card
            const form = comentariosCard.querySelector('#comentarioForm');
            
            // Criar novo elemento de comentário
            const novoComentario = document.createElement('div');
            novoComentario.className = 'comentario-item';
            novoComentario.innerHTML = `
                <div class="comentario-header">
                    <strong><i class="fas fa-user-shield"></i> ${data.comentario.usuario_nome} (Admin)</strong>
                    <span>${new Date().toLocaleString('pt-BR')}</span>
                </div>
                <p>${comentario.replace(/\n/g, '<br>').replace(/</g, '&lt;').replace(/>/g, '&gt;')}</p>
            `;
            
            // Adicionar o comentário ANTES do formulário
            comentariosCard.insertBefore(novoComentario, form);
            
            // Mostrar mensagem de sucesso
            showNotification('Comentário enviado com sucesso!', 'success');
        } else {
            showNotification(data.message || 'Erro ao enviar comentário', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showNotification('Erro ao conectar ao servidor', 'error');
    })
    .finally(() => {
        // Reabilitar botão
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

// ============================================================================
// FECHAR MODAL AO CLICAR FORA
// ============================================================================
window.onclick = function(event) {
    const modal = document.getElementById('statusModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// ============================================================================
// INICIALIZAR
// ============================================================================
document.addEventListener('DOMContentLoaded', function() {
    // Garantir que o modal de status comece escondido
    const statusModal = document.getElementById('statusModal');
    if(statusModal) {
        statusModal.style.display = 'none';
    }
});
</script>

<?php include FRONTEND_PATH . 'includes/footer.php'; ?>