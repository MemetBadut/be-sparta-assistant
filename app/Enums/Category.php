<?php

namespace App\Enums;

enum Category: string
{
    case Network = 'Wi-Fi / Network';
    case Windows = 'Windows';
    case Computer = 'Laptop / PC';
    case Printer = 'Printer';
    case Software = 'Basic Software Issues';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $category): string => $category->value, self::cases());
    }

    public static function requiresDeviceCode(string $category): bool
    {
        return in_array($category, [self::Computer->value, self::Printer->value], true);
    }
}
