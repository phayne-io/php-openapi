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
 * Class Contact
 *
 * Contact information for the exposed API.
 *
 * @link https://github.com/OAI/OpenAPI-Specification/blob/3.0.2/versions/3.0.2.md#contactObject
 *
 * @property string $name
 * @property string $url
 * @property string $email
 * @package Phayne\OpenAPI\Specification
 */
class Contact extends SpecBaseObject
{
    #[Override]
    protected function attributes(): array
    {
        return [
            'name' => Type::STRING,
            'url' => Type::STRING,
            'email' => Type::STRING,
        ];
    }

    #[Override]
    protected function performValidation(): void
    {
        $this->validateEmail('email');
        $this->validateUrl('url');
    }
}
