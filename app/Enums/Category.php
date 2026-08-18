<?php

namespace App\Enums;

enum Category: string
{
    case Network = 'wifi_network';
    case Windows = 'windows';
    case Computer = 'laptop_pc';
    case Printer = 'printer';
    case Software = 'basic_software_issues';

    public function label(): string
    {
        return match ($this) {
            self::Network => 'Wi-Fi / Network',
            self::Windows => 'Windows',
            self::Computer => 'Laptop / PC',
            self::Printer => 'Printer',
            self::Software => 'Basic Software Issues',
        };
    }

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
