// Scroll reveals: .reveal starts hidden (CSS) and gets .is-in when it nears view. Reduced motion is handled in CSS.
(function () {
  var els = document.querySelectorAll('.reveal');
  if (!('IntersectionObserver' in window)) { els.forEach(function (el) { el.classList.add('is-in'); }); return; }
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } });
  }, { rootMargin: '0px 0px -10% 0px' });
  els.forEach(function (el) { io.observe(el); });
})();

// Contact form: send with fetch when JS is on; the plain POST + thank-you page still works without it.
(function () {
  var form = document.querySelector('form[data-enhance]');
  if (!form || !window.fetch) return;
  var status = form.querySelector('.contact-form__status');
  var msgs = { missing: 'Please fill in your name, a valid email and a message.', spam: "That didn't go through. Please try again.",
               send: 'Something went wrong sending your message. Please call us instead.', config: 'The form is not set up yet. Please call us instead.' };
  form.addEventListener('submit', function (ev) {
    ev.preventDefault();
    var btn = form.querySelector('[type=submit]');
    btn.disabled = true;
    fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d.ok) { window.location.href = '/contact/thanks/'; return; }
        status.hidden = false; status.textContent = msgs[d.error] || msgs.send; btn.disabled = false;
      })
      .catch(function () { status.hidden = false; status.textContent = msgs.send; btn.disabled = false; });
  });
})();
