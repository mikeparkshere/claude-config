<footer class="site-footer">
  <div class="site-footer__inner">
    <p class="site-footer__name"><?= e($site['name']) ?></p>
    <address class="site-footer__nap">
      <?= e($site['address']['street']) ?><br><?= e($site['address']['city']) ?>, <?= e($site['address']['state']) ?> <?= e($site['address']['zip']) ?><br>
      <a href="<?= e(tel($site['phone'])) ?>"><?= e($site['phone']) ?></a>
    </address>
    <p class="site-footer__copy">&copy; <?= date('Y') ?> <?= e($site['name']) ?></p>
  </div>
</footer>
<?php
// One ld+json script, one @graph. Replace LocalBusiness with the most specific schema.org type that fits.
$graph = [
    ['@type' => 'LocalBusiness', '@id' => $site['url'] . '/#business', 'name' => $site['name'], 'url' => $site['url'] . '/',
     'telephone' => $site['phone'], 'email' => $site['email'],
     'address' => ['@type' => 'PostalAddress', 'streetAddress' => $site['address']['street'], 'addressLocality' => $site['address']['city'],
                   'addressRegion' => $site['address']['state'], 'postalCode' => $site['address']['zip'], 'addressCountry' => 'US']],
    ['@type' => 'WebSite', '@id' => $site['url'] . '/#website', 'url' => $site['url'] . '/', 'name' => $site['name'], 'publisher' => ['@id' => $site['url'] . '/#business']],
    ['@type' => 'WebPage', '@id' => $site['url'] . $page['path'] . '#webpage', 'url' => $site['url'] . $page['path'], 'name' => $page['title'],
     'isPartOf' => ['@id' => $site['url'] . '/#website'], 'about' => ['@id' => $site['url'] . '/#business']],
];
if (empty($page['noindex'])): ?>
<script type="application/ld+json"><?= json_encode(['@context' => 'https://schema.org', '@graph' => array_merge($graph, $page['schema'])], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php endif; ?>
</body>
</html>
