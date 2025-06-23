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

use function array_keys;
use function is_array;
use function preg_match;

/**
 * Class Components
 *
 * All objects defined within the components object will have no effect on the API unless they are explicitly referenced
 *  from properties outside the components object.
 *
 * @link https://github.com/OAI/OpenAPI-Specification/blob/3.0.2/versions/3.0.2.md#componentsObject
 *
 * @property Schema[]|Reference[] $schemas
 * @property Response[]|Reference[] $responses
 * @property Parameter[]|Reference[] $parameters
 * @property Example[]|Reference[] $examples
 * @property RequestBody[]|Reference[] $requestBodies
 * @property Header[]|Reference[] $headers
 * @property SecurityScheme[]|Reference[] $securitySchemes
 * @property Link[]|Reference[] $links
 * @property Callback[]|Reference[] $callbacks
 * @package Phayne\OpenAPI\Specification
 */
class Components extends SpecBaseObject
{
    #[Override]
    protected function attributes(): array
    {
        return [
            'schemas' => [Type::STRING, Schema::class],
            'responses' => [Type::STRING, Response::class],
            'parameters' => [Type::STRING, Parameter::class],
            'examples' => [Type::STRING, Example::class],
            'requestBodies' => [Type::STRING, RequestBody::class],
            'headers' => [Type::STRING, Header::class],
            'securitySchemes' => [Type::STRING, SecurityScheme::class],
            'links' => [Type::STRING, Link::class],
            'callbacks' => [Type::STRING, Callback::class],
        ];
    }

    #[Override]
    protected function performValidation(): void
    {
        foreach (array_keys($this->attributes()) as $attribute) {
            if (is_array($this->$attribute)) {
                foreach ($this->$attribute as $k => $v) {
                    if (!preg_match('~^[a-zA-Z0-9\.\-_]+$~', (string)$k)) {
                        $this->addError(
                            "Invalid key '$k' used in Components Object for attribute '$attribute', does not match ^[a-zA-Z0-9\.\-_]+\$." //phpcs:ignore
                        );
                    }
                }
            }
        }
    }
}
