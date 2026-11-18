// Aguardar o DOM carregar completamente
document.addEventListener('DOMContentLoaded', function() {
    console.log('validations.js carregado!'); // Para debug
    
    const form = document.getElementById('registerForm');
    
    if(form) {
        console.log('Formulário encontrado!'); // Para debug
        
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            console.log('Formulário submetido!'); // Para debug
            
            // Limpar mensagens anteriores
            document.querySelectorAll('.error-message').forEach(el => el.remove());
            document.querySelectorAll('.invalid').forEach(el => el.classList.remove('invalid'));
            
            // Validar campos
            let isValid = true;
            
            // Validar nome
            const nome = document.getElementById('nome');
            if(!validarNome(nome.value)) {
                mostrarErro(nome, 'Nome deve ter pelo menos 3 caracteres');
                isValid = false;
            }
            
            // Validar email
            const email = document.getElementById('email');
            if(!validarEmail(email.value)) {
                mostrarErro(email, 'Email inválido');
                isValid = false;
            }
            
            // Validar telefone
            const telefone = document.getElementById('telefone');
            if(!validarTelefone(telefone.value)) {
                mostrarErro(telefone, 'Telefone inválido');
                isValid = false;
            }
            
            // Validar data nascimento
            const dataNasc = document.getElementById('data_nascimento');
            if(!dataNasc.value) {
                mostrarErro(dataNasc, 'Data de nascimento obrigatória');
                isValid = false;
            }
            
            // Validar CEP
            const cep = document.getElementById('cep');
            if(!validarCEP(cep.value)) {
                mostrarErro(cep, 'CEP inválido');
                isValid = false;
            }
            
            // Validar logradouro
            const logradouro = document.getElementById('logradouro');
            if(!logradouro.value) {
                mostrarErro(logradouro, 'Logradouro obrigatório');
                isValid = false;
            }
            
            // Validar número
            const numero = document.getElementById('numero');
            if(!numero.value.trim()) {
                mostrarErro(numero, 'Número é obrigatório');
                isValid = false;
            }
            
            // Validar bairro
            const bairro = document.getElementById('bairro');
            if(!bairro.value) {
                mostrarErro(bairro, 'Bairro obrigatório');
                isValid = false;
            }
            
            // Validar cidade
            const cidade = document.getElementById('cidade');
            if(!cidade.value) {
                mostrarErro(cidade, 'Cidade obrigatória');
                isValid = false;
            }
            
            // Validar UF
            const uf = document.getElementById('uf');
            if(!uf.value) {
                mostrarErro(uf, 'UF obrigatória');
                isValid = false;
            }
            
            // Validar senha
            const senha = document.getElementById('senha');
            if(!validarSenha(senha.value)) {
                mostrarErro(senha, 'Senha deve ter 6+ caracteres, 1 maiúscula e 1 número');
                isValid = false;
            }
            
            // Validar confirmação de senha
            const confirmar = document.getElementById('confirmar_senha');
            if(senha.value !== confirmar.value) {
                mostrarErro(confirmar, 'Senhas não conferem');
                isValid = false;
            }
            
            // Validar termos
            const termos = document.getElementById('termos');
            if(!termos.checked) {
                mostrarErro(termos, 'Você deve aceitar os termos');
                isValid = false;
            }
            
            if(isValid) {
                console.log('Formulário válido, enviando...'); // Para debug
                
                // Mostrar loading no botão
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cadastrando...';
                submitBtn.disabled = true;
                
                // Coletar dados do formulário
                const formData = new FormData(form);
                
                try {
                    const response = await fetch('../backend/auth/register_process.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    console.log('Resposta:', data); // Para debug
                    
                    if(data.success) {
                        mostrarNotificacao('Cadastro realizado com sucesso!', 'success');
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 2000);
                    } else {
                        mostrarNotificacao(data.message, 'error');
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                } catch(error) {
                    console.error('Erro:', error);
                    mostrarNotificacao('Erro ao conectar ao servidor', 'error');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            } else {
                console.log('Formulário inválido'); // Para debug
            }
        });
    } else {
        console.log('Formulário NÃO encontrado!'); // Para debug
    }
});

// Funções de validação
function validarNome(nome) {
    return nome.trim().length >= 3;
}

function validarEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function validarTelefone(telefone) {
    const nums = telefone.replace(/\D/g, '');
    return nums.length >= 10 && nums.length <= 11;
}

function validarCEP(cep) {
    const nums = cep.replace(/\D/g, '');
    return nums.length === 8;
}

function validarSenha(senha) {
    return senha.length >= 6 && 
           /[A-Z]/.test(senha) && 
           /[0-9]/.test(senha);
}

function mostrarErro(campo, mensagem) {
    campo.classList.add('invalid');
    
    const erro = document.createElement('span');
    erro.className = 'error-message';
    erro.textContent = mensagem;
    erro.style.color = '#d63031';
    erro.style.fontSize = '0.8rem';
    erro.style.marginTop = '0.2rem';
    erro.style.display = 'block';
    
    // Inserir após o campo
    if(campo.type === 'checkbox') {
        campo.parentNode.appendChild(erro);
    } else {
        campo.parentNode.appendChild(erro);
    }
}

function mostrarNotificacao(mensagem, tipo) {
    const container = document.getElementById('notificacao-container');
    
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

// Buscar CEP
const buscarCepBtn = document.getElementById('buscarCep');
if(buscarCepBtn) {
    buscarCepBtn.addEventListener('click', function() {
        const cep = document.getElementById('cep').value.replace(/\D/g, '');
        
        if(cep.length !== 8) {
            mostrarNotificacao('CEP inválido!', 'error');
            return;
        }
        
        buscarCepBtn.disabled = true;
        buscarCepBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        fetch(`../backend/api/viacep.php?cep=${cep}`)
            .then(response => response.json())
            .then(data => {
                if(data.erro) {
                    mostrarNotificacao(data.erro, 'error');
                } else {
                    document.getElementById('logradouro').value = data.logradouro || '';
                    document.getElementById('bairro').value = data.bairro || '';
                    document.getElementById('cidade').value = data.cidade || '';
                    document.getElementById('uf').value = data.uf || '';
                    mostrarNotificacao('CEP encontrado!', 'success');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarNotificacao('Erro ao buscar CEP', 'error');
            })
            .finally(() => {
                buscarCepBtn.disabled = false;
                buscarCepBtn.innerHTML = '<i class="fas fa-search"></i>';
            });
    });
}

// Máscara de telefone
const telefoneInput = document.getElementById('telefone');
if(telefoneInput) {
    telefoneInput.addEventListener('input', function(e) {
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
}

// Máscara de CEP
const cepInput = document.getElementById('cep');
if(cepInput) {
    cepInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 5) {
            value = value.substring(0, 5) + '-' + value.substring(5, 8);
        }
        e.target.value = value;
    });
}

// Toggle password visibility
document.querySelectorAll('.toggle-password').forEach(icon => {
    icon.addEventListener('click', function() {
        const input = this.previousElementSibling;
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });
});

// Password strength
const senhaInput = document.getElementById('senha');
if(senhaInput) {
    senhaInput.addEventListener('input', function() {
        const password = this.value;
        const strengthBar = document.querySelector('.strength-bar');
        
        if(strengthBar) {
            let strength = 0;
            if(password.length >= 6) strength += 33.33;
            if(/[A-Z]/.test(password)) strength += 33.33;
            if(/[0-9]/.test(password)) strength += 33.34;
            
            strengthBar.style.width = strength + '%';
            
            if(strength <= 33.33) {
                strengthBar.className = 'strength-bar weak';
            } else if(strength <= 66.66) {
                strengthBar.className = 'strength-bar medium';
            } else {
                strengthBar.className = 'strength-bar strong';
            }
        }
    });
}