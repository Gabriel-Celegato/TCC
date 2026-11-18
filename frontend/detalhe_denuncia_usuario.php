<?php
// ============================================================================
// ARQUIVO: detalhe_denuncia_usuario.php
// DESCRIÇÃO: Página para o usuário visualizar os detalhes de uma denúncia
// ============================================================================

session_start();
$pageTitle = "Detalhes da Denúncia - Controle de Pragas";

// Verificar se usuário está logado
if(!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/header.php';
include 'includes/navbar.php';
require_once '../backend/auth/csrf.php'; 
// ============================================================================
// CONEXÃO COM BANCO
// ============================================================================
require_once '../backend/config/database.php';
$database = new Database();
$db = $database->getConnection();

// ============================================================================
// PEGAR ID DA DENÚNCIA
// ============================================================================
$denuncia_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($denuncia_id <= 0) {
    header("Location: minhas_denuncias.php?error=invalid_id");
    exit();
}

// ============================================================================
// BUSCAR DETALHES DA DENÚNCIA (APENAS DO USUÁRIO LOGADO)
// ============================================================================
$query = "SELECT d.*, 
          u.nome as usuario_nome, 
          u.email as usuario_email,
          u.telefone as usuario_telefone,
          tp.nome as tipo_praga,
          tp.nivel_risco as tipo_risco,
          tp.descricao as tipo_descricao
          FROM denuncias d
          JOIN usuarios u ON d.usuario_id = u.id
          JOIN tipos_praga tp ON d.tipo_praga_id = tp.id
          WHERE d.id = :id AND d.usuario_id = :usuario_id";

$stmt = $db->prepare($query);
$stmt->bindParam(':id', $denuncia_id);
$stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);
$stmt->execute();

$denuncia = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$denuncia) {
    header("Location: minhas_denuncias.php?error=not_found");
    exit();
}

// ============================================================================
// BUSCAR COMENTÁRIOS DA ADMINISTRAÇÃO
// ============================================================================
$query_comentarios = "SELECT c.*, u.nome as usuario_nome, u.tipo as usuario_tipo
                      FROM comentarios_denuncia c
                      JOIN usuarios u ON c.usuario_id = u.id
                      WHERE c.denuncia_id = :id
                      ORDER BY c.created_at DESC";

$stmt_comentarios = $db->prepare($query_comentarios);
$stmt_comentarios->bindParam(':id', $denuncia_id);
$stmt_comentarios->execute();
$comentarios = $stmt_comentarios->fetchAll(PDO::FETCH_ASSOC);

// ============================================================================
// BUSCAR HISTÓRICO DE ALTERAÇÕES (para o usuário ver)
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
?>

<style>
    .denuncia-detalhes-container {
        padding: 2rem;
        max-width: 1000px;
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

    .card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 2rem;
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

    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
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

    .comentario-item {
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 10px;
        margin-bottom: 1rem;
        border-left: 4px solid #6c5ce7;
    }

    .comentario-item.admin {
        border-left-color: #d63031;
        background: #fff5f5;
    }

    .comentario-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        color: #666;
        font-size: 0.9rem;
    }

    .comentario-header strong {
        color: #6c5ce7;
    }

    .comentario-header strong.admin {
        color: #d63031;
    }

    .historico-item {
        padding: 1rem;
        border-left: 3px solid #6c5ce7;
        margin-bottom: 1rem;
        background: #f8f9fa;
        border-radius: 5px;
    }

    .protocolo-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        text-align: center;
        padding: 1.5rem;
        border-radius: 15px;
        margin-bottom: 2rem;
    }

    .protocolo-card h3 {
        font-size: 0.9rem;
        opacity: 0.9;
        margin-bottom: 0.5rem;
    }

    .protocolo-card .protocolo-numero {
        font-size: 1.8rem;
        font-weight: bold;
        letter-spacing: 2px;
    }

    .alert-info {
        background: #e3f2fd;
        border-left: 4px solid #2196f3;
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1rem;
    }

    .alert-info i {
        color: #2196f3;
        margin-right: 0.5rem;
    }

    @media (max-width: 768px) {
        .denuncia-detalhes-container {
            padding: 1rem;
        }
        
        .info-grid {
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
</style>

<div class="denuncia-detalhes-container">
    
    <!-- Botão Voltar -->
    <div class="back-button">
        <a href="minhas_denuncias.php">
            <i class="fas fa-arrow-left"></i> Voltar para Minhas Denúncias
        </a>
    </div>

    <!-- Protocolo -->
    <div class="protocolo-card">
        <h3><i class="fas fa-hashtag"></i> Número de Protocolo</h3>
        <div class="protocolo-numero">
            DEN<?php echo date('Ymd', strtotime($denuncia['data_ocorrencia'])); ?>-<?php echo str_pad($denuncia['id'], 5, '0', STR_PAD_LEFT); ?>
        </div>
        <small>Guarde este número para consultas futuras</small>
    </div>

    <!-- Status Atual -->
    <div class="alert-info">
        <i class="fas fa-info-circle"></i>
        <strong>Status atual:</strong> 
        <span class="status-badge status-<?php echo $denuncia['status']; ?>">
            <?php 
            $status_nomes = [
                'pendente' => 'Pendente - Aguardando análise',
                'em_andamento' => 'Em Andamento - Em análise',
                'resolvido' => 'Resolvido - Problema solucionado',
                'falso_alarme' => 'Falso Alarme - Não confirmado'
            ];
            echo $status_nomes[$denuncia['status']];
            ?>
        </span>
    </div>

    <!-- Informações da Denúncia -->
    <div class="card">
        <h2><i class="fas fa-bug"></i> Detalhes da Denúncia</h2>
        
        <div class="info-grid">
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
                <div class="info-label">Data da Ocorrência:</div>
                <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($denuncia['data_ocorrencia'])); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Data do Registro:</div>
                <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($denuncia['created_at'])); ?></div>
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
            
            <?php if($denuncia['autoridade_notificada']): ?>
            <div class="info-row">
                <div class="info-label">Autoridade Notificada:</div>
                <div class="info-value">
                    <?php echo htmlspecialchars($denuncia['autoridade_nome']); ?>
                    <?php if($denuncia['autoridade_contato']): ?>
                        <br><small><?php echo htmlspecialchars($denuncia['autoridade_contato']); ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
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
    </div>

    <!-- Comentários da Administração -->
    <div class="card">
        <h2><i class="fas fa-comments"></i> Observações da Administração</h2>
        
        <?php if(count($comentarios) > 0): ?>
            <?php foreach($comentarios as $c): ?>
                <div class="comentario-item <?php echo $c['usuario_tipo'] == 'admin' ? 'admin' : ''; ?>">
                    <div class="comentario-header">
                        <strong class="<?php echo $c['usuario_tipo'] == 'admin' ? 'admin' : ''; ?>">
                            <i class="fas <?php echo $c['usuario_tipo'] == 'admin' ? 'fa-user-shield' : 'fa-user'; ?>"></i> 
                            <?php echo htmlspecialchars($c['usuario_nome']); ?>
                            <?php if($c['usuario_tipo'] == 'admin'): ?>
                                <span style="font-size: 0.8rem;">(Administrador)</span>
                            <?php endif; ?>
                        </strong>
                        <span><?php echo date('d/m/Y H:i', strtotime($c['created_at'])); ?></span>
                    </div>
                    <p><?php echo nl2br(htmlspecialchars($c['comentario'])); ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: #666; text-align: center; padding: 2rem;">
                <i class="fas fa-comment-slash"></i> Nenhuma observação ainda.
                <br>Aguardando análise da administração.
            </p>
        <?php endif; ?>
    </div>

    <!-- Histórico de Alterações -->
    <?php if(count($historico) > 0): ?>
    <div class="card">
        <h2><i class="fas fa-history"></i> Histórico de Atualizações</h2>
        
        <?php foreach($historico as $h): ?>
            <div class="historico-item">
                <strong><?php echo date('d/m/Y H:i', strtotime($h['created_at'])); ?></strong><br>
                Status: <strong><?php echo ucfirst(str_replace('_', ' ', $h['status_anterior'])); ?></strong> → 
                <strong><?php echo ucfirst(str_replace('_', ' ', $h['status_novo'])); ?></strong><br>
                <?php if($h['observacao']): ?>
                    Observação: <?php echo htmlspecialchars($h['observacao']); ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Informações Adicionais -->
    <div class="card">
        <h2><i class="fas fa-question-circle"></i> Informações Importantes</h2>
        
        <div class="info-row">
            <div class="info-label">O que acontece agora?</div>
            <div class="info-value">
                <?php if($denuncia['status'] == 'pendente'): ?>
                    Sua denúncia está aguardando análise. Nossa equipe irá verificar e em breve atualizaremos o status.
                <?php elseif($denuncia['status'] == 'em_andamento'): ?>
                    Sua denúncia está sendo analisada. Estamos trabalhando para resolver a situação.
                <?php elseif($denuncia['status'] == 'resolvido'): ?>
                    Sua denúncia foi resolvida! Agradecemos pela colaboração.
                <?php elseif($denuncia['status'] == 'falso_alarme'): ?>
                    Após análise, não foi confirmada a ocorrência. Se precisar, você pode fazer uma nova denúncia.
                <?php endif; ?>
            </div>
        </div>
        
        <div class="info-row">
            <div class="info-label">Precisa de ajuda?</div>
            <div class="info-value">
                <i class="fas fa-envelope"></i> contato@controlepragas.com.br<br>
                <i class="fas fa-phone"></i> (11) 99999-9999
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>