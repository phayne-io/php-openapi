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

use Override;
use Phayne\OpenAPI\Exception\TypeErrorException;
use Phayne\OpenAPI\Exception\UnknownPropertyException;
use Phayne\OpenAPI\Json\JsonPointer;
use Phayne\OpenAPI\Json\JsonReference;
use Phayne\OpenAPI\Specification\Reference;
use Phayne\OpenAPI\Specification\Type;
use TypeError;

use function array_key_exists;
use function array_merge;
use function count;
use function get_class;
use function is_array;
use function is_bool;
use function is_string;
use function print_r;
use function str_contains;

/**
 * Class SpecBaseObject
 *
 * @package Phayne\OpenAPI
 */
abstract class SpecBaseObject implements SpecObjectInterface, RawSpecificationDataInterface, DocumentContextInterface
{
    private array $properties = [];

    private array $errors = [];

    private bool $recursingSerializableData = false;

    private bool $recursingValidate = false;

    private bool $recursingErrors = false;

    private bool $recursingReferences = false;

    private bool $recursingReferenceContext = false;

    private bool $recursingDocumentContext = false;

    abstract protected function attributes(): array;

    abstract protected function performValidation(): void;

    public function __construct(array $data)
    {
        $this->rawSpecificationData = $data;

        foreach ($this->attributes() as $property => $type) {
            if (! isset($data[$property])) {
                continue;
            }

            if ($type === Type::BOOLEAN) {
                if (! is_bool($data[$property])) {
                    $this->errors[] = "Property '{$property}' must be a boolean, but got " . gettype($data[$property]);
                    continue;
                }
                $this->properties[$property] = (bool)$data[$property];
            } elseif (is_array($type)) {
                if (! is_array($data[$property])) {
                    $this->errors[] = "Property '{$property}' must be an array, but got " . gettype($data[$property]);
                    continue;
                }
                switch (count($type)) {
                    case 1:
                        if (isset($data[$property]['$ref'])) {
                            $this->properties[$property] = new Reference($data[$property], null);
                        } else {
                            $this->properties[$property] = [];
                            foreach ($data[$property] as $item) {
                                if ($type[0] === Type::STRING) {
                                    if (! is_string($item)) {
                                        $this->errors[] = "property '$property' must be array of strings, but array has " . gettype($item) . " element.";
                                    }
                                    $this->properties[$property][] = $item;
                                } elseif (Type::isScalar($type[0])) {
                                    $this->properties[$property][] = $item;
                                } elseif ($type[0] === Type::ANY) {
                                    if (is_array($item) && isset($item['$ref'])) {
                                        $this->properties[$property][] = new Reference($item, null);
                                    } else {
                                        $this->properties[$property][] = $item;
                                    }
                                } else {
                                    $this->properties[$property][] = $this->instantiate($type[0], $item);
                                }
                            }
                        }
                        break;
                    case 2:
                        if ($type[0] !== Type::STRING) {
                            throw new TypeErrorException('Invalid map key type: ' . $type[0]);
                        }
                        $this->properties[$property] = [];
                        foreach ($data[$property] as $key => $item) {
                            if ($type[1] === Type::STRING) {
                                if (! is_string($item)) {
                                    $this->errors[] = "property '$property' must be map<string, string>, but entry '$key' is of type " . \gettype($item) . '.';
                                }
                                $this->properties[$property][$key] = $item;
                            } elseif ($type[1] === Type::ANY || Type::isScalar($type[1])) {
                                $this->properties[$property][$key] = $item;
                            } else {
                                $this->properties[$property][$key] = $this->instantiate($type[1], $item);
                            }
                        }
                        break;
                }
            } elseif (Type::isScalar($type)) {
                $this->properties[$property] = $data[$property];
            } elseif ($type === Type::ANY) {
                if (is_array($data[$property]) && isset($data[$property]['$ref'])) {
                    $this->properties[$property] = new Reference($data[$property], null);
                } else {
                    $this->properties[$property] = $data[$property];
                }
            } else {
                $this->properties[$property] = $this->instantiate($type, $data[$property]);
            }
            unset($data[$property]);
        }

        foreach ($data as $additionalProperty => $value) {
            $this->properties[$additionalProperty] = $value;
        }
    }

    public function __get($name): mixed
    {
        if (isset($this->properties[$name])) {
            return $this->properties[$name];
        }

        $defaults = $this->attributeDefaults();

        if (array_key_exists($name, $defaults)) {
            return $defaults[$name];
        }

        if (isset($this->attributes()[$name])) {
            if (is_array($this->attributes()[$name])) {
                return [];
            } elseif ($this->attributes()[$name] === Type::BOOLEAN) {
                return false;
            }
            return null;
        }

        throw new UnknownPropertyException('Getting unknown property: ' . get_class($this) . '::' . $name);
    }

    public function __set($name, $value)
    {
        $this->properties[$name] = $value;
    }

    public function __isset($name): bool
    {
        if (
            isset($this->properties[$name]) ||
            isset($this->attributeDefaults()[$name]) ||
            isset($this->attributes()[$name])
        ) {
            return $this->__get($name) !== null;
        }

        return false;
    }

    public function __unset($name): void
    {
        unset($this->properties[$name]);
    }

    #[Override]
    public function validate(): bool
    {
        if ($this->recursingValidate) {
            return true;
        }
        $this->recursingValidate = true;
        $valid = true;
        foreach ($this->properties as $v) {
            if ($v instanceof SpecObjectInterface) {
                if (!$v->validate()) {
                    $valid = false;
                }
            } elseif (is_array($v)) {
                foreach ($v as $item) {
                    if ($item instanceof SpecObjectInterface) {
                        if (!$item->validate()) {
                            $valid = false;
                        }
                    }
                }
            }
        }
        $this->recursingValidate = false;

        $this->performValidation();

        if (!empty($this->errors)) {
            $valid = false;
        }

        return $valid;
    }

    #[Override]
    public function resolveReferences(?ReferenceContext $context = null): void
    {
        if ($this->recursingReferences) {
            return;
        }
        $this->recursingReferences = true;

        foreach ($this->properties as $property => $value) {
            if ($value instanceof Reference) {
                $referencedObject = $value->resolve($context);
                $this->properties[$property] = $referencedObject;
                if (! $referencedObject instanceof Reference && $referencedObject instanceof SpecObjectInterface) {
                    $referencedObject->resolveReferences();
                }
            } elseif ($value instanceof SpecObjectInterface) {
                $value->resolveReferences($context);
            } elseif (is_array($value)) {
                foreach ($value as $k => $item) {
                    if ($item instanceof Reference) {
                        $referencedObject = $item->resolve($context);
                        $this->properties[$property][$k] = $referencedObject;
                        if (! $referencedObject instanceof Reference && $referencedObject instanceof SpecObjectInterface) {
                            $referencedObject->resolveReferences();
                        }
                    } elseif ($item instanceof SpecObjectInterface) {
                        $item->resolveReferences($context);
                    }
                }
            }
        }

        $this->recursingReferences = false;
    }

    #[Override]
    public function setReferenceContext(ReferenceContext $context): void
    {
        if ($this->recursingReferenceContext) {
            return;
        }
        $this->recursingReferenceContext = true;

        foreach ($this->properties as $value) {
            if ($value instanceof Reference) {
                $value->context = $context;
            } elseif ($value instanceof SpecObjectInterface) {
                $value->setReferenceContext($context);
            } elseif (is_array($value)) {
                foreach ($value as $k => $item) {
                    if ($item instanceof Reference) {
                        $item->context = $context;
                    } elseif ($item instanceof SpecObjectInterface) {
                        $item->setReferenceContext($context);
                    }
                }
            }
        }

        $this->recursingReferenceContext = false;
    }

    #[Override]
    public function setDocumentContext(SpecObjectInterface $baseDocument, JsonPointer $jsonPointer): void
    {
        $this->baseDocument = $baseDocument;
        $this->documentPosition = $jsonPointer;

        // avoid recursion to get stuck in a loop
        if ($this->recursingDocumentContext) {
            return;
        }
        $this->recursingDocumentContext = true;

        foreach ($this->properties as $property => $value) {
            if ($value instanceof DocumentContextInterface) {
                $value->setDocumentContext($baseDocument, $jsonPointer->append($property));
            } elseif (is_array($value)) {
                foreach ($value as $k => $item) {
                    if ($item instanceof DocumentContextInterface) {
                        $item->setDocumentContext($baseDocument, $jsonPointer->append((string)$property)->append((string)$k));
                    }
                }
            }
        }

        $this->recursingDocumentContext = false;
    }

    public ?SpecObjectInterface $baseDocument {
        #[Override]
        get => $this->baseDocument;
    }
    public ?JsonPointer $documentPosition {
        #[Override]
        get => $this->documentPosition ?? null;
    }

    public array $rawSpecificationData {
        #[Override]
        get => $this->rawSpecificationData;
    }

    #[Override]
    public function errors(): array
    {
        if ($this->recursingErrors) {
            return [];
        } else {
            $this->recursingErrors = true;

            if (($pos = $this->documentPosition) !== null) {
                $errors = array_map(fn ($error) => "[{$pos->pointer}] $error", $this->errors);
            } else {
                $errors = [$this->errors];
            }

            foreach ($this->properties as $value) {
                if ($value instanceof SpecObjectInterface) {
                    $errors[] = $value->errors();
                } elseif (is_array($value)) {
                    foreach ($value as $item) {
                        if ($item instanceof SpecObjectInterface) {
                            $errors[] = $item->errors();
                        }
                    }
                }
            }
        }
        $this->recursingErrors = false;

        /** @psalm-suppress NamedArgumentNotAllowed */
        return array_merge(...$errors);
    }

    public array $extensions {
        get {
            $extensions = [];
            foreach ($this->properties as $property => $value) {
                if (str_starts_with($property, 'x-')) {
                    $extensions[$property] = $value;
                }
            }
            return $extensions;
        }
    }

    #[Override]
    public function serializableData(): object|array
    {
        if ($this->recursingSerializableData) {
            return (object)['$ref' => JsonReference::createFromUri('', $this->documentPosition)->reference];
        }
        $this->recursingSerializableData = true;

        $data = $this->properties;

        foreach ($data as $k => $v) {
            if ($v instanceof SpecObjectInterface) {
                $data[$k] = $v->serializableData();
            } elseif (is_array($v)) {
                $toObject = false;

                if (! empty($v)) {
                    $j = 0;

                    foreach ($v as $i => $d) {
                        if ($j++ !== $i) {
                            $toObject = true;
                        }
                        if ($d instanceof SpecObjectInterface) {
                            $data[$k][$i] = $d->serializableData();
                        }
                    }
                } elseif (
                    isset($this->attributes()[$k]) &&
                    is_array($this->attributes()[$k]) &&
                    2 === count($this->attributes()[$k])
                ) {
                    $toObject = true;
                }

                if ($toObject) {
                    $data[$k] = (object)$data[$k];
                }
            }
        }

        $this->recursingSerializableData = false;

        return $data;
    }

    protected function addError(string $error, string $class = ''): void
    {
        $shortName = explode('\\', $class);
        $this->errors[] = end($shortName) . $error;
    }

    protected function hasPropertyValue(string $name): bool
    {
        return isset($this->properties[$name]);
    }

    protected function requireProperties(array $names): void
    {
        foreach ($names as $name) {
            if (! isset($this->properties[$name])) {
                $this->addError(" is missing required property: $name", get_class($this));
            }
        }
    }

    protected function validateEmail(string $property): void
    {
        if (! empty($this->$property) && ! str_contains($this->$property, '@')) {
            $this->addError(
                '::$' . $property . ' does not seem to be a valid email address: ' . $this->$property,
                get_class($this)
            );
        }
    }

    protected function validateUrl(string $property): void
    {
        if (! empty($this->$property) && ! str_contains($this->$property, '//')) {
            $this->addError(
                '::$'.$property.' does not seem to be a valid URL: ' . $this->$property,
                get_class($this)
            );
        }
    }

    protected function instantiate(mixed $type, mixed $data): mixed
    {
        if ($data instanceof $type || $data instanceof Reference) {
            return $data;
        }

        if (is_array($data) && isset($data['$ref'])) {
            return new Reference($data, $type);
        }

        if (! is_array($data)) {
            throw new TypeErrorException(
                "Unable to instantiate {$type} Object with data '" .
                print_r($data, true) . "' at " . $this->documentPosition
            );
        }
        try {
            return new $type($data);
        } catch (TypeError $e) {
            throw new TypeErrorException(
                "Unable to instantiate {$type} Object with data '" .
                print_r($data, true) . "' at " . $this->documentPosition,
                $e->getCode(),
                $e
            );
        }
    }

    protected function attributeDefaults(): array
    {
        return [];
    }
}
