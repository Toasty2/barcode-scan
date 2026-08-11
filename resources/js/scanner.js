const BARCODE_FORMATS = ['ean_13', 'ean_8', 'upc_a', 'upc_e'];
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

const preview = document.getElementById('preview');
const status = document.getElementById('status');
const startBtn = document.getElementById('start-btn');

const entryForm = document.getElementById('entry-form');
const entryName = document.getElementById('entry-name');
const entryPrice = document.getElementById('entry-price');
const entryQuantity = document.getElementById('entry-quantity');
const entryStaleWarning = document.getElementById('entry-stale-warning');
const addItemBtn = document.getElementById('add-item-btn');
const addCouponBtn = document.getElementById('add-coupon-btn');

const tripList = document.getElementById('trip-list');
const tripCount = document.getElementById('trip-count');
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

async function lookupOwnProduct(upc) {
    try {
        const response = await fetch(`/products/${encodeURIComponent(upc)}`);
        if (!response.ok) return { found: false };
        return await response.json();
    } catch {
        return { found: false };
    }
}

async function lookupOpenFoodFacts(upc) {
    const url = `https://world.openfoodfacts.org/api/v2/product/${encodeURIComponent(upc)}.json?fields=product_name`;

    try {
        const response = await fetch(url);
        if (!response.ok) return null;

        const data = await response.json();
        return data.status === 1 ? (data.product?.product_name ?? null) : null;
    } catch {
        return null;
    }
}

async function onDetected(barcode) {
    stopScan();
    pendingUpc = barcode.rawValue;

    entryName.value = '';
    entryPrice.value = '';
    entryQuantity.value = '1';
    entryStaleWarning.classList.add('hidden');
    entryForm.classList.remove('hidden');
    setStatus('Looking up product…');

    const own = await lookupOwnProduct(pendingUpc);

    if (own.found) {
        entryName.value = own.product_name;
        entryPrice.value = own.price;
        entryStaleWarning.classList.toggle('hidden', !own.stale);
        setStatus('Found cached price — check and add.');
        return;
    }

    const offName = await lookupOpenFoodFacts(pendingUpc);
    entryName.value = offName ?? '';
    setStatus(offName ? 'New product — enter the price.' : 'Product name not found — enter it manually.');
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
    pendingUpc = null;
    setStatus('Added. Tap "Start scan" for the next item.');
}

function addCoupon() {
    const row = createTripRow({
        upc: null,
        entryType: 'coupon',
        name: 'Coupons',
        price: '',
        quantity: '1',
    });

    tripList.prepend(row);
    updateTripCount();
    row.querySelector('.item-price').focus();
}

async function submitTrip() {
    const items = [...tripList.children].map((row) => ({
        upc: row.dataset.upc || null,
        entry_type: row.dataset.entryType,
        product_name: row.querySelector('.item-name').value.trim(),
        price: parseFloat(row.querySelector('.item-price').value),
        quantity: parseInt(row.querySelector('.item-quantity').value, 10) || 1,
    }));

    if (items.some((item) => !item.product_name || Number.isNaN(item.price))) {
        submitStatus.textContent = 'Every item needs a name and a valid price.';
        return;
    }

    submitTripBtn.disabled = true;
    submitStatus.textContent = 'Saving trip…';

    try {
        const response = await fetch('/trips', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
            body: JSON.stringify({ items }),
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message ?? 'Save failed');
        }

        tripList.innerHTML = '';
        updateTripCount();
        submitStatus.textContent = 'Trip saved!';
    } catch (err) {
        submitStatus.textContent = `Save failed: ${err.message}`;
        submitTripBtn.disabled = false;
    }
}

startBtn.addEventListener('click', startScan);
addItemBtn.addEventListener('click', addItemToTrip);
addCouponBtn.addEventListener('click', addCoupon);
submitTripBtn.addEventListener('click', submitTrip);

init();
