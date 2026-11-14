<?php
$page = [
    'title'       => 'Contact | Example Business',
    'description' => 'Questions or a quote: send a note and we answer within one business day.',
    'slug'        => 'contact',
    'path'        => '/contact/',
];
require $_SERVER['DOCUMENT_ROOT'] . '/../includes/bootstrap.php';
// Error messages come from this fixed table, keyed by code: nothing from the request is echoed back.
$errors = ['missing' => 'Please fill in your name, a valid email and a message.', 'spam' => 'That didn\'t go through. Please try again.',
           'send' => 'Something went wrong sending your message. Please call us instead.', 'config' => 'The form is not set up yet. Please call us instead.'];
$err = $errors[$_GET['error'] ?? ''] ?? '';
include INC . '/head.php';
include INC . '/header.php';
?>
<main id="main">
  <section class="contact">
    <h1 class="contact__heading">Contact</h1>
    <form class="contact-form" action="/api/contact.php" method="post" data-enhance>
<?php if ($err): ?>
      <p class="contact-form__error" role="alert"><?= e($err) ?></p>
<?php endif; ?>
      <p class="contact-form__status" role="status" aria-live="polite" hidden></p>
      <label class="contact-form__field">Name <input name="name" autocomplete="name" required maxlength="100"></label>
      <label class="contact-form__field">Email <input name="email" type="email" autocomplete="email" required maxlength="200"></label>
      <label class="contact-form__field">Phone (optional) <input name="phone" type="tel" autocomplete="tel" maxlength="40"></label>
      <label class="contact-form__field">Message <textarea name="message" rows="6" required maxlength="5000"></textarea></label>
      <!-- Honeypot: hidden from people, filled by bots. -->
      <div class="contact-form__trap" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
<?php if (!empty($secrets['turnstile_sitekey'])): ?>
      <div class="cf-turnstile" data-sitekey="<?= e($secrets['turnstile_sitekey']) ?>"></div>
      <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>
      <button class="contact-form__submit button" type="submit">Send</button>
    </form>
  </section>
</main>
<?php include INC . '/footer.php'; ?>
