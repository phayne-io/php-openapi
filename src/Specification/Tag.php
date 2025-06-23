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
 * Class Tag
 *
 * Adds metadata to a single tag that is used by the Operation Object.
 *
 * @link https://github.com/OAI/OpenAPI-Specification/blob/3.0.2/versions/3.0.2.md#tagObject
 *
 * @property string $name
 * @property string $description
 * @property ExternalDocumentation|null $externalDocs
 * @package Phayne\OpenAPI\Specification
 */
class Tag extends SpecBaseObject
{
    #[Override]
    protected function attributes(): array
    {
        return [
            'name' => Type::STRING,
            'description' => Type::STRING,
            'externalDocs' => ExternalDocumentation::class,
        ];
    }

    #[Override]
    protected function performValidation(): void
    {
        $this->requireProperties(['name']);
    }
}
