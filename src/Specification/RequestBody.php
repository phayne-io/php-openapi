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
 * Class RequestBody
 *
 * Describes a single request body.
 *
 * @link https://github.com/OAI/OpenAPI-Specification/blob/3.0.2/versions/3.0.2.md#requestBodyObject
 *
 * @property string $description
 * @property MediaType[] $content
 * @property boolean $required
 * @package Phayne\OpenAPI\Specification
 */
class RequestBody extends SpecBaseObject
{
    #[Override]
    protected function attributes(): array
    {
        return [
            'description' => Type::STRING,
            'content' => [Type::STRING, MediaType::class],
            'required' => Type::BOOLEAN,
        ];
    }

    #[Override]
    protected function performValidation(): void
    {
        $this->requireProperties(['content']);
    }
}
