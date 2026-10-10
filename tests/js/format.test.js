import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import * as format from '../../public/aset/js/format.js';

const { cases } = JSON.parse(readFileSync(new URL('../kasus/format.json', import.meta.url), 'utf8'));

// format_date -> formatDate
const jsName = (phpName) => phpName.replace(/_(\w)/g, (_, letter) => letter.toUpperCase());

for (const { fn, args, expected, throws } of cases) {
    test(`${fn}(${JSON.stringify(args).slice(1, -1)})`, () => {
        const call = () => format[jsName(fn)](...args);
        if (throws) {
            assert.throws(call, RangeError);
        } else {
            assert.equal(call(), expected);
        }
    });
}

test('formatDatetime accepts a Date instant', () => {
    assert.equal(format.formatDatetime(new Date(Date.UTC(2026, 9, 13, 0, 32, 5)), true), '13 Okt 2026, 07.32.05');
});
