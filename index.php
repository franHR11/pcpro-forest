<?php
session_start();

// Definir valores por defecto completos
$default_config = [
    'base_url' => '',
    'sitemap_filename' => 'sitemap.xml',
    'sitemap_path' => '../', // Ruta por defecto (un nivel arriba)
    'max_urls' => 50000,
    'crawl_timeout' => 3600,
    'exclude_paths' => [
        '/admin/',
        '/login/',
        '/wp-admin/',
        '/cart/',
        '/checkout/',
        '/private/',
    ],
    'priorities' => [
        'home' => '1.0',
        'category' => '0.8',
        'product' => '0.6',
        'post' => '0.6',
        'other' => '0.4'
    ]
];

// Función para asegurar que todas las claves necesarias existan
function ensureConfigKeys($config, $default_config) {
    foreach ($default_config as $key => $value) {
        if (!isset($config[$key])) {
            $config[$key] = $value;
        }
        if (is_array($value)) {
            $config[$key] = ensureConfigKeys($config[$key], $value);
        }
    }
    return $config;
}

// Cargar e inicializar configuración
$config = $default_config;
if (file_exists('config.php')) {
    $loaded_config = require 'config.php';
    if (is_array($loaded_config)) {
        $config = ensureConfigKeys($loaded_config, $default_config);
    }
}

// Asegurar que sitemap_filename tenga un valor válido
$config['sitemap_filename'] = !empty($config['sitemap_filename']) ? $config['sitemap_filename'] : 'sitemap.xml';

require_once 'generador_sitemap.php';

// Procesar el formulario POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y sanitizar todas las entradas
    $config['base_url'] = filter_input(INPUT_POST, 'base_url', FILTER_SANITIZE_URL) ?: '';
    $config['sitemap_filename'] = filter_input(INPUT_POST, 'sitemap_filename', FILTER_SANITIZE_STRING) ?: 'sitemap.xml';
    $config['max_urls'] = filter_input(INPUT_POST, 'max_urls', FILTER_VALIDATE_INT) ?: 50000;
    $config['crawl_timeout'] = filter_input(INPUT_POST, 'crawl_timeout', FILTER_VALIDATE_INT) ?: 3600;
    
    // Validar y sanitizar la ruta del sitemap
    $config['sitemap_path'] = filter_input(INPUT_POST, 'sitemap_path', FILTER_SANITIZE_STRING) ?: '../';
    $config['sitemap_path'] = rtrim($config['sitemap_path'], '/') . '/';

    // Guardar exclusiones personalizadas con validación
    if (!empty($_POST['exclude_paths'])) {
        $config['exclude_paths'] = array_filter(
            array_map('trim', explode("\n", $_POST['exclude_paths'])),
            'strlen'
        );
    }

    // Guardar prioridades con validación
    if (isset($_POST['priorities']) && is_array($_POST['priorities'])) {
        foreach ($default_config['priorities'] as $key => $default_value) {
            $config['priorities'][$key] = isset($_POST['priorities'][$key]) && 
                                        is_numeric($_POST['priorities'][$key]) && 
                                        $_POST['priorities'][$key] >= 0 && 
                                        $_POST['priorities'][$key] <= 1 
                                            ? (float)$_POST['priorities'][$key] 
                                            : $default_value;
        }
    }

    try {
        $generator = new SitemapGenerator($config);
        $generator->crawlSite();
        $fullPath = $config['sitemap_path'] . $config['sitemap_filename'];
        
        // Verificar si el directorio existe y es escribible
        if (!is_dir($config['sitemap_path'])) {
            throw new Exception("El directorio de destino no existe");
        }
        if (!is_writable($config['sitemap_path'])) {
            throw new Exception("El directorio de destino no tiene permisos de escritura");
        }
        
        $generator->generateXML($fullPath);
        $_SESSION['message'] = "Sitemap generado correctamente en " . $fullPath;
        $_SESSION['message_type'] = 'success';
    } catch (Exception $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = 'error';
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Asegurar que todas las claves necesarias estén definidas
$config = array_merge([
    'base_url' => '',
    'sitemap_filename' => 'sitemap.xml',
    'max_urls' => 50000,
    'crawl_timeout' => 3600,
    'exclude_paths' => [],
    'priorities' => [
        'home' => '1.0',
        'category' => '0.8',
        'product' => '0.6',
        'post' => '0.6',
        'other' => '0.4'
    ]
], $config ?? []);

// Asegurar que los arrays anidados existan
$config['exclude_paths'] = $config['exclude_paths'] ?? [];
$config['priorities'] = $config['priorities'] ?? [];

// Sanitizar valores
$config['base_url'] = htmlspecialchars($config['base_url'] ?? '');
$config['sitemap_filename'] = htmlspecialchars($config['sitemap_filename'] ?? 'sitemap.xml');
$config['max_urls'] = intval($config['max_urls'] ?? 50000);
$config['crawl_timeout'] = intval($config['crawl_timeout'] ?? 3600);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - Generador de Sitemap</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Forest Sitemap Generator</h1>
            <p>Generador profesional de sitemaps XML</p>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                <?php 
                if ($_SESSION['message_type'] === 'success') {
                    echo "¡Sitemap generado correctamente! 🎉<br>";
                    echo "<small>" . $_SESSION['message'] . "</small>";
                } else {
                    echo "⚠️ " . $_SESSION['message'];
                }
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="base_url">URL del Sitio:</label>
                <input type="text" id="base_url" name="base_url" 
                    value="<?php echo htmlspecialchars($config['base_url'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="sitemap_filename">Nombre del Archivo Sitemap:</label>
                <input type="text" id="sitemap_filename" name="sitemap_filename" 
                    value="<?php echo htmlspecialchars($config['sitemap_filename'] ?? 'sitemap.xml'); ?>">
            </div>

            <div class="form-group">
                <label for="sitemap_path">Ruta de destino del Sitemap:</label>
                <input type="text" id="sitemap_path" name="sitemap_path" 
                    value="<?php echo htmlspecialchars($config['sitemap_path'] ?? '../'); ?>"
                    placeholder="../">
                <small>Ruta relativa donde se guardará el sitemap (ej: ../ para la carpeta superior)</small>
            </div>

            <div class="form-group">
                <label for="max_urls">Máximo de URLs:</label>
                <input type="number" id="max_urls" name="max_urls" 
                    value="<?php echo intval($config['max_urls'] ?? 50000); ?>">
            </div>

            <div class="form-group">
                <label for="crawl_timeout">Tiempo máximo de rastreo (segundos):</label>
                <input type="number" id="crawl_timeout" name="crawl_timeout" 
                    value="<?php echo intval($config['crawl_timeout'] ?? 3600); ?>">
            </div>

            <div class="form-group">
                <label for="exclude_paths">Rutas a Excluir (una por línea):</label>
                <textarea id="exclude_paths" name="exclude_paths" rows="5"><?php 
                    echo htmlspecialchars(implode("\n", (array)($config['exclude_paths'] ?? []))); 
                ?></textarea>
            </div>

            <div class="form-group">
                <h3>Prioridades</h3>
                <?php foreach (($config['priorities'] ?? []) as $key => $value): ?>
                <label>
                    <?php echo htmlspecialchars(ucfirst($key)); ?>:
                    <input type="number" name="priorities[<?php echo htmlspecialchars($key); ?>]" 
                        value="<?php echo htmlspecialchars($value); ?>" step="0.1" min="0" max="1">
                </label>
                <?php endforeach; ?>
            </div>

            <button type="submit">Generar Sitemap</button>
        </form>

        <?php 
        if (isset($config['sitemap_path']) && isset($config['sitemap_filename'])):
            $fullPath = $config['sitemap_path'] . $config['sitemap_filename'];
            if (file_exists($fullPath)):
        ?>
        <div class="status">
            <h3>Estado del Sitemap</h3>
            <p>Último sitemap generado: <?php echo date("Y-m-d H:i:s", filemtime($fullPath)); ?></p>
            <p>Tamaño del archivo: <?php echo round(filesize($fullPath)/1024, 2); ?> KB</p>
            <p>Ubicación: <?php echo htmlspecialchars($fullPath); ?></p>
            <p><a href="<?php echo htmlspecialchars($fullPath); ?>" target="_blank">Ver Sitemap</a></p>
        </div>
        <?php 
            endif;
        endif;
        ?>
    </div>
</body>
</html>
