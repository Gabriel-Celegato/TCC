<?php
// ============================================================================
// ARQUIVO: Env.php
// DESCRIÇÃO: Classe para carregar variáveis de ambiente do arquivo .env
// ============================================================================

class Env {
    private static $variables = [];
    
    /**
     * Carrega o arquivo .env
     * @param string $path Caminho para o arquivo .env
     */
    public static function load($path = null) {
        if(empty($path)) {
            $path = __DIR__ . '/../../.env';
        }
        
        if(!file_exists($path)) {
            throw new Exception("Arquivo .env não encontrado em: " . $path);
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach($lines as $line) {
            // Ignorar comentários
            if(strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse da linha
            $parts = explode('=', $line, 2);
            if(count($parts) == 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                
                // Remover aspas se houver
                $value = trim($value, '"\'');
                
                self::$variables[$key] = $value;
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
    
    /**
     * Obtém uma variável de ambiente
     * @param string $key Nome da variável
     * @param mixed $default Valor padrão se não existir
     * @return mixed
     */
    public static function get($key, $default = null) {
        return self::$variables[$key] ?? $_ENV[$key] ?? getenv($key) ?: $default;
    }
    
    /**
     * Verifica se uma variável existe
     * @param string $key Nome da variável
     * @return bool
     */
    public static function has($key) {
        return isset(self::$variables[$key]) || isset($_ENV[$key]) || getenv($key) !== false;
    }
    
    /**
     * Define uma variável de ambiente
     * @param string $key Nome da variável
     * @param mixed $value Valor
     */
    public static function set($key, $value) {
        self::$variables[$key] = $value;
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
    
    /**
     * Retorna todas as variáveis
     * @return array
     */
    public static function all() {
        return self::$variables;
    }
}
?>