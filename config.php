<?php
return [
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
        '.php',
        '.jpg',
        '.jpeg',
        '.gif',
        '.png',
        '.pdf'
    ],
    'priorities' => [
        'home' => '1.0',
        'category' => '0.8',
        'product' => '0.6',
        'post' => '0.6',
        'other' => '0.4'
    ],
    'default_changefreq' => 'weekly'
];
?>
