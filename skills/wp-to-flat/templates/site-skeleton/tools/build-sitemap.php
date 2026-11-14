<?php
// CLI only: php tools/build-sitemap.php  → public/sitemap.xml from $site['pages'] (production URLs).
if (PHP_SAPI !== 'cli') exit(1);
$root = dirname(__DIR__);
require $root . '/includes/config.php';   // production values only, never config.local.php
$out = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];
foreach ($site['pages'] as $key => $p) {
    if (!empty($p['sitemap']) && $p['sitemap'] === false) continue;
    $file = $root . '/public' . rtrim($p['path'], '/') . '/index.php';
    $mod = is_file($file) ? date('Y-m-d', filemtime($file)) : date('Y-m-d');
    $out[] = '  <url><loc>' . htmlspecialchars($site['url'] . $p['path']) . '</loc><lastmod>' . $mod . '</lastmod></url>';
}
$out[] = '</urlset>';
file_put_contents($root . '/public/sitemap.xml', implode("\n", $out) . "\n");
echo 'sitemap: ' . count($site['pages']) . " URLs\n";
