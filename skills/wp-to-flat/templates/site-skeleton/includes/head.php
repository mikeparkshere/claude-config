<?php $canonical = $site['url'] . $page['path']; ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page['title']) ?></title>
<?php if ($page['description']): ?><meta name="description" content="<?= e($page['description']) ?>">
<?php endif; ?>
<?php if ($page['noindex']): ?><meta name="robots" content="noindex">
<?php endif; ?>
<link rel="canonical" href="<?= e($canonical) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e($site['name']) ?>">
<meta property="og:title" content="<?= e($page['title']) ?>">
<meta property="og:description" content="<?= e($page['description']) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:image" content="<?= e($site['url'] . $page['og_image']) ?>">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('/assets/css/main.css')) ?>">
<?php if ($site['ga_id'] && is_production()): ?>
<script src="<?= e(asset('/assets/js/ga.js')) ?>" data-ga-id="<?= e($site['ga_id']) ?>" defer></script>
<?php endif; ?>
<script src="<?= e(asset('/assets/js/main.js')) ?>" defer></script>
</head>
<body class="<?= e($page['body_class']) ?>">
