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
use Phayne\OpenAPI\DocumentContextInterface;
use Phayne\OpenAPI\Exception\InvalidJsonPointerSyntaxException;
use Phayne\OpenAPI\Exception\NonexistentJsonPointerReferenceException;
use Phayne\OpenAPI\Exception\TypeErrorException;
use Phayne\OpenAPI\Exception\UnresolvableReferenceException;
use Phayne\OpenAPI\Json\JsonPointer;
use Phayne\OpenAPI\Json\JsonReference;
use Phayne\OpenAPI\RawSpecificationDataInterface;
use Phayne\OpenAPI\ReferenceContext;
use Phayne\OpenAPI\SpecObjectInterface;
use Throwable;

use function dirname;
use function is_string;
use function is_subclass_of;
use function print_r;
use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * Class Reference
 *
 * @package Phayne\OpenAPI\Specification
 */
class Reference implements SpecObjectInterface, DocumentContextInterface, RawSpecificationDataInterface
{
    public ?ReferenceContext $context = null;

    private array $errors = [];

    public array $rawSpecificationData {
        #[Override]
        get => $this->rawSpecificationData ?? [];
    }

    protected(set) string $reference;

    public ?SpecObjectInterface $baseDocument {
        #[Override]
        get => $this->baseDocument ?? null;
    }

    public ?JsonPointer $documentPosition {
        #[Override]
        get => $this->documentPosition ?? null;
    }

    protected(set) ?JsonReference $jsonReference = null;

    private bool $recursingInsideFile = false;

    public function __construct(array $data, private ?string $to = null)
    {
        $this->rawSpecificationData = $data;

        if (! isset($data['$ref'])) {
            throw new TypeErrorException(sprintf(
                'Unable to instantiate Reference object with data "%s',
                print_r($data, true)
            ));
        }

        if ($to !== null && ! is_subclass_of($to, SpecObjectInterface::class, true)) {
            throw new TypeErrorException(
                'Unable to instantiate Reference Object, referenced class must implement SpecObjectInterface'
            );
        }

        if (! is_string($data['$ref'])) {
            throw new TypeErrorException(
                'Unable to instantiate Reference Object, $ref value must be a string'
            );
        }

        $this->reference = $data['$ref'];

        try {
            $this->jsonReference = JsonReference::createFromReference($this->reference);
        } catch (InvalidJsonPointerSyntaxException $exception) {
            $this->errors[] = 'Reference: value of $ref is not a valid JSON pointer: ' . $exception->getMessage();
        }

        if (count($data) !== 1) {
            $this->errors[] = 'Reference: additional properties are given. Only $ref should be set in a Reference Object.';
        }
    }

    #[Override]
    public function errors(): array
    {
        if (($pos = $this->documentPosition) !== null) {
            return array_map(fn ($error) => "[{$pos}] $error", $this->errors);
        } else {
            return $this->errors;
        }
    }

    #[Override]
    public function serializableData(): object|array
    {
        return (object) ['$ref' => $this->reference];
    }

    #[Override]
    public function setDocumentContext(SpecObjectInterface $baseDocument, JsonPointer $jsonPointer): void
    {
        $this->baseDocument = $baseDocument;
        $this->documentPosition = $jsonPointer;
    }

    #[Override]
    public function validate(): bool
    {
        return empty($this->errors);
    }

    #[Override]
    public function resolveReferences(?ReferenceContext $context = null): void
    {
        throw new UnresolvableReferenceException(
            'Cyclic reference detected, resolveReferences() called on a Reference Object.'
        );
    }

    #[Override]
    public function setReferenceContext(ReferenceContext $context): void
    {
        throw new UnresolvableReferenceException(
            'Cyclic reference detected, setReferenceContext() called on a Reference Object.'
        );
    }

    public function resolve(?ReferenceContext $context = null): mixed
    {
        if ($context === null) {
            $context = $this->context;
            if ($context === null) {
                throw new UnresolvableReferenceException('No context given for resolving reference.');
            }
        }
        $jsonReference = $this->jsonReference;
        try {
            if ($jsonReference->documentUri === '') {
                if ($context->mode === ReferenceContext::RESOLVE_MODE_INLINE) {
                    return $this;
                }

                if (null !== $context->baseSpecification) {
                    $referencedObject = $jsonReference->jsonPointer->evaluate($context->baseSpecification);

                    if ($referencedObject instanceof Reference) {
                        $referencedObject = $this->resolveTransitiveReference($referencedObject, $context);
                    }

                    if ($referencedObject instanceof SpecObjectInterface) {
                        $referencedObject->setReferenceContext($context);
                    }

                    return $referencedObject;
                } else {
                    $jsonReference = JsonReference::createFromUri($context->uri, $jsonReference->jsonPointer);
                }
            }

            $file = $context->resolveRelativeUri($jsonReference->documentUri);

            try {
                $referencedDocument = $context->fetchReferencedFile($file);
            } catch (Throwable $e) {
                $exception = new UnresolvableReferenceException(
                    sprintf(
                        'Unable to resolve reference "%s" to "%s" object',
                        $this->reference,
                        $this->to
                    ),
                    $e->getCode(),
                    $e
                );
                $exception->context = $this->documentPosition;
                throw $exception;
            }

            $referencedDocument = $this->adjustRelativeReferences(
                $referencedDocument,
                $file,
                null,
                $context
            );
            $referencedObject = $context->resolveReferenceData(
                $file,
                $jsonReference->jsonPointer,
                $referencedDocument,
                $this->to
            );

            if ($referencedObject instanceof DocumentContextInterface) {
                if (null === $referencedObject->documentPosition && null !== $this->documentPosition) {
                    $referencedObject->setDocumentContext($context->baseSpecification, $this->documentPosition);
                }
            }

            if ($referencedObject instanceof Reference) {
                if (
                    $context->mode === ReferenceContext::RESOLVE_MODE_INLINE &&
                    strncmp($referencedObject->reference, '#', 1) === 0
                ) {
                    $referencedObject->context = $context;
                } else {
                    return $this->resolveTransitiveReference($referencedObject, $context);
                }
            } else {
                if ($referencedObject instanceof SpecObjectInterface) {
                    $referencedObject->setReferenceContext($context);
                }
            }

            return $referencedObject;

        } catch (NonexistentJsonPointerReferenceException $exception) {
            $message = sprintf(
                'Failed to resolve Reference "%s" to %s Object: %s',
                $this->reference,
                $this->to,
                $exception->getMessage()
            );

            if ($context->throwException) {
                $exception = new UnresolvableReferenceException($message, 0, $exception);
                $exception->context = $this->documentPosition;
                throw $exception;
            }
            $this->errors[] = $message;
            $this->jsonReference = null;
            return $this;
        } catch (UnresolvableReferenceException $exception) {
            $exception->context = $this->documentPosition;
            if ($context->throwException) {
                throw $exception;
            }
            $this->errors[] = $exception->getMessage();
            $this->jsonReference = null;
            return $this;
        }
    }

    private function resolveTransitiveReference(Reference $referencedObject, ReferenceContext $context): array|null|SpecObjectInterface
    {
        if ($referencedObject->to === null) {
            $referencedObject->to = $this->to;
        }
        $referencedObject->context = $context;

        if ($referencedObject === $this) { // catch recursion
            throw new UnresolvableReferenceException('Cyclic reference detected on a Reference Object.');
        }

        $transitiveRefResult = $referencedObject->resolve();

        if ($transitiveRefResult === $this) { // catch recursion
            throw new UnresolvableReferenceException('Cyclic reference detected on a Reference Object.');
        }

        return $transitiveRefResult;
    }

    private function adjustRelativeReferences($referencedDocument, $basePath, $baseDocument = null, $oContext = null): mixed
    {
        $context = new ReferenceContext(null, $basePath);
        if ($baseDocument === null) {
            $baseDocument = $referencedDocument;
        }

        foreach ($referencedDocument as $key => $value) {
            if ($key === '$ref' && is_string($value)) {
                if (isset($value[0]) && $value[0] === '#') {
                    $inlineDocument = new JsonPointer(substr($value, 1))->evaluate($baseDocument);
                    if ($this->recursingInsideFile) {
                        return ['$ref' => $basePath . $value];
                    }
                    $this->recursingInsideFile = true;
                    $return = $this->adjustRelativeReferences($inlineDocument, $basePath, $baseDocument, $oContext);
                    $this->recursingInsideFile = false;
                    return $return;
                }
                $referencedDocument[$key] = $context->resolveRelativeUri($value);
                $parts = explode('#', $referencedDocument[$key], 2);

                if ($parts[0] === $oContext->uri) {
                    $referencedDocument[$key] = '#' . ($parts[1] ?? '');
                } else {
                    $referencedDocument[$key] = $this->makeRelativePath($oContext->uri, $referencedDocument[$key]);
                }

                continue;
            }

            if ($key === 'externalValue' && is_string($value)) {
                $referencedDocument[$key] = $this->makeRelativePath($oContext->uri, $context->resolveRelativeUri($value));
                continue;
            }

            if (is_array($value)) {
                $referencedDocument[$key] = $this->adjustRelativeReferences($value, $basePath, $baseDocument, $oContext);
            }
        }

        return $referencedDocument;
    }

    private function makeRelativePath(string $base, string $path): string
    {
        if (str_starts_with($path, dirname($base))) {
            return './' . substr($path, strlen(dirname($base) . '/'));
        }

        return $path;
    }
}
