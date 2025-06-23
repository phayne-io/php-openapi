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
 * Class Operation
 *
 * Describes a single API operation on a path.
 *
 * @link https://github.com/OAI/OpenAPI-Specification/blob/3.0.2/versions/3.0.2.md#operationObject
 *
 * @property string[] $tags
 * @property string $summary
 * @property string $description
 * @property ExternalDocumentation|null $externalDocs
 * @property string $operationId
 * @property Parameter[]|Reference[] $parameters
 * @property RequestBody|Reference|null $requestBody
 * @property Responses|Response[]|null $responses
 * @property Callback[]|Reference[] $callbacks
 * @property bool $deprecated
 * @property SecurityRequirement[] $security
 * @property Server[] $servers
 * @package Phayne\OpenAPI\Specification
 */
class Operation extends SpecBaseObject
{
    #[Override]
    protected function attributes(): array
    {
        return [
            'tags' => [Type::STRING],
            'summary' => Type::STRING,
            'description' => Type::STRING,
            'externalDocs' => ExternalDocumentation::class,
            'operationId' => Type::STRING,
            'parameters' => [Parameter::class],
            'requestBody' => RequestBody::class,
            'responses' => Responses::class,
            'callbacks' => [Type::STRING, Callback::class],
            'deprecated' => Type::BOOLEAN,
            'security' => SecurityRequirements::class,
            'servers' => [Server::class],
        ];
    }

    #[Override]
    protected function attributeDefaults(): array
    {
        return [
            'security' => null,
        ];
    }

    #[Override]
    protected function performValidation(): void
    {
        $this->requireProperties(['responses']);
    }
}
