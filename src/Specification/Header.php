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

/**
 * Class Header
 *
 * @package Phayne\OpenAPI\Specification
 */
class Header extends Parameter
{
    #[Override]
    protected function performValidation(): void
    {
        if (! empty($this->name)) {
            $this->addError("'name' must not be specified in Header Object.");
        }

        if (! empty($this->in)) {
            $this->addError("'in' must not be specified in Header Object.");
        }

        if (! empty($this->content) && !empty($this->schema)) {
            $this->addError(
                "A Header Object MUST contain either a schema property, or a content property, but not both."
            );
        }
    }
}
