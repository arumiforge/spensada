import { test } from 'node:test';
import assert from 'node:assert/strict';
import { tindakan, teksCari } from '../../public/aset/js/cari-siswa.js';

test('only a 200 HTML answer replaces the results (docs/10 §6.1 item 2)', () => {
    assert.equal(tindakan(200, 'text/html; charset=UTF-8', null), 'ganti');
    assert.equal(tindakan(200, 'application/json', null), 'gagal');
    assert.equal(tindakan(200, null, null), 'gagal');
});

test('error codes map to the page reaction (docs/10 API-03)', () => {
    assert.equal(tindakan(401, 'application/json', 'login_ulang'), 'sesi');
    assert.equal(tindakan(403, 'application/json', 'nonaktif'), 'muat_ulang');
    assert.equal(tindakan(403, 'application/json', 'ditolak'), 'ditolak');
    assert.equal(tindakan(429, 'application/json', 'terlalu_sering'), 'sering');
    assert.equal(tindakan(400, 'application/json', 'permintaan_rusak'), 'gagal');
    assert.equal(tindakan(500, 'text/html', null), 'gagal');
});

test('search starts at 2 characters and is cut at 50 (docs/11 VAL-29)', () => {
    assert.equal(teksCari(' a '), null);
    assert.equal(teksCari(''), null);
    assert.equal(teksCari(undefined), null);
    assert.equal(teksCari(' Ra '), 'Ra');
    assert.equal(teksCari('x'.repeat(60)).length, 50);
});
