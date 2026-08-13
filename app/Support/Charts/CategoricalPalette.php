<?php

namespace App\Support\Charts;

class CategoricalPalette
{
    /**
     * Okabe & Ito's (2008) qualitative palette — chosen to remain
     * distinguishable under the common forms of colour blindness
     * (protanopia/deuteranopia), rather than picked arbitrarily. Black is
     * omitted from the original 8-colour set: Chart.js's own axis/grid/text
     * already default to black/grey, so a data series in black would blend
     * into the chrome instead of standing out.
     *
     * Every chart in this project that needs distinct per-series colours
     * (rather than a single semantic colour) should draw from this one
     * list, rather than each widget hardcoding its own.
     *
     * Cycles once a chart has more series than colours here (7) — the same
     * ceiling every fixed qualitative palette has (e.g. D3's Tableau10
     * cycles past 10). Not expected to matter for this project's own data
     * for several years yet.
     */
    private const COLOURS = [
        '#E69F00', // orange
        '#56B4E9', // sky blue
        '#009E73', // bluish green
        '#F0E442', // yellow
        '#0072B2', // blue
        '#D55E00', // vermillion
        '#CC79A7', // reddish purple
    ];

    public static function colour(int $index): string
    {
        return self::COLOURS[$index % count(self::COLOURS)];
    }
}
