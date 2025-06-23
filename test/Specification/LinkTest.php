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
use Phayne\OpenAPI\Specification\Link;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class LinkTest
 *
 * @package PhayneTest\OpenAPI\Specification
 */
#[CoversClass(Link::class)]
class LinkTest extends TestCase
{
    public function testRead(): void
    {
        /** @var $link Link */
        $link = Reader::readFromJson(<<<JSON
{
    "operationId": "getUserAddress",
    "parameters": {
        "userId": "test.path.id"
    }
}
JSON
            , Link::class);

        $result = $link->validate();
        $this->assertEquals([], $link->errors());
        $this->assertTrue($result);

        $this->assertEquals(null, $link->operationRef);
        $this->assertEquals('getUserAddress', $link->operationId);
        $this->assertEquals(['userId' => 'test.path.id'], $link->parameters);
        $this->assertEquals(null, $link->requestBody);
        $this->assertEquals(null, $link->server);
    }

    public function testValidateBothOperationIdAndOperationRef(): void
    {
        /** @var $link Link */
        $link = Reader::readFromJson(<<<JSON
{
    "operationId": "getUserAddress",
    "operationRef": "getUserAddressRef"
}
JSON
            , Link::class);

        $result = $link->validate();
        $this->assertEquals([
            'Link: operationId and operationRef are mutually exclusive.'
        ], $link->errors());
        $this->assertFalse($result);
    }
}
