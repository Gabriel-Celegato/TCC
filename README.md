# SGDP — Sistema de Gerenciamento e Denúncias de Pragas Urbanas

<p align="center">
  <strong>Uma plataforma web para facilitar a comunicação entre cidadãos e responsáveis pelo controle de pragas urbanas.</strong>
</p>

---

## Sobre o projeto

O **SGDP (Sistema de Gerenciamento e Denúncias de Pragas Urbanas)** é um projeto desenvolvido como Trabalho de Conclusão de Curso (TCC) na ETEC Ferrúcio Humberto Gazzetta, em 2026.

A plataforma foi idealizada para facilitar o registro de ocorrências envolvendo pragas urbanas, como ratos, escorpiões, mosquitos e outros animais que podem representar riscos à saúde pública.

Seu principal propósito é estabelecer uma conexão entre a população e os órgãos responsáveis pelo controle dessas ocorrências, permitindo que os cidadãos registrem denúncias e acompanhem seu andamento, enquanto os administradores podem visualizar, organizar e gerenciar os registros.

O projeto também está relacionado ao **ODS 3 da ONU — Saúde e Bem-Estar**, buscando contribuir para ambientes urbanos mais seguros e salubres.

## Objetivos

- Facilitar o registro de denúncias relacionadas às pragas urbanas.
- Aproximar os cidadãos dos órgãos responsáveis pelo controle dessas ocorrências.
- Permitir o acompanhamento do andamento das denúncias.
- Disponibilizar ferramentas administrativas para gerenciamento das ocorrências.
- Auxiliar na identificação de regiões com maior concentração de denúncias.
- Contribuir para a conscientização sobre os impactos das pragas urbanas na saúde pública.

## Funcionalidades

### Cidadão

- Cadastro de usuários.
- Autenticação e gerenciamento de sessão.
- Edição de informações pessoais.
- Gerenciamento de endereços.
- Preenchimento automático de endereços por CEP.
- Registro de denúncias de pragas urbanas.
- Envio de fotografias das ocorrências.
- Seleção da localização por meio de mapa interativo.
- Consulta ao histórico de denúncias.
- Acompanhamento do status das ocorrências.
- Visualização de comentários e devolutivas dos administradores.

### Administrador

- Painel administrativo.
- Visualização e gerenciamento das denúncias.
- Visualização de ocorrências em mapa.
- Mapa de calor e agrupamento de marcadores.
- Alteração do status das denúncias.
- Inserção de comentários e justificativas.
- Acompanhamento do histórico das ocorrências.
- Registro de atividades administrativas.

## Tecnologias utilizadas

| Tecnologia | Aplicação |
|---|---|
| HTML5 | Estrutura das páginas |
| CSS3 | Estilização e responsividade |
| JavaScript | Interatividade e validações |
| PHP | Processamento e lógica do sistema |
| MySQL | Gerenciamento do banco de dados |
| PDO | Comunicação com o banco de dados |
| Leaflet | Mapas interativos |
| OpenStreetMap / Nominatim | Geolocalização |
| ViaCEP | Consulta e preenchimento de endereços |
| Aiven | Hospedagem do banco de dados |

## Segurança

A segurança foi considerada durante o desenvolvimento do SGDP, com a implementação de mecanismos destinados à proteção das informações e ao controle de acesso.

Entre as medidas utilizadas, destacam-se:

- **Hash de senhas:** utilização de `password_hash()` com bcrypt.
- **Prepared Statements:** consultas parametrizadas por meio de PDO, reduzindo riscos de SQL Injection.
- **Proteção CSRF:** utilização de tokens para validar requisições sensíveis.
- **Validação de dados:** verificações no backend antes do processamento das informações.
- **Controle de acesso:** restrição de funcionalidades administrativas conforme o nível de permissão.
- **Proteção de informações:** utilização de variáveis de ambiente para armazenar configurações e credenciais.
- **Conexão SSL:** comunicação criptografada com o banco de dados hospedado na Aiven.
- **Registros de atividades:** histórico de ações administrativas e alterações nas denúncias.

## Estrutura do projeto

```text
TCC/
│
├── backend/
│   ├── admin/
│   ├── api/
│   ├── auth/
│   ├── config/
│   ├── denuncias/
│   └── validations/
│
├── frontend/
│   ├── admin/
│   ├── css/
│   ├── includes/
│   ├── js/
│   ├── index.php
│   ├── login.php
│   ├── register.php
│   ├── dashboard.php
│   ├── nova_denuncia.php
│   └── minhas_denuncias.php
│
├── config.php
├── .gitignore
└── sistema_controle_pragas.pdf
```

## Pesquisa de campo

Durante o desenvolvimento do projeto, foi realizada uma pesquisa de campo por meio do Google Forms, contando com **54 participantes**.

Os resultados auxiliaram na identificação das necessidades da população e na definição das funcionalidades do sistema.

Alguns resultados relevantes:

| Indicador | Resultado |
|---|---:|
| Participantes que resolvem a presença de pragas por conta própria | 74,1% |
| Interesse em receber alertas sobre pragas na região | 75,9% |
| Interesse em acompanhar o status das denúncias | 74,1% |
| Interesse em enviar fotografias das ocorrências | 70,4% |
| Participantes que utilizariam um sistema digital de denúncias | 63% |
| Participantes que consideram útil um mapa de ocorrências | 81,5% |

Os dados foram utilizados como referência para orientar o desenvolvimento e identificar possíveis melhorias.

## Como executar o projeto

### 1. Pré-requisitos

- PHP com suporte a PDO MySQL.
- MySQL ou MariaDB.
- Servidor web, como Apache.
- XAMPP ou ambiente equivalente.
- Extensões PHP necessárias para as integrações utilizadas.

### 2. Clonar o repositório

```bash
git clone https://github.com/Gabriel-Celegato/TCC.git
```

### 3. Configurar o ambiente

Coloque a pasta do projeto no diretório do servidor web.

No XAMPP, por exemplo:

```text
C:\xampp\htdocs\TCC
```

Crie um arquivo `.env` na raiz do projeto, utilizando o seguinte modelo:

```env
APP_ENV=development
DEBUG_MODE=true

SITE_NAME=Controle de Pragas
ADMIN_EMAIL=admin@sistema.com

DB_HOST=localhost
DB_PORT=3306
DB_NAME=tcc_pragas
DB_USER=root
DB_PASSWORD=

TIMEZONE=America/Sao_Paulo
```

**Importante:** ajuste as configurações conforme o ambiente utilizado. Não compartilhe suas credenciais reais.

### 4. Configurar o banco de dados

Crie o banco de dados MySQL e configure suas tabelas conforme o modelo de dados do projeto (MER).

Caso utilize o banco hospedado na Aiven, configure as credenciais de conexão e disponibilize o certificado CA necessário no ambiente de execução.

O arquivo `ca.pem` não é disponibilizado neste repositório por ser um arquivo de configuração externa.

### 5. Executar o projeto

Inicie o Apache e o MySQL pelo XAMPP.

Acesse pelo navegador:

```text
http://localhost/TCC/
```

---

## Limitações e trabalhos futuros

O SGDP foi desenvolvido como uma proposta de ferramenta para auxiliar no gerenciamento de ocorrências de pragas urbanas. Entretanto, algumas limitações foram identificadas durante o desenvolvimento.

Entre as melhorias propostas para versões futuras estão:

- Implementação de alertas sobre ocorrências por região.
- Inclusão de orientações e dicas de prevenção.
- Disponibilização de um mapa público de ocorrências.
- Desenvolvimento de uma aplicação móvel.
- Realização de testes de usabilidade com usuários reais.
- Avaliação e aprimoramento da adequação à Lei Geral de Proteção de Dados (LGPD).
- Possível integração com órgãos públicos e empresas especializadas.

**Observação:** o sistema não foi integrado a órgãos públicos ou empresas de dedetização, nem submetido a testes com usuários reais durante a elaboração do trabalho.

## Equipe de desenvolvimento

Projeto desenvolvido por:

- **Gabriel Henrique Celegato de Oliveira**
- **João Vitor Rodrigues da Cruz**
- **Miguel Manochio Florentino**

**Instituição:** ETEC Ferrúcio Humberto Gazzetta  
**Local:** Nova Odessa — SP  
**Ano:** 2026

---

<p align="center">
  <strong>SGDP — Tecnologia a serviço da saúde pública.</strong>
</p>