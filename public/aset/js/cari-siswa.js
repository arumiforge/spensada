/**
 * Live student search (docs/09 RT-14, docs/10 EP-MD-01, §6.1, docs/08 UI-28).
 *
 * An input with data-cari-siswa="<fragment URL>", data-cari-untuk="<untuk>"
 * and data-cari-hasil="<id of the results box>" gets results while typing:
 * 300 ms after the last key, from 2 characters, with the previous request
 * aborted. The box is replaced only by a 200 text/html answer. Without
 * JavaScript the form's Cari button reloads the page with `cari`.
 */

export const MIN = 2;
export const MAX = 50;
export const JEDA = 300;

const PESAN = {
    sesi: 'Sesi berakhir. Muat ulang halaman, lalu login lagi.',
    ditolak: 'Anda tidak memiliki akses ke pencarian ini.',
    sering: 'Pencarian terlalu sering. Tunggu sebentar, lalu ketik lagi.',
    gagal: 'Hasil pencarian belum dapat dimuat. Coba lagi beberapa saat lagi.',
};

/**
 * What to do with an answer (docs/10 §6.1 item 2): 'ganti' (show the
 * HTML), 'muat_ulang' (account deactivated), or a key of PESAN.
 */
export function tindakan(status, contentType, kode) {
    if (status === 200 && /^text\/html/i.test(contentType || '')) return 'ganti';
    if (status === 401) return 'sesi';
    if (status === 403 && kode === 'nonaktif') return 'muat_ulang';
    if (status === 403 && kode === 'ditolak') return 'ditolak';
    if (status === 429) return 'sering';
    return 'gagal';
}

/** The trimmed text to search for, or null when it is too short. */
export function teksCari(nilai) {
    const teks = String(nilai ?? '').trim();
    return teks.length < MIN ? null : teks.slice(0, MAX);
}

function pasang(isian) {
    const kotak = document.getElementById(isian.dataset.cariHasil);
    if (!kotak) return;
    let pengatur = null;
    let jeda = 0;

    const tulis = (pesan) => {
        const p = document.createElement('p');
        p.className = 'mb-0';
        p.textContent = pesan;
        kotak.replaceChildren(p);
    };

    const cari = async (teks) => {
        pengatur?.abort();
        pengatur = new AbortController();
        const url = new URL(isian.dataset.cariSiswa, window.location.href);
        url.searchParams.set('cari', teks);
        url.searchParams.set('untuk', isian.dataset.cariUntuk);
        try {
            const jawaban = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
                credentials: 'same-origin',
                signal: pengatur.signal,
            });
            let kode = null;
            if (jawaban.status === 403) kode = (await jawaban.json().catch(() => ({}))).kode;
            const aksi = tindakan(jawaban.status, jawaban.headers.get('Content-Type'), kode);
            if (aksi === 'ganti') kotak.innerHTML = await jawaban.text();
            else if (aksi === 'muat_ulang') window.location.reload();
            else tulis(PESAN[aksi]);
        } catch (galat) {
            if (galat.name !== 'AbortError') tulis(PESAN.gagal);
        }
    };

    isian.addEventListener('input', () => {
        clearTimeout(jeda);
        const teks = teksCari(isian.value);
        if (teks === null) {
            pengatur?.abort();
            kotak.replaceChildren();
            return;
        }
        jeda = setTimeout(() => cari(teks), JEDA);
    });
}

if (typeof document !== 'undefined') {
    document.querySelectorAll('[data-cari-siswa]').forEach(pasang);
}
