/**
 * Indonesian locale formatting for the browser and the kiosk.
 *
 * Mirrors app/Helpers/format_helper.php exactly; both are checked against
 * the shared cases in tests/kasus/format.json (docs/08 UI-53 to UI-55).
 * Output is always WIB (Asia/Jakarta), whatever the machine time zone is.
 *
 * Date input contract (same meaning as the PHP helper):
 * - Date: an instant (the kiosk passes new Date(correctedMs)).
 * - number: Unix timestamp in SECONDS, like PHP int (not milliseconds).
 * - string: "YYYY-MM-DD", optionally followed by " HH:MM[:SS[.fff]]" or
 *   "THH:MM[:SS[.fff]]", optionally followed by "Z" or "+HH:MM"/"-HH:MM".
 *   Without an offset the string is read as WIB. Other strings throw RangeError.
 * - null, undefined, '': the function returns ''.
 * Numbers may be numbers or numeric strings (e.g. DECIMAL values from the API).
 */

const TIME_ZONE = 'Asia/Jakarta';

const DATE_STYLES = {
    long: { day: 'numeric', month: 'long', year: 'numeric' },
    full: { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' },
    short: { day: 'numeric', month: 'short', year: 'numeric' },
    day: { day: 'numeric', month: 'short' },
    numeric: { day: '2-digit', month: '2-digit', year: 'numeric' },
};

const formatters = new Map();

function intl(Type, options) {
    const key = Type.name + JSON.stringify(options);
    if (!formatters.has(key)) {
        formatters.set(key, new Type('id-ID', options));
    }
    return formatters.get(key);
}

const isEmpty = (value) => value === null || value === undefined || value === '';

function toDate(value) {
    if (value instanceof Date) {
        return value;
    }
    if (typeof value === 'number') {
        return new Date(value * 1000);
    }
    const match = /^(\d{4}-\d{2}-\d{2})(?:[T ](\d{2}:\d{2}(?::\d{2}(?:\.\d+)?)?))?(Z|[+-]\d{2}:\d{2})?$/.exec(String(value));
    const date = match && new Date(`${match[1]}T${match[2] ?? '00:00'}${match[3] ?? '+07:00'}`);
    if (!date || Number.isNaN(date.getTime())) {
        throw new RangeError(`Unreadable date: ${value}`);
    }
    return date;
}

function formatDateTime(value, options) {
    return intl(Intl.DateTimeFormat, { timeZone: TIME_ZONE, ...options }).format(toDate(value));
}

/** Styles: 'long' (default), 'full', 'short', 'day', 'numeric'. */
export function formatDate(value, style = 'long') {
    if (isEmpty(value)) {
        return '';
    }
    if (!Object.hasOwn(DATE_STYLES, style)) {
        throw new RangeError(`Unknown date style: ${style}`);
    }
    return formatDateTime(value, DATE_STYLES[style]);
}

/** 07.32, or 07.32.05 with seconds. */
export function formatTime(value, withSeconds = false) {
    if (isEmpty(value)) {
        return '';
    }
    const options = { hour: '2-digit', minute: '2-digit', hourCycle: 'h23' };
    return formatDateTime(value, withSeconds ? { ...options, second: '2-digit' } : options);
}

/** 13 Okt 2026, 07.32 */
export function formatDatetime(value, withSeconds = false) {
    return isEmpty(value) ? '' : `${formatDate(value, 'short')}, ${formatTime(value, withSeconds)}`;
}

/** 1.024 or 12,5. Halves round away from zero; never prints "-0". */
export function formatNumber(value, decimals = 0) {
    if (isEmpty(value)) {
        return '';
    }
    // Pre-round to 15 significant digits, as format_pre_round() does in PHP,
    // so 23 / 40 * 100 (57.49999999999999) rounds as 57.5.
    const preRounded = Number(value).toPrecision(15);
    return intl(Intl.NumberFormat, {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
        roundingMode: 'halfExpand',
        signDisplay: 'negative',
    }).format(preRounded);
}

/** Rp 1.250.000, negative -Rp 1.250.000. */
export function formatRupiah(value, decimals = 0) {
    if (isEmpty(value)) {
        return '';
    }
    const amount = Number(value);
    const digits = formatNumber(Math.abs(amount), decimals);
    // A negative amount that rounds to zero shows as "Rp 0", not "-Rp 0".
    return `${amount < 0 && /[1-9]/.test(digits) ? '-' : ''}Rp ${digits}`;
}

/** 88% (whole number, halves round up, docs/13 IE-04). */
export function formatPercent(value) {
    return isEmpty(value) ? '' : `${formatNumber(value, 0)}%`;
}
