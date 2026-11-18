<?php
session_start();
require_once '../config/database.php';

if(!isset($_SESSION['usuario_id'])) {
    header("Location: ../../frontend/login.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../frontend/dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$usuario_id = $_SESSION['usuario_id'];
$nome = trim($_POST['nome'] ?? '');
$telefone = preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? '');
$data_nascimento = $_POST['data_nascimento'] ?? '';
$genero = $_POST['genero'] ?? '';

// Validações básicas
if(empty($nome) || empty($telefone) || empty($data_nascimento)) {
    $_SESSION['profile_error'] = 'Preencha todos os campos obrigatórios';
    header("Location: ../../frontend/dashboard.php#edit-perfil");
    exit();
}

// Verificar se vai alterar senha
if(!empty($_POST['nova_senha'])) {
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    // Verificar senha atual
    $query = "SELECT senha FROM usuarios WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $usuario_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!password_verify($senha_atual, $user['senha'])) {
        $_SESSION['profile_error'] = 'Senha atual incorreta';
        header("Location: ../../frontend/dashboard.php#edit-perfil");
        exit();
    }
    
    if($nova_senha !== $confirmar_senha) {
        $_SESSION['profile_error'] = 'As senhas não conferem';
        header("Location: ../../frontend/dashboard.php#edit-perfil");
        exit();
    }
    
    if(strlen($nova_senha) < 6) {
        $_SESSION['profile_error'] = 'A nova senha deve ter no mínimo 6 caracteres';
        header("Location: ../../frontend/dashboard.php#edit-perfil");
        exit();
    }
    
    // Atualizar com senha
    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
    $query = "UPDATE usuarios SET nome = :nome, telefone = :telefone, data_nascimento = :data_nascimento, genero = :genero, senha = :senha WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':senha', $senha_hash);
} else {
    // Atualizar sem senha
    $query = "UPDATE usuarios SET nome = :nome, telefone = :telefone, data_nascimento = :data_nascimento, genero = :genero WHERE id = :id";
    $stmt = $db->prepare($query);
}

$stmt->bindParam(':nome', $nome);
$stmt->bindParam(':telefone', $telefone);
$stmt->bindParam(':data_nascimento', $data_nascimento);
$stmt->bindParam(':genero', $genero);
$stmt->bindParam(':id', $usuario_id);

if($stmt->execute()) {
    // Atualizar nome na sessão
    $_SESSION['usuario_nome'] = $nome;
    $_SESSION['profile_success'] = 'Perfil atualizado com sucesso!';
} else {
    $_SESSION['profile_error'] = 'Erro ao atualizar perfil';
}

header("Location: ../../frontend/dashboard.php");
exit();
?>