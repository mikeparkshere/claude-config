<?php
$page = ['title' => 'Thanks | Example Business', 'description' => 'Your message is on its way.', 'slug' => 'contact', 'path' => '/contact/thanks/', 'noindex' => true];
require $_SERVER['DOCUMENT_ROOT'] . '/../includes/bootstrap.php';
include INC . '/head.php';
include INC . '/header.php';
?>
<main id="main">
  <section class="contact">
    <h1 class="contact__heading">Thanks, We Got It</h1>
    <p>We answer within one business day. If it's urgent, call <a href="<?= e(tel($site['phone'])) ?>"><?= e($site['phone']) ?></a>.</p>
  </section>
</main>
<?php include INC . '/footer.php'; ?>
