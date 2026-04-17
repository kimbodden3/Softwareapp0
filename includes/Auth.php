<?php
/**
 * Clase de Autenticación y Gestión de Sesiones
 * Manejo seguro de usuarios, login y permisos
 */

class Auth {
    private Database $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Inicia sesión de usuario con validación de seguridad
     * @param string $usuario Nombre de usuario
     * @param string $password Contraseña en texto plano
     * @return array Resultado del intento de login
     */
    public function login(string $usuario, string $password): array {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Verificar si el usuario existe
        $sql = "SELECT id, nombre_completo, usuario, password_hash, rol, estado, 
                       intentos_fallidos, bloqueo_hasta 
                FROM usuarios 
                WHERE usuario = ? AND estado = 1";
        
        $user = $this->db->fetchOne($sql, 's', [$usuario]);
        
        if (!$user) {
            $this->logAudit(null, $ip, false);
            return ['success' => false, 'message' => 'Usuario o contraseña incorrectos'];
        }
        
        // Verificar si está bloqueado temporalmente
        if ($user['bloqueo_hasta'] && strtotime($user['bloqueo_hasta']) > time()) {
            $this->logAudit($user['id'], $ip, false);
            $minutesLeft = ceil((strtotime($user['bloqueo_hasta']) - time()) / 60);
            return ['success' => false, 'message' => "Cuenta bloqueada por {$minutesLeft} minutos debido a múltiples intentos fallidos"];
        }
        
        // Verificar contraseña
        if (!password_verify($password, $user['password_hash'])) {
            // Incrementar intentos fallidos
            $this->incrementFailedAttempts($user['id']);
            $this->logAudit($user['id'], $ip, false);
            
            return ['success' => false, 'message' => 'Usuario o contraseña incorrectos'];
        }
        
        // Login exitoso - Resetear intentos fallidos
        $this->resetFailedAttempts($user['id']);
        
        // Crear sesión
        session_start();
        session_regenerate_id(true);
        
        $_SESSION[SESSION_NAME] = [
            'user_id' => $user['id'],
            'nombre_completo' => $user['nombre_completo'],
            'usuario' => $user['usuario'],
            'rol' => $user['rol'],
            'last_activity' => time(),
            'ip_address' => $ip
        ];
        
        // Actualizar último acceso
        $this->updateLastAccess($user['id']);
        
        // Registrar auditoría
        $this->logAudit($user['id'], $ip, true);
        
        return ['success' => true, 'message' => 'Login exitoso'];
    }
    
    /**
     * Cierra la sesión del usuario actual
     */
    public function logout(): void {
        session_start();
        session_unset();
        session_destroy();
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
    }
    
    /**
     * Verifica si el usuario está autenticado
     * @return bool True si está autenticado
     */
    public function isAuthenticated(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION[SESSION_NAME])) {
            return false;
        }
        
        // Verificar timeout de sesión
        if (time() - $_SESSION[SESSION_NAME]['last_activity'] > SESSION_TIMEOUT) {
            $this->logout();
            return false;
        }
        
        // Actualizar última actividad
        $_SESSION[SESSION_NAME]['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Verifica si el usuario tiene el rol requerido
     * @param string|array $roles Rol o array de roles permitidos
     * @return bool True si tiene permiso
     */
    public function hasRole(string|array $roles): bool {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        if (is_string($roles)) {
            $roles = [$roles];
        }
        
        return in_array($_SESSION[SESSION_NAME]['rol'], $roles);
    }
    
    /**
     * Obtiene el ID del usuario actual
     * @return int|null ID del usuario o null si no está autenticado
     */
    public function getCurrentUserId(): ?int {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        return $_SESSION[SESSION_NAME]['user_id'];
    }
    
    /**
     * Obtiene los datos del usuario actual
     * @return array|null Datos del usuario o null
     */
    public function getCurrentUser(): ?array {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        return $_SESSION[SESSION_NAME];
    }
    
    /**
     * Requiere autenticación para acceder a una página
     * Redirige al login si no está autenticado
     */
    public function requireAuth(): void {
        if (!$this->isAuthenticated()) {
            header('Location: ' . APP_URL . 'modules/login/login.php');
            exit;
        }
    }
    
    /**
     * Requiere un rol específico para acceder
     * @param string|array $roles Roles permitidos
     */
    public function requireRole(string|array $roles): void {
        $this->requireAuth();
        
        if (!$this->hasRole($roles)) {
            header('HTTP/1.0 403 Forbidden');
            die('Acceso denegado. No tiene permisos suficientes.');
        }
    }
    
    /**
     * Incrementa los intentos fallidos de login
     */
    private function incrementFailedAttempts(int $userId): void {
        $sql = "UPDATE usuarios 
                SET intentos_fallidos = intentos_fallidos + 1,
                    bloqueo_hasta = CASE 
                        WHEN intentos_fallidos + 1 >= ? 
                        THEN DATE_ADD(NOW(), INTERVAL ? SECOND)
                        ELSE NULL
                    END
                WHERE id = ?";
        
        $this->db->query($sql, 'iis', [MAX_LOGIN_ATTEMPTS, LOCKOUT_TIME, $userId]);
    }
    
    /**
     * Resetea los intentos fallidos después de login exitoso
     */
    private function resetFailedAttempts(int $userId): void {
        $sql = "UPDATE usuarios 
                SET intentos_fallidos = 0, bloqueo_hasta = NULL 
                WHERE id = ?";
        
        $this->db->query($sql, 'i', [$userId]);
    }
    
    /**
     * Actualiza el timestamp del último acceso
     */
    private function updateLastAccess(int $userId): void {
        $sql = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?";
        $this->db->query($sql, 'i', [$userId]);
    }
    
    /**
     * Registra un intento de login en la auditoría
     */
    private function logAudit(?int $userId, string $ip, bool $exitoso): void {
        $sql = "INSERT INTO auditoria_login (usuario_id, ip_address, intento_exitoso) 
                VALUES (?, ?, ?)";
        
        $userIdParam = $userId ?? null;
        $exitosoParam = $exitoso ? 1 : 0;
        
        $this->db->query($sql, 'isi', [$userIdParam, $ip, $exitosoParam]);
    }
    
    /**
     * Genera un token CSRF para protección de formularios
     * @return string Token CSRF
     */
    public function generateCSRFToken(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        
        return $token;
    }
    
    /**
     * Verifica un token CSRF
     * @param string $token Token a verificar
     * @return bool True si es válido
     */
    public function verifyCSRFToken(string $token): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Valida formato de RTN hondureño (14 dígitos)
     * @param string $rtn RTN a validar
     * @return bool True si es válido
     */
    public static function validateRTN(string $rtn): bool {
        // Eliminar guiones y espacios
        $rtn = preg_replace('/[-\s]/', '', $rtn);
        
        // Debe ser exactamente 14 dígitos numéricos
        if (!preg_match('/^\d{14}$/', $rtn)) {
            return false;
        }
        
        // Algoritmo de validación de RTN (módulo 10)
        $weights = [7, 6, 5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        
        for ($i = 0; $i < 12; $i++) {
            $sum += intval($rtn[$i]) * $weights[$i];
        }
        
        $remainder = $sum % 11;
        $checkDigit = 11 - $remainder;
        
        if ($checkDigit === 10) {
            $checkDigit = 0;
        } elseif ($checkDigit === 11) {
            $checkDigit = 1;
        }
        
        return intval($rtn[12]) === $checkDigit;
    }
}
