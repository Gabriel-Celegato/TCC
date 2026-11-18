<?php
$pageTitle = "Início - Controle de Pragas";
include 'includes/header.php';
include 'includes/navbar.php';

// Estatísticas reais do banco pra seção hero
require_once '../backend/config/database.php';
$database = new Database();
$db = $database->getConnection();

$totalDenuncias = 0;
$taxaResolucao = 0;

try {
    $stmt = $db->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'resolvido' THEN 1 ELSE 0 END) as resolvidas FROM denuncias");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalDenuncias = (int)($row['total'] ?? 0);
    $resolvidas = (int)($row['resolvidas'] ?? 0);
    $taxaResolucao = $totalDenuncias > 0 ? round(($resolvidas / $totalDenuncias) * 100) : 0;
} catch (Exception $e) {
    error_log("Erro ao buscar estatísticas da home: " . $e->getMessage());
}
?>

<main>
    <section class="hero">
        <div class="hero-grid">
            <div class="hero-text">
                <span class="pill-tag"><span class="pill-dot"></span> Serviço municipal</span>

                <h1 class="hero-title">Denuncie pragas urbanas na sua região</h1>

                <p class="hero-lead">
                    Roedores, pombos, baratas e mosquitos. Registre o problema com endereço
                    e fotos, acompanhe o andamento e ajude a manter sua cidade sob controle.
                </p>

                <div class="hero-buttons">
                    <a href="nova_denuncia.php" class="btn btn-primary">Fazer denúncia</a>
                    <a href="minhas_denuncias.php" class="btn btn-secondary">Acompanhar denúncia</a>
                </div>

                <div class="hero-stats">
                    <div class="stat">
                        <span class="stat-number"><?php echo number_format($totalDenuncias, 0, ',', '.'); ?></span>
                        <span class="stat-label">denúncias registradas</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number"><?php echo $taxaResolucao; ?>%</span>
                        <span class="stat-label">taxa de resolução</span>
                    </div>
                </div>
            </div>

            <div class="hero-image-placeholder">
                <i class="fas fa-image"></i>
                <p>foto de ação de controle de pragas</p>
            </div>
        </div>
    </section>

    <section class="pests-section">
        <span class="section-label">O que você pode denunciar</span>
        <h2>Focos de pragas mais comuns na cidade</h2>

        <div class="pest-grid">
            <div class="pest-card">
                <i class="fas fa-mosquito"></i>
                <span>Mosquitos</span>
            </div>
            <div class="pest-card">
                <i class="fas fa-dove"></i>
                <span>Pombos</span>
            </div>
            <div class="pest-card">
                <i class="fas fa-bug"></i>
                <span>Baratas</span>
            </div>
            <div class="pest-card">
                <i class="fas fa-paw"></i>
                <span>Roedores</span>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>