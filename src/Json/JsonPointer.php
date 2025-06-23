<?php

/**
 * This file is part of phayne-io/php-openapi and is proprietary and confidential.
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 *
 * @see       https://github.com/phayne-io/php-openapi for the canonical source repository
 * @copyright Copyright (c) 2024-2025 Phayne Limited. (https://phayne.io)
 */

declare(strict_types=1);

namespace Phayne\OpenAPI\Json;

use ArrayAccess;
use Override;
use Phayne\OpenAPI\Exception\InvalidJsonPointerSyntaxException;
use Phayne\OpenAPI\Exception\NonexistentJsonPointerReferenceException;
use Stringable;

use function array_key_exists;
use function array_map;
use function array_pop;
use function explode;
use function implode;
use function is_array;
use function is_object;
use function preg_match;
use function property_exists;
use function strtr;
use function substr;

/**
 * Class JsonPointer
 *
 * @package Phayne\OpenAPI\Json
 */
final readonly class JsonPointer implements Stringable
{
    public function __construct(public string $pointer)
    {
       if (! preg_match('~^(/[^/]*)*$~', $this->pointer)) {
           throw new InvalidJsonPointerSyntaxException('Invalid JSON Pointer syntax: ' . $this->pointer);
       }
    }

    public static function encode(string $string): string
    {
        return strtr($string, [
            '~' => '~0',
            '/' => '~1',
        ]);
    }

    public static function decode(string $string): string
    {
        return strtr($string, [
            '~1' => '/',
            '~0' => '~',
        ]);
    }

    public function path(): array
    {
        if (empty($this->pointer)) {
            return [];
        }

        $pointer = substr($this->pointer, 1);

        return array_map([self::class, 'decode'], explode(('/'), $pointer));
    }

    public function append(string $subPath): JsonPointer
    {
        return new JsonPointer($this->pointer . '/' . JsonPointer::encode($subPath));
    }

    public function parent(): ?JsonPointer
    {
        $path = $this->path();

        if (empty($path)) {
            return null;
        }

        array_pop($path);

        if (empty($path)) {
            return new JsonPointer('');
        }

        return new JsonPointer('/' . implode('/', array_map([self::class, 'encode'], $path)));
    }

    public function evaluate(mixed $jsonDocument): mixed
    {
        $currentReference = $jsonDocument;
        $currentPath = '';

        foreach ($this->path() as $part) {
            if (is_array($currentReference)) {
                if ($part === '-' || ! array_key_exists($part, $currentReference)) {
                    throw new NonexistentJsonPointerReferenceException(sprintf(
                        'Failed to evaluate pointer "%s". Array has no member "%s" at path "%s".',
                        $this->pointer,
                        $part,
                        $currentPath
                    ));
                }
                $currentReference = $currentReference[$part];
            } elseif ($currentReference instanceof ArrayAccess) {
                if (false === $currentReference->offsetExists($part)) {
                    throw new NonexistentJsonPointerReferenceException(sprintf(
                        'Failed to evaluate pointer "%s". Array has no member "%s" at path "%s".',
                        $this->pointer,
                        $part,
                        $currentPath
                    ));
                }
                $currentReference = $currentReference[$part];
            } elseif (is_object($currentReference)) {
                if (false === isset($currentReference->$part) && false === property_exists($currentReference, $part)) {
                    throw new NonexistentJsonPointerReferenceException(sprintf(
                        'Failed to evaluate pointer "%s". Object has no member "%s" at path "%s".',
                        $this->pointer,
                        $part,
                        $currentPath
                    ));
                }
                $currentReference = $currentReference->$part;
            } else {
                throw new NonexistentJsonPointerReferenceException(sprintf(
                    'Failed to evaluate pointer "%s". Value at path "%s" is not an array or object',
                    $this->pointer,
                    $currentPath,
                ));
            }

            $currentPath = sprintf('%s/%s', $currentPath, $part);
        }

        return $currentReference;
    }

    #[Override]
    public function __toString(): string
    {
        return $this->pointer;
    }
}
