const BARCODE_FORMATS = ['ean_13', 'ean_8', 'upc_a', 'upc_e'];

const preview = document.getElementById('preview');
const status = document.getElementById('status');
const startBtn = document.getElementById('start-btn');
const scanAgainBtn = document.getElementById('scan-again-btn');
const resultEl = document.getElementById('result');
const resultValue = document.getElementById('result-value');
const resultFormat = document.getElementById('result-format');

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

function onDetected(barcode) {
    stopScan();
    setStatus('');
    resultValue.textContent = barcode.rawValue;
    resultFormat.textContent = barcode.format;
    resultEl.hidden = false;
}

startBtn.addEventListener('click', startScan);
scanAgainBtn.addEventListener('click', startScan);

init();
