<?php

/**
 * This file is part of phayne-io/php-openapi and is proprietary and confidential.
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 *
 * @see       https://github.com/phayne-io/php-openapi for the canonical source repository
 * @copyright Copyright (c) 2024-2025 Phayne Limited. (https://phayne.io)
 */

declare(strict_types=1);

namespace Phayne\OpenAPI\Specification;

/**
 * Enum Type
 *
 * @package Phayne\OpenAPI\Specification
 */
final readonly class Type
{
    public const string ANY = 'any';
    public const string INTEGER = 'integer';
    public const string NUMBER = 'number';
    public const string STRING = 'string';
    public const string BOOLEAN = 'boolean';
    public const string OBJECT = 'object';
    public const string ARRAY = 'array';

    public static function isScalar(mixed $type): bool
    {
        return match ($type) {
            self::INTEGER,
            self::NUMBER,
            self::STRING,
            self::BOOLEAN => true,
            default => false,
        };
    }
}
