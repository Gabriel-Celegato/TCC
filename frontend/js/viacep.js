// ViaCEP Integration
document.addEventListener('DOMContentLoaded', function() {
    const cepInput = document.getElementById('cep');
    const buscarCepBtn = document.getElementById('buscarCep');
    const cepLoading = document.querySelector('.cep-loading');
    
    // Máscara do CEP
    cepInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 5) {
            value = value.substring(0, 5) + '-' + value.substring(5, 8);
        }
        e.target.value = value;
    });
    
    // Buscar CEP ao clicar no botão
    buscarCepBtn.addEventListener('click', function() {
        buscarEnderecoPorCep();
    });
    
    // Buscar CEP ao sair do campo (se tiver 8 dígitos)
    cepInput.addEventListener('blur', function() {
        const cepLimpo = this.value.replace(/\D/g, '');
        if (cepLimpo.length === 8) {
            buscarEnderecoPorCep();
        }
    });
    
    function buscarEnderecoPorCep() {
        const cep = cepInput.value.replace(/\D/g, '');
        
        if (cep.length !== 8) {
            mostrarNotificacao('CEP inválido! Digite um CEP com 8 números.', 'error');
            return;
        }
        
        // Mostrar loading
        cepLoading.style.display = 'block';
        buscarCepBtn.disabled = true;
        
        // Fazer requisição para nossa API
        fetch(`../backend/api/viacep.php?cep=${cep}`)
            .then(response => response.json())
            .then(data => {
                if (data.erro) {
                    mostrarNotificacao(data.erro, 'error');
                    limparCamposEndereco();
                } else {
                    preencherCamposEndereco(data);
                    mostrarNotificacao('CEP encontrado!', 'success');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarNotificacao('Erro ao buscar CEP. Tente novamente.', 'error');
                limparCamposEndereco();
            })
            .finally(() => {
                cepLoading.style.display = 'none';
                buscarCepBtn.disabled = false;
            });
    }
    
    function preencherCamposEndereco(data) {
        document.getElementById('logradouro').value = data.logradouro || '';
        document.getElementById('bairro').value = data.bairro || '';
        document.getElementById('cidade').value = data.cidade || '';
        document.getElementById('uf').value = data.uf || '';
        document.getElementById('complemento').value = data.complemento || '';
        
        // Focar no número após preencher
        document.getElementById('numero').focus();
    }
    
    function limparCamposEndereco() {
        document.getElementById('logradouro').value = '';
        document.getElementById('bairro').value = '';
        document.getElementById('cidade').value = '';
        document.getElementById('uf').value = '';
        document.getElementById('complemento').value = '';
    }
    
    function mostrarNotificacao(mensagem, tipo) {
        // Criar elemento de notificação
        const notificacao = document.createElement('div');
        notificacao.className = `notificacao ${tipo}`;
        notificacao.innerHTML = `
            <i class="fas ${tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            <span>${mensagem}</span>
        `;
        
        document.body.appendChild(notificacao);
        
        // Remover após 3 segundos
        setTimeout(() => {
            notificacao.classList.add('fade-out');
            setTimeout(() => notificacao.remove(), 300);
        }, 3000);
    }
});

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

// Validação de data de nascimento (maior de 16 anos)
const dataNascimento = document.getElementById('data_nascimento');
if(dataNascimento) {
    dataNascimento.addEventListener('change', function() {
        const data = new Date(this.value);
        const hoje = new Date();
        const idade = hoje.getFullYear() - data.getFullYear();
        const mes = hoje.getMonth() - data.getMonth();
        
        if (mes < 0 || (mes === 0 && hoje.getDate() < data.getDate())) {
            idade--;
        }
        
        if (idade < 16) {
            mostrarNotificacao('Você deve ter pelo menos 16 anos para se cadastrar.', 'error');
            this.value = '';
        }
    });
}