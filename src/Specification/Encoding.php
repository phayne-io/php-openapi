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
 * Class Encoding
 *
 * A single encoding definition applied to a single schema property.
 *
 * @link https://github.com/OAI/OpenAPI-Specification/blob/3.0.2/versions/3.0.2.md#encodingObject
 *
 * @property string $contentType
 * @property Header[]|Reference[] $headers
 * @property string $style
 * @property boolean $explode
 * @property boolean $allowReserved
 * @package Phayne\OpenAPI\Specification
 */
class Encoding extends SpecBaseObject
{
    private array $attributeDefaults = [];

    public function __construct(array $data, ?Schema $schema = null)
    {
        if (isset($data['style'])) {
            // Spec: When style is form, the default value is true.
            $this->attributeDefaults['explode'] = ($data['style'] === 'form');
        }
        if ($schema !== null) {
            // Spec: Default value depends on the property type:
            // for string with format being binary – application/octet-stream;
            // for other primitive types – text/plain;
            // for object - application/json;
            // for array – the default is defined based on the inner type.
            switch ($schema->type === 'array' ? ($schema->items->type ?? 'array') : $schema->type) {
                case Type::STRING:
                    if ($schema->format === 'binary') {
                        $this->attributeDefaults['contentType'] = 'application/octet-stream';
                        break;
                    }
                // no break here
                case Type::BOOLEAN:
                case Type::INTEGER:
                case Type::NUMBER:
                    $this->attributeDefaults['contentType'] = 'text/plain';
                    break;
                case 'object':
                    $this->attributeDefaults['contentType'] = 'application/json';
                    break;
            }
        }

        parent::__construct($data);
    }

    #[Override]
    protected function attributes(): array
    {
        return [
            'contentType' => Type::STRING,
            'headers' => [Type::STRING, Header::class],
            // TODO implement default values for style
            // https://github.com/OAI/OpenAPI-Specification/blob/3.0.2/versions/3.0.2.md#encodingObject
            'style' => Type::STRING,
            'explode' => Type::BOOLEAN,
            'allowReserved' => Type::BOOLEAN,
        ];
    }

    #[Override]
    protected function performValidation(): void
    {
    }

    #[Override]
    protected function attributeDefaults(): array
    {
        return $this->attributeDefaults;
    }
}
