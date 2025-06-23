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
use Phayne\OpenAPI\Specification\Xml;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class XmlTest
 *
 * @package PhayneTest\OpenAPI\Specification
 */
#[CoversClass(Xml::class)]
class XmlTest extends TestCase
{
    public function testRead(): void
    {
        /** @var $xml Xml */
        $xml = Reader::readFromYaml(<<<YAML
name: animal
attribute: true
namespace: http://example.com/schema/sample
prefix: sample
wrapped: false
YAML
            , Xml::class);

        $result = $xml->validate();
        $this->assertEquals([], $xml->errors());
        $this->assertTrue($result);

        $this->assertEquals('animal', $xml->name);
        $this->assertTrue($xml->attribute);
        $this->assertEquals('http://example.com/schema/sample', $xml->namespace);
        $this->assertEquals('sample', $xml->prefix);
        $this->assertFalse($xml->wrapped);

        /** @var $xml Xml */
        $xml = Reader::readFromYaml(<<<YAML
name: animal
YAML
            , Xml::class);

        $result = $xml->validate();
        $this->assertEquals([], $xml->errors());
        $this->assertTrue($result);

        // attribute Default value is false.
        $this->assertFalse($xml->attribute);
        // wrapped Default value is false.
        $this->assertFalse($xml->wrapped);
    }
}
