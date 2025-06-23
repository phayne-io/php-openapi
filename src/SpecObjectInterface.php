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

/**
 * Interface SpecObjectInterface
 *
 * @package Phayne\OpenAPI
 */
interface SpecObjectInterface
{
    public function __construct(array $data);

    public function errors(): array;

    public function serializableData(): object|array;

    public function validate(): bool;

    public function resolveReferences(?ReferenceContext $context = null): void;

    public function setReferenceContext(ReferenceContext $context): void;
}
