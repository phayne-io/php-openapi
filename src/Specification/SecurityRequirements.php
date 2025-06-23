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

use Override;
use Phayne\OpenAPI\SpecBaseObject;

/**
 * Class SecurityRequirements
 *
 * Lists the required security schemes to execute this operation.
 *
 * @link https://github.com/OAI/OpenAPI-Specification/blob/3.0.2/versions/3.0.2.md#securityRequirementObject
 * @package Phayne\OpenAPI\Specification
 */
class SecurityRequirements extends SpecBaseObject
{
    private array $securityRequirements = [];

    public function __construct(array $data)
    {
        parent::__construct($data);

        foreach ($data as $index => $value) {
            if (is_numeric($index)) {
                $this->securityRequirements[array_keys($value)[0]] = new SecurityRequirement(array_values($value)[0]);
            } else {
                $this->securityRequirements[$index] = $value;
            }
        }

        if ($data === []) {
            $this->securityRequirements = [];
        }
    }

    #[Override]
    public function serializableData(): object|array
    {
        $data = [];
        foreach ($this->securityRequirements ?? [] as $name => $securityRequirement) {
            /** @var SecurityRequirement $securityRequirement */
            $data[] = [$name => $securityRequirement->serializableData()];
        }
        return $data;
    }

    #[Override]
    protected function attributes(): array
    {
        return [];
    }

    #[Override]
    protected function performValidation(): void
    {
    }

    public function requirement(string $name): mixed
    {
        return $this->securityRequirements[$name] ?? null;
    }

    public function requirements(): array
    {
        return $this->securityRequirements;
    }
}
