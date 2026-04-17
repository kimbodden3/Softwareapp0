<?php
/**
 * Funciones Utilitarias del Sistema
 * Funciones comunes para formateo, validaciones y utilidades generales
 */

/**
 * Formatea un número como moneda hondureña (Lempiras)
 * @param float $amount Cantidad a formatear
 * @return string Cantidad formateada
 */
function formatCurrency(float $amount): string {
    return 'L. ' . number_format($amount, 2, '.', ',');
}

/**
 * Formatea una fecha al formato hondureño (DD/MM/YYYY)
 * @param string|DateTime $date Fecha a formatear
 * @return string Fecha formateada
 */
function formatDate(string|DateTime $date): string {
    if (is_string($date)) {
        $date = new DateTime($date);
    }
    return $date->format('d/m/Y');
}

/**
 * Formatea una fecha y hora al formato hondureño
 * @param string|DateTime $datetime Fecha y hora a formatear
 * @return string Fecha y hora formateadas
 */
function formatDateTime(string|DateTime $datetime): string {
    if (is_string($datetime)) {
        $datetime = new DateTime($datetime);
    }
    return $datetime->format('d/m/Y H:i');
}

/**
 * Valida un email
 * @param string $email Email a validar
 * @return bool True si es válido
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valida un número de teléfono (formato Honduras)
 * @param string $phone Teléfono a validar
 * @return bool True si es válido
 */
function isValidPhone(string $phone): bool {
    // Eliminar espacios y guiones
    $phone = preg_replace('/[-\s()]/', '', $phone);
    
    // Debe ser 8 dígitos comenzando con 2, 3, 5, 8 o 9
    return preg_match('/^[23589]\d{7}$/', $phone);
}

/**
 * Sanitiza una entrada de texto
 * @param string $input Texto a sanitizar
 * @return string Texto sanitizado
 */
function sanitizeInput(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Genera un número de factura con formato SAR
 * Formato: ESTABLECIMIENTO-PUNTO EMISIÓN-TIPO-CONSECUTIVO
 * @param string $establecimiento Código de establecimiento (3 dígitos)
 * @param string $puntoEmision Punto de emisión (2 dígitos)
 * @param int $consecutivo Número consecutivo
 * @return string Número de factura formateado
 */
function generateFacturaNumber(string $establecimiento, string $puntoEmision, int $consecutivo): string {
    return sprintf(
        '%03d-%02d-01-%08d',
        intval($establecimiento),
        intval($puntoEmision),
        $consecutivo
    );
}

/**
 * Calcula el ISV (Impuesto Sobre Ventas) para servicios (15%)
 * @param float $amount Monto base
 * @return float ISV calculado
 */
function calculateISVServicios(float $amount): float {
    return round($amount * (ISV_SERVICIOS / 100), 2);
}

/**
 * Calcula el ISV (Impuesto Sobre Ventas) para productos (18%)
 * @param float $amount Monto base
 * @return float ISV calculado
 */
function calculateISVProductos(float $amount): float {
    return round($amount * (ISV_PRODUCTOS / 100), 2);
}

/**
 * Obtiene el siguiente número consecutivo de factura
 * @return int Siguiente consecutivo
 */
function getNextFacturaConsecutive(): int {
    $db = Database::getInstance();
    
    $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(numero_factura, '-', -1) AS UNSIGNED)) as max_consecutivo 
            FROM facturas 
            WHERE estado != 'anulada'";
    
    $result = $db->fetchOne($sql);
    
    return ($result['max_consecutivo'] ?? 0) + 1;
}

/**
 * Obtiene la configuración CAI actual
 * @return array|null Configuración CAI o null
 */
function getCurrentCAIConfig(): ?array {
    $db = Database::getInstance();
    
    $sql = "SELECT * FROM configuracion_cai 
            WHERE estado = 1 
            AND rango_desde <= (SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(numero_factura, '-', -1) AS UNSIGNED)), 0) + 1 FROM facturas)
            AND rango_hasta >= (SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(numero_factura, '-', -1) AS UNSIGNED)), 0) + 1 FROM facturas)
            AND fecha_vencimiento >= CURDATE()
            LIMIT 1";
    
    return $db->fetchOne($sql);
}

/**
 * Redimensiona y guarda una imagen subida
 * @param array $file Archivo subido de $_FILES
 * @param string $targetPath Ruta donde guardar la imagen
 * @param int $maxWidth Ancho máximo
 * @param int $maxHeight Alto máximo
 * @return array Resultado con success y path o error
 */
function processUploadedImage(array $file, string $targetPath, int $maxWidth = 400, int $maxHeight = 400): array {
    // Verificar errores de subida
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Error en la subida del archivo'];
    }
    
    // Verificar tipo MIME
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Tipo de archivo no permitido. Solo JPG, PNG y GIF'];
    }
    
    // Verificar tamaño
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'El archivo excede el tamaño máximo de 2MB'];
    }
    
    // Crear directorio si no existe
    if (!file_exists($targetPath)) {
        mkdir($targetPath, 0755, true);
    }
    
    // Obtener dimensiones originales
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'error' => 'No se pudo procesar la imagen'];
    }
    
    $originalWidth = $imageInfo[0];
    $originalHeight = $imageInfo[1];
    
    // Calcular nuevas dimensiones manteniendo aspect ratio
    $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
    $newWidth = intval($originalWidth * $ratio);
    $newHeight = intval($originalHeight * $ratio);
    
    // Crear imagen redimensionada
    $sourceImage = match($mimeType) {
        'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
        'image/png' => imagecreatefrompng($file['tmp_name']),
        'image/gif' => imagecreatefromgif($file['tmp_name']),
        default => null
    };
    
    if (!$sourceImage) {
        return ['success' => false, 'error' => 'Error al crear la imagen fuente'];
    }
    
    $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Manejar transparencia para PNG y GIF
    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        imagealphablending($resizedImage, false);
        imagesavealpha($resizedImage, true);
        $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
        imagefill($resizedImage, 0, 0, $transparent);
    }
    
    imagecopyresampled(
        $resizedImage,
        $sourceImage,
        0, 0, 0, 0,
        $newWidth, $newHeight,
        $originalWidth, $originalHeight
    );
    
    // Generar nombre único para el archivo
    $filename = uniqid('prod_') . '.jpg';
    $filePath = rtrim($targetPath, '/') . '/' . $filename;
    
    // Guardar como JPEG (mejor compresión)
    $result = imagejpeg($resizedImage, $filePath, 85);
    
    imagedestroy($sourceImage);
    imagedestroy($resizedImage);
    
    if (!$result) {
        return ['success' => false, 'error' => 'Error al guardar la imagen'];
    }
    
    return [
        'success' => true,
        'path' => $filePath,
        'filename' => $filename,
        'url' => APP_URL . 'uploads/productos/' . $filename
    ];
}

/**
 * Muestra un mensaje flash (notificación temporal)
 * @param string $message Mensaje a mostrar
 * @param string $type Tipo de mensaje (success, error, warning, info)
 */
function setFlashMessage(string $message, string $type = 'info'): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Obtiene y elimina un mensaje flash
 * @return array|null Mensaje flash o null
 */
function getFlashMessage(): ?array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    
    return null;
}

/**
 * Redirige a una URL específica
 * @param string $url URL de destino
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Registra un movimiento de inventario
 * @param int $productoId ID del producto
 * @param string $tipo Tipo de movimiento (entrada, salida, ajuste, etc.)
 * @param int $cantidad Cantidad movida
 * @param string $motivo Motivo del movimiento
 * @param int $userId ID del usuario que realiza el movimiento
 * @return bool True si se registró correctamente
 */
function registerInventoryMovement(int $productoId, string $tipo, int $cantidad, string $motivo, int $userId): bool {
    $db = Database::getInstance();
    
    $sql = "INSERT INTO inventario_movimientos (producto_id, tipo, cantidad, motivo, usuario_id) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $db->query($sql, 'isisi', [$productoId, $tipo, $cantidad, $motivo, $userId]);
    
    return $stmt !== false;
}

/**
 * Obtiene el stock actual de un producto
 * @param int $productoId ID del producto
 * @return int Stock actual
 */
function getProductStock(int $productoId): int {
    $db = Database::getInstance();
    
    $sql = "SELECT stock_actual FROM productos WHERE id = ?";
    $result = $db->fetchOne($sql, 'i', [$productoId]);
    
    return $result['stock_actual'] ?? 0;
}

/**
 * Convierte una cadena a slug (URL amigable)
 * @param string $text Texto a convertir
 * @return string Slug generado
 */
function createSlug(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9ñáéíóúü\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    
    return trim($text, '-') ?: uniqid();
}

/**
 * Verifica si una fecha está en un formato válido
 * @param string $date Fecha a verificar
 * @param string $format Formato esperado (por defecto Y-m-d)
 * @return bool True si es válida
 */
function isValidDate(string $date, string $format = 'Y-m-d'): bool {
    $dateTime = DateTime::createFromFormat($format, $date);
    return $dateTime && $dateTime->format($format) === $date;
}

/**
 * Calcula la diferencia entre dos fechas en días
 * @param string $date1 Primera fecha
 * @param string $date2 Segunda fecha
 * @return int Diferencia en días
 */
function dateDiffInDays(string $date1, string $date2): int {
    $datetime1 = new DateTime($date1);
    $datetime2 = new DateTime($date2);
    
    $interval = $datetime1->diff($datetime2);
    
    return $interval->days;
}

/**
 * Obtiene los alertas de stock bajo
 * @return array Productos con stock bajo
 */
function getLowStockAlerts(): array {
    $db = Database::getInstance();
    
    $sql = "SELECT p.*, pr.nombre_empresa as proveedor_nombre
            FROM productos p
            LEFT JOIN proveedores pr ON p.proveedor_id = pr.id
            WHERE p.stock_actual <= p.stock_minimo 
            AND p.estado = 1
            ORDER BY p.stock_actual ASC";
    
    return $db->fetchAll($sql);
}

/**
 * Imprime un array para debugging (solo desarrollo)
 * @param mixed $data Datos a imprimir
 * @param bool $die Si debe terminar la ejecución después de imprimir
 */
function debug(mixed $data, bool $die = true): void {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    
    if ($die) {
        exit;
    }
}
