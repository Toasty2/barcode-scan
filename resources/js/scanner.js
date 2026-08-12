import { GBP, toMinorUnits, toMajorUnitsString } from './currency.js';

const BARCODE_FORMATS = ['ean_13', 'ean_8', 'upc_a', 'upc_e'];
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
const APP_BASE_URL = document.querySelector('meta[name="app-base-url"]').content;

const preview = document.getElementById('preview');
const status = document.getElementById('status');
const startBtn = document.getElementById('start-btn');
const retryLookupBtn = document.getElementById('retry-lookup-btn');

const entryForm = document.getElementById('entry-form');
const entryName = document.getElementById('entry-name');
const entryPrice = document.getElementById('entry-price');
const entryQuantity = document.getElementById('entry-quantity');
const entryStaleWarning = document.getElementById('entry-stale-warning');
const addItemBtn = document.getElementById('add-item-btn');

const tripList = document.getElementById('trip-list');
const tripCount = document.getElementById('trip-count');
const tripDiscount = document.getElementById('trip-discount');
const submitTripBtn = document.getElementById('submit-trip-btn');
const submitStatus = document.getElementById('submit-status');

let detector = null;
let stream = null;
let cancelled = false;
let pendingUpc = null;

function setStatus(message) {
    status.textContent = message;
}

// --- Barcode detection ---------------------------------------------------

async function init() {
    if (!('BarcodeDetector' in window)) {
        setStatus('This browser does not support barcode scanning. Use Chrome on Android.');
        startBtn.disabled = true;
        return;
    }

    const supported = await window.BarcodeDetector.getSupportedFormats();
    const formats = BARCODE_FORMATS.filter((format) => supported.includes(format));
    if (formats.length === 0) {
        setStatus('No supported barcode formats found on this device.');
        startBtn.disabled = true;
        return;
    }

    detector = new window.BarcodeDetector({ formats });
    setStatus('Ready.');
}

async function startScan() {
    entryForm.classList.add('hidden');
    retryLookupBtn.classList.add('hidden');
    setStatus('Requesting camera…');

    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' },
        });
    } catch (err) {
        setStatus(`Camera access failed: ${err.message}`);
        return;
    }

    preview.srcObject = stream;
    await preview.play();
    setStatus('Point the camera at a barcode…');
    cancelled = false;
    requestAnimationFrame(scanFrame);
}

function stopScan() {
    cancelled = true;
    stream?.getTracks().forEach((track) => track.stop());
    stream = null;
}

async function scanFrame() {
    if (cancelled) return;

    try {
        const barcodes = await detector.detect(preview);
        if (barcodes.length > 0) {
            onDetected(barcodes[0]);
            return;
        }
    } catch (err) {
        setStatus(`Detection error: ${err.message}`);
        stopScan();
        return;
    }

    requestAnimationFrame(scanFrame);
}

// --- Lookup ----------------------------------------------------------------
//
// Each lookup resolves to a "found"/"not_found" outcome or one of three
// distinct failure outcomes, so the UI can report specifically what went
// wrong rather than a single generic failure message:
//   - network_error: fetch() itself failed (offline, DNS, connection refused)
//   - http_error: a response came back but with a non-2xx status
//   - parse_error: a 2xx response whose body wasn't valid JSON

async function fetchJson(url, options) {
    let response;
    try {
        response = await fetch(url, options);
    } catch {
        return { outcome: 'network_error' };
    }

    if (!response.ok) {
        return { outcome: 'http_error', status: response.status };
    }

    try {
        return { outcome: 'ok', data: await response.json() };
    } catch {
        return { outcome: 'parse_error' };
    }
}

async function lookupOwnProduct(upc) {
    const result = await fetchJson(`${APP_BASE_URL}/products/${encodeURIComponent(upc)}`);
    if (result.outcome !== 'ok') return result;

    return result.data.found
        ? {
              outcome: 'found',
              productName: result.data.product_name,
              price: result.data.price,
              stale: result.data.stale,
          }
        : { outcome: 'not_found' };
}

async function lookupOpenFoodFacts(upc) {
    const url = `https://world.openfoodfacts.org/api/v2/product/${encodeURIComponent(upc)}.json?fields=product_name`;
    const result = await fetchJson(url);
    if (result.outcome !== 'ok') return result;

    return result.data.status === 1 && result.data.product?.product_name
        ? { outcome: 'found', productName: result.data.product.product_name }
        : { outcome: 'not_found' };
}

function describeLookupFailure(result, sourceLabel) {
    if (result.outcome === 'network_error') {
        return `Couldn't reach ${sourceLabel} — check your connection.`;
    }
    if (result.outcome === 'http_error') {
        return `${sourceLabel} returned an error (status ${result.status}).`;
    }
    return `${sourceLabel} returned an unexpected response.`;
}

async function performLookup(upc) {
    retryLookupBtn.classList.add('hidden');
    setStatus('Looking up product…');

    const own = await lookupOwnProduct(upc);

    if (own.outcome === 'found') {
        entryName.value = own.productName;
        entryPrice.value = toMajorUnitsString(own.price, GBP);
        entryStaleWarning.classList.toggle('hidden', !own.stale);
        setStatus('Found cached price — check and add.');
        return;
    }

    if (own.outcome !== 'not_found') {
        setStatus(describeLookupFailure(own, 'saved prices'));
        retryLookupBtn.classList.remove('hidden');
        return;
    }

    const off = await lookupOpenFoodFacts(upc);

    if (off.outcome === 'found') {
        entryName.value = off.productName;
        setStatus('New product — enter the price.');
        return;
    }

    if (off.outcome !== 'not_found') {
        setStatus(describeLookupFailure(off, 'Open Food Facts'));
        retryLookupBtn.classList.remove('hidden');
        return;
    }

    setStatus('Product not found in Open Food Facts — enter it manually.');
}

function onDetected(barcode) {
    stopScan();
    pendingUpc = barcode.rawValue;

    entryName.value = '';
    entryPrice.value = '';
    entryQuantity.value = '1';
    entryStaleWarning.classList.add('hidden');
    entryForm.classList.remove('hidden');

    performLookup(pendingUpc);
}

// --- Trip list ---------------------------------------------------------------

function createTripRow({ upc, entryType, name, price, quantity }) {
    const row = document.createElement('div');
    row.className = 'trip-item flex gap-2 items-center bg-gray-800 rounded-lg p-2';
    row.dataset.upc = upc ?? '';
    row.dataset.entryType = entryType;

    row.innerHTML = `
        <input type="text" class="item-name flex-1 min-w-0 p-1 rounded bg-gray-900 border border-gray-700 text-sm">
        <input type="text" inputmode="decimal" class="item-price w-16 p-1 rounded bg-gray-900 border border-gray-700 text-sm">
        <input type="number" min="1" class="item-quantity w-12 p-1 rounded bg-gray-900 border border-gray-700 text-sm">
        <button type="button" class="item-remove text-red-400 px-2 text-lg leading-none" aria-label="Remove">&times;</button>
    `;

    row.querySelector('.item-name').value = name;
    row.querySelector('.item-price').value = price;
    row.querySelector('.item-quantity').value = quantity;

    row.querySelector('.item-remove').addEventListener('click', () => {
        row.remove();
        updateTripCount();
    });

    return row;
}

function updateTripCount() {
    const count = tripList.children.length;
    tripCount.textContent = count;
    submitTripBtn.disabled = count === 0;
}

function addItemToTrip() {
    if (!entryName.value.trim() || !entryPrice.value.trim()) return;

    const row = createTripRow({
        upc: pendingUpc,
        entryType: 'scan',
        name: entryName.value.trim(),
        price: entryPrice.value.trim(),
        quantity: entryQuantity.value || '1',
    });

    tripList.prepend(row);
    updateTripCount();

    entryForm.classList.add('hidden');
    retryLookupBtn.classList.add('hidden');
    pendingUpc = null;
    setStatus('Added. Tap "Start scan" for the next item.');
}

async function submitTrip() {
    const items = [...tripList.children].map((row) => ({
        upc: row.dataset.upc || null,
        entry_type: row.dataset.entryType,
        product_name: row.querySelector('.item-name').value.trim(),
        price: toMinorUnits(row.querySelector('.item-price').value, GBP),
        quantity: parseInt(row.querySelector('.item-quantity').value, 10) || 1,
    }));

    if (items.some((item) => !item.product_name || item.price === null)) {
        submitStatus.textContent = 'Every item needs a name and a valid price.';
        return;
    }

    const discount = toMinorUnits(tripDiscount.value, GBP) ?? 0;

    submitTripBtn.disabled = true;
    submitStatus.textContent = 'Saving trip…';

    try {
        const response = await fetch(`${APP_BASE_URL}/trips`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
            body: JSON.stringify({ items, discount }),
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message ?? 'Save failed');
        }

        tripList.innerHTML = '';
        updateTripCount();
        tripDiscount.value = '';
        submitStatus.textContent = 'Trip saved!';
    } catch (err) {
        submitStatus.textContent = `Save failed: ${err.message}`;
        submitTripBtn.disabled = false;
    }
}

startBtn.addEventListener('click', startScan);
retryLookupBtn.addEventListener('click', () => {
    if (pendingUpc) performLookup(pendingUpc);
});
addItemBtn.addEventListener('click', addItemToTrip);
submitTripBtn.addEventListener('click', submitTrip);

init();
