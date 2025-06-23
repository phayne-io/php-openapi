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
 * Class Server
 *
 * An object representing a Server.
 *
 * @link https://github.com/OAI/OpenAPI-Specification/blob/3.0.2/versions/3.0.2.md#serverObject
 *
 * @property string $url
 * @property string $description
 * @property ServerVariable[] $variables
 * @package Phayne\OpenAPI\Specification
 */
class Server extends SpecBaseObject
{
    #[Override]
    protected function attributes(): array
    {
        return [
            'url' => Type::STRING,
            'description' => Type::STRING,
            'variables' => [Type::STRING, ServerVariable::class],
        ];
    }

    #[Override]
    protected function performValidation(): void
    {
        $this->requireProperties(['url']);
    }
}
