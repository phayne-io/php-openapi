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
 * Class Xml
 *
 * A metadata object that allows for more fine-tuned XML model definitions.
 *
 * @link https://github.com/OAI/OpenAPI-Specification/blob/3.0.2/versions/3.0.2.md#xmlObject
 *
 * @property string $name
 * @property string $namespace
 * @property string $prefix
 * @property boolean $attribute
 * @property boolean $wrapped
 * @package Phayne\OpenAPI\Specification
 */
class Xml extends SpecBaseObject
{
    #[Override]
    protected function attributes(): array
    {
        return [
            'name' => Type::STRING,
            'namespace' => Type::STRING,
            'prefix' => Type::STRING,
            'attribute' => Type::BOOLEAN,
            'wrapped' => Type::BOOLEAN,
        ];
    }

    #[Override]
    protected function performValidation(): void
    {
    }
}
