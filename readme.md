# Generador de Sitemap XML

Un generador de sitemap XML profesional con panel de control para sitios web PHP. Esta herramienta permite crear y gestionar automáticamente sitemaps XML con una interfaz visual intuitiva.

## Características

- Panel de control visual para gestionar la configuración
- Rastreo automático de sitio web configurable
- Control de tiempo máximo de rastreo y límite de URLs
- Asignación automática de prioridades personalizable
- Exclusión de rutas configurables desde la interfaz
- Monitoreo del estado del sitemap generado
- Soporte para atributos lastmod, changefreq y priority
- Generación de XML formateado y válido

## Instalación

1. Descarga todos los archivos del generador:
   - generador_sitemap.php
   - index.php
   - config.php
   - style.css
   - README.md

2. Coloca los archivos en tu servidor web

3. Accede al panel de control mediante:
```
http://tudominio.com/ruta/al/generador/index.php
```

## Panel de Control

El panel de control permite configurar:

- URL base del sitio
- Nombre del archivo sitemap
- Número máximo de URLs a incluir
- Tiempo máximo de rastreo
- Rutas a excluir
- Prioridades por tipo de contenido

### Configuración Disponible

- **URL del Sitio**: URL base para el rastreo
- **Nombre del Archivo**: Nombre del sitemap.xml
- **Máximo de URLs**: Límite de URLs a incluir (default: 50000)
- **Tiempo máximo**: Límite de tiempo de rastreo en segundos (default: 3600)
- **Rutas a Excluir**: Lista personalizable de rutas a excluir
- **Prioridades**: Valores de 0.0 a 1.0 para cada tipo de contenido

## Uso Programático

También puedes usar el generador desde código PHP:

```php
$config = [
    'base_url' => 'https://tudominio.com',
    'max_urls' => 50000,
    'crawl_timeout' => 3600,
    'exclude_paths' => ['/admin/', '/login/'],
    'priorities' => [
        'home' => '1.0',
        'category' => '0.8',
        'product' => '0.6',
        'post' => '0.6',
        'other' => '0.4'
    ]
];

$generator = new SitemapGenerator($config);
$generator->crawlSite();
$generator->generateXML('sitemap.xml');
```

## Salida XML

El generador crea un archivo sitemap.xml con este formato:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://tudominio.com/</loc>
        <lastmod>2023-12-20</lastmod>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>
    <!-- más URLs aquí -->
</urlset>
```

## Monitoreo

El panel de control muestra:
- Fecha y hora del último sitemap generado
- Tamaño del archivo
- Vista previa del sitemap
- Mensajes de éxito o error

## Requisitos

- PHP 7.0 o superior
- Extensión DOM de PHP
- Permisos de escritura en el directorio

## Seguridad

El generador incluye:
- Sanitización de entradas
- Límites de tiempo y recursos
- Validación de URLs internas
- Exclusión de rutas sensibles

## Autor

- **Autor:** franhr
- **Web:** [https://pcprogramacion.es/](https://pcprogramacion.es/)

## Licencia

Este proyecto está disponible bajo la Licencia MIT.
