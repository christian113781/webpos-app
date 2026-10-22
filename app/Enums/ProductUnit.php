<?php

namespace App\Enums;

enum ProductUnit: string
{
    case Piece = 'piece';
    case Pack = 'pack';
    case Box = 'box';
    case Carton = 'carton';
    case Bottle = 'bottle';
    case Can = 'can';
    case Sachet = 'sachet';
    case Bag = 'bag';
    case Dozen = 'dozen';
    case Kilogram = 'kg';
    case Gram = 'g';
    case Liter = 'l';
    case Milliliter = 'ml';

    public function label(): string
    {
        return match ($this) {
            self::Piece => 'pcs',
            self::Kilogram => 'Kilogram (kg)',
            self::Gram => 'Gram (g)',
            self::Liter => 'Liter (L)',
            self::Milliliter => 'Milliliter (mL)',
            default => ucfirst($this->value),
        };
    }
}
