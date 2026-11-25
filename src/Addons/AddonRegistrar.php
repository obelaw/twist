<?php

declare(strict_types=1);

namespace Obelaw\Twist\Addons;

use Obelaw\Twist\Facades\Twist;

class AddonRegistrar
{
    /** @var array<string, array{path: string, panels: list<string>}> */
    private static array $paths = [];

    /**
     * @param string|list<string>|null $panels
     */
    public static function register(string $name, string $path, string|array|null $panels = null): void
    {
        static::$paths[$name] = [
            'path' => $path,
            'panels' => is_array($panels) ? array_values($panels) : [$panels ?? Twist::defaultPanel()],
        ];
    }

    /**
     * @return array<string, array{path: string, panels: list<string>}>
     */
    public static function getPaths(): array
    {
        return static::$paths;
    }

    public static function reset(): void
    {
        static::$paths = [];
    }
}
