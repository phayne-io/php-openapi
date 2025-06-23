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
 * Class Link
 *
 * The Link object represents a possible design-time link for a response.
 *
 * @link https://github.com/OAI/OpenAPI-Specification/blob/3.0.2/versions/3.0.2.md#linkObject
 *
 * @property string $operationRef
 * @property string $operationId
 * @property array $parameters
 * @property mixed $requestBody
 * @property string $description
 * @property Server|null $server
 * @package Phayne\OpenAPI\Specification
 */
class Link extends SpecBaseObject
{
    #[Override]
    protected function attributes(): array
    {
        return [
            'operationRef' => Type::STRING,
            'operationId' => Type::STRING,
            'parameters' => [Type::STRING, Type::ANY], // TODO: how to specify {expression}?
            'requestBody' => Type::ANY, // TODO: how to specify {expression}?
            'description' => Type::STRING,
            'server' => Server::class,
        ];
    }

    #[Override]
    protected function performValidation(): void
    {
        if (! empty($this->operationId) && ! empty($this->operationRef)) {
            $this->addError('Link: operationId and operationRef are mutually exclusive.');
        }
    }
}
