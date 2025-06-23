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
use Phayne\OpenAPI\Specification\Encoding;
use Phayne\OpenAPI\Specification\Header;
use Phayne\OpenAPI\Specification\MediaType;
use Phayne\OpenAPI\Specification\RequestBody;
use PHPUnit\Framework\TestCase;

/**
 * Class RequestBodyTest
 *
 * @package PhayneTest\OpenAPI\Specification
 */
class RequestBodyTest extends TestCase
{
    public function testRead(): void
    {
        /** @var $requestBody RequestBody */
        $requestBody = Reader::readFromJson(<<<'JSON'
{
  "description": "user to add to the system",
  "content": {
    "application/json": {
      "schema": {
        "$ref": "#/components/schemas/User"
      }
    }
  }
}
JSON
            , RequestBody::class);

        $result = $requestBody->validate();
        $this->assertEquals([], $requestBody->errors());
        $this->assertTrue($result);

        $this->assertEquals('user to add to the system', $requestBody->description);
        $this->assertArrayHasKey("application/json", $requestBody->content);
        $this->assertInstanceOf(MediaType::class, $requestBody->content["application/json"]);

        // required Defaults to false.
        $this->assertFalse($requestBody->required);

        /** @var $response RequestBody */
        $requestBody = Reader::readFromJson(<<<'JSON'
{
  "description": "user to add to the system"
}
JSON
            , RequestBody::class);

        $result = $requestBody->validate();
        $this->assertEquals([
            'RequestBody is missing required property: content',
        ], $requestBody->errors());
        $this->assertFalse($result);
    }

    public function testEncoding(): void
    {
        /** @var $requestBody RequestBody */
        $requestBody = Reader::readFromYaml(<<<'YAML'
content:
  multipart/mixed:
    schema:
      type: object
      properties:
        id:
          # default is text/plain
          type: string
          format: uuid
    encoding:
      historyMetadata:
        # require XML Content-Type in utf-8 encoding
        contentType: application/xml; charset=utf-8
      profileImage:
        # only accept png/jpeg
        contentType: image/png, image/jpeg
        headers:
          X-Rate-Limit-Limit:
            description: The number of allowed requests in the current period
            schema:
              type: integer
      other:
        style: form
YAML
            , RequestBody::class);

        $result = $requestBody->validate();
        $this->assertEquals([], $requestBody->errors());
        $this->assertTrue($result);

        $this->assertArrayHasKey("multipart/mixed", $requestBody->content);
        $this->assertInstanceOf(MediaType::class, $mediaType = $requestBody->content["multipart/mixed"]);

        $this->assertCount(3, $mediaType->encoding);
        $this->assertArrayHasKey("historyMetadata", $mediaType->encoding);
        $this->assertArrayHasKey("profileImage", $mediaType->encoding);
        $this->assertArrayHasKey("other", $mediaType->encoding);
        $this->assertInstanceOf(Encoding::class, $mediaType->encoding["profileImage"]);
        $this->assertInstanceOf(Encoding::class, $mediaType->encoding["historyMetadata"]);
        $this->assertInstanceOf(Encoding::class, $mediaType->encoding["other"]);

        $profileImage = $mediaType->encoding["profileImage"];
        $this->assertEquals('image/png, image/jpeg', $profileImage->contentType);
        $this->assertInstanceOf(Header::class, $profileImage->headers['X-Rate-Limit-Limit']);
        $this->assertEquals(
            'The number of allowed requests in the current period',
            $profileImage->headers['X-Rate-Limit-Limit']->description
        );

        // When style is form, the default value is true. For all other styles, the default value is false.
        $this->assertFalse($profileImage->explode);
        $this->assertTrue($mediaType->encoding["other"]->explode);

        // allowReserved default value is false
        $this->assertFalse($profileImage->allowReserved);
        $this->assertFalse($mediaType->encoding["other"]->allowReserved);
    }
}
