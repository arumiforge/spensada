// Copy and print buttons on the one-time password page (docs/09 RT-09).
// Buttons stay hidden without JavaScript; the password is still on screen.
(() => {
  for (const tombol of document.querySelectorAll('[data-salin]')) {
    const sumber = document.getElementById(tombol.dataset.salin);
    if (!sumber || !navigator.clipboard) continue;
    const teks = tombol.lastChild;
    tombol.hidden = false;
    tombol.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(sumber.textContent.trim());
        teks.textContent = ' Sudah disalin';
      } catch {
        teks.textContent = ' Belum dapat disalin. Catat secara manual.';
      }
    });
  }
  for (const tombol of document.querySelectorAll('[data-cetak]')) {
    tombol.hidden = false;
    tombol.addEventListener('click', () => window.print());
  }
})();
