<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header">
  <div class="site-header__inner">
    <a class="site-header__brand" href="/"><?= e($site['name']) ?></a>
    <nav class="site-nav" aria-label="Main">
      <ul class="site-nav__list">
<?php foreach ($site['pages'] as $key => $p): if (empty($p['nav'])) continue; ?>
        <li><a class="site-nav__link" href="<?= e($p['path']) ?>"<?= $key === $page['slug'] ? ' aria-current="page"' : '' ?>><?= e($p['label']) ?></a></li>
<?php endforeach; ?>
      </ul>
    </nav>
  </div>
</header>
