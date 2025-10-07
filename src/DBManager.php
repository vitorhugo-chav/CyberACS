<?php

namespace CyberACS;

// Gerencia a conexão com o MariaDB/MySQL
class DBManager
{
  private static ?\PDO $instance = null;
  private const DB_HOST = 'localhost';
  private const DB_NAME = 'cyber_acs_db';
  private const DB_USER = 'cyber_acs_user';
  private const DB_PASS = 'SUA_SENHA_DB'; // <-- MUDAR AQUI!

  // O construtor é privado, forçando o uso do método getInstance (Singleton Pattern - Padrão POO!)
  private function __construct() {}

  // Método estático para obter a única instância de conexão PDO
  public static function getInstance(): \PDO
  {
    if (self::$instance === null) {
      try {
        $dsn = "mysql:host=" . self::DB_HOST . ";dbname=" . self::DB_NAME . ";charset=utf8mb4";
        self::$instance = new \PDO($dsn, self::DB_USER, self::DB_PASS);
        // Configura para lançar exceções em caso de erro SQL
        self::$instance->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
      } catch (\PDOException $e) {
        // Loga o erro, em vez de mostrar na tela
        error_log("Erro de Conexão com DB: " . $e->getMessage(), 0);
        throw new \Exception("Falha na conexão com o banco de dados. " . $e->getMessage());
      }
    }
    return self::$instance;
  }
}
