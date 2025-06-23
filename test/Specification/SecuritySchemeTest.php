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

use Generator;
use Phayne\OpenAPI\Exception\TypeErrorException;
use Phayne\OpenAPI\Reader;
use Phayne\OpenAPI\Specification\MediaType;
use Phayne\OpenAPI\Specification\OAuthFlow;
use Phayne\OpenAPI\Specification\OAuthFlows;
use Phayne\OpenAPI\Specification\Response;
use Phayne\OpenAPI\Specification\Responses;
use Phayne\OpenAPI\Specification\SecurityRequirement;
use Phayne\OpenAPI\Specification\SecurityRequirements;
use Phayne\OpenAPI\Specification\SecurityScheme;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Class SecuritySchemeTest
 *
 * @package PhayneTest\OpenAPI\Specification
 */
#[CoversClass(SecurityScheme::class)]
#[CoversClass(OAuthFlows::class)]
#[CoversClass(OAuthFlow::class)]
#[CoversClass(SecurityRequirement::class)]
#[CoversClass(SecurityRequirements::class)]
class SecuritySchemeTest extends TestCase
{
    public function testRead(): void
    {
        /** @var $response Response */
        $response = Reader::readFromJson(<<<'JSON'
{
  "description": "A complex object array response",
  "content": {
    "application/json": {
      "schema": {
        "type": "array",
        "items": {
          "$ref": "#/components/schemas/VeryComplexType"
        }
      }
    }
  }
}
JSON
            , Response::class);

        $result = $response->validate();
        $this->assertEquals([], $response->errors());
        $this->assertTrue($result);

        $this->assertEquals('A complex object array response', $response->description);
        $this->assertArrayHasKey("application/json", $response->content);
        $this->assertInstanceOf(MediaType::class, $response->content["application/json"]);

        /** @var $response Response */
        $response = Reader::readFromJson(<<<'JSON'
{
  "content": {
    "application/json": {
      "schema": {
        "type": "array",
        "items": {
          "$ref": "#/components/schemas/VeryComplexType"
        }
      }
    }
  }
}
JSON
            , Response::class);

        $result = $response->validate();
        $this->assertEquals([
            'Response is missing required property: description',
        ], $response->errors());
        $this->assertFalse($result);
    }

    public function testResponses(): void
    {
        /** @var $responses Responses */
        $responses = Reader::readFromYaml(<<<'YAML'
'200':
  description: a pet to be returned
  content:
    application/json:
      schema:
        $ref: '#/components/schemas/Pet'
default:
  description: Unexpected error
  content:
    application/json:
      schema:
        $ref: '#/components/schemas/ErrorModel'
YAML
            , Responses::class);

        $result = $responses->validate();
        $this->assertEquals([], $responses->errors());
        $this->assertTrue($result);

        $this->assertTrue($responses->hasResponse(200));
        $this->assertFalse($responses->hasResponse(201));
        $this->assertTrue($responses->hasResponse('200'));
        $this->assertFalse($responses->hasResponse('201'));
        $this->assertTrue($responses->hasResponse('default'));
        $this->assertTrue(isset($responses[200]));
        $this->assertFalse(isset($responses[201]));
        $this->assertTrue(isset($responses['200']));
        $this->assertFalse(isset($responses['201']));
        $this->assertTrue(isset($responses['default']));

        $this->assertCount(2, $responses->responses);
        $this->assertCount(2, $responses);
        $this->assertInstanceOf(Response::class, $responses->responses[200]);
        $this->assertInstanceOf(Response::class, $responses->responses['200']);
        $this->assertInstanceOf(Response::class, $responses->responses['default']);

        $this->assertInstanceOf(Response::class, $responses->response(200));
        $this->assertInstanceOf(Response::class, $responses->response('200'));
        $this->assertInstanceOf(Response::class, $responses->response('default'));
        $this->assertNull($responses->response('201'));
        $this->assertInstanceOf(Response::class, $responses[200]);
        $this->assertInstanceOf(Response::class, $responses['200']);
        $this->assertInstanceOf(Response::class, $responses['default']);
        $this->assertNull($responses['201']);

        $this->assertEquals('a pet to be returned', $responses->response('200')->description);
        $this->assertEquals('a pet to be returned', $responses['200']->description);

        $keys = [];
        foreach ($responses as $k => $response) {
            $keys[] = $k;
            $this->assertInstanceOf(Response::class, $response);
        }
        $this->assertEquals([200, 'default'], $keys);
    }

    public function testResponseCodes(): void
    {
        /** @var $responses Responses */
        $responses = Reader::readFromYaml(<<<'YAML'
'200':
  description: valid statuscode
'99':
  description: invalid statuscode
'302':
  description: valid statuscode
'401':
  description: valid statuscode
'601':
  description: invalid statuscode
'6X1':
  description: invalid statuscode
'2X1':
  description: invalid statuscode
'2XX':
  description: valid statuscode
'default':
  description: valid statuscode
'example':
  description: valid statuscode
YAML
            , Responses::class);

        $result = $responses->validate();
        $this->assertEquals([
            'Responses: 99 is not a valid HTTP status code.',
            'Responses: 601 is not a valid HTTP status code.',
            'Responses: 6X1 is not a valid HTTP status code.',
            'Responses: 2X1 is not a valid HTTP status code.',
            'Responses: example is not a valid HTTP status code.',

        ], $responses->errors());
        $this->assertFalse($result);
    }

    public function testCreationFromObjects(): void
    {
        $responses = new Responses([
            200 => new Response(['description' => 'A list of pets.']),
            404 => ['description' => 'The pets list is gone 🙀'],
        ]);

        $this->assertSame('A list of pets.', $responses->response(200)->description);
        $this->assertSame('The pets list is gone 🙀', $responses->response(404)->description);
    }

    public static function badResponseProvider(): Generator
    {
        yield [['200' => 'foo'], 'Response MUST be either an array, a Response or a Reference object, "string" given'];
        yield [['200' => 42], 'Response MUST be either an array, a Response or a Reference object, "integer" given'];
        yield [['200' => false], 'Response MUST be either an array, a Response or a Reference object, "boolean" given'];
        yield [
            ['200' => new stdClass()],
            'Response MUST be either an array, a Response or a Reference object, "stdClass" given'
        ];
    }

    #[DataProvider('badResponseProvider')]
    public function testPathsCanNotBeCreatedFromBullshit($config, $expectedException)
    {
        $this->expectException(TypeErrorException::class);
        $this->expectExceptionMessage($expectedException);

        new Responses($config);
    }
}
