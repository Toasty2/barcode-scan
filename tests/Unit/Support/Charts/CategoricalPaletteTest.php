<?php

namespace Tests\Unit\Support\Charts;

use App\Support\Charts\CategoricalPalette;
use Tests\TestCase;

class CategoricalPaletteTest extends TestCase
{
    public function test_different_indexes_return_different_colours(): void
    {
        $this->assertNotSame(CategoricalPalette::colour(0), CategoricalPalette::colour(1));
    }

    public function test_the_same_index_always_returns_the_same_colour(): void
    {
        $this->assertSame(CategoricalPalette::colour(2), CategoricalPalette::colour(2));
    }

    public function test_it_cycles_once_the_index_exceeds_the_palette_size(): void
    {
        $this->assertSame(CategoricalPalette::colour(0), CategoricalPalette::colour(7));
    }
}
