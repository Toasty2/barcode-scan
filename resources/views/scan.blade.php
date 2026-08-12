<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-base-url" content="{{ url('/') }}">
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

    <button id="retry-lookup-btn" type="button" class="w-full py-2 rounded-lg bg-amber-700 text-sm font-medium hidden">
        Retry lookup
    </button>

    <form id="entry-form" class="space-y-3 hidden">
        <div>
            <label for="entry-name" class="block text-sm mb-1">Product name</label>
            <input id="entry-name" type="text" class="w-full p-2 rounded bg-gray-800 border border-gray-700" required>
        </div>
        <div class="flex gap-3">
            <div class="flex-1">
                <label for="entry-price" class="block text-sm mb-1">Price (£)</label>
                <input id="entry-price" type="text" inputmode="decimal" class="w-full p-2 rounded bg-gray-800 border border-gray-700" required>
            </div>
            <div class="w-20">
                <label for="entry-quantity" class="block text-sm mb-1">Qty</label>
                <input id="entry-quantity" type="number" min="1" value="1" class="w-full p-2 rounded bg-gray-800 border border-gray-700" required>
            </div>
        </div>
        <p id="entry-stale-warning" class="text-amber-400 text-sm hidden">
            This price hasn't been confirmed in a while — please double check it.
        </p>
        <button id="add-item-btn" type="button" class="w-full py-3 rounded-lg bg-green-700 font-medium">
            Add to trip
        </button>
    </form>

    <div>
        <h2 class="text-sm font-semibold text-gray-300 mb-2">This trip (<span id="trip-count">0</span> items)</h2>
        <div id="trip-list" class="space-y-2 max-h-96 overflow-y-auto"></div>
    </div>

    <div>
        <label for="trip-discount" class="block text-sm mb-1">Coupons / discount for this trip (£)</label>
        <input id="trip-discount" type="text" inputmode="decimal" placeholder="0.00" class="w-full p-2 rounded bg-gray-800 border border-gray-700">
    </div>

    <button id="submit-trip-btn" type="button" class="w-full py-3 rounded-lg bg-blue-700 disabled:bg-gray-600 font-medium" disabled>
        Submit trip
    </button>

    <p id="submit-status" class="text-center text-sm"></p>
</main>
</body>
</html>
