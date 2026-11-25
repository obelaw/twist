<?php

declare(strict_types=1);

namespace Obelaw\Twist\Addons;

class AddonsPool
{
    public const LEVELONE = '/*/twist.php';

    public const LEVELTWO = '/**/*/twist.php';

    /** @var list<array{level: string, path: string}> */
    private static array $poolPaths = [];

    /** @var list<string> */
    private static array $twistPaths = [];

    public static function setPoolPath(string $path, ?string $level = null): void
    {
        static::$poolPaths[] = [
            'level' => $level ?? self::LEVELTWO,
            'path' => $path,
        ];
    }

    public static function hasPools(): bool
    {
        return static::$poolPaths !== [] || static::$twistPaths !== [];
    }

    public static function loadTwist(string $twistPath): void
    {
        static::$twistPaths[] = $twistPath;
    }

    public static function scan(): void
    {
        foreach (static::$poolPaths as $scan) {
            foreach (glob($scan['path'] . $scan['level']) ?: [] as $obelaw) {
                require $obelaw;
            }
        }

        foreach (static::$twistPaths as $twistPath) {
            if (is_file($twistPath)) {
                require $twistPath;
            }
        }
    }

    /**
     * @return list<array{level: string, path: string}>
     */
    public static function getPoolPaths(): array
    {
        return static::$poolPaths;
    }

    public static function reset(): void
    {
        static::$poolPaths = [];
        static::$twistPaths = [];
    }
}
