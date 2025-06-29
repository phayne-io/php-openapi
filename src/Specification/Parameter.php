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

use function count;
use function in_array;

/**
 * Class Parameter
 *
 * Describes a single operation parameter.
 *
 * @link https://github.com/OAI/OpenAPI-Specification/blob/3.0.2/versions/3.0.2.md#parameterObject
 *
 * @property string $name
 * @property string $in
 * @property string $description
 * @property bool $required
 * @property bool $deprecated
 * @property bool $allowEmptyValue
 *
 * @property string $style
 * @property boolean $explode
 * @property boolean $allowReserved
 * @property Schema|Reference|null $schema
 * @property mixed $example
 * @property Example[] $examples
 *
 * @property MediaType[] $content
 * @package Phayne\OpenAPI\Specification
 */
class Parameter extends SpecBaseObject
{
    private array $attributeDefaults = [];

    public function __construct(array $data)
    {
        if (isset($data['in'])) {
            // Spec: Default values (based on value of in):
            // for query - form;
            // for path - simple;
            // for header - simple;
            // for cookie - form.
            switch ($data['in']) {
                case 'query':
                case 'cookie':
                    $this->attributeDefaults['style'] = 'form';
                    $this->attributeDefaults['explode'] = true;
                    break;
                case 'path':
                case 'header':
                    $this->attributeDefaults['style'] = 'simple';
                    $this->attributeDefaults['explode'] = false;
                    break;
            }
        }

        if (isset($data['style'])) {
            // Spec: When style is form, the default value is true. For all other styles, the default value is false.
            $this->attributeDefaults['explode'] = ($data['style'] === 'form');
        }

        parent::__construct($data);
    }

    #[Override]
    protected function attributes(): array
    {
        return [
            'name' => Type::STRING,
            'in' => Type::STRING,
            'description' => Type::STRING,
            'required' => Type::BOOLEAN,
            'deprecated' => Type::BOOLEAN,
            'allowEmptyValue' => Type::BOOLEAN,

            'style' => Type::STRING,
            'explode' => Type::BOOLEAN,
            'allowReserved' => Type::BOOLEAN,
            'schema' => Schema::class,
            'example' => Type::ANY,
            'examples' => [Type::STRING, Example::class],

            'content' => [Type::STRING, MediaType::class],
        ];
    }

    #[Override]
    protected function performValidation(): void
    {
        $this->requireProperties(['name', 'in']);

        if ($this->in === 'path') {
            $this->requireProperties(['required']);

            if (! $this->required) {
                $this->addError("Parameter 'required' must be true for 'in': 'path'.");
            }
        }

        if (! empty($this->content) && ! empty($this->schema)) {
            $this->addError(
                'A Parameter Object MUST contain either a schema property, or a content property, but not both.'
            );
        }
        if (! empty($this->content) && count($this->content) !== 1) {
            $this->addError('A Parameter Object with Content property MUST have A SINGLE content type.');
        }

        $supportedSerializationStyles = [
            'path' => ['simple', 'label', 'matrix'],
            'query' => ['form', 'spaceDelimited', 'pipeDelimited', 'deepObject'],
            'header' => ['simple'],
            'cookie' => ['form'],
        ];
        if (
            isset($supportedSerializationStyles[$this->in]) &&
            ! in_array($this->style, $supportedSerializationStyles[$this->in])
        ) {
            $this->addError('A Parameter Object DOES NOT support this serialization style.');
        }
    }

    #[Override]
    protected function attributeDefaults(): array
    {
        return $this->attributeDefaults;
    }
}
