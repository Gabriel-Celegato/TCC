<?php

session_start();

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/csrf.php';


/*
|--------------------------------------------------------------------------
| CONFIGURAÇÕES
|--------------------------------------------------------------------------
*/

const RATE_LIMIT_MAX_TENTATIVAS = 5;
const RATE_LIMIT_JANELA_MINUTOS = 15;
const RATE_LIMIT_BLOQUEIO_MINUTOS = 15;


/*
|--------------------------------------------------------------------------
| CONEXÃO
|--------------------------------------------------------------------------
*/

try {

    $database = new Database();
    $db = $database->getConnection();

} catch (Exception $e) {

    error_log('Erro de conexão: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor.'
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| MÉTODO
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido.'
    ]);

    exit();
}

csrfRequire();

/*
|--------------------------------------------------------------------------
| IP
|--------------------------------------------------------------------------
*/

$ipCliente = $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';


/*
|--------------------------------------------------------------------------
| GARANTIR TABELA
|--------------------------------------------------------------------------
*/

$db->exec("
    CREATE TABLE IF NOT EXISTS tentativas_login (
        ip VARCHAR(45) PRIMARY KEY,
        tentativas INT NOT NULL DEFAULT 1,
        ultima_tentativa DATETIME NOT NULL,
        bloqueado_ate DATETIME NULL
    ) ENGINE=InnoDB
");


/*
|--------------------------------------------------------------------------
| VERIFICAR BLOQUEIO
|--------------------------------------------------------------------------
|
| IMPORTANTE:
| A comparação do horário é feita pelo próprio MySQL.
| Assim evitamos problemas de fuso horário entre PHP e MySQL.
|
*/

$query = "
    SELECT
        tentativas,
        bloqueado_ate,
        CASE
            WHEN bloqueado_ate IS NOT NULL
                 AND bloqueado_ate > NOW()
            THEN TIMESTAMPDIFF(
                SECOND,
                NOW(),
                bloqueado_ate
            )
            ELSE 0
        END AS segundos_restantes
    FROM tentativas_login
    WHERE ip = :ip
    LIMIT 1
";

$stmt = $db->prepare($query);

$stmt->bindValue(
    ':ip',
    $ipCliente,
    PDO::PARAM_STR
);

$stmt->execute();

$rateLimit = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| BLOQUEIO ATIVO
|--------------------------------------------------------------------------
*/

if (
    $rateLimit &&
    (int)$rateLimit['segundos_restantes'] > 0
) {

    $segundos = (int)$rateLimit['segundos_restantes'];

    $minutos = max(
        1,
        (int)ceil($segundos / 60)
    );

    echo json_encode([
        'success' => false,
        'message' =>
            "Muitas tentativas de login. " .
            "Tente novamente em {$minutos} minuto(s)."
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| LIMPAR BLOQUEIO EXPIRADO
|--------------------------------------------------------------------------
*/

if (
    $rateLimit &&
    !empty($rateLimit['bloqueado_ate'])
) {

    $query = "
        DELETE FROM tentativas_login
        WHERE ip = :ip
          AND bloqueado_ate IS NOT NULL
          AND bloqueado_ate <= NOW()
    ";

    $stmt = $db->prepare($query);

    $stmt->bindValue(
        ':ip',
        $ipCliente,
        PDO::PARAM_STR
    );

    $stmt->execute();
}


/*
|--------------------------------------------------------------------------
| RECEBER DADOS
|--------------------------------------------------------------------------
*/

$email = trim(
    $_POST['email'] ?? ''
);

$senha = $_POST['senha'] ?? '';


/*
|--------------------------------------------------------------------------
| CAMPOS VAZIOS
|--------------------------------------------------------------------------
*/

if ($email === '' || $senha === '') {

    echo json_encode([
        'success' => false,
        'message' => 'Preencha todos os campos.'
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| EMAIL INVÁLIDO
|--------------------------------------------------------------------------
*/

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    registrarFalha(
        $db,
        $ipCliente
    );

    echo json_encode([
        'success' => false,
        'message' => 'Email ou senha incorretos.'
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| BUSCAR USUÁRIO
|--------------------------------------------------------------------------
*/

$query = "
    SELECT
        id,
        nome,
        email,
        senha,
        status
    FROM usuarios
    WHERE email = :email
    LIMIT 1
";

$stmt = $db->prepare($query);

$stmt->bindValue(
    ':email',
    $email,
    PDO::PARAM_STR
);

$stmt->execute();

$user = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| USUÁRIO NÃO EXISTE
|--------------------------------------------------------------------------
*/

if (!$user) {

    registrarFalha(
        $db,
        $ipCliente
    );

    echo json_encode([
        'success' => false,
        'message' => 'Email ou senha incorretos.'
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| USUÁRIO INATIVO
|--------------------------------------------------------------------------
*/

if ($user['status'] !== 'ativo') {

    echo json_encode([
        'success' => false,
        'message' => 'Usuário inativo.'
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| SENHA INCORRETA
|--------------------------------------------------------------------------
*/

if (!password_verify(
    $senha,
    $user['senha']
)) {

    registrarFalha(
        $db,
        $ipCliente
    );

    echo json_encode([
        'success' => false,
        'message' => 'Email ou senha incorretos.'
    ]);

    exit();
}


/*
|--------------------------------------------------------------------------
| LOGIN CORRETO
|--------------------------------------------------------------------------
*/

$query = "
    DELETE FROM tentativas_login
    WHERE ip = :ip
";

$stmt = $db->prepare($query);

$stmt->bindValue(
    ':ip',
    $ipCliente,
    PDO::PARAM_STR
);

$stmt->execute();


/*
|--------------------------------------------------------------------------
| SEGURANÇA DA SESSÃO
|--------------------------------------------------------------------------
*/

session_regenerate_id(true);

$_SESSION['usuario_id'] =
    $user['id'];

$_SESSION['usuario_nome'] =
    $user['nome'];

$_SESSION['usuario_email'] =
    $user['email'];


/*
|--------------------------------------------------------------------------
| SUCESSO
|--------------------------------------------------------------------------
*/

echo json_encode([
    'success' => true,
    'message' => 'Login realizado com sucesso!',
    'redirect' => 'dashboard.php'
]);

exit();


/*
|--------------------------------------------------------------------------
| REGISTRAR FALHA
|--------------------------------------------------------------------------
*/

function registrarFalha(
    PDO $db,
    string $ip
): void {

    /*
    |--------------------------------------------------------------------------
    | BUSCAR REGISTRO ATUAL
    |--------------------------------------------------------------------------
    */

    $query = "
        SELECT
            tentativas,
            ultima_tentativa
        FROM tentativas_login
        WHERE ip = :ip
        LIMIT 1
    ";

    $stmt = $db->prepare($query);

    $stmt->bindValue(
        ':ip',
        $ip,
        PDO::PARAM_STR
    );

    $stmt->execute();

    $registro = $stmt->fetch(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | PRIMEIRA TENTATIVA
    |--------------------------------------------------------------------------
    */

    if (!$registro) {

        $query = "
            INSERT INTO tentativas_login
            (
                ip,
                tentativas,
                ultima_tentativa,
                bloqueado_ate
            )
            VALUES
            (
                :ip,
                1,
                NOW(),
                NULL
            )
        ";

        $stmt = $db->prepare($query);

        $stmt->bindValue(
            ':ip',
            $ip,
            PDO::PARAM_STR
        );

        $stmt->execute();

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | VERIFICAR JANELA DE 15 MINUTOS
    |--------------------------------------------------------------------------
    */

    $query = "
        SELECT
            TIMESTAMPDIFF(
                SECOND,
                ultima_tentativa,
                NOW()
            )
        FROM tentativas_login
        WHERE ip = :ip
    ";

    $stmt = $db->prepare($query);

    $stmt->bindValue(
        ':ip',
        $ip,
        PDO::PARAM_STR
    );

    $stmt->execute();

    $segundos = (int)$stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | JANELA EXPIROU
    |--------------------------------------------------------------------------
    */

    if ($segundos >= (RATE_LIMIT_JANELA_MINUTOS * 60)) {

        $query = "
            UPDATE tentativas_login
            SET
                tentativas = 1,
                ultima_tentativa = NOW(),
                bloqueado_ate = NULL
            WHERE ip = :ip
        ";

        $stmt = $db->prepare($query);

        $stmt->bindValue(
            ':ip',
            $ip,
            PDO::PARAM_STR
        );

        $stmt->execute();

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | INCREMENTAR TENTATIVAS
    |--------------------------------------------------------------------------
    */

    $query = "
        UPDATE tentativas_login
        SET
            tentativas = tentativas + 1,
            ultima_tentativa = NOW()
        WHERE ip = :ip
    ";

    $stmt = $db->prepare($query);

    $stmt->bindValue(
        ':ip',
        $ip,
        PDO::PARAM_STR
    );

    $stmt->execute();


    /*
    |--------------------------------------------------------------------------
    | PEGAR NOVO CONTADOR
    |--------------------------------------------------------------------------
    */

    $query = "
        SELECT tentativas
        FROM tentativas_login
        WHERE ip = :ip
    ";

    $stmt = $db->prepare($query);

    $stmt->bindValue(
        ':ip',
        $ip,
        PDO::PARAM_STR
    );

    $stmt->execute();

    $tentativas = (int)$stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | BLOQUEAR AO ATINGIR 5
    |--------------------------------------------------------------------------
    */

    if ($tentativas >= RATE_LIMIT_MAX_TENTATIVAS) {

        $query = "
            UPDATE tentativas_login
            SET
                bloqueado_ate =
                    DATE_ADD(
                        NOW(),
                        INTERVAL 15 MINUTE
                    )
            WHERE ip = :ip
        ";

        $stmt = $db->prepare($query);

        $stmt->bindValue(
            ':ip',
            $ip,
            PDO::PARAM_STR
        );

        $stmt->execute();
    }
}

?>