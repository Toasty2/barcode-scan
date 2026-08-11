<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Scan a barcode</title>
    @vite(['resources/css/app.css', 'resources/js/scanner.js'])
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen">
<main class="max-w-md mx-auto p-4 space-y-4">
    <h1 class="text-xl font-semibold text-center">Scan a barcode</h1>

    <div class="relative w-full aspect-[3/4] bg-black rounded-lg overflow-hidden">
        <video id="preview" class="w-full h-full object-cover" playsinline autoplay muted></video>
    </div>

    <p id="status" class="text-center min-h-[1.5em] text-sm text-gray-300"></p>

    <button id="start-btn" type="button" class="w-full py-3 rounded-lg bg-green-700 disabled:bg-gray-600 font-medium">
        Start scan
    </button>

    <div id="result" class="text-center space-y-2" hidden>
        <p>Detected: <strong id="result-value"></strong> (<span id="result-format"></span>)</p>
        <button id="scan-again-btn" type="button" class="w-full py-3 rounded-lg bg-gray-700 font-medium">
            Scan again
        </button>
    </div>
</main>
</body>
</html>
