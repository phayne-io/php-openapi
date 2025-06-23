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
 * Class Response
 *
 * Describes a single response from an API Operation, including design-time, static links to operations based on the response.
 *
 * @link https://github.com/OAI/OpenAPI-Specification/blob/3.0.2/versions/3.0.2.md#responseObject
 *
 * @property string $description
 * @property Header[]|Reference[] $headers
 * @property MediaType[] $content
 * @property Link[]|Reference[] $links
 * @package Phayne\OpenAPI\Specification
 */
class Response extends SpecBaseObject
{
    #[Override]
    protected function attributes(): array
    {
        return [
            'description' => Type::STRING,
            'headers' => [Type::STRING, Header::class],
            'content' => [Type::STRING, MediaType::class],
            'links' => [Type::STRING, Link::class],
        ];
    }

    #[Override]
    protected function performValidation(): void
    {
        $this->requireProperties(['description']);
    }
}
