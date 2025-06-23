<?php

/**
 * This file is part of phayne-io/php-openapi and is proprietary and confidential.
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 *
 * @see       https://github.com/phayne-io/php-openapi for the canonical source repository
 * @copyright Copyright (c) 2024-2025 Phayne Limited. (https://phayne.io)
 */

declare(strict_types=1);

namespace Phayne\OpenAPI;

use function array_key_exists;

/**
 * Class ReferenceContextCache
 *
 * @package Phayne\OpenAPI
 */
class ReferenceContextCache
{
    private array $cache = [];

    public function set(string $ref, ?string $type, mixed $data): void
    {
        $this->cache[$ref][$type ?? ''] = $data;

        if ($type !== null && ! isset($this->cache[$ref][''])) {
            $this->cache[$ref][''] = $data;
        }
    }

    public function get(string $ref, ?string $type = null): mixed
    {
        return $this->cache[$ref][$type ?? ''] ?? null;
    }

    public function has(string $ref, ?string $type = null): bool
    {
        return isset($this->cache[$ref]) && array_key_exists($type ?? '', $this->cache[$ref]);
    }
}
