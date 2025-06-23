<?php

/**
 * This file is part of phayne-io/php-openapi and is proprietary and confidential.
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 *
 * @see       https://github.com/phayne-io/php-openapi for the canonical source repository
 * @copyright Copyright (c) 2024-2025 Phayne Limited. (https://phayne.io)
 */

declare(strict_types=1);

namespace Phayne\OpenAPI\Json;

use JsonSerializable;
use Override;
use Phayne\OpenAPI\Exception\MalformedJsonReferenceObjectException;
use ReturnTypeWillChange;

use function explode;
use function json_decode;
use function rawurldecode;
use function str_contains;

/**
 * Class JsonReference
 *
 * @package Phayne\OpenAPI\Json
 */
class JsonReference implements JsonSerializable
{
    private(set) string $documentUri = '';

    protected(set) JsonPointer $jsonPointer;

    public string $reference {
        get {
            return $this->documentUri .
                '#' .
                strtr(rawurlencode($this->jsonPointer->pointer), ['%2F' => '/', '%3F' => '?', '%7E' => '~']);
        }
    }

    private function __construct()
    {
    }

    public function __clone()
    {
        $this->jsonPointer = clone $this->jsonPointer;
    }

    public static function createFromJson(string $json): JsonReference
    {
        $refObject = json_decode($json, true);

        if (!isset($refObject['$ref'])) {
            throw new MalformedJsonReferenceObjectException('JSON reference object must contain the "$ref" property');
        }

        return JsonReference::createFromReference($refObject['$ref']);
    }

    public static function createFromUri(string $uri, ?JsonPointer $jsonPointer = null): JsonReference
    {
        $jsonReference = JsonReference::createFromReference($uri);
        $jsonReference->jsonPointer = $jsonPointer ?: new JsonPointer('');
        return $jsonReference;
    }

    public static function createFromReference(string $referenceUri): JsonReference
    {
        $jsonReference = new self();

        if (str_contains($referenceUri, '#')) {
            list($uri, $fragment) = explode('#', $referenceUri, 2);
            $jsonReference->documentUri = $uri;
            $jsonReference->jsonPointer = new JsonPointer(rawurldecode($fragment));
        } else {
            $jsonReference->documentUri = $referenceUri;
            $jsonReference->jsonPointer = new JsonPointer('');
        }

        return $jsonReference;
    }

    #[Override]
    #[ReturnTypeWillChange]
    public function jsonSerialize(): object
    {
        return (object) ['$ref' => $this->reference];
    }
}
