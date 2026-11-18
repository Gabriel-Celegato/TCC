<?php
$pageTitle = "Cadastro - Controle de Pragas";
include 'includes/header.php';
include 'includes/navbar.php';
require_once '../backend/auth/csrf.php'; 

if(isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>

<main class="wizard-wrapper">
    <div class="wizard-card">

        <!-- Header -->
        <div class="wizard-header">
            <i class="fas fa-spider"></i>
            <h2>Criar Conta</h2>
            <p>Sistema de Controle e Gestão de Pragas</p>
        </div>

        <!-- Stepper -->
        <div class="stepper">
            <div class="step-item active" id="step-indicator-1">
                <div class="step-circle"><i class="fas fa-user" style="font-size:0.8rem"></i></div>
                <span class="step-label">Dados</span>
            </div>
            <div class="step-connector" id="connector-1"></div>
            <div class="step-item" id="step-indicator-2">
                <div class="step-circle"><i class="fas fa-map-marker-alt" style="font-size:0.8rem"></i></div>
                <span class="step-label">Endereço</span>
            </div>
            <div class="step-connector" id="connector-2"></div>
            <div class="step-item" id="step-indicator-3">
                <div class="step-circle"><i class="fas fa-lock" style="font-size:0.8rem"></i></div>
                <span class="step-label">Senha</span>
            </div>
        </div>

        <!-- FORM -->
        <form id="registerForm" class="wizard-form">
        <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
            <!-- ===== ETAPA 1: DADOS PESSOAIS ===== -->
            <div class="form-step active" id="step-1">
                <p class="step-title"><i class="fas fa-user"></i> Dados Pessoais</p>

                <div class="form-group">
                    <label><i class="fas fa-user"></i> Nome Completo *</label>
                    <input type="text" id="nome" name="nome" placeholder="Seu nome completo" autocomplete="name">
                    <div class="field-msg" id="msg-nome"></div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email *</label>
                        <input type="email" id="email" name="email" placeholder="voce@email.com" autocomplete="email">
                        <div class="field-msg" id="msg-email"></div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Telefone *</label>
                        <input type="text" id="telefone" name="telefone" placeholder="(11) 99999-9999" maxlength="15" autocomplete="tel">
                        <div class="field-msg" id="msg-telefone"></div>
                    </div>
                </div>

                <div class="form-group" style="max-width:220px">
                    <label><i class="fas fa-calendar"></i> Data de Nascimento *</label>
                    <input type="date" id="data_nascimento" name="data_nascimento" autocomplete="bday">
                    <div class="field-msg" id="msg-nascimento"></div>
                </div>

                <div class="wizard-nav">
                    <button type="button" class="btn btn-primary" id="btn-next-1" onclick="goToStep(2)">
                        Próximo <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- ===== ETAPA 2: ENDEREÇO ===== -->
            <div class="form-step" id="step-2">
                <p class="step-title"><i class="fas fa-map-marker-alt"></i> Endereço</p>

                <div class="form-group" style="max-width:200px">
                    <label><i class="fas fa-mail-bulk"></i> CEP *</label>
                    <div class="cep-input-group">
                        <input type="text" id="cep" name="cep" placeholder="00000-000" maxlength="9">
                        <button type="button" id="buscarCep" class="btn-cep">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <div class="field-msg" id="msg-cep"></div>
                </div>

                <div class="form-group">
                    <label>Logradouro *</label>
                    <input type="text" id="logradouro" name="logradouro" class="field-readonly" placeholder="Rua, Avenida..." readonly>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Número *</label>
                        <input type="text" id="numero" name="numero" placeholder="Ex: 123">
                        <div class="field-msg" id="msg-numero"></div>
                    </div>
                    <div class="form-group">
                        <label>Complemento</label>
                        <input type="text" id="complemento" name="complemento" placeholder="Apto, Casa...">
                    </div>
                </div>

                <div class="form-row three">
                    <div class="form-group">
                        <label>Bairro *</label>
                        <input type="text" id="bairro" name="bairro" class="field-readonly" readonly>
                    </div>
                    <div class="form-group">
                        <label>Cidade *</label>
                        <input type="text" id="cidade" name="cidade" class="field-readonly" readonly>
                    </div>
                    <div class="form-group">
                        <label>UF *</label>
                        <input type="text" id="uf" name="uf" class="field-readonly" readonly maxlength="2">
                    </div>
                </div>

                <div class="wizard-nav">
                    <button type="button" class="btn btn-secondary" onclick="goToStep(1)">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="goToStep(3)">
                        Próximo <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- ===== ETAPA 3: SENHA ===== -->
            <div class="form-step" id="step-3">
                <p class="step-title"><i class="fas fa-lock"></i> Segurança</p>

                <div class="form-group">
                    <label>Senha *</label>
                    <div class="password-input">
                        <input type="password" id="senha" name="senha" placeholder="Mínimo 6 caracteres">
                        <button type="button" class="toggle-password-btn" data-target="senha" aria-label="Mostrar senha"
                            style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; z-index:2; padding:4px;">
                            <i class="fas fa-eye" style="pointer-events:none;"></i>
                        </button>
                    </div>
                    <div class="strength-bar-wrap">
                        <div class="strength-seg" id="seg1"></div>
                        <div class="strength-seg" id="seg2"></div>
                        <div class="strength-seg" id="seg3"></div>
                        <div class="strength-seg" id="seg4"></div>
                    </div>
                    <div class="strength-label" id="strength-label">—</div>
                    <div class="field-msg" id="msg-senha"></div>
                </div>

                <div class="form-group">
                    <label>Confirmar Senha *</label>
                    <div class="password-input">
                        <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="Digite novamente">
                        <button type="button" class="toggle-password-btn" data-target="confirmar_senha" aria-label="Mostrar senha"
                            style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; z-index:2; padding:4px;">
                            <i class="fas fa-eye" style="pointer-events:none;"></i>
                        </button>
                    </div>
                    <div class="field-msg" id="msg-confirmar"></div>
                </div>

                <div class="terms-group">
                    <input type="checkbox" id="termos">
                    <label for="termos">Li e aceito os <a href="#" id="abrirTermos">termos de uso</a></label>
                </div>

                <div class="wizard-nav">
                    <button type="button" class="btn btn-secondary" onclick="goToStep(2)">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btn-submit">
                        <i class="fas fa-user-plus"></i> Finalizar Cadastro
                    </button>
                </div>
            </div>

        </form>

        <div class="wizard-footer">
            Já tem cadastro? <a href="login.php">Faça login</a>
        </div>
    </div>

    <!-- ===== MODAL DE TERMOS DE USO ===== -->
    <div id="modalTermos" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fas fa-file-contract"></i> Termos de Uso — SGDP</h3>
                <button type="button" id="fecharTermos" class="modal-close" aria-label="Fechar">&times;</button>
            </div>
            <div class="modal-content">
                <p><strong>1. Sobre o sistema</strong><br>
                O SGDP (Sistema de Gestão de Denúncia de Pragas) é um projeto de Trabalho de Conclusão de Curso (TCC), com finalidade acadêmica e de gestão de ocorrências de pragas urbanas.</p>

                <p><strong>2. Dados coletados</strong><br>
                Para usar o sistema, coletamos: nome, e-mail, telefone, data de nascimento e endereço (via CEP). Ao registrar uma denúncia, você também poderá enviar fotos e permitir o acesso à sua localização atual via GPS do navegador — usada apenas para vincular a denúncia à sua região, nunca compartilhada com terceiros fora da finalidade do sistema.</p>

                <p><strong>3. Uso dos dados (LGPD)</strong><br>
                Seus dados são usados exclusivamente para: criar e gerenciar sua conta, registrar e acompanhar denúncias, e notificar técnicos/autoridades responsáveis. Não vendemos nem compartilhamos seus dados com terceiros para fins comerciais. Você pode solicitar a exclusão da sua conta e dos seus dados a qualquer momento.</p>

                <p><strong>4. Responsabilidades do usuário</strong><br>
                Ao enviar uma denúncia, você declara que as informações fornecidas são verdadeiras. Denúncias falsas ou enviadas de má-fé podem resultar na suspensão da conta.</p>

                <p><strong>5. Contato</strong><br>
                Dúvidas sobre estes termos ou sobre seus dados podem ser enviadas pelos canais de contato do sistema.</p>

                <p style="opacity:0.7; font-size:0.85rem;">Última atualização: <span id="dataAtualizacaoTermos"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="aceitarTermos">Li e aceito</button>
            </div>
        </div>
    </div>
</main>

<div id="notificacao-container" class="notificacao-container"></div>

<script>
// =============================================
//  WIZARD — Navegação entre etapas
// =============================================
let currentStep = 1;
const totalSteps = 3;

function goToStep(target) {
    // Valida antes de avançar
    if (target > currentStep) {
        if (!validateStep(currentStep)) return;
    }

    const leaving = document.getElementById(`step-${currentStep}`);
    leaving.classList.remove('active', 'slide-back');
    leaving.style.display = 'none';

    const entering = document.getElementById(`step-${target}`);
    entering.style.display = 'block';
    entering.classList.remove('active', 'slide-back');
    void entering.offsetWidth; // force reflow
    entering.classList.add('active');
    if (target < currentStep) entering.classList.add('slide-back');

    // Atualiza indicadores
    updateStepper(currentStep, target);
    currentStep = target;
}

function updateStepper(from, to) {
    for (let i = 1; i <= totalSteps; i++) {
        const item = document.getElementById(`step-indicator-${i}`);
        item.classList.remove('active', 'done');
        if (i < to)       item.classList.add('done');
        else if (i === to) item.classList.add('active');
    }
    for (let i = 1; i < totalSteps; i++) {
        const conn = document.getElementById(`connector-${i}`);
        conn.classList.toggle('done', i < to);
    }
}

// =============================================
//  VALIDAÇÕES POR ETAPA
// =============================================
function validateStep(step) {
    if (step === 1) return validateStep1();
    if (step === 2) return validateStep2();
    return true;
}

function setField(inputId, msgId, ok, msg) {
    const input = document.getElementById(inputId);
    const msgEl = document.getElementById(msgId);
    if (!input) return ok;
    input.classList.toggle('valid',   ok);
    input.classList.toggle('invalid', !ok);
    if (msgEl) {
        msgEl.textContent = msg;
        msgEl.className = `field-msg ${ok ? 'success' : 'error'}`;
    }
    return ok;
}

function validateStep1() {
    let ok = true;

    // Nome
    const nome = document.getElementById('nome').value.trim();
    ok = setField('nome', 'msg-nome',
        nome.length >= 3 && /^[a-zA-ZÀ-ÿ\s]+$/.test(nome),
        nome.length < 3 ? 'Mínimo 3 caracteres' : !/^[a-zA-ZÀ-ÿ\s]+$/.test(nome) ? 'Apenas letras' : '✓ Ok'
    ) && ok;

    // Email
    const email = document.getElementById('email').value.trim();
    const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    ok = setField('email', 'msg-email', emailOk, emailOk ? '✓ Ok' : 'Email inválido') && ok;

    // Telefone
    const tel = document.getElementById('telefone').value.replace(/\D/g, '');
    const telOk = tel.length >= 10 && tel.length <= 11;
    ok = setField('telefone', 'msg-telefone', telOk, telOk ? '✓ Ok' : 'Telefone inválido') && ok;

    // Nascimento
    const nasc = document.getElementById('data_nascimento').value;
    const nascOk = nasc !== '';
    ok = setField('data_nascimento', 'msg-nascimento', nascOk, nascOk ? '✓ Ok' : 'Informe a data de nascimento') && ok;

    return ok;
}

function validateStep2() {
    let ok = true;

    const cep = document.getElementById('cep').value.replace(/\D/g, '');
    ok = setField('cep', 'msg-cep', cep.length === 8, cep.length === 8 ? '✓ Ok' : 'CEP deve ter 8 dígitos') && ok;

    const numero = document.getElementById('numero').value.trim();
    ok = setField('numero', 'msg-numero', numero !== '', numero !== '' ? '✓ Ok' : 'Informe o número') && ok;

    // Verifica se o ViaCEP já preencheu os campos
    const logradouro = document.getElementById('logradouro').value.trim();
    if (!logradouro) {
        showNotificacao('Consulte o CEP antes de continuar.', 'error');
        ok = false;
    }

    return ok;
}

// =============================================
//  MÁSCARAS
// =============================================
document.getElementById('telefone').addEventListener('input', function() {
    let v = this.value.replace(/\D/g, '').slice(0, 11);
    if (v.length <= 10) {
        v = v.replace(/^(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
    } else {
        v = v.replace(/^(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
    }
    this.value = v;
});

document.getElementById('cep').addEventListener('input', function() {
    let v = this.value.replace(/\D/g, '').slice(0, 8);
    this.value = v.length > 5 ? v.replace(/^(\d{5})(\d{0,3})/, '$1-$2') : v;
    if (v.length === 8) buscarCep(v);
});

// =============================================
//  CEP — via proxy do backend (mesmo endpoint que o resto do site usa,
//  em vez de bater direto no ViaCEP a partir do front)
// =============================================
async function buscarCep(cep) {
    const btn   = document.getElementById('buscarCep');
    const msgEl = document.getElementById('msg-cep');
    if (!cep) cep = document.getElementById('cep').value.replace(/\D/g, '');
    if (cep.length !== 8) { 
        msgEl.textContent = 'CEP deve ter 8 dígitos'; 
        msgEl.className = 'field-msg error';
        return; 
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    msgEl.textContent = 'Buscando...';
    msgEl.className = 'field-msg info';

    try {
        const res  = await fetch(`../backend/api/viacep.php?cep=${cep}`);
        const data = await res.json();

        if (data.erro) {
            msgEl.textContent = 'CEP não encontrado';
            msgEl.className = 'field-msg error';
            document.getElementById('cep').classList.add('invalid');
        } else {
            document.getElementById('logradouro').value = data.logradouro || '';
            document.getElementById('bairro').value     = data.bairro     || '';
            document.getElementById('cidade').value     = data.cidade    || '';
            document.getElementById('uf').value         = data.uf         || '';
            msgEl.textContent = '✓ CEP encontrado';
            msgEl.className = 'field-msg success';
            document.getElementById('cep').classList.remove('invalid');
            document.getElementById('cep').classList.add('valid');
            document.getElementById('numero').focus();
        }
    } catch (e) {
        msgEl.textContent = 'Erro ao consultar CEP';
        msgEl.className = 'field-msg error';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-search"></i>';
    }
}

document.getElementById('buscarCep').addEventListener('click', () => buscarCep());

// =============================================
//  FORÇA DA SENHA — progressão terracota (fraca) até oliva escuro (forte),
//  seguindo a paleta do site em vez de vermelho/verde neon genéricos
// =============================================
document.getElementById('senha').addEventListener('input', function() {
    const v = this.value;
    let score = 0;
    if (v.length >= 6)              score++;
    if (/[A-Z]/.test(v))            score++;
    if (/[0-9]/.test(v))            score++;
    if (/[^A-Za-z0-9]/.test(v))     score++;

    const colors = ['', 'var(--error-color)', 'var(--warning-color)', 'var(--secondary-color)', 'var(--primary-color)'];
    const labels = ['', 'Fraca', 'Razoável', 'Boa', 'Forte'];
    const segs   = ['seg1', 'seg2', 'seg3', 'seg4'];

    segs.forEach((id, i) => {
        document.getElementById(id).style.background = i < score ? colors[score] : 'rgba(32,30,27,0.12)';
    });

    const label = document.getElementById('strength-label');
    label.textContent = v.length ? labels[score] : '—';
    label.style.color = v.length ? colors[score] : 'rgba(32,30,27,0.45)';
});

document.getElementById('confirmar_senha').addEventListener('input', function() {
    const senha  = document.getElementById('senha').value;
    const match  = this.value === senha && this.value !== '';
    setField('confirmar_senha', 'msg-confirmar', match, match ? '✓ Senhas conferem' : 'As senhas não coincidem');
});

// =============================================
//  TOGGLE MOSTRAR SENHA
//  Classe renomeada pra .toggle-password-btn de propósito: o main.js global
//  tem seu próprio listener pra .toggle-password, e os dois brigavam no
//  mesmo clique (um mostrava, o outro escondia de novo no mesmo evento).
//  Se ainda não trocou no style.css, duplica a regra .toggle-password lá
//  pra também valer pra .toggle-password-btn (ou renomeia de vez).
// =============================================
document.querySelectorAll('.toggle-password-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.target);
        const icon  = btn.querySelector('i');
        const show  = input.type === 'password';
        input.type  = show ? 'text' : 'password';
        icon.classList.toggle('fa-eye', !show);
        icon.classList.toggle('fa-eye-slash', show);
    });
});

// =============================================
//  SUBMIT
// =============================================
document.getElementById('registerForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    // Validação final da senha
    const senha    = document.getElementById('senha').value;
    const confirm  = document.getElementById('confirmar_senha').value;
    const termos   = document.getElementById('termos').checked;

    if (senha.length < 6 || !/[A-Z]/.test(senha) || !/[0-9]/.test(senha)) {
        showNotificacao('Senha deve ter 6+ caracteres, 1 maiúscula e 1 número.', 'error');
        setField('senha', 'msg-senha', false, 'Senha não atende aos requisitos');
        return;
    }

    if (senha !== confirm) {
        showNotificacao('As senhas não coincidem.', 'error');
        setField('confirmar_senha', 'msg-confirmar', false, 'As senhas não coincidem');
        return;
    }

    if (!termos) {
        showNotificacao('Aceite os termos de uso para continuar.', 'error');
        return;
    }

    const btn = document.getElementById('btn-submit');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cadastrando...';

    try {
        const formData = new FormData(this);
        const res  = await fetch('../backend/auth/register_process.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            showNotificacao(data.message, 'success');
            setTimeout(() => window.location.href = 'login.php', 2000);
        } else {
            showNotificacao(data.message, 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-user-plus"></i> Finalizar Cadastro';
        }
    } catch (err) {
        showNotificacao('Erro de conexão. Tente novamente.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-user-plus"></i> Finalizar Cadastro';
    }
});

// =============================================
//  NOTIFICAÇÕES — usa a classe .notificacao global (style.css),
//  os mesmos estados success/error do resto do site
// =============================================
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

// =============================================
//  MODAL DE TERMOS DE USO
// =============================================
const modalTermos = document.getElementById('modalTermos');

document.getElementById('abrirTermos').addEventListener('click', (e) => {
    e.preventDefault();
    document.getElementById('dataAtualizacaoTermos').textContent =
        new Date().toLocaleDateString('pt-BR');
    modalTermos.style.display = 'flex';
});

document.getElementById('fecharTermos').addEventListener('click', () => {
    modalTermos.style.display = 'none';
});

document.getElementById('aceitarTermos').addEventListener('click', () => {
    document.getElementById('termos').checked = true;
    modalTermos.style.display = 'none';
});

// fecha clicando fora da caixa
modalTermos.addEventListener('click', (e) => {
    if (e.target === modalTermos) modalTermos.style.display = 'none';
});
</script>

<?php include 'includes/footer.php'; ?>