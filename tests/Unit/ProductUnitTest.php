<?php

namespace Tests\Unit;

use App\Enums\ProductUnit;
use App\Models\Product;
use PHPUnit\Framework\TestCase;

class ProductUnitTest extends TestCase
{
    public function test_product_unit_enum_has_common_inventory_units(): void
    {
        $this->assertSame('piece', ProductUnit::Piece->value);
        $this->assertSame('Piece (pc)', ProductUnit::Piece->label());
        $this->assertSame('kg', ProductUnit::Kilogram->value);
        $this->assertSame('Kilogram (kg)', ProductUnit::Kilogram->label());
    }

    public function test_product_defaults_to_piece_and_casts_unit_to_enum(): void
    {
        $product = new Product;

        $this->assertSame(ProductUnit::Piece, $product->unit);

        $product->unit = ProductUnit::Pack;

        $this->assertSame(ProductUnit::Pack, $product->unit);
    }
}
