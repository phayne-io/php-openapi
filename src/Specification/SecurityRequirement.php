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
 * Class SecurityRequirement
 *
 * A required security scheme to execute this operation.
 *
 * @link https://github.com/OAI/OpenAPI-Specification/blob/3.0.2/versions/3.0.2.md#securityRequirementObject
 * @package Phayne\OpenAPI\Specification
 */
class SecurityRequirement extends SpecBaseObject
{
    private array $securityRequirement;

    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->securityRequirement = $data;
    }

    #[Override]
    public function serializableData(): object|array
    {
        return $this->securityRequirement;
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
}
