// GA4, production host only (head.php prints this tag only there). A file, not Google's inline snippet, so the CSP
// needs no 'unsafe-inline'. gtag.js itself loads after the page, when the browser is idle.
(function () {
  var id = document.currentScript && document.currentScript.dataset.gaId;
  if (!id) return;
  window.dataLayer = window.dataLayer || [];
  window.gtag = function () { window.dataLayer.push(arguments); };
  window.gtag('js', new Date());
  window.gtag('config', id);
  addEventListener('load', function () {
    (window.requestIdleCallback || setTimeout)(function () {
      var s = document.createElement('script');
      s.async = true;
      s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(id);
      document.head.appendChild(s);
    }, { timeout: 2000 });
  });
})();
