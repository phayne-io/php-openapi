<?php

/**
 * This file is part of phayne-io/php-openapi and is proprietary and confidential.
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 *
 * @see       https://github.com/phayne-io/php-openapi for the canonical source repository
 * @copyright Copyright (c) 2024-2025 Phayne Limited. (https://phayne.io)
 */

declare(strict_types=1);

namespace Phayne\OpenAPI;

use Phayne\OpenAPI\Json\JsonPointer;

/**
 * Interface DocumentContextInterface
 *
 * @package Phayne\OpenAPI
 */
interface DocumentContextInterface
{
    public ?SpecObjectInterface $baseDocument {
        get;
    }

    public ?JsonPointer $documentPosition {
        get;
    }

    public function setDocumentContext(SpecObjectInterface $baseDocument, JsonPointer $jsonPointer): void;
}
