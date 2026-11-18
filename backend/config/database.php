<?php
// ============================================================================
// ARQUIVO: database.php
// DESCRIÇÃO: Classe de conexão com o banco de dados usando .env
// ============================================================================

require_once __DIR__ . '/Env.php';

try {
    Env::load();
} catch(Exception $e) {
    if(Env::get('APP_ENV') === 'development') {
        Env::set('DB_HOST', 'localhost');
        Env::set('DB_PORT', '3306');
        Env::set('DB_NAME', 'tcc_pragas');
        Env::set('DB_USER', 'root');
        Env::set('DB_PASSWORD', '');
    } else {
        die('Arquivo de configuração .env não encontrado');
    }
}

class Database {
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        $this->host = Env::get('DB_HOST');
        $this->port = Env::get('DB_PORT');
        $this->db_name = Env::get('DB_NAME');
        $this->username = Env::get('DB_USER');
        $this->password = Env::get('DB_PASSWORD');
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";

            // CA cert sempre usado quando não for localhost (Aiven exige SSL)
            $ca_path = __DIR__ . '/../../ca.pem';
            $usar_ssl = $this->host !== 'localhost' && $this->host !== '127.0.0.1';

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            if($usar_ssl) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = $ca_path;
                // Verificação do certificado SEMPRE ativa - protege contra MITM
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
            }

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);

        } catch(PDOException $exception) {
            error_log("Erro de conexão com banco: " . $exception->getMessage());
            if(Env::get('DEBUG_MODE') === 'true') {
                die("Erro de conexão: " . $exception->getMessage());
            } else {
                die("Erro ao conectar ao banco de dados. Contate o administrador.");
            }
        }

        return $this->conn;
    }

    public function testConnection() {
        try {
            $conn = $this->getConnection();
            $conn->query("SELECT 1");
            return true;
        } catch(Exception $e) {
            return false;
        }
    }
}
?>