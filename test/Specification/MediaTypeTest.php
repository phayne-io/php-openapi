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
use Phayne\OpenAPI\Specification\Encoding;
use Phayne\OpenAPI\Specification\Example;
use Phayne\OpenAPI\Specification\Header;
use Phayne\OpenAPI\Specification\MediaType;
use Phayne\OpenAPI\Specification\OpenApi;
use Phayne\OpenAPI\Specification\Reference;
use Phayne\OpenAPI\Specification\Schema;
use Phayne\OpenAPI\Specification\Type;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Yaml\Yaml;

/**
 * Class MediaTypeTest
 *
 * @package PhayneTest\OpenAPI\Specification
 */
#[CoversClass(MediaType::class)]
#[CoversClass(Example::class)]
class MediaTypeTest extends TestCase
{
    public function testRead(): void
    {
        /** @var $mediaType MediaType */
        $mediaType = Reader::readFromYaml(<<<'YAML'
schema:
  $ref: "#/components/schemas/Pet"
examples:
  cat:
    summary: An example of a cat
    value:
      name: Fluffy
      petType: Cat
      color: White
      gender: male
      breed: Persian
  dog:
    summary: An example of a dog with a cat's name
    value:
      name: Puma
      petType: Dog
      color: Black
      gender: Female
      breed: Mixed
  frog:
    $ref: "#/components/examples/frog-example"
YAML
            , MediaType::class);

        $result = $mediaType->validate();
        $this->assertEquals([], $mediaType->errors());
        $this->assertTrue($result);

        $this->assertInstanceOf(Reference::class, $mediaType->schema);

        $this->assertIsArray($mediaType->examples);

        $this->assertCount(3, $mediaType->examples);
        $this->assertArrayHasKey('cat', $mediaType->examples);
        $this->assertArrayHasKey('dog', $mediaType->examples);
        $this->assertArrayHasKey('frog', $mediaType->examples);
        $this->assertInstanceOf(Example::class, $mediaType->examples['cat']);
        $this->assertInstanceOf(Example::class, $mediaType->examples['dog']);
        $this->assertInstanceOf(Reference::class, $mediaType->examples['frog']);

        $this->assertEquals('An example of a cat', $mediaType->examples['cat']->summary);
        $expectedCat = [ // TODO we might actually expect this to be an object of stdClass
            'name' => 'Fluffy',
            'petType' => 'Cat',
            'color' => 'White',
            'gender' => 'male',
            'breed' => 'Persian',
        ];
        $this->assertEquals($expectedCat, $mediaType->examples['cat']->value);
    }

    public function testCreationFromObjects(): void
    {
        $mediaType = new MediaType([
            'schema' => new Schema([
                'type' => Type::OBJECT,
                'properties' => [
                    'id' => new Schema(['type' => 'string', 'format' => 'uuid']),
                    'profileImage' => new Schema(['type' => 'string', 'format' => 'binary']),
                ],
            ]),
            'encoding' => [
                'id' => [],
                'profileImage' => new Encoding([
                    'contentType' => 'image/png, image/jpeg',
                    'headers' => [
                        'X-Rate-Limit-Limit' => new Header([
                            'description' => 'The number of allowed requests in the current period',
                            'schema' => new Schema(['type' => 'integer']),
                        ]),
                    ],
                ]),
            ],
        ]);

        // default value should be extracted
        $this->assertEquals('text/plain', $mediaType->encoding['id']->contentType);
        // object should be passed.
        $this->assertInstanceOf(Encoding::class, $mediaType->encoding['profileImage']);
    }

    public static function badEncodingProvider(): Generator
    {
        yield [['encoding' => ['id' => 'foo']], 'Encoding MUST be either array or Encoding object, "string" given'];
        yield [['encoding' => ['id' => 42]], 'Encoding MUST be either array or Encoding object, "integer" given'];
        yield [['encoding' => ['id' => false]], 'Encoding MUST be either array or Encoding object, "boolean" given'];
        yield [
            ['encoding' => ['id' => new stdClass()]],
            'Encoding MUST be either array or Encoding object, "stdClass" given'
        ];
    }

    #[DataProvider('badEncodingProvider')]
    public function testPathsCanNotBeCreatedFromBullshit($config, $expectedException): void
    {
        $this->expectException(TypeErrorException::class);
        $this->expectExceptionMessage($expectedException);

        new MediaType($config);
    }

    public function testUnresolvedReferencesInEncoding(): void
    {
        $yaml = Yaml::parse(<<<'YAML'
openapi: "3.0.0"
info:
  version: 1.0.0
  title: Encoding test
paths:
  /pets:
    post:
      summary: Create a pet
      operationId: createPets
      requestBody:
        content:
          multipart/form-data:
            schema:
              type: object
              properties:
                pet:
                  $ref: '#/components/schemas/Pet'
                petImage:
                  type: string
                  format: binary
            encoding:
              pet:
                contentType: application/json
              petImage:
                contentType: image/*
          application/json:
            schema:
              $ref: '#/components/schemas/Pet'
      responses:
        '201':
          description: Null response
components:
  schemas:
    Pet:
      type: object
      properties:
        name:
          type: string
YAML
        );
        $openapi = new OpenApi($yaml);
        $result = $openapi->validate();

        $this->assertEquals([], $openapi->errors());
        $this->assertTrue($result);
    }
}
