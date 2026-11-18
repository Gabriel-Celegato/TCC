<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

if(isset($_GET['cep'])) {
    $cep = preg_replace('/[^0-9]/', '', $_GET['cep']);

    if(strlen($cep) != 8) {
        http_response_code(400);
        echo json_encode(['erro' => 'CEP inválido']);
        exit();
    }

    $url = "https://viacep.com.br/ws/{$cep}/json/";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Verificação SSL SEMPRE ativa - protege contra MITM
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if($response === false) {
        error_log("Erro cURL viacep: " . $curlError);
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao consultar CEP']);
        exit();
    }

    if($httpCode == 200) {
        $data = json_decode($response, true);

        if(isset($data['erro'])) {
            echo json_encode(['erro' => 'CEP não encontrado']);
        } else {
            echo json_encode([
                'cep' => $data['cep'],
                'logradouro' => $data['logradouro'],
                'bairro' => $data['bairro'],
                'cidade' => $data['localidade'],
                'uf' => $data['uf'],
                'complemento' => $data['complemento']
            ]);
        }
    } else {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao consultar CEP']);
    }
} else {
    http_response_code(400);
    echo json_encode(['erro' => 'CEP não informado']);
}
?>