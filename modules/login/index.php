<?php
/**
 * Controlador de Login
 * Maneja la autenticación de usuarios
 */

// Incluir archivos necesarios
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Inicializar auth
$auth = new Auth();

// Si ya está autenticado, redirigir al dashboard
if ($auth->isAuthenticated()) {
    redirect(APP_URL . 'index.php?page=dashboard');
}

$error = '';
$success = '';

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || !$auth->verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Token de seguridad inválido. Por favor intente nuevamente.';
    } else {
        $usuario = sanitizeInput($_POST['usuario'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($usuario) || empty($password)) {
            $error = 'Por favor ingrese usuario y contraseña';
        } else {
            $result = $auth->login($usuario, $password);
            
            if ($result['success']) {
                setFlashMessage('¡Bienvenido! Ha iniciado sesión correctamente.', 'success');
                redirect(APP_URL . 'index.php?page=dashboard');
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Incluir vista
include __DIR__ . '/login.php';
