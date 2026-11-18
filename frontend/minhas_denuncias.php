<?php
session_start();
$pageTitle = "Minhas Denúncias - Controle de Pragas";

if(!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/header.php';
include 'includes/navbar.php';

require_once '../backend/config/database.php';
$database = new Database();
$db = $database->getConnection();

// Buscar denúncias do usuário
$query = "SELECT d.*, tp.nome as tipo_praga_nome, tp.nivel_risco
          FROM denuncias d
          JOIN tipos_praga tp ON d.tipo_praga_id = tp.id
          WHERE d.usuario_id = :usuario_id
          ORDER BY d.data_ocorrencia DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);
$stmt->execute();
$denuncias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="minhas-denuncias-container">
    <div class="page-header">
        <h1><i class="fas fa-list"></i> Minhas Denúncias</h1>
        <a href="nova_denuncia.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nova Denúncia
        </a>
    </div>

    <?php if(count($denuncias) > 0): ?>
        <div class="denuncias-grid">
            <?php foreach($denuncias as $denuncia): ?>
                <div class="denuncia-item-card">
                    <div class="denuncia-item-header">
                        <div class="denuncia-tipo">
                            <i class="fas fa-bug"></i>
                            <span><?php echo htmlspecialchars($denuncia['tipo_praga_nome']); ?></span>
                        </div>
                        <span class="status-badge status-<?php echo htmlspecialchars($denuncia['status']); ?>">
                            <?php 
                            $status_text = [
                                'pendente' => 'Pendente',
                                'em_andamento' => 'Em Andamento',
                                'resolvido' => 'Resolvido',
                                'falso_alarme' => 'Falso Alarme'
                            ];
                            echo htmlspecialchars($status_text[$denuncia['status']] ?? $denuncia['status']);
                            ?>
                        </span>
                    </div>

                    <div class="denuncia-body">
                        <?php if($denuncia['foto_path']): ?>
                            <img src="../<?php echo htmlspecialchars($denuncia['foto_path']); ?>" 
                                 alt="Foto da praga" class="denuncia-foto">
                        <?php endif; ?>

                        <div class="denuncia-info">
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($denuncia['local_denuncia']); ?></p>
                            <p><i class="fas fa-sort-numeric-up"></i> Quantidade: <?php echo (int)$denuncia['quantidade']; ?></p>
                            <p><i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($denuncia['data_ocorrencia'])); ?></p>
                            
                            <div class="urgencia-indicator urgencia-<?php echo htmlspecialchars($denuncia['urgencia']); ?>">
                                <i class="fas fa-exclamation-triangle"></i>
                                Urgência: <?php echo htmlspecialchars(ucfirst($denuncia['urgencia'])); ?>
                            </div>
                        </div>
                    </div>

                    <div class="denuncia-footer">
                        <a href="detalhe_denuncia_usuario.php?id=<?php echo (int)$denuncia['id']; ?>" 
                           class="btn-ver-mais">
                            Ver Detalhes <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-bug"></i>
            <h3>Nenhuma denúncia registrada</h3>
            <p>Você ainda não fez nenhuma denúncia de praga.</p>
            <a href="nova_denuncia.php" class="btn btn-primary">Fazer Primeira Denúncia</a>
        </div>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>