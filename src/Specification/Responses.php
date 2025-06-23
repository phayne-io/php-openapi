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
use ReturnTypeWillChange;
use Traversable;

use function is_array;
use function get_class;
use function gettype;
use function preg_match;
use function sprintf;

/**
 * Class Responses
 *
 * @package Phayne\OpenAPI\Specification
 * @template T
 * @implements IteratorAggregate<T>
 * @template-implements ArrayAccess<string, mixed>
 */
class Responses implements SpecObjectInterface, IteratorAggregate, Countable, ArrayAccess, DocumentContextInterface
{
    protected(set) array $responses = [];

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
        foreach ($data as $statusCode => $response) {
            // From Spec: This field MUST be enclosed in quotation marks (for example, "200")
            // for compatibility between JSON and YAML.
            $code = (string) $statusCode;
            if (preg_match('~^(?:default|[1-5](?:[0-9][0-9]|XX))$~', $code)) {
                if ($response instanceof Response || $response instanceof Reference) {
                    $this->responses[$code] = $response;
                } elseif (is_array($response) && isset($response['$ref'])) {
                    $this->responses[$code] = new Reference($response, Response::class);
                } elseif (is_array($response)) {
                    $this->responses[$code] = new Response($response);
                } else {
                    $givenType = gettype($response);
                    if ($givenType === 'object') {
                        $givenType = get_class($response);
                    }
                    throw new TypeErrorException(sprintf(
                        'Response MUST be either an array, a Response or a Reference object, "%s" given',
                        $givenType
                    ));
                }
            } else {
                $this->errors[] = "Responses: $statusCode is not a valid HTTP status code.";
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

        foreach ($this->responses as $response) {
            if (null === $response) {
                continue;
            }
            $errors[] = $response->errors();
        }

        return array_merge(...$errors);
    }

    #[Override]
    public function serializableData(): object|array
    {
        $data = [];
        /**
         * @var int $statusCode
         * @var Response|Reference|null $response
         */
        foreach ($this->responses as $statusCode => $response) {
            $data[$statusCode] = ($response === null) ? null : $response->serializableData();
        }
        return (object)$data;
    }

    public function hasResponse(string|int $statusCode): bool
    {
        return isset($this->responses[(string)$statusCode]);
    }

    public function response(string|int $statusCode): Response|Reference|null
    {
        return $this->responses[(string)$statusCode] ?? null;
    }

    public function addResponse(string|int $statusCode, $response): void
    {
        $this->responses[(string)$statusCode] = $response;
    }

    public function removeResponse(string|int $statusCode): void
    {
        unset($this->responses[(string)$statusCode]);
    }

    #[Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->responses);
    }

    #[Override]
    public function offsetExists(mixed $offset): bool
    {
        return $this->hasResponse($offset);
    }

    #[Override]
    #[ReturnTypeWillChange]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->response($offset);
    }

    #[Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->addResponse((string)$offset, $value);
    }

    #[Override]
    public function offsetUnset(mixed $offset): void
    {
        $this->removeResponse($offset);
    }

    #[Override]
    public function count(): int
    {
        return count($this->responses);
    }

    #[Override]
    public function setDocumentContext(SpecObjectInterface $baseDocument, JsonPointer $jsonPointer): void
    {
        $this->baseDocument = $baseDocument;
        $this->documentPosition = $jsonPointer;

        foreach ($this->responses as $key => $response) {
            if ($response instanceof DocumentContextInterface) {
                $response->setDocumentContext($baseDocument, $jsonPointer->append((string)$key));
            }
        }
    }

    #[Override]
    public function validate(): bool
    {
        $valid = true;

        foreach ($this->responses as $response) {
            if ($response === null) {
                continue;
            }

            if (! $response->validate()) {
                $valid = false;
            }
        }

        return $valid && empty($this->errors);
    }

    #[Override]
    public function resolveReferences(?ReferenceContext $context = null): void
    {
        foreach ($this->responses as $key => $response) {
            if ($response instanceof Reference) {
                /** @var Response|Reference|null $referencedObject */
                $referencedObject = $response->resolve($context);
                $this->responses[$key] = $referencedObject;
                if (! $referencedObject instanceof Reference && $referencedObject instanceof SpecObjectInterface) {
                    $referencedObject->resolveReferences();
                }
            } elseif ($response instanceof SpecObjectInterface) {
                $response->resolveReferences($context);
            }
        }
    }

    #[Override]
    public function setReferenceContext(ReferenceContext $context): void
    {
        foreach ($this->responses as $response) {
            if ($response instanceof Reference) {
                $response->context = $context;
            } else {
                $response->setReferenceContext($context);
            }
        }
    }
}
