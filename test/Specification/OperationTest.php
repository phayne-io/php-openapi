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
use Phayne\OpenAPI\Specification\Operation;
use Phayne\OpenAPI\Specification\Parameter;
use Phayne\OpenAPI\Specification\RequestBody;
use Phayne\OpenAPI\Specification\Responses;
use Phayne\OpenAPI\Specification\SecurityRequirement;
use Phayne\OpenAPI\Specification\SecurityRequirements;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class OperationTest
 *
 * @package PhayneTest\OpenAPI\Specification
 */
#[CoversClass(Operation::class)]
#[CoversClass(ExternalDocumentation::class)]
class OperationTest extends TestCase
{
    public function testRead(): void
    {
        /** @var $operation Operation */
        $operation = Reader::readFromYaml(<<<'YAML'
tags:
- pet
summary: Updates a pet in the store with form data
operationId: updatePetWithForm
parameters:
- name: petId
  in: path
  description: ID of pet that needs to be updated
  required: true
  schema:
    type: string
requestBody:
  content:
    'application/x-www-form-urlencoded':
      schema:
       properties:
          name: 
            description: Updated name of the pet
            type: string
          status:
            description: Updated status of the pet
            type: string
       required:
         - status
responses:
  '200':
    description: Pet updated.
    content: 
      'application/json': {}
      'application/xml': {}
  '405':
    description: Method Not Allowed
    content: 
      'application/json': {}
      'application/xml': {}
security:
- petstore_auth:
  - write:pets
  - read:pets
externalDocs:
  description: Find more info here
  url: https://example.com
YAML
            , Operation::class);

        $result = $operation->validate();
        $this->assertEquals([], $operation->errors());
        $this->assertTrue($result);

        $this->assertCount(1, $operation->tags);
        $this->assertEquals(['pet'], $operation->tags);

        $this->assertEquals('Updates a pet in the store with form data', $operation->summary);
        $this->assertEquals('updatePetWithForm', $operation->operationId);

        $this->assertCount(1, $operation->parameters);
        $this->assertInstanceOf(Parameter::class, $operation->parameters[0]);
        $this->assertEquals('petId', $operation->parameters[0]->name);

        $this->assertInstanceOf(RequestBody::class, $operation->requestBody);
        $this->assertCount(1, $operation->requestBody->content);
        $this->assertArrayHasKey('application/x-www-form-urlencoded', $operation->requestBody->content);

        $this->assertInstanceOf(Responses::class, $operation->responses);

        $this->assertCount(1, $operation->security->requirements());
        $this->assertInstanceOf(SecurityRequirements::class, $operation->security);
        $this->assertInstanceOf(SecurityRequirement::class, $operation->security->requirement('petstore_auth'));
        $this->assertCount(2, $operation->security->requirement('petstore_auth')->serializableData());
        $this->assertEquals(
            ['write:pets', 'read:pets'],
            $operation->security->requirement('petstore_auth')->serializableData()
        );

        $this->assertInstanceOf(ExternalDocumentation::class, $operation->externalDocs);
        $this->assertEquals('Find more info here', $operation->externalDocs->description);
        $this->assertEquals('https://example.com', $operation->externalDocs->url);

        // deprecated Default value is false.
        $this->assertFalse($operation->deprecated);
    }
}
