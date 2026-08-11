const BARCODE_FORMATS = ['ean_13', 'ean_8', 'upc_a', 'upc_e'];

const preview = document.getElementById('preview');
const status = document.getElementById('status');
const startBtn = document.getElementById('start-btn');
const scanAgainBtn = document.getElementById('scan-again-btn');
const resultEl = document.getElementById('result');
const resultValue = document.getElementById('result-value');
const resultFormat = document.getElementById('result-format');
const productName = document.getElementById('product-name');

let detector = null;
let stream = null;
let cancelled = false;

function setStatus(message) {
    status.textContent = message;
}

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
    resultEl.hidden = true;
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

async function lookupProductName(barcode) {
    const url = `https://world.openfoodfacts.org/api/v2/product/${encodeURIComponent(barcode)}.json?fields=product_name`;

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
    setStatus('');
    resultValue.textContent = barcode.rawValue;
    resultFormat.textContent = barcode.format;
    resultEl.hidden = false;

    productName.textContent = 'Looking up product name…';
    const name = await lookupProductName(barcode.rawValue);
    productName.textContent = name ?? 'Product name not found';
}

startBtn.addEventListener('click', startScan);
scanAgainBtn.addEventListener('click', startScan);

init();
