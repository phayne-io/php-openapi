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
use Phayne\OpenAPI\Json\JsonPointer;
use Phayne\OpenAPI\ReferenceContext;
use Phayne\OpenAPI\SpecObjectInterface;

use function count;
use function key;
use function reset;

/**
 * Class Callback
 *
 * @package Phayne\OpenAPI\Specification
 */
class Callback implements SpecObjectInterface, DocumentContextInterface
{
    public ?string $url = null;

    private array $errors = [];

    public ?PathItem $request = null;

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
        if (count($data) !== 1) {
            $this->errors[] = 'Callback object must have exactly one URL.';
            return;
        }
        $this->request = new PathItem(reset($data));
        $this->url = key($data);
    }

    #[Override]
    public function errors(): array
    {
        if (($pos = $this->documentPosition) !== null) {
            $errors = array_map(fn ($error) => "[{$pos}] $error", $this->errors);
        } else {
            $errors = $this->errors;
        }

        $pathItemErrors = $this->request === null ? [] : $this->request->errors();

        return array_merge($errors, $pathItemErrors);
    }

    #[Override]
    public function serializableData(): object|array
    {
        return (object)[$this->url => ($this->request === null) ? null : $this->request->serializableData()];
    }

    #[Override]
    public function validate(): bool
    {
        return (null === $this->request || $this->request->validate()) && empty($this->errors);
    }

    #[Override]
    public function resolveReferences(?ReferenceContext $context = null): void
    {
        if ($this->request !== null) {
            $this->request->resolveReferences($context);
        }
    }

    #[Override]
    public function setReferenceContext(ReferenceContext $context): void
    {
        if ($this->request !== null) {
            $this->request->setReferenceContext($context);
        }
    }

    #[Override]
    public function setDocumentContext(SpecObjectInterface $baseDocument, JsonPointer $jsonPointer): void
    {
        $this->baseDocument = $baseDocument;
        $this->documentPosition = $jsonPointer;

        if ($this->request instanceof DocumentContextInterface) {
            $this->request->setDocumentContext($baseDocument, $jsonPointer->append($this->url));
        }
    }
}
