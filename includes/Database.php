<?php
/**
 * Clase de Conexión a Base de Datos
 * Implementa patrón Singleton para conexión única
 */

class Database {
    private static ?Database $instance = null;
    private mysqli $connection;
    
    /**
     * Constructor privado para implementar Singleton
     */
    private function __construct() {
        $this->connect();
    }
    
    /**
     * Previene clonación de la instancia
     */
    private function __clone() {}
    
    /**
     * Previene deserialización de la instancia
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    /**
     * Obtiene la instancia única de la clase
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Establece la conexión a la base de datos
     */
    private function connect(): void {
        try {
            $this->connection = new mysqli(
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME
            );
            
            if ($this->connection->connect_error) {
                throw new Exception("Error de conexión: " . $this->connection->connect_error);
            }
            
            // Configurar charset utf8mb4
            $this->connection->set_charset(DB_CHARSET);
            
            // Configurar timezone
            $this->connection->query("SET time_zone = '-06:00'");
            
        } catch (Exception $e) {
            error_log($e->getMessage());
            die("Error de conexión a la base de datos. Por favor contacte al administrador.");
        }
    }
    
    /**
     * Obtiene la conexión mysqli
     */
    public function getConnection(): mysqli {
        return $this->connection;
    }
    
    /**
     * Ejecuta una consulta preparada de forma segura
     * @param string $sql Consulta SQL
     * @param string $types Tipos de parámetros (s,i,d,b)
     * @param array $params Parámetros a vincular
     * @return mysqli_stmt|false Statement ejecutado o false en caso de error
     */
    public function query(string $sql, string $types = '', array $params = []): mysqli_stmt|false {
        try {
            $stmt = $this->connection->prepare($sql);
            
            if ($stmt === false) {
                throw new Exception("Error preparando statement: " . $this->connection->error);
            }
            
            if (!empty($types) && !empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            
            return $stmt;
            
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }
    
    /**
     * Ejecuta una consulta SELECT y retorna todos los resultados
     * @param string $sql Consulta SQL
     * @param string $types Tipos de parámetros
     * @param array $params Parámetros a vincular
     * @return array Array de resultados
     */
    public function fetchAll(string $sql, string $types = '', array $params = []): array {
        $stmt = $this->query($sql, $types, $params);
        
        if ($stmt === false) {
            return [];
        }
        
        $result = $stmt->get_result();
        $rows = [];
        
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        
        $stmt->close();
        return $rows;
    }
    
    /**
     * Ejecuta una consulta SELECT y retorna un solo resultado
     * @param string $sql Consulta SQL
     * @param string $types Tipos de parámetros
     * @param array $params Parámetros a vincular
     * @return array|null Resultado o null si no hay datos
     */
    public function fetchOne(string $sql, string $types = '', array $params = []): ?array {
        $stmt = $this->query($sql, $types, $params);
        
        if ($stmt === false) {
            return null;
        }
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row ?: null;
    }
    
    /**
     * Obtiene el último ID insertado
     */
    public function lastInsertId(): int {
        return $this->connection->insert_id;
    }
    
    /**
     * Inicia una transacción
     */
    public function beginTransaction(): bool {
        return $this->connection->begin_transaction();
    }
    
    /**
     * Confirma una transacción
     */
    public function commit(): bool {
        return $this->connection->commit();
    }
    
    /**
     * Revierte una transacción
     */
    public function rollback(): bool {
        return $this->connection->rollback();
    }
    
    /**
     * Escapa caracteres especiales en un string (para casos específicos)
     */
    public function escapeString(string $string): string {
        return $this->connection->real_escape_string($string);
    }
    
    /**
     * Cierra la conexión (generalmente no es necesario por el destructor)
     */
    public function close(): void {
        if (isset($this->connection)) {
            $this->connection->close();
        }
    }
    
    /**
     * Destructor - cierra la conexión automáticamente
     */
    public function __destruct() {
        $this->close();
    }
}
