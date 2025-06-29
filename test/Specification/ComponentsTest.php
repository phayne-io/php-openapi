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
use Phayne\OpenAPI\Specification\Callback;
use Phayne\OpenAPI\Specification\Components;
use Phayne\OpenAPI\Specification\Example;
use Phayne\OpenAPI\Specification\Header;
use Phayne\OpenAPI\Specification\Link;
use Phayne\OpenAPI\Specification\Parameter;
use Phayne\OpenAPI\Specification\RequestBody;
use Phayne\OpenAPI\Specification\Response;
use Phayne\OpenAPI\Specification\Schema;
use Phayne\OpenAPI\Specification\SecurityScheme;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class ComponentsTest
 *
 * @package PhayneTest\OpenAPI\Specification
 */
#[CoversClass(Components::class)]
class ComponentsTest extends TestCase
{
    public function testRead(): void
    {
        /** @var $components Components */
        $components = Reader::readFromYaml(<<<'YAML'
schemas:
  GeneralError:
    type: object
    properties:
      code:
        type: integer
        format: int32
      message:
        type: string
  Category:
    type: object
    properties:
      id:
        type: integer
        format: int64
      name:
        type: string
  Tag:
    type: object
    properties:
      id:
        type: integer
        format: int64
      name:
        type: string
parameters:
  skipParam:
    name: skip
    in: query
    description: number of items to skip
    required: true
    schema:
      type: integer
      format: int32
  limitParam:
    name: limit
    in: query
    description: max records to return
    required: true
    schema:
      type: integer
      format: int32
responses:
  NotFound:
    description: Entity not found.
  IllegalInput:
    description: Illegal input for operation.
  GeneralError:
    description: General Error
    content:
      application/json:
        schema:
          $ref: '#/components/schemas/GeneralError'
securitySchemes:
  api_key:
    type: apiKey
    name: api_key
    in: header
  petstore_auth:
    type: oauth2
    flows: 
      implicit:
        authorizationUrl: http://example.org/api/oauth/dialog
        scopes:
          write:pets: modify pets in your account
          read:pets: read your pets
YAML
            , Components::class);

        $result = $components->validate();
        $this->assertEquals([], $components->errors());
        $this->assertTrue($result);

        $this->assertAllInstanceOf(Schema::class, $components->schemas);
        $this->assertCount(3, $components->schemas);
        $this->assertArrayHasKey('GeneralError', $components->schemas);
        $this->assertArrayHasKey('Category', $components->schemas);
        $this->assertArrayHasKey('Tag', $components->schemas);
        $this->assertAllInstanceOf(Response::class, $components->responses);
        $this->assertCount(3, $components->responses);
        $this->assertArrayHasKey('NotFound', $components->responses);
        $this->assertArrayHasKey('IllegalInput', $components->responses);
        $this->assertArrayHasKey('GeneralError', $components->responses);
        $this->assertAllInstanceOf(Parameter::class, $components->parameters);
        $this->assertCount(2, $components->parameters);
        $this->assertArrayHasKey('skipParam', $components->parameters);
        $this->assertArrayHasKey('limitParam', $components->parameters);
        $this->assertAllInstanceOf(Example::class, $components->examples);
        $this->assertCount(0, $components->examples); // TODO
        $this->assertAllInstanceOf(RequestBody::class, $components->requestBodies);
        $this->assertCount(0, $components->requestBodies); // TODO
        $this->assertAllInstanceOf(Header::class, $components->headers);
        $this->assertCount(0, $components->headers); // TODO
        $this->assertAllInstanceOf(SecurityScheme::class, $components->securitySchemes);
        $this->assertCount(2, $components->securitySchemes);
        $this->assertArrayHasKey('api_key', $components->securitySchemes);
        $this->assertArrayHasKey('petstore_auth', $components->securitySchemes);
        $this->assertAllInstanceOf(Link::class, $components->links);
        $this->assertCount(0, $components->links); // TODO
        $this->assertAllInstanceOf(Callback::class, $components->callbacks);
        $this->assertCount(0, $components->callbacks); // TODO
    }

    public function assertAllInstanceOf($className, $array): void
    {
        foreach ($array as $k => $v) {
            $this->assertInstanceOf(
                $className,
                $v,
                "Asserting that item with key '$k' is instance of $className"
            );
        }
    }
}
