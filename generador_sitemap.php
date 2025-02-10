<?php
class SitemapGenerator {
    private $urls = array();
    private $baseUrl;
    private $config;
    private $startTime;

    public function __construct($config) {
        $this->config = $config;
        $this->baseUrl = rtrim($config['base_url'], '/');
        $this->startTime = time();
    }

    public function addUrl($url, $priority = null, $changefreq = 'weekly', $lastmod = null) {
        if (!$lastmod) {
            $lastmod = date('Y-m-d');
        }
        
        if (!$priority) {
            $priority = $this->getPriorityByUrl($url);
        }

        $this->urls[] = array(
            'loc' => $url,
            'lastmod' => $lastmod,
            'changefreq' => $changefreq,
            'priority' => $priority
        );
    }

    private function getPriorityByUrl($url) {
        $path = parse_url($url, PHP_URL_PATH);
        
        if ($path == '/' || $path == '/index.php') {
            return $this->config['priorities']['home'];
        } elseif (strpos($path, '/category/') !== false) {
            return $this->config['priorities']['category'];
        } elseif (strpos($path, '/product/') !== false) {
            return $this->config['priorities']['product'];
        } elseif (strpos($path, '/post/') !== false) {
            return $this->config['priorities']['post'];
        }
        
        return $this->config['priorities']['other'];
    }

    public function crawlSite() {
        $found = array();
        $queue = array('/');

        while (!empty($queue) && count($this->urls) < $this->config['max_urls']) {
            // Verificar timeout
            if (time() - $this->startTime > $this->config['crawl_timeout']) {
                throw new Exception("Tiempo máximo de rastreo alcanzado");
            }

            $url = array_shift($queue);
            
            if (in_array($url, $found)) {
                continue;
            }

            $found[] = $url;
            $fullUrl = $this->baseUrl . $url;

            if ($this->shouldExclude($url)) {
                continue;
            }

            $this->addUrl($fullUrl);

            // Obtener enlaces de la página
            $html = @file_get_contents($fullUrl);
            if ($html === false) continue;

            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $links = $dom->getElementsByTagName('a');

            foreach ($links as $link) {
                $href = $link->getAttribute('href');
                if (empty($href)) continue;

                $parsedUrl = parse_url($href);
                
                // Asegurarse de que el enlace es interno
                if (!empty($parsedUrl['host']) && $parsedUrl['host'] !== parse_url($this->baseUrl, PHP_URL_HOST)) {
                    continue;
                }

                $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '/';
                if (!in_array($path, $queue) && !in_array($path, $found)) {
                    $queue[] = $path;
                }
            }
        }
    }

    private function shouldExclude($url) {
        foreach ($this->config['exclude_paths'] as $excludePath) {
            if (strpos($url, $excludePath) !== false) {
                return true;
            }
        }
        return false;
    }

    public function generateXML($filename = 'sitemap.xml') {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>');

        foreach ($this->urls as $url) {
            $urlElement = $xml->addChild('url');
            $urlElement->addChild('loc', htmlspecialchars($url['loc']));
            $urlElement->addChild('lastmod', $url['lastmod']);
            $urlElement->addChild('changefreq', $url['changefreq']);
            $urlElement->addChild('priority', $url['priority']);
        }

        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        
        file_put_contents($filename, $dom->saveXML());
    }
}

// Ejemplo de uso
try {
    // Configuración
    $config = array(
        'base_url' => 'https://tudominio.com',
        'max_urls' => 500,
        'crawl_timeout' => 300,
        'exclude_paths' => array(
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
        ),
        'priorities' => array(
            'home' => '1.0',
            'category' => '0.8',
            'product' => '0.6',
            'post' => '0.6',
            'other' => '0.4'
        )
    );

    // Inicializar el generador
    $generator = new SitemapGenerator($config);
    
    // Crawlear el sitio automáticamente
    $generator->crawlSite();
    
    // O añadir URLs manualmente
    $generator->addUrl('https://tudominio.com/pagina-importante', '0.8', 'daily');
    
    // Generar el archivo XML
    $generator->generateXML();
    
    echo "Sitemap generado correctamente!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>