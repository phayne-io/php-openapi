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
use Phayne\OpenAPI\Json\JsonPointer;
use Phayne\OpenAPI\ReferenceContext;
use Phayne\OpenAPI\SpecBaseObject;
use Phayne\OpenAPI\SpecObjectInterface;

/**
 * Class PathItem
 *
 * Describes the operations available on a single path.
 *
 *  A Path Item MAY be empty, due to ACL constraints. The path itself is still exposed to the documentation
 *  viewer but they will not know which operations and parameters are available.
 *
 * @link https://github.com/OAI/OpenAPI-Specification/blob/3.0.2/versions/3.0.2.md#pathItemObject
 *
 * @property string $summary
 * @property string $description
 * @property Operation|null $get
 * @property Operation|null $put
 * @property Operation|null $post
 * @property Operation|null $delete
 * @property Operation|null $options
 * @property Operation|null $head
 * @property Operation|null $patch
 * @property Operation|null $trace
 * @property Server[] $servers
 * @property Parameter[]|Reference[] $parameters
 * @package Phayne\OpenAPI\Specification
 */
class PathItem extends SpecBaseObject
{
    protected(set) ?Reference $reference = null;

    public function __construct(array $data)
    {
        if (isset($data['$ref'])) {
            // Allows for an external definition of this path item.
            // $ref in a Path Item Object is not a Reference.
            // https://github.com/OAI/OpenAPI-Specification/issues/1038
            $this->reference = new Reference(['$ref' => $data['$ref']], PathItem::class);
            unset($data['$ref']);
        }

        parent::__construct($data);
    }

    #[Override]
    public function serializableData(): object|array
    {
        $data = (object) parent::serializableData();

        if ($this->reference instanceof Reference) {
            $data->{'$ref'} = $this->reference->reference;
        }

        if (isset($data->servers) && empty($data->servers)) {
            unset($data->servers);
        }

        if (isset($data->parameters) && empty($data->parameters)) {
            unset($data->parameters);
        }

        return $data;
    }

    #[Override]
    public function setReferenceContext(ReferenceContext $context): void
    {
        if ($this->reference instanceof Reference) {
            $this->reference->context = $context;
        }

        parent::setReferenceContext($context);
    }

    #[Override]
    public function setDocumentContext(SpecObjectInterface $baseDocument, JsonPointer $jsonPointer): void
    {
        parent::setDocumentContext($baseDocument, $jsonPointer);

        if ($this->reference instanceof Reference) {
            $this->reference->setDocumentContext($baseDocument, $jsonPointer);
        }
    }

    #[Override]
    public function resolveReferences(?ReferenceContext $context = null): void
    {
        if ($this->reference instanceof Reference) {
            $pathItem = $this->reference->resolve($context);
            $this->reference = null;

            foreach (self::attributes() as $attribute => $type) {
                if (! isset($pathItem->$attribute)) {
                    continue;
                }

                if (isset($this->$attribute) && ! empty($this->$attribute)) {
                    $this->addError(sprintf(
                        'Conflicting properties, property "%s" exists in local PathItem and also in the referenced one.',
                        $attribute
                    ));
                }
                $this->$attribute = $pathItem->$attribute;

                if ($this->$attribute instanceof Reference) {
                    $referencedObject = $this->$attribute->resolve();
                    $this->$attribute = $referencedObject;

                    if (! $referencedObject instanceof Reference && $referencedObject !== null) {
                        $referencedObject->resolveReferences();
                    }
                } elseif ($this->$attribute instanceof SpecObjectInterface) {
                    $this->$attribute->resolveReferences();
                } elseif (is_array($this->$attribute)) {
                    foreach ($this->$attribute as $k => $item) {
                        if ($item instanceof Reference) {
                            $referencedObject = $item->resolve();
                            $this->$attribute = $this->$attribute + [$k => $referencedObject];

                            if (! $referencedObject instanceof Reference && $referencedObject !== null) {
                                $referencedObject->resolveReferences();
                            }
                        } elseif ($item instanceof SpecObjectInterface) {
                            $item->resolveReferences();
                        }
                    }
                }
            }

            if ($pathItem instanceof SpecBaseObject) {
                foreach ($pathItem->extensions as $extensionKey => $extension) {
                    $this->{$extensionKey} = $extension;
                }
            }
        }

        parent::resolveReferences($context);
    }

    public function operations(): array
    {
        $operations = [];

        foreach ($this->attributes() as $attribute => $type) {
            if ($type === Operation::class && isset($this->$attribute)) {
                $operations[$attribute] = $this->$attribute;
            }
        }

        return $operations;
    }

    #[Override]
    protected function attributes(): array
    {
        return [
            'summary' => Type::STRING,
            'description' => Type::STRING,
            'get' => Operation::class,
            'put' => Operation::class,
            'post' => Operation::class,
            'delete' => Operation::class,
            'options' => Operation::class,
            'head' => Operation::class,
            'patch' => Operation::class,
            'trace' => Operation::class,
            'servers' => [Server::class],
            'parameters' => [Parameter::class],
        ];
    }

    #[Override]
    protected function performValidation(): void
    {
    }
}
