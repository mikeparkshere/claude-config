# Forms

**Tested by the second port (11/16/2026):** three forms (contact, a booking request with a conditional field, an
event request) on one handler driven by a single definitions file, honeypot plus the old site's reCAPTCHA v3 key,
Mailgun HTTP API. Delivered on staging and production. What it taught is folded in below.

## Before anything else: is the old site's mail working?

Test the WordPress site's mail credential at harvest (one authenticated request with the key or SMTP login it uses).
On the second port the Mailgun sending key in `wp-config.php` had died, the custom transport fired `wp_mail_failed`
and logged nothing, and the client had been missing booking requests for weeks while the form plugin kept saving
them. Distinguish the domain from the key: if another key on the account can send for the domain, the domain is fine
and only the key is dead. A domain sending key (send-only) is the right credential for the new site.

## Porting a form

1. **Inventory** (Gate 1): `reduced/inventory.md` → Forms lists plugin forms (WS Form, CF7, Gravity, WPForms,
   Fluent) with their fields; Bricks form elements show up in the page files as `[form]` with fields, actions and
   recipient. Note where each one sends today and what the confirmation says.
2. **Several forms:** one definitions file (fields, types, required, options, subject, success message) drives
   both the markup and the server checks, so a field can't be validated differently from how it renders. A field
   shown only for another field's value (WS Form conditional logic) can be done in CSS with `:has()`, no JS, and the
   server skips its check when it's hidden.
3. **Rebuild** with the skeleton handler: a plain HTML form that posts to `/api/contact.php`, which validates, checks
   the honeypot (and Turnstile if configured), sends through the **Mailgun HTTP API**, and answers JSON for the JS path
   or redirects to a thank-you page for the no-JS path. It works without JavaScript.
4. **Secrets** live in `includes/config.local.php` (`$secrets`), gitignored, written on the server by hand: Mailgun key
   and domain, recipient. Staging sends to Michael, production to the client.
5. **Rules** (`flatsite.md` → Security): never bare `mail()`; submitted values only in the message body; `h:Reply-To`
   only after `FILTER_VALIDATE_EMAIL`; strip CR/LF from anything in a header; escape anything echoed back.
6. **Test** on staging, then once on production after cutover, and confirm the message actually arrives (Mailgun
   "accepted" is not "delivered"). Check the sending subdomain has its `mxa`/`mxb.mailgun.org` MX records, or
   Exchange-style receivers reject every message while Gmail tests pass.
7. **Spam:** honeypot first. Add Turnstile when spam gets past it; its script host then needs a CSP entry.
   Reusing the old site's **reCAPTCHA v3** key works, with two catches: the key's domain list in the reCAPTCHA
   admin usually doesn't include the staging host (run staging on the honeypot alone, secret unset), and a headless
   browser scores 0, so production tests of the send path need the secret off for the seconds of the test, then
   back on. Load Google's script on first focus of a form, not on page load.
8. **A secret a partial needs** (a captcha site key) must be in scope: the skeleton's `partial()` imports `$secrets`
   (fixed after it hid the key at cutover).

## Old submissions

Never harvested. If the client needs past entries, export them from the plugin's admin before the WordPress site is
decommissioned.
