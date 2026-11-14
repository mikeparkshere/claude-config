<?php
// Every page: require $_SERVER['DOCUMENT_ROOT'] . '/../includes/bootstrap.php';
define('INC', __DIR__);
require INC . '/config.php';
if (is_file(INC . '/config.local.php')) require INC . '/config.local.php';
require INC . '/data.php';
$env = $env ?? 'production';

function e($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

// Cache-busted asset URL: /assets/css/main.css?v=<mtime>. /assets/ caches for 30 days on RunCloud.
function asset(string $path): string {
    $file = $_SERVER['DOCUMENT_ROOT'] . $path;
    return $path . (is_file($file) ? '?v=' . filemtime($file) : '');
}

// A component: includes/partials/<name>.php, one BEM block, all variable content through $args.
// Record each one in components.json (the way back to WordPress) when you write it.
function partial(string $name, array $args = []): void {
    global $site, $page, $data;
    include INC . '/partials/' . $name . '.php';
}

function tel(string $phone): string { return 'tel:+1' . preg_replace('/\D/', '', $phone); }

// True only on the production host (www or apex). Analytics and HSTS key on this; staging stays quiet.
function is_production(): bool {
    global $site;
    $host = preg_replace('/^www\./', '', strtolower($_SERVER['HTTP_HOST'] ?? ''));
    return $host !== '' && $host === parse_url($site['url'], PHP_URL_HOST) && ($GLOBALS['env'] ?? 'production') === 'production';
}

$page = array_merge([
    'title'       => $site['name'],
    'description' => '',
    'slug'        => '',
    'path'        => '/',
    'noindex'     => false,
    'og_image'    => '/assets/img/og-default.jpg',
    'body_class'  => '',
    'schema'      => [],      // extra @graph nodes for this page
], $page ?? []);
