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
 * Class License
 *
 * @package Phayne\OpenAPI\Specification
 */
class License extends SpecBaseObject
{
    #[Override]
    protected function attributes(): array
    {
        return [
            'name' => Type::STRING,
            'url' => Type::STRING,
        ];
    }

    #[Override]
    protected function performValidation(): void
    {
        $this->requireProperties(['name']);
        $this->validateUrl('url');
    }
}
