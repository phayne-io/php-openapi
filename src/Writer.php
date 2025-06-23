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
use Phayne\OpenAPI\Specification\OpenApi;
use Symfony\Component\Yaml\Yaml;

use function file_put_contents;
use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_UNICODE;

/**
 * Class Writer
 *
 * @package Phayne\OpenAPI
 */
class Writer
{
    public static function writeToJson(
        SpecObjectInterface|OpenApi $object,
        int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    ): string {
        return json_encode($object->serializableData(), $flags);
    }

    public static function writeToYaml(SpecObjectInterface|OpenApi $object): string
    {
        return Yaml::dump(
            $object->serializableData(),
            256,
            2,
            Yaml::DUMP_OBJECT_AS_MAP | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE
        );
    }

    public static function writeToJsonFile(SpecObjectInterface|OpenApi $object, string $fileName): void
    {
        if (file_put_contents($fileName, static::writeToJson($object)) === false) {
            throw new IOException("Failed to write file: '$fileName'");
        }
    }

    public static function writeToYamlFile(SpecObjectInterface|OpenApi $object, string $fileName): void
    {
        if (file_put_contents($fileName, static::writeToYaml($object)) === false) {
            throw new IOException("Failed to write file: '$fileName'");
        }
    }
}
