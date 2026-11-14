<?php
// hero: heading (required), subheading, image ['src','alt','width','height'], cta_label, cta_url.
// components.json: "hero". Every value is escaped here; pass plain text.
$a = array_merge(['heading' => '', 'subheading' => '', 'image' => null, 'cta_label' => '', 'cta_url' => ''], $args);
?>
<section class="hero reveal">
  <div class="hero__inner">
    <h1 class="hero__heading"><?= e($a['heading']) ?></h1>
<?php if ($a['subheading']): ?>
    <p class="hero__subheading"><?= e($a['subheading']) ?></p>
<?php endif; ?>
<?php if ($a['cta_label'] && $a['cta_url']): ?>
    <a class="hero__cta button" href="<?= e($a['cta_url']) ?>"><?= e($a['cta_label']) ?></a>
<?php endif; ?>
  </div>
<?php if ($a['image']): ?>
  <img class="hero__image" src="<?= e($a['image']['src']) ?>" alt="<?= e($a['image']['alt']) ?>" width="<?= (int) $a['image']['width'] ?>" height="<?= (int) $a['image']['height'] ?>" fetchpriority="high">
<?php endif; ?>
</section>
