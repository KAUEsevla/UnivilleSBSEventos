<?php
// =========================================================
// Configurações gerais
// =========================================================

// Banco de dados (XAMPP padrão: usuário root sem senha)
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'gestao_eventos');
define('DB_USER', 'root');
define('DB_PASS', '');

define('APP_NOME', 'Eventos Universidade');

date_default_timezone_set('America/Sao_Paulo');

// URL base detectada automaticamente (ex.: /eventos quando a pasta está em htdocs/eventos)
$__raiz = strtolower(str_replace('\\', '/', realpath(__DIR__ . '/..')));
$__doc  = strtolower(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: ''));
define('BASE_URL', ($__doc !== '' && strpos($__raiz, $__doc) === 0)
    ? rtrim(substr(str_replace('\\', '/', realpath(__DIR__ . '/..')), strlen($__doc)), '/')
    : '/eventos');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Erro ao conectar ao banco de dados. Verifique o arquivo config/config.php e se o MySQL do XAMPP está ativo.');
        }
    }
    return $pdo;
}

require_once __DIR__ . '/../includes/functions.php';
