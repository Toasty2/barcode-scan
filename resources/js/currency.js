// Mirrors app/Support/Money/{Currency,Currencies/GBP}.php: a currency is a
// plain definition (code, symbol, decimal places), and amounts are moved
// between minor units (the wire/storage format) and major units (what a
// user types or reads) via pure functions that take a currency as a
// parameter rather than assuming one. Add other currencies the same way —
// export a definition object, no code changes needed elsewhere.

export const GBP = Object.freeze({
    code: 'GBP',
    symbol: '£',
    decimalPlaces: 2,
});

/**
 * Convert a user-entered major-unit amount (e.g. "1.99") to integer minor
 * units (e.g. 199). Returns null if the input isn't a finite number.
 */
export function toMinorUnits(input, currency) {
    const amount = typeof input === 'string' ? parseFloat(input) : input;
    if (!Number.isFinite(amount)) return null;

    return Math.round(amount * 10 ** currency.decimalPlaces);
}

/**
 * Convert integer minor units to a plain decimal string (e.g. 199 -> "1.99"),
 * suitable for populating an editable amount input.
 */
export function toMajorUnitsString(minorUnits, currency) {
    return (minorUnits / 10 ** currency.decimalPlaces).toFixed(currency.decimalPlaces);
}

/**
 * Format integer minor units for display, including the currency symbol
 * (e.g. 199 -> "£1.99").
 */
export function formatAmount(minorUnits, currency) {
    const sign = minorUnits < 0 ? '-' : '';
    const majorUnits = Math.abs(minorUnits) / 10 ** currency.decimalPlaces;

    return `${sign}${currency.symbol}${majorUnits.toFixed(currency.decimalPlaces)}`;
}
