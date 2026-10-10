// Show/hide button for password fields (docs/08 UI-38), from app/Views/komponen/isian_password.php.
// The component adds this script once per field, so run only once.
(() => {
  if (window.isianPasswordSiap) return;
  window.isianPasswordSiap = true;

  for (const tombol of document.querySelectorAll('[data-isian-password]')) {
    const isian = document.getElementById(tombol.dataset.isianPassword);
    const ikon = tombol.querySelector('use');
    const teks = tombol.querySelector('.visually-hidden');
    if (!isian) continue;

    const atur = (tampil) => {
      isian.type = tampil ? 'text' : 'password';
      tombol.setAttribute('aria-pressed', String(tampil));
      teks.textContent = tampil ? 'Sembunyikan password' : 'Tampilkan password';
      ikon.setAttribute('href', ikon.getAttribute('href').replace(/#.*$/, tampil ? '#eye-off' : '#eye'));
    };

    tombol.hidden = false;
    tombol.addEventListener('click', () => atur(isian.type === 'password'));
    // Hide again before sending, so the browser never stores the field as plain text.
    isian.form?.addEventListener('submit', () => atur(false));
  }
})();
