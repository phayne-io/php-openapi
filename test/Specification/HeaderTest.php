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
use Phayne\OpenAPI\Specification\Header;
use Phayne\OpenAPI\Specification\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class HeaderTest
 *
 * @package PhayneTest\OpenAPI\Specification
 */
#[CoversClass(Header::class)]
class HeaderTest extends TestCase
{
    public function testRead(): void
    {
        /** @var $header Header */
        $header = Reader::readFromJson(<<<JSON
{
  "description": "The number of allowed requests in the current period",
  "schema": {
    "type": "integer"
  }
}
JSON
            , Header::class);

        $result = $header->validate();
        $this->assertEquals([], $header->errors());
        $this->assertTrue($result);

        $this->assertEquals('The number of allowed requests in the current period', $header->description);
        $this->assertInstanceOf(Schema::class, $header->schema);
        $this->assertEquals('integer', $header->schema->type);
    }
}
