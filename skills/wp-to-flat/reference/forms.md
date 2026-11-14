# Forms

⚠️ **Untested by a finished port.** The first port had no form (its contact was a `mailto:`). The handler in
`templates/site-skeleton/public/api/contact.php` follows `flatsite.md`'s security rules, but its first real use should
be treated as part of that port's QA, with a lesson written up afterward.

## Porting a form

1. **Inventory** (Gate 1): `reduced/inventory.md` → Forms lists plugin forms (WS Form, CF7, Gravity, WPForms,
   Fluent) with their fields; Bricks form elements show up in the page files as `[form]` with fields, actions and
   recipient. Note where each one sends today and what the confirmation says.
2. **Rebuild** with the skeleton handler: a plain HTML form that posts to `/api/contact.php`, which validates, checks
   the honeypot (and Turnstile if configured), sends through the **Mailgun HTTP API**, and answers JSON for the JS path
   or redirects to a thank-you page for the no-JS path. It works without JavaScript.
3. **Secrets** live in `includes/config.local.php` (`$secrets`), gitignored, written on the server by hand: Mailgun key
   and domain, recipient. Staging sends to Michael, production to the client.
4. **Rules** (`flatsite.md` → Security): never bare `mail()`; submitted values only in the message body; `h:Reply-To`
   only after `FILTER_VALIDATE_EMAIL`; strip CR/LF from anything in a header; escape anything echoed back.
5. **Test** on staging, then once on production after cutover, and confirm the message actually arrives (Mailgun
   "accepted" is not "delivered"). Check the sending subdomain has its `mxa`/`mxb.mailgun.org` MX records, or
   Exchange-style receivers reject every message while Gmail tests pass.
6. **Spam:** honeypot first. Add Turnstile when spam gets past it; its script host then needs a CSP entry.

## Old submissions

Never harvested. If the client needs past entries, export them from the plugin's admin before the WordPress site is
decommissioned.
