<?php

/**
 * This file is part of phayne-io/php-openapi and is proprietary and confidential.
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 *
 * @see       https://github.com/phayne-io/php-openapi for the canonical source repository
 * @copyright Copyright (c) 2024-2025 Phayne Limited. (https://phayne.io)
 */

declare(strict_types=1);

namespace Phayne\OpenAPI\Exception;

use Exception;
use Phayne\OpenAPI\Json\JsonPointer;

/**
 * Class UnresolvableReferenceException
 *
 * @package Phayne\OpenAPI\Exception
 */
class UnresolvableReferenceException extends Exception
{
    public ?JsonPointer $context = null;
}
