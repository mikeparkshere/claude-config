<?php
http_response_code(404);
$page = ['title' => 'Page Not Found | Example Business', 'description' => '', 'slug' => '404', 'path' => '/404/', 'noindex' => true];
require $_SERVER['DOCUMENT_ROOT'] . '/../includes/bootstrap.php';
include INC . '/head.php';
include INC . '/header.php';
?>
<main id="main">
  <section class="contact">
    <h1 class="contact__heading">Page Not Found</h1>
    <p>That page moved or never existed. Try the <a href="/">home page</a>.</p>
  </section>
</main>
<?php include INC . '/footer.php'; ?>
