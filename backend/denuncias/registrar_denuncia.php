<?php
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não logado']);
    exit();
}

require_once '../config/database.php';
require_once '../config/config.php'; // precisa estar incluído p/ ALLOWED_EXTENSIONS e MAX_FILE_SIZE

$database = new Database();
$db = $database->getConnection();

// ============================================================================
// PEGAR DADOS DO FORMULÁRIO
// ============================================================================
$usuario_id = $_SESSION['usuario_id'];
$tipo_praga_id = $_POST['tipo_praga'] ?? '';
$quantidade = $_POST['quantidade'] ?? '';
$local = $_POST['local'] ?? '';
$urgencia = $_POST['urgencia'] ?? 'média';
$data_ocorrencia = $_POST['data_ocorrencia'] ?? '';
$descricao = $_POST['descricao'] ?? '';
$notificar_autoridade = isset($_POST['notificar_autoridade']) ? 1 : 0;
$autoridade_nome = $_POST['autoridade_nome'] ?? '';
$autoridade_contato = $_POST['autoridade_contato'] ?? '';

$latitude = $_POST['latitude'] ?? null;
$longitude = $_POST['longitude'] ?? null;

if(empty($tipo_praga_id) || empty($quantidade) || empty($local) || empty($data_ocorrencia)) {
    echo json_encode(['success' => false, 'message' => 'Preencha todos os campos obrigatórios']);
    exit();
}

if(empty($latitude) || empty($longitude)) {
    $endereco_busca = urlencode($local);
    $url = "https://nominatim.openstreetmap.org/search?format=json&q={$endereco_busca}&limit=1";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'ControlePragas/1.0');
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    if(!empty($data)) {
        $latitude = $data[0]['lat'];
        $longitude = $data[0]['lon'];
    }
}

// ============================================================================
// PROCESSAR FOTO - validação real de extensão + mimetype + tamanho
// ============================================================================
$foto_path = null;
if(isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {

    if($_FILES['foto']['size'] > MAX_FILE_SIZE) {
        echo json_encode(['success' => false, 'message' => 'Arquivo excede o tamanho máximo permitido']);
        exit();
    }

    $extensao = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

    if(!in_array($extensao, ALLOWED_EXTENSIONS)) {
        echo json_encode(['success' => false, 'message' => 'Tipo de arquivo não permitido']);
        exit();
    }

    // Checa o mimetype REAL do arquivo (não confia na extensão informada pelo navegador)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_real = finfo_file($finfo, $_FILES['foto']['tmp_name']);
    finfo_close($finfo);

    $mimes_permitidos = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'
    ];

    if(!isset($mimes_permitidos[$extensao]) || $mime_real !== $mimes_permitidos[$extensao]) {
        echo json_encode(['success' => false, 'message' => 'Arquivo não é uma imagem válida']);
        exit();
    }

    // Nome de arquivo gerado pelo servidor - nunca usar o nome enviado pelo usuário
    $nome_arquivo = 'denuncia_' . bin2hex(random_bytes(8)) . '_' . $usuario_id . '.' . $extensao;
    $pasta_upload = __DIR__ . '/../../uploads/denuncias';
    $caminho = $pasta_upload . '/' . $nome_arquivo;

    if(!file_exists($pasta_upload)) {
        mkdir($pasta_upload, 0755, true);
    }

    if(move_uploaded_file($_FILES['foto']['tmp_name'], $caminho)) {
        $foto_path = 'uploads/denuncias/' . $nome_arquivo;
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar a foto']);
        exit();
    }
}

// ============================================================================
// INSERIR DENÚNCIA
// ============================================================================
$query = "INSERT INTO denuncias (
    usuario_id, tipo_praga_id, quantidade, foto_path,
    local_denuncia, latitude, longitude,
    urgencia, data_ocorrencia, descricao,
    autoridade_notificada, autoridade_nome, autoridade_contato,
    status
) VALUES (
    :usuario_id, :tipo_praga_id, :quantidade, :foto_path,
    :local, :latitude, :longitude,
    :urgencia, :data_ocorrencia, :descricao,
    :autoridade_notificada, :autoridade_nome, :autoridade_contato,
    'pendente'
)";

$stmt = $db->prepare($query);
$stmt->bindParam(':usuario_id', $usuario_id);
$stmt->bindParam(':tipo_praga_id', $tipo_praga_id);
$stmt->bindParam(':quantidade', $quantidade);
$stmt->bindParam(':foto_path', $foto_path);
$stmt->bindParam(':local', $local);
$stmt->bindParam(':latitude', $latitude);
$stmt->bindParam(':longitude', $longitude);
$stmt->bindParam(':urgencia', $urgencia);
$stmt->bindParam(':data_ocorrencia', $data_ocorrencia);
$stmt->bindParam(':descricao', $descricao);
$stmt->bindParam(':autoridade_notificada', $notificar_autoridade);
$stmt->bindParam(':autoridade_nome', $autoridade_nome);
$stmt->bindParam(':autoridade_contato', $autoridade_contato);

try {
    if($stmt->execute()) {
        $denuncia_id = $db->lastInsertId();
        $protocolo = 'DEN' . date('Ymd') . str_pad($denuncia_id, 5, '0', STR_PAD_LEFT);

        echo json_encode([
            'success' => true,
            'message' => 'Denúncia registrada com sucesso!',
            'protocolo' => $protocolo,
            'denuncia_id' => $denuncia_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao registrar denúncia']);
    }
} catch(Exception $e) {
    error_log("Erro ao registrar denuncia: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao registrar denúncia']);
}
?>