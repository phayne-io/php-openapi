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
 * Class Info
 *
 * The object provides metadata about the API.
 *
 *  The metadata MAY be used by the clients if needed, and MAY be presented in editing or documentation generation tools for convenience.
 *
 * @link https://github.com/OAI/OpenAPI-Specification/blob/3.0.2/versions/3.0.2.md#infoObject
 *
 * @property string $title
 * @property string $description
 * @property string $termsOfService
 * @property Contact|null $contact
 * @property License|null $license
 * @property string $version
 * @package Phayne\OpenAPI\Specification
 */
class Info extends SpecBaseObject
{
    #[Override]
    protected function attributes(): array
    {
        return [
            'title' => Type::STRING,
            'description' => Type::STRING,
            'termsOfService' => Type::STRING,
            'contact' => Contact::class,
            'license' => License::class,
            'version' => Type::STRING,
        ];
    }

    #[Override]
    protected function performValidation(): void
    {
        $this->requireProperties(['title', 'version']);
    }
}
