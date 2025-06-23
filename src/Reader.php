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

use Phayne\OpenAPI\Exception\IOException;
use Phayne\OpenAPI\Json\JsonPointer;
use Phayne\OpenAPI\Specification\OpenApi;
use Symfony\Component\Yaml\Yaml;

use function file_get_contents;
use function is_string;
use function json_decode;

/**
 * Class Reader
 *
 * @package Phayne\OpenAPI
 */
class Reader
{
    public static function readFromJson(string $json, string $baseType = OpenApi::class): SpecObjectInterface
    {
        return new $baseType(json_decode($json, true));
    }

    public static function readFromYaml(string $yaml, string $baseType = OpenApi::class): SpecObjectInterface
    {
        return new $baseType(Yaml::parse($yaml));
    }

    public static function readFromJsonFile(
        string $fileName,
        string $baseType = OpenApi::class,
        bool $resolveReferences = true
    ): SpecObjectInterface {
        $fileContent = file_get_contents($fileName);

        if ($fileContent === false) {
            $e = new IOException("Failed to read file: '$fileName'");
            $e->fileName = $fileName;
            throw $e;
        }

        $spec = static::readFromJson($fileContent, $baseType);
        $context = new ReferenceContext($spec, $fileName);
        $spec->setReferenceContext($context);

        if ($resolveReferences !== false) {
            if (is_string($resolveReferences)) {
                /** @psalm-suppress NoValue */
                $context->mode = $resolveReferences;
            }

            if ($spec instanceof DocumentContextInterface) {
                $spec->setDocumentContext($spec, new JsonPointer(''));
            }

            $spec->resolveReferences();
        }

        return $spec;
    }

    public static function readFromYamlFile(
        string $fileName,
        string $baseType = OpenApi::class,
        bool|string $resolveReferences = true
    ): SpecObjectInterface {
        $fileContent = file_get_contents($fileName);

        if ($fileContent === false) {
            $e = new IOException("Failed to read file: '$fileName'");
            $e->fileName = $fileName;
            throw $e;
        }

        $spec = static::readFromYaml($fileContent, $baseType);
        $context = new ReferenceContext($spec, $fileName);
        $spec->setReferenceContext($context);

        if ($resolveReferences !== false) {
            if (is_string($resolveReferences)) {
                $context->mode = $resolveReferences;
            }

            if ($spec instanceof DocumentContextInterface) {
                $spec->setDocumentContext($spec, new JsonPointer(''));
            }

            $spec->resolveReferences();
        }

        return $spec;
    }
}
