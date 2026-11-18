<?php
// ============================================================================
// ARQUIVO: nova_denuncia.php
// DESCRIÇÃO: Página para registrar nova denúncia de praga com mapa interativo
// ============================================================================

session_start();
$pageTitle = "Nova Denúncia - Controle de Pragas";

// Verificar se usuário está logado
if(!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/header.php';
include 'includes/navbar.php';
require_once '../backend/auth/csrf.php'; 
// Conectar ao banco para buscar tipos de praga
require_once '../backend/config/database.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM tipos_praga ORDER BY nome";
$stmt = $db->prepare($query);
$stmt->execute();
$tipos_praga = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pegar coordenadas da URL (se veio do mapa)
// Validadas como numéricas antes de usar: evita XSS refletido (o valor ia
// direto pro HTML) e evita tratar lat/lng = 0 como "sem coordenadas".
$latParam = $_GET['lat'] ?? '';
$lngParam = $_GET['lng'] ?? '';
$lat = is_numeric($latParam) ? (float)$latParam : null;
$lng = is_numeric($lngParam) ? (float)$lngParam : null;
$temCoordenadas = ($lat !== null && $lng !== null);
$endereco = $_GET['endereco'] ?? '';

// Raio máximo (km) em que o usuário pode registrar uma denúncia, a partir
// da localização atual dele. Precisa bater com RAIO_MAXIMO_KM do backend
// (registrar_denuncia.php) — se mudar um, muda o outro.
$raioMaximoKm = 50;
?>

<!-- Leaflet CSS/JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<main class="denuncia-container">
    <div class="denuncia-card">
        <div class="denuncia-header">
            <i class="fas fa-map-marked-alt denuncia-icon"></i>
            <h2>Registrar Nova Denúncia</h2>
            <p>Clique no mapa para marcar o local exato da ocorrência</p>
        </div>

        <!-- =================================================================
             STATUS DA LOCALIZAÇÃO DO USUÁRIO
             ================================================================= -->
        <div id="statusLocalizacao" class="localizacao-status">
            <i class="fas fa-spinner fa-spin"></i>
            <span>Obtendo sua localização atual...</span>
        </div>

        <form id="denunciaForm" class="denuncia-form" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">

            <!-- Localização atual do usuário (GPS) - define a área permitida -->
            <input type="hidden" id="usuario_lat" name="usuario_lat" value="">
            <input type="hidden" id="usuario_lng" name="usuario_lng" value="">

            <!-- =================================================================
                 MAPA INTERATIVO
                 ================================================================= -->
            <div class="form-group">
                <label><i class="fas fa-map"></i> Selecione o local no mapa *</label>
                <div class="map-container">
                    <div id="localMapa"></div>
                </div>
                <small class="form-hint">Só é possível marcar locais em até <?php echo $raioMaximoKm; ?> km da sua localização atual.</small>
                
                <!-- Campos ocultos para coordenadas -->
                <input type="hidden" id="latitude" name="latitude" value="<?php echo htmlspecialchars($lat ?? ''); ?>">
                <input type="hidden" id="longitude" name="longitude" value="<?php echo htmlspecialchars($lng ?? ''); ?>">
                <input type="hidden" id="local_denuncia" name="local" value="<?php echo htmlspecialchars($endereco); ?>">
                
                <!-- Informações do local selecionado -->
                <div class="coord-info" id="coordInfo" style="<?php echo !$temCoordenadas ? 'display: none;' : ''; ?>">
                    <i class="fas fa-map-pin"></i>
                    <div class="coord-text">
                        <small>Local selecionado:</small>
                        <p id="enderecoSelecionado"><?php echo htmlspecialchars($endereco) ?: 'Clique no mapa para selecionar'; ?></p>
                        <small id="coordDisplay"><?php echo $temCoordenadas ? htmlspecialchars("$lat, $lng") : ''; ?></small>
                    </div>
                </div>
            </div>

            <!-- =================================================================
                 BUSCA POR ENDEREÇO/CEP
                 ================================================================= -->
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-search"></i> Buscar por endereço ou CEP</label>
                    <div class="cep-input-group">
                        <input type="text" id="buscaEndereco" placeholder="Digite um endereço ou CEP (ex: 13382-452)">
                        <button type="button" class="btn-cep" onclick="buscarEndereco(event)">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                    <small class="form-hint">Digite um CEP ou endereço para localizar no mapa</small>
                </div>
            </div>

            <!-- =================================================================
                 TIPO DE PRAGA
                 ================================================================= -->
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-biohazard"></i> Tipo de Praga *</label>
                    <select id="tipo_praga" name="tipo_praga" required>
                        <option value="">Selecione o tipo</option>
                        <?php foreach($tipos_praga as $tipo): ?>
                            <option value="<?php echo $tipo['id']; ?>" 
                                    data-risco="<?php echo $tipo['nivel_risco']; ?>">
                                <?php echo htmlspecialchars($tipo['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-hint" id="riscoHint"></small>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-sort-numeric-up"></i> Quantidade Aproximada *</label>
                    <input type="number" id="quantidade" name="quantidade" 
                           min="1" max="10000" placeholder="Ex: 10" required>
                </div>
            </div>

            <!-- =================================================================
                 DATA E URGÊNCIA
                 ================================================================= -->
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-calendar"></i> Data da Ocorrência *</label>
                    <input type="datetime-local" id="data_ocorrencia" name="data_ocorrencia" 
                           value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-exclamation-triangle"></i> Nível de Urgência *</label>
                    <select id="urgencia" name="urgencia" required>
                        <option value="baixa">Baixa - Posso esperar</option>
                        <option value="média" selected>Média - Resolver em breve</option>
                        <option value="alta">Alta - Resolver rápido</option>
                        <option value="urgente">Urgente - Risco imediato!</option>
                    </select>
                </div>
            </div>

            <!-- =================================================================
                 FOTO
                 ================================================================= -->
            <div class="form-group">
                <label><i class="fas fa-camera"></i> Foto da Praga</label>
                <div class="file-upload">
                    <input type="file" id="foto" name="foto" accept="image/*">
                    <div class="file-upload-label">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Clique para enviar uma foto (opcional)</span>
                    </div>
                </div>
                <div id="previewFoto" class="preview-foto"></div>
            </div>

            <!-- =================================================================
                 NOTIFICAR AUTORIDADE
                 ================================================================= -->
            <div class="form-group">
                <label style="flex-direction: row; gap: 0.5rem;">
                    <input type="checkbox" id="notificar_autoridade" name="notificar_autoridade">
                    <i class="fas fa-gavel"></i> Notificar autoridade competente
                </label>
                
                <div id="autoridadeFields" class="autoridade-fields" style="display: none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nome da Autoridade/Órgão</label>
                            <input type="text" id="autoridade_nome" name="autoridade_nome" 
                                   placeholder="Ex: Vigilância Sanitária">
                        </div>
                        <div class="form-group">
                            <label>Contato</label>
                            <input type="text" id="autoridade_contato" name="autoridade_contato" 
                                   placeholder="Telefone ou email">
                        </div>
                    </div>
                </div>
            </div>

            <!-- =================================================================
                 DESCRIÇÃO
                 ================================================================= -->
            <div class="form-group">
                <label><i class="fas fa-align-left"></i> Descrição Detalhada</label>
                <textarea id="descricao" name="descricao" rows="4" 
                          placeholder="Descreva detalhes da ocorrência (tamanho, comportamento, local exato, etc)..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-block" id="btnSubmit" disabled>
                <i class="fas fa-paper-plane"></i> Registrar Denúncia
            </button>
        </form>
    </div>
</main>

<!-- =================================================================
     MODAL DE CONFIRMAÇÃO
     ================================================================= -->
<div id="confirmModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <i class="fas fa-check-circle"></i>
            <h3>Denúncia Registrada!</h3>
        </div>
        <div class="modal-body">
            <p>Sua denúncia foi registrada com sucesso.</p>
            <p>Protocolo da denúncia:</p>
            <div class="protocolo" id="protocoloNumero"></div>
            <p>Em breve nossa equipe analisará o caso.</p>
        </div>
        <div class="modal-footer">
            <a href="minhas_denuncias.php" class="btn btn-primary">Ver Minhas Denúncias</a>
            <a href="nova_denuncia.php" class="btn btn-secondary">Nova Denúncia</a>
        </div>
    </div>
</div>

<div id="notificacao-container" class="notificacao-container"></div>

<script>
    // ============================================================================
    // VARIÁVEIS GLOBAIS
    // ============================================================================
    let map;
    let marker;
    let userMarker;
    let userLat = null;
    let userLng = null;
    const RAIO_MAXIMO_KM = <?php echo $raioMaximoKm; ?>;

    // ============================================================================
    // NOTIFICAÇÕES — mesmo padrão visual (.notificacao) do resto do site,
    // em vez de alert() nativo do navegador
    // ============================================================================
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

    // ============================================================================
    // DISTÂNCIA ENTRE DUAS COORDENADAS (Haversine, em km)
    // ============================================================================
    function distanciaKm(lat1, lon1, lat2, lon2) {
        const R = 6371;
        const toRad = deg => deg * Math.PI / 180;
        const dLat = toRad(lat2 - lat1);
        const dLon = toRad(lon2 - lon1);
        const a = Math.sin(dLat / 2) ** 2
            + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    // ============================================================================
    // OBTER LOCALIZAÇÃO ATUAL DO USUÁRIO (GPS) — define a área permitida
    // ============================================================================
    function obterLocalizacaoUsuario() {
        const status = document.getElementById('statusLocalizacao');

        if(!navigator.geolocation) {
            status.innerHTML = '<i class="fas fa-times-circle"></i> <span>Seu navegador não suporta geolocalização. Não é possível registrar denúncias.</span>';
            status.classList.add('erro');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            function(position) {
                userLat = position.coords.latitude;
                userLng = position.coords.longitude;

                document.getElementById('usuario_lat').value = userLat.toFixed(6);
                document.getElementById('usuario_lng').value = userLng.toFixed(6);

                status.innerHTML = '<i class="fas fa-check-circle"></i> <span>Localização confirmada.</span>';
                status.classList.add('ok');
                document.getElementById('btnSubmit').disabled = false;

                initMap();
            },
            function(error) {
                status.innerHTML = '<i class="fas fa-times-circle"></i> <span>Não foi possível obter sua localização. Permita o acesso à localização no navegador e recarregue a página.</span>';
                status.classList.add('erro');
                showNotificacao('É necessário permitir o acesso à localização para registrar uma denúncia.', 'error');
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    }
    
    // ============================================================================
    // INICIALIZAÇÃO DO MAPA — centralizado na localização atual do usuário,
    // com o clique/marcação restritos ao raio permitido
    // ============================================================================
    function initMap() {
        // Limites do mundo: impedem o usuário de arrastar/zoomar para fora
        // e, junto com noWrap (abaixo), evitam que o mapa fique repetindo
        // cópias do mundo lado a lado ("duplicando infinitamente")
        const limitesMundo = [[-90, -180], [90, 180]];

        map = L.map('localMapa', {
            maxBounds: limitesMundo,
            maxBoundsViscosity: 1.0
        }).setView([userLat, userLng], 14);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            noWrap: true,
            bounds: limitesMundo
        }).addTo(map);

        // Recalcula o tamanho do mapa depois que o layout termina de assentar
        setTimeout(() => map.invalidateSize(), 150);

        // Marca a posição atual do usuário no mapa, pra referência visual
        userMarker = L.circleMarker([userLat, userLng], {
            radius: 8, color: '#2e7d32', fillColor: '#66bb6a', fillOpacity: 0.9
        }).addTo(map).bindPopup('Você está aqui');

        // Círculo mostrando visualmente a área permitida
        L.circle([userLat, userLng], {
            radius: RAIO_MAXIMO_KM * 1000,
            color: '#2e7d32', fillOpacity: 0.05, weight: 1, dashArray: '4'
        }).addTo(map);
        
        // Se já veio coordenada por parâmetro de URL, tenta marcar (ainda passa pela validação de distância)
        <?php if($temCoordenadas): ?>
        selecionarLocal(<?php echo $lat; ?>, <?php echo $lng; ?>);
        <?php endif; ?>
        
        map.on('click', function(e) {
            selecionarLocal(e.latlng.lat, e.latlng.lng);
        });
    }

    // ============================================================================
    // SELECIONAR UM LOCAL NO MAPA — valida a distância antes de aceitar
    // ============================================================================
    function selecionarLocal(lat, lng) {
        const distancia = distanciaKm(userLat, userLng, lat, lng);

        if(distancia > RAIO_MAXIMO_KM) {
            showNotificacao(`Esse local está a ${Math.round(distancia)} km da sua localização atual. Só é possível denunciar em até ${RAIO_MAXIMO_KM} km.`, 'error');
            return;
        }

        document.getElementById('latitude').value = lat.toFixed(6);
        document.getElementById('longitude').value = lng.toFixed(6);
        document.getElementById('coordDisplay').textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;

        if(marker) map.removeLayer(marker);
        marker = L.marker([lat, lng]).addTo(map);

        document.getElementById('coordInfo').style.display = 'flex';
        document.getElementById('enderecoSelecionado').textContent = 'Buscando endereço...';

        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`)
            .then(response => response.json())
            .then(data => {
                const endereco = data.display_name || `Coordenadas: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                document.getElementById('enderecoSelecionado').textContent = endereco;
                document.getElementById('local_denuncia').value = endereco;
            })
            .catch(() => {
                document.getElementById('enderecoSelecionado').textContent = `Coordenadas: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            });
    }
    
    // ============================================================================
    // BUSCAR ENDEREÇO POR TEXTO OU CEP
    // ============================================================================
    function buscarEndereco(event) {
        if(!userLat || !userLng) {
            showNotificacao('Aguarde a confirmação da sua localização atual.', 'error');
            return;
        }

        const query = document.getElementById('buscaEndereco').value.trim();
        
        if(!query) {
            showNotificacao('Digite um endereço ou CEP', 'error');
            return;
        }
        
        // Mostrar loading
        const btn = event.currentTarget;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';
        btn.disabled = true;
        
        // Se for CEP (apenas números ou formato 00000-000)
        if(query.replace(/\D/g, '').length === 8) {
            // Buscar CEP na nossa API
            fetch(`../backend/api/viacep.php?cep=${query.replace(/\D/g, '')}`)
                .then(response => response.json())
                .then(data => {
                    if(data.erro) {
                        showNotificacao(data.erro, 'error');
                    } else {
                        const enderecoCompleto = `${data.logradouro}, ${data.bairro}, ${data.cidade} - ${data.uf}`;
                        buscarNoMapa(enderecoCompleto);
                    }
                })
                .catch(error => {
                    showNotificacao('Erro ao buscar CEP', 'error');
                })
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
        } else {
            // Buscar endereço normal
            buscarNoMapa(query);
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }
    
    // ============================================================================
    // BUSCAR LOCAL NO MAPA PELO NOMINATIM
    // ============================================================================
    function buscarNoMapa(query) {
        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`)
            .then(response => response.json())
            .then(data => {
                if(data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lon = parseFloat(data[0].lon);

                    const distancia = distanciaKm(userLat, userLng, lat, lon);
                    if(distancia > RAIO_MAXIMO_KM) {
                        showNotificacao(`Esse endereço está a ${Math.round(distancia)} km da sua localização atual. Só é possível denunciar em até ${RAIO_MAXIMO_KM} km.`, 'error');
                        return;
                    }

                    map.setView([lat, lon], 18);
                    selecionarLocal(lat, lon);
                } else {
                    showNotificacao('Endereço não encontrado', 'error');
                }
            })
            .catch(error => {
                showNotificacao('Erro ao buscar endereço', 'error');
            });
    }
    
    // ============================================================================
    // PREVIEW DA FOTO
    // ============================================================================
    document.getElementById('foto')?.addEventListener('change', function(e) {
        const preview = document.getElementById('previewFoto');
        preview.innerHTML = '';
        
        if(this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                preview.appendChild(img);
            }
            reader.readAsDataURL(this.files[0]);
        }
    });
    
    // ============================================================================
    // MOSTRAR CAMPO DE AUTORIDADE
    // ============================================================================
    document.getElementById('notificar_autoridade')?.addEventListener('change', function() {
        const fields = document.getElementById('autoridadeFields');
        fields.style.display = this.checked ? 'block' : 'none';
    });
    
    // ============================================================================
    // MOSTRAR RISCO DO TIPO DE PRAGA
    // ============================================================================
    document.getElementById('tipo_praga')?.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        const risco = selected.dataset.risco;
        const hint = document.getElementById('riscoHint');
        
        if(risco) {
            hint.innerHTML = `Nível de risco: <span class="risco-badge risco-${risco}">${risco}</span>`;
        } else {
            hint.innerHTML = '';
        }
    });
    
    // ============================================================================
    // ENVIO DO FORMULÁRIO VIA AJAX
    // ============================================================================
    document.getElementById('denunciaForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();

        if(!userLat || !userLng) {
            showNotificacao('Não foi possível confirmar sua localização atual. Recarregue a página e permita o acesso à localização.', 'error');
            return;
        }

        // Validar se local foi selecionado (coordenadas)
        const lat = document.getElementById('latitude').value;
        const lng = document.getElementById('longitude').value;

        if(!lat || !lng) {
            showNotificacao('Clique no mapa para selecionar o local da ocorrência.', 'error');
            return;
        }

        // Validar se tipo de praga foi selecionado
        if(!document.getElementById('tipo_praga').value) {
            showNotificacao('Selecione o tipo de praga.', 'error');
            return;
        }
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        
        // Mostrar loading
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...';
        submitBtn.disabled = true;
        
        try {
            const response = await fetch('../backend/denuncias/registrar_denuncia.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if(data.success) {
                // Mostrar modal de confirmação
                document.getElementById('protocoloNumero').textContent = data.protocolo;
                document.getElementById('confirmModal').style.display = 'block';
            } else {
                showNotificacao(data.message || 'Erro ao registrar denúncia', 'error');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        } catch(error) {
            console.error('Erro:', error);
            showNotificacao('Erro ao conectar ao servidor', 'error');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
    
    // ============================================================================
    // FECHAR MODAL CLICANDO FORA
    // ============================================================================
    window.onclick = function(event) {
        const modal = document.getElementById('confirmModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
    
    // ============================================================================
    // MÁSCARA PARA CEP (enquanto digita)
    // ============================================================================
    document.getElementById('buscaEndereco')?.addEventListener('input', function(e) {
        let value = e.target.value;
        
        // Se parece com CEP (só números)
        if(/^\d+$/.test(value.replace(/\D/g, ''))) {
            const nums = value.replace(/\D/g, '');
            if(nums.length > 5) {
                value = nums.substring(0, 5) + '-' + nums.substring(5, 8);
                e.target.value = value;
            }
        }
    });
    
    // ============================================================================
    // INICIALIZAR — pede a localização do usuário assim que a página carrega;
    // o mapa só é montado depois que a localização é confirmada (ver
    // obterLocalizacaoUsuario -> initMap)
    // ============================================================================
    window.addEventListener('load', obterLocalizacaoUsuario);
</script>

<?php include 'includes/footer.php'; ?>