<?php

/**
 * This file is part of phayne-io/php-openapi and is proprietary and confidential.
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 *
 * @see       https://github.com/phayne-io/php-openapi for the canonical source repository
 * @copyright Copyright (c) 2024-2025 Phayne Limited. (https://phayne.io)
 */

declare(strict_types=1);

namespace PhayneTest\OpenAPI\Specification;

use Phayne\OpenAPI\Reader;
use Phayne\OpenAPI\Specification\ExternalDocumentation;
use Phayne\OpenAPI\Specification\Tag;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class TagTest
 *
 * @package PhayneTest\OpenAPI\Specification
 */
#[CoversClass(Tag::class)]
class TagTest extends TestCase
{
    public function testRead(): void
    {
        /** @var $tag Tag */
        $tag = Reader::readFromYaml(<<<YAML
name: pet
description: Pets operations
YAML
            , Tag::class);

        $result = $tag->validate();
        $this->assertEquals([], $tag->errors());
        $this->assertTrue($result);

        $this->assertEquals('pet', $tag->name);
        $this->assertEquals('Pets operations', $tag->description);
        $this->assertNull($tag->externalDocs);

        /** @var $tag Tag */
        $tag = Reader::readFromYaml(<<<YAML
description: Pets operations
externalDocs:
  url: https://example.com
YAML
            , Tag::class);

        $result = $tag->validate();
        $this->assertEquals(['Tag is missing required property: name'], $tag->errors());
        $this->assertFalse($result);

        $this->assertInstanceOf(ExternalDocumentation::class, $tag->externalDocs);
    }
}
