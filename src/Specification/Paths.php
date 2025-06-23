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

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Override;
use Phayne\OpenAPI\DocumentContextInterface;
use Phayne\OpenAPI\Exception\TypeErrorException;
use Phayne\OpenAPI\Json\JsonPointer;
use Phayne\OpenAPI\ReferenceContext;
use Phayne\OpenAPI\SpecObjectInterface;
use Traversable;

/**
 * Class Paths
 *
 * Holds the relative paths to the individual endpoints and their operations.
 *
 *  The path is appended to the URL from the Server Object in order to construct the full URL.
 *  The Paths MAY be empty, due to ACL constraints.
 *
 * @link https://github.com/OAI/OpenAPI-Specification/blob/3.0.2/versions/3.0.2.md#pathsObject
 * @package Phayne\OpenAPI\Specification
 * @template T
 * @implements IteratorAggregate<T>
 * @template-implements ArrayAccess<string, mixed>
 */
class Paths implements SpecObjectInterface, DocumentContextInterface, Countable, ArrayAccess, IteratorAggregate
{
    protected(set) array $paths = [];

    private array $errors = [];

    public ?SpecObjectInterface $baseDocument {
        #[Override]
        get => $this->baseDocument;
    }
    public ?JsonPointer $documentPosition {
        #[Override]
        get => $this->documentPosition ?? null;
    }

    public function __construct(array $data)
    {
        foreach ($data as $path => $object) {
            if ($object === null) {
                $this->paths[$path] = null;
            } elseif (is_array($object)) {
                $this->paths[$path] = new PathItem($object);
            } elseif ($object instanceof PathItem) {
                $this->paths[$path] = $object;
            } else {
                $givenType = gettype($object);
                if ($givenType === 'object') {
                    $givenType = get_class($object);
                }
                throw new TypeErrorException(sprintf(
                    'Path MUST be either array or PathItem object, "%s" given',
                    $givenType
                ));
            }
        }
    }

    #[Override]
    public function errors(): array
    {
        if (($pos = $this->documentPosition) !== null) {
            $errors = [
                array_map(fn ($error) => "[{$pos}] $error", $this->errors)
            ];
        } else {
            $errors = [$this->errors];
        }

        foreach ($this->paths as $path) {
            if (null === $path) {
                continue;
            }
            $errors[] = $path->errors();
        }

        return array_merge(...$errors);
    }

    #[Override]
    public function serializableData(): object|array
    {
        $data = [];
        foreach ($this->paths as $path => $pathItem) {
            $data[$path] = ($pathItem === null) ? null : $pathItem->serializableData();
        }
        return (object)$data;
    }

    public function hasPath(string $name): bool
    {
        return isset($this->paths[$name]);
    }

    public function path(string $name): ?PathItem
    {
        return $this->paths[$name] ?? null;
    }

    public function addPath(string $name, PathItem $pathItem): void
    {
        $this->paths[$name] = $pathItem;
    }

    public function removePath(string $name): void
    {
        unset($this->paths[$name]);
    }

    #[Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->paths);
    }

    #[Override]
    public function offsetExists(mixed $offset): bool
    {
        return $this->hasPath($offset);
    }

    #[Override]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->path($offset);
    }

    #[Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->addPath($offset, $value);
    }

    #[Override]
    public function offsetUnset(mixed $offset): void
    {
        $this->removePath($offset);
    }

    #[Override]
    public function count(): int
    {
        return count($this->paths);
    }

    #[Override]
    public function validate(): bool
    {
        $valid = true;
        $this->errors = [];

        foreach ($this->paths as $key => $path) {
            if ($path === null) {
                continue;
            }

            if (! $path->validate()) {
                $valid = false;
            }

            if (! str_starts_with($key, '/')) {
                $this->errors[] = "Path must begin with /: $key";
            }
        }

        return $valid && empty($this->errors);
    }

    #[Override]
    public function resolveReferences(?ReferenceContext $context = null): void
    {
        foreach ($this->paths as $path) {
            if ($path === null) {
                continue;
            }

            $path->resolveReferences($context);
        }
    }

    #[Override]
    public function setReferenceContext(ReferenceContext $context): void
    {
        foreach ($this->paths as $path) {
            if ($path === null) {
                continue;
            }

            $path->setReferenceContext($context);
        }
    }

    #[Override]
    public function setDocumentContext(SpecObjectInterface $baseDocument, JsonPointer $jsonPointer): void
    {
        $this->baseDocument = $baseDocument;
        $this->documentPosition = $jsonPointer;

        foreach ($this->paths as $key => $path) {
            if ($path instanceof DocumentContextInterface) {
                $path->setDocumentContext($baseDocument, $jsonPointer->append($key));
            }
        }
    }
}
