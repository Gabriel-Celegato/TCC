<?php
// ============================================================================
// ARQUIVO: dashboard.php (Painel Admin)
// ============================================================================

// Carregar configurações globais PRIMEIRO
require_once __DIR__ . '/../../config.php';

// Agora sim, usar as constantes definidas
require_once BACKEND_PATH . 'auth/check_admin.php';

// Verificar se é admin
requireAdmin();

// Título da página
$pageTitle = "Painel Admin - " . SITE_NAME;

// Incluir header e navbar
include FRONTEND_PATH . 'includes/header.php';
include FRONTEND_PATH . 'includes/navbar.php';

// ============================================================================
// CONEXÃO COM BANCO
// ============================================================================
require_once BACKEND_PATH . 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// ============================================================================
// ESTATÍSTICAS
// ============================================================================
$stats = [
    'total' => $db->query("SELECT COUNT(*) FROM denuncias")->fetchColumn(),
    'pendentes' => $db->query("SELECT COUNT(*) FROM denuncias WHERE status = 'pendente'")->fetchColumn(),
    'urgentes' => $db->query("SELECT COUNT(*) FROM denuncias WHERE urgencia = 'urgente'")->fetchColumn(),
    'hoje' => $db->query("SELECT COUNT(*) FROM denuncias WHERE DATE(data_ocorrencia) = CURDATE()")->fetchColumn()
];

// ============================================================================
// BUSCAR TODAS AS DENÚNCIAS PARA O MAPA
// ============================================================================
$query = "SELECT d.*, 
          u.nome as usuario_nome, 
          tp.nome as tipo_praga,
          tp.nivel_risco
          FROM denuncias d
          JOIN usuarios u ON d.usuario_id = u.id
          JOIN tipos_praga tp ON d.tipo_praga_id = tp.id
          WHERE d.latitude IS NOT NULL 
          AND d.longitude IS NOT NULL
          ORDER BY d.data_ocorrencia DESC";

$stmt = $db->prepare($query);
$stmt->execute();
$denuncias_mapa = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================================
// BUSCAR TODAS AS DENÚNCIAS PARA A TABELA
// ============================================================================
$query_tabela = "SELECT d.*, 
          u.nome as usuario_nome, 
          tp.nome as tipo_praga
          FROM denuncias d
          JOIN usuarios u ON d.usuario_id = u.id
          JOIN tipos_praga tp ON d.tipo_praga_id = tp.id
          ORDER BY d.data_ocorrencia DESC
          LIMIT 100";

$stmt_tabela = $db->prepare($query_tabela);
$stmt_tabela->execute();
$denuncias_tabela = $stmt_tabela->fetchAll(PDO::FETCH_ASSOC);

// Converter para JSON para usar no JavaScript.
// As flags JSON_HEX_* evitam que uma denúncia com "</script>" no texto
// (local, tipo, nome de usuário) feche a tag <script> e injete HTML/JS.
$denuncias_json = json_encode(
    $denuncias_mapa,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />

<div class="admin-container">
    <!-- HEADER -->
    <div class="admin-header">
        <h1>
            <i class="fas fa-crown"></i>
            Painel Administrativo
        </h1>
        <div class="admin-badge">
            <i class="fas fa-shield-alt"></i> Modo Admin
        </div>
    </div>

    <!-- STATS CARDS -->
    <div class="stats-grid">
        <div class="admin-stat-card">
            <div class="admin-stat-icon">
                <i class="fas fa-bug"></i>
            </div>
            <div class="admin-stat-info">
                <h3>Total Denúncias</h3>
                <p><?php echo (int)$stats['total']; ?></p>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon pendentes">
                <i class="fas fa-clock"></i>
            </div>
            <div class="admin-stat-info">
                <h3>Pendentes</h3>
                <p><?php echo (int)$stats['pendentes']; ?></p>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon urgentes">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="admin-stat-info">
                <h3>Urgentes</h3>
                <p><?php echo (int)$stats['urgentes']; ?></p>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon hoje">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="admin-stat-info">
                <h3>Hoje</h3>
                <p><?php echo (int)$stats['hoje']; ?></p>
            </div>
        </div>
    </div>

    <!-- MAPA DE DENÚNCIAS -->
    <div class="map-section">
        <h2>
            <i class="fas fa-map-marked-alt"></i>
            Mapa de Ocorrências
        </h2>
        
        <div id="denunciasMap"></div>
        
        <div class="map-controls">
            <select id="filtroStatus" class="map-filter">
                <option value="">Todos os status</option>
                <option value="pendente">Pendentes</option>
                <option value="em_andamento">Em Andamento</option>
                <option value="resolvido">Resolvidos</option>
                <option value="falso_alarme">Falso Alarme</option>
            </select>
            
            <select id="filtroUrgencia" class="map-filter">
                <option value="">Todas urgências</option>
                <option value="baixa">Baixa</option>
                <option value="média">Média</option>
                <option value="alta">Alta</option>
                <option value="urgente">Urgente</option>
            </select>
            
            <select id="filtroTipo" class="map-filter">
                <option value="">Todos os tipos</option>
                <?php 
                $tipos_unicos = [];
                foreach($denuncias_mapa as $d) {
                    $tipos_unicos[$d['tipo_praga']] = $d['tipo_praga'];
                }
                foreach($tipos_unicos as $tipo): 
                ?>
                    <option value="<?php echo htmlspecialchars($tipo); ?>">
                        <?php echo htmlspecialchars($tipo); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button class="btn-search" onclick="aplicarFiltros()">
                <i class="fas fa-filter"></i> Aplicar Filtros
            </button>
            
            <button class="btn-search" onclick="centralizarMapa()">
                <i class="fas fa-crosshairs"></i> Centralizar
            </button>
            
            <button class="btn-search" onclick="mostrarTodas()">
                <i class="fas fa-eye"></i> Mostrar Todas
            </button>
        </div>
        
        <div class="map-legend">
            <span class="legend-item"><span class="legend-dot legend-baixa"></span> Baixa</span>
            <span class="legend-item"><span class="legend-dot legend-média"></span> Média</span>
            <span class="legend-item"><span class="legend-dot legend-alta"></span> Alta</span>
            <span class="legend-item"><span class="legend-dot legend-urgente"></span> Urgente</span>
            <span class="legend-item"><span class="legend-dot legend-resolvido"></span> Resolvido</span>
        </div>
    </div>

    <!-- LISTA DE DENÚNCIAS -->
    <div class="denuncias-section">
        <div class="section-header">
            <h2><i class="fas fa-list"></i> Todas as Denúncias</h2>
            
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Buscar por local, tipo...">
                <button class="btn-search" onclick="buscarDenuncias()">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="denuncias-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Foto</th>
                        <th>Usuário</th>
                        <th>Tipo</th>
                        <th>Local</th>
                        <th>Data</th>
                        <th>Urgência</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($denuncias_tabela as $d): ?>
                    <tr data-id="<?php echo (int)$d['id']; ?>">
                        <td>#<?php echo (int)$d['id']; ?></td>
                        <td>
                            <?php if($d['foto_path']): ?>
                                <img src="<?php echo BASE_URL . htmlspecialchars($d['foto_path']); ?>" 
                                     class="foto-thumb" 
                                     onclick="abrirFoto('<?php echo BASE_URL . htmlspecialchars($d['foto_path'], ENT_QUOTES); ?>')">
                            <?php else: ?>
                                <i class="fas fa-image" style="color: rgba(32,30,27,0.25);"></i>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($d['usuario_nome']); ?></td>
                        <td><?php echo htmlspecialchars($d['tipo_praga']); ?></td>
                        <td><?php echo htmlspecialchars($d['local_denuncia']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($d['data_ocorrencia'])); ?></td>
                        <td>
                            <span class="status-badge urgencia-<?php echo htmlspecialchars($d['urgencia']); ?>">
                                <?php echo htmlspecialchars(ucfirst($d['urgencia'])); ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo htmlspecialchars($d['status']); ?>">
                                <?php 
                                $status = [
                                    'pendente' => 'Pendente',
                                    'em_andamento' => 'Em Andamento',
                                    'resolvido' => 'Resolvido',
                                    'falso_alarme' => 'Falso Alarme'
                                ];
                                echo htmlspecialchars($status[$d['status']] ?? $d['status']);
                                ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-action" onclick="verDetalhes(<?php echo (int)$d['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn-action" onclick="editarStatus(<?php echo (int)$d['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-action ver-mapa" onclick="localizarNoMapa(<?php echo (int)$d['id']; ?>)" 
                                        title="Ver no mapa">
                                    <i class="fas fa-map-marker-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL DE FOTO -->
<div id="fotoModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Foto da Denúncia</h3>
            <span class="close-modal" onclick="fecharFoto()">&times;</span>
        </div>
        <img id="fotoModalImg" src="" alt="Foto da denúncia" style="width: 100%; border-radius: var(--radius-sm);">
    </div>
</div>

<!-- MODAL DE EDIÇÃO DE STATUS -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Alterar Status</h3>
            <span class="close-modal" onclick="fecharStatusModal()">&times;</span>
        </div>
        
        <form id="statusForm" class="admin-modal-form" onsubmit="salvarStatus(event)">
            <input type="hidden" id="statusDenunciaId">
            
            <div class="form-group">
                <label>Novo Status</label>
                <select id="novoStatus" required>
                    <option value="pendente">Pendente</option>
                    <option value="em_andamento">Em Andamento</option>
                    <option value="resolvido">Resolvido</option>
                    <option value="falso_alarme">Falso Alarme</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Observação</label>
                <textarea id="statusObservacao" rows="3"></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Salvar</button>
        </form>
    </div>
</div>

<div id="notificacao-container" class="notificacao-container"></div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<script>
    const todasDenuncias = <?php echo $denuncias_json; ?>;
    let map;
    let markersLayer;
    let denunciasFiltradas = [...todasDenuncias];
    const baseUrl = '<?php echo BASE_URL; ?>';
    const adminUrl = '<?php echo ADMIN_URL; ?>';

    // Mesma paleta usada nos badges/legenda — terra-claro (baixa) até terra-escuro
    // (urgente), resolvido em oliva (--success-color)
    const CORES = {
        baixa: '#56633f',
        média: '#b2622d',
        alta: '#8c491a',
        urgente: '#5c2c14',
        resolvido: '#56633f'
    };

    function showNotificacao(msg, tipo = 'error') {
        const container = document.getElementById('notificacao-container');
        const el = document.createElement('div');
        el.className = `notificacao ${tipo}`;
        const icons = { success: 'fa-check-circle', error: 'fa-times-circle' };
        el.innerHTML = `<i class="fas ${icons[tipo] || 'fa-info-circle'}"></i> <span>${msg}</span>`;
        container.appendChild(el);
        setTimeout(() => {
            el.classList.add('fade-out');
            setTimeout(() => el.remove(), 300);
        }, 3500);
    }

    // Escapa texto antes de inserir no HTML do popup do mapa. Campos como
    // "local_denuncia" vêm de texto que o próprio usuário controla no
    // formulário de denúncia — sem isso, dava pra injetar HTML/JS que
    // rodaria na sessão do admin ao abrir o popup no mapa.
    function escapeHtml(valor) {
        if(valor === null || valor === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(valor);
        return div.innerHTML;
    }
    
    function initMap() {
        // Limites do mundo: evitam que o mapa fique repetindo cópias do
        // mundo lado a lado quando está com pouco zoom (mesmo bug do
        // nova_denuncia.php)
        const limitesMundo = [[-90, -180], [90, 180]];

        map = L.map('denunciasMap', {
            maxBounds: limitesMundo,
            maxBoundsViscosity: 1.0
        }).setView([-15.788, -47.879], 4);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            noWrap: true,
            bounds: limitesMundo
        }).addTo(map);

        // Recalcula o tamanho depois que o layout termina de assentar
        setTimeout(() => map.invalidateSize(), 150);
        
        markersLayer = L.markerClusterGroup({
            maxClusterRadius: 50,
            spiderfyOnMaxZoom: true,
            showCoverageOnHover: false,
            zoomToBoundsOnClick: true
        });
        
        adicionarMarcadores(todasDenuncias);
        map.addLayer(markersLayer);
    }
    
    function adicionarMarcadores(denuncias) {
        markersLayer.clearLayers();
        
        denuncias.forEach(d => {
            let markerColor = CORES.baixa;
            
            if(d.urgencia === 'média') markerColor = CORES.média;
            else if(d.urgencia === 'alta') markerColor = CORES.alta;
            else if(d.urgencia === 'urgente') markerColor = CORES.urgente;
            if(d.status === 'resolvido') markerColor = CORES.resolvido;
            
            const icon = L.divIcon({
                className: 'custom-marker',
                html: `<div style="background-color: ${markerColor}; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>`,
                iconSize: [20, 20],
                iconAnchor: [10, 10],
                popupAnchor: [0, -10]
            });
            
            const marker = L.marker([parseFloat(d.latitude), parseFloat(d.longitude)], { icon });
            
            const data = new Date(d.data_ocorrencia);
            const dataFormatada = data.toLocaleDateString('pt-BR');
            
            const popupContent = `
                <div style="min-width: 250px;">
                    <h4><i class="fas fa-bug"></i> ${escapeHtml(d.tipo_praga)}</h4>
                    <p><strong>Local:</strong> ${escapeHtml(d.local_denuncia)}</p>
                    <p><strong>Data:</strong> ${dataFormatada}</p>
                    <p><strong>Status:</strong> ${escapeHtml(d.status)}</p>
                    <div style="display: flex; gap: 5px; margin-top: 10px;">
                        <a href="${adminUrl}detalhe_denuncia.php?id=${encodeURIComponent(d.id)}" 
                           style="flex: 1; background: #3d472b; color: white; text-decoration: none; padding: 8px; border-radius: 6px; text-align: center;">
                            Ver Detalhes
                        </a>
                    </div>
                </div>
            `;
            
            marker.bindPopup(popupContent);
            markersLayer.addLayer(marker);
        });
        
        map.addLayer(markersLayer);
    }
    
    function aplicarFiltros() {
        const status = document.getElementById('filtroStatus').value;
        const urgencia = document.getElementById('filtroUrgencia').value;
        const tipo = document.getElementById('filtroTipo').value;
        
        denunciasFiltradas = todasDenuncias.filter(d => {
            if(status && d.status !== status) return false;
            if(urgencia && d.urgencia !== urgencia) return false;
            if(tipo && d.tipo_praga !== tipo) return false;
            return true;
        });
        
        adicionarMarcadores(denunciasFiltradas);
    }
    
    function centralizarMapa() {
        if(markersLayer && markersLayer.getLayers().length > 0) {
            map.fitBounds(markersLayer.getBounds());
        }
    }
    
    function mostrarTodas() {
        document.getElementById('filtroStatus').value = '';
        document.getElementById('filtroUrgencia').value = '';
        document.getElementById('filtroTipo').value = '';
        adicionarMarcadores(todasDenuncias);
    }
    
    function localizarNoMapa(id) {
        const denuncia = todasDenuncias.find(d => d.id == id);
        if(denuncia && denuncia.latitude != null && denuncia.longitude != null) {
            map.setView([parseFloat(denuncia.latitude), parseFloat(denuncia.longitude)], 18);
        }
    }
    
    function abrirFoto(src) {
        document.getElementById('fotoModalImg').src = src;
        document.getElementById('fotoModal').style.display = 'flex';
    }
    
    function fecharFoto() {
        document.getElementById('fotoModal').style.display = 'none';
    }
    
    function verDetalhes(id) {
        window.location.href = adminUrl + 'detalhe_denuncia.php?id=' + id;
    }
    
    function editarStatus(id) {
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

        const url = '<?php echo BACKEND_URL; ?>admin/atualizar_status.php';

        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ denuncia_id: id, status: status, observacao: observacao })
        })
        .then(async response => {
            const text = await response.text();
            try {
                const data = JSON.parse(text);
                if(data.success) {
                    showNotificacao('Status atualizado com sucesso!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotificacao(data.message || 'Erro ao atualizar status', 'error');
                }
            } catch(e) {
                console.error('Resposta não é JSON válido:', text);
                showNotificacao('Resposta do servidor inválida', 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showNotificacao('Erro ao conectar ao servidor', 'error');
        });
    }
    
    function buscarDenuncias() {
        showNotificacao('Função de busca será implementada em breve', 'info');
    }
    
    window.onclick = function(event) {
        const fotoModal = document.getElementById('fotoModal');
        const statusModal = document.getElementById('statusModal');
        
        if (event.target == fotoModal) fotoModal.style.display = 'none';
        if (event.target == statusModal) statusModal.style.display = 'none';
    }
    
    window.addEventListener('load', initMap);
</script>

<?php include FRONTEND_PATH . 'includes/footer.php'; ?>