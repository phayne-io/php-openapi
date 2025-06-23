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
 * Class ServerVariable
 *
 * An object representing a Server Variable for server URL template substitution.
 *
 * @link https://github.com/OAI/OpenAPI-Specification/blob/3.0.2/versions/3.0.2.md#serverVariableObject
 *
 * @property string[] $enum
 * @property string $default
 * @property string $description
 * @package Phayne\OpenAPI\Specification
 */
class ServerVariable extends SpecBaseObject
{
    #[Override]
    protected function attributes(): array
    {
        return [
            'enum' => [Type::STRING],
            'default' => Type::STRING,
            'description' => Type::STRING,
        ];
    }

    #[Override]
    protected function performValidation(): void
    {
        $this->requireProperties(['default']);
    }
}
