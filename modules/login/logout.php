<?php
/**
 * Logout - Cerrar Sesión
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Auth.php';

$auth = new Auth();
$auth->logout();

redirect(APP_URL . 'modules/login/index.php');
