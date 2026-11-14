<?php
$page = [
    'title'       => 'Example Business | Springfield, Ohio',
    'description' => 'One-sentence meta description, carried over verbatim from the old site\'s SEO plugin.',
    'slug'        => 'home',
    'path'        => '/',
];
require $_SERVER['DOCUMENT_ROOT'] . '/../includes/bootstrap.php';
include INC . '/head.php';
include INC . '/header.php';
?>
<main id="main">
<?php partial('hero', [
    'heading'    => 'A Heading Carried Over From the Old Home Page',
    'subheading' => 'Copy unique to this page lives here, in the page file, and goes into components as arguments.',
    'cta_label'  => 'Get in touch',
    'cta_url'    => '/contact/',
]); ?>
</main>
<?php include INC . '/footer.php'; ?>
