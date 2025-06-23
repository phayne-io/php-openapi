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
use Phayne\OpenAPI\Specification\OpenApi;
use Phayne\OpenAPI\Specification\Operation;
use Phayne\OpenAPI\Specification\Parameter;
use Phayne\OpenAPI\Specification\PathItem;
use Phayne\OpenAPI\Specification\Paths;
use Phayne\OpenAPI\Specification\Reference;
use Phayne\OpenAPI\Specification\Response;
use Phayne\OpenAPI\Specification\Responses;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Class PathsTest
 *
 * @package PhayneTest\OpenAPI\Specification
 */
#[CoversClass(Paths::class)]
#[CoversClass(PathItem::class)]
class PathsTest extends TestCase
{
    public function testRead(): void
    {
        /** @var $paths Paths */
        $paths = Reader::readFromJson(<<<'JSON'
{
  "/pets": {
    "get": {
      "description": "Returns all pets from the system that the user has access to",
      "responses": {
        "200": {
          "description": "A list of pets.",
          "content": {
            "application/json": {
              "schema": {
                "type": "array",
                "items": {
                  "$ref": "#/components/schemas/pet"
                }
              }
            }
          }
        }
      }
    }
  }
}
JSON
            , Paths::class);

        $result = $paths->validate();
        $this->assertEquals([], $paths->errors());
        $this->assertTrue($result);

        $this->assertTrue($paths->hasPath('/pets'));
        $this->assertTrue(isset($paths['/pets']));
        $this->assertFalse($paths->hasPath('/dog'));
        $this->assertFalse(isset($paths['/dog']));

        $this->assertInstanceOf(PathItem::class, $paths->path('/pets'));
        $this->assertInstanceOf(PathItem::class, $paths['/pets']);
        $this->assertInstanceOf(Operation::class, $paths->path('/pets')->get);
        $this->assertNull($paths->path('/dog'));
        $this->assertNull($paths['/dog']);

        $this->assertCount(1, $paths->paths);
        $this->assertCount(1, $paths);
        foreach ($paths as $path => $pathItem) {
            $this->assertEquals('/pets', $path);
            $this->assertInstanceOf(PathItem::class, $pathItem);
        }
    }

    public function testCreationFromObjects(): void
    {
        $paths = new Paths([
            '/pets' => new PathItem([
                'get' => new Operation([
                    'responses' => new Responses([
                        200 => new Response(['description' => 'A list of pets.']),
                        404 => ['description' => 'The pets list is gone 🙀'],
                    ])
                ])
            ])
        ]);

        $this->assertTrue($paths->hasPath('/pets'));
        $this->assertInstanceOf(PathItem::class, $paths->path('/pets'));
        $this->assertInstanceOf(PathItem::class, $paths['/pets']);
        $this->assertInstanceOf(Operation::class, $paths->path('/pets')->get);

        $this->assertSame('A list of pets.', $paths->path('/pets')->get->responses->response(200)->description);
        $this->assertSame('The pets list is gone 🙀', $paths->path('/pets')->get->responses->response(404)->description);
    }

    public static function badPathsConfigProvider(): Generator
    {
        yield [['/pets' => 'foo'], 'Path MUST be either array or PathItem object, "string" given'];
        yield [['/pets' => 42], 'Path MUST be either array or PathItem object, "integer" given'];
        yield [['/pets' => false], 'Path MUST be either array or PathItem object, "boolean" given'];
        yield [['/pets' => new stdClass()], 'Path MUST be either array or PathItem object, "stdClass" given'];
        // The last one can be supported in future, but now SpecBaseObjects::__construct() requires array explicitly
    }

    #[DataProvider('badPathsConfigProvider')]
    public function testPathsCanNotBeCreatedFromBullshit($config, $expectedException): void
    {
        $this->expectException(TypeErrorException::class);
        $this->expectExceptionMessage($expectedException);

        new Paths($config);
    }

    public function testInvalidPath(): void
    {
        /** @var $paths Paths */
        $paths = Reader::readFromJson(<<<'JSON'
{
  "pets": {
    "get": {
      "description": "Returns all pets from the system that the user has access to",
      "responses": {
        "200": {
          "description": "A list of pets."
        }
      }
    }
  }
}
JSON
            , Paths::class);

        $result = $paths->validate();
        $this->assertEquals([
            'Path must begin with /: pets'
        ], $paths->errors());
        $this->assertFalse($result);
    }

    public function testPathItemReference(): void
    {
        $file = __DIR__ . '/Data/paths/openapi.yaml';
        /** @var $openapi OpenApi */
        $openapi = Reader::readFromYamlFile($file, OpenApi::class, false);

        $result = $openapi->validate();
        $this->assertEquals([], $openapi->errors(), print_r($openapi->errors(), true));
        $this->assertTrue($result);

        $this->assertInstanceOf(Paths::class, $openapi->paths);
        $this->assertInstanceOf(PathItem::class, $fooPath = $openapi->paths['/foo']);
        $this->assertInstanceOf(PathItem::class, $barPath = $openapi->paths['/bar']);
        $this->assertSame([
            'x-extension-1' => 'Extension1',
            'x-extension-2' => 'Extension2'
        ], $openapi->extensions);

        $this->assertEmpty($fooPath->operations());
        $this->assertEmpty($barPath->operations());

        $this->assertInstanceOf(Reference::class, $fooPath->reference);
        $this->assertInstanceOf(Reference::class, $barPath->reference);

        $this->assertNull($fooPath->reference->resolve());
        $this->assertInstanceOf(PathItem::class, $ReferencedBarPath = $barPath->reference->resolve());

        $this->assertCount(1, $ReferencedBarPath->operations());
        $this->assertInstanceOf(Operation::class, $ReferencedBarPath->get);
        $this->assertEquals('getBar', $ReferencedBarPath->get->operationId);

        $this->assertInstanceOf(Reference::class, $reference200 = $ReferencedBarPath->get->responses['200']);
        $this->assertInstanceOf(Response::class, $ReferencedBarPath->get->responses['404']);
        $this->assertEquals('non-existing resource', $ReferencedBarPath->get->responses['404']->description);

        $path200 = $reference200->resolve();
        $this->assertInstanceOf(Response::class, $path200);
        $this->assertEquals('A bar', $path200->description);

        /** @var $openapi OpenApi */
        $openapi = Reader::readFromYamlFile($file, OpenApi::class, true);

        $result = $openapi->validate();
        $this->assertEquals([], $openapi->errors(), print_r($openapi->errors(), true));
        $this->assertTrue($result);

        $this->assertInstanceOf(Paths::class, $openapi->paths);
        $this->assertInstanceOf(PathItem::class, $fooPath = $openapi->paths['/foo']);
        $this->assertInstanceOf(PathItem::class, $barPath = $openapi->paths['/bar']);

        $this->assertEmpty($fooPath->operations());
        $this->assertCount(1, $barPath->operations());
        $this->assertInstanceOf(Operation::class, $barPath->get);
        $this->assertEquals('getBar', $barPath->get->operationId);

        $this->assertEquals('A bar', $barPath->get->responses['200']->description);
        $this->assertEquals('non-existing resource', $barPath->get->responses['404']->description);
    }

    public function testPathParametersAreArrays(): void
    {
        $file = __DIR__ . '/Data/path-params/openapi.yaml';
        /** @var $openapi OpenApi */
        $openapi = Reader::readFromYamlFile($file, OpenApi::class, true);

        $result = $openapi->validate();
        $this->assertEquals([], $openapi->errors(), print_r($openapi->errors(), true));
        $this->assertTrue($result);

        $this->assertInstanceOf(Paths::class, $openapi->paths);
        $this->assertSame(gettype($openapi->paths->paths), 'array');
        $this->assertInstanceOf(
            PathItem::class,
            $usersPath = $openapi->paths['/v1/organizations/{organizationId}/user']
        );
        $this->assertInstanceOf(
            PathItem::class,
            $userIdPath = $openapi->paths['/v1/organizations/{organizationId}/user/{id}']
        );

        $result = $usersPath->validate();
        $this->assertTrue($result);
        $this->assertSame(gettype($usersPath->parameters), 'array');
        $this->assertInstanceOf(Parameter::class, $usersPath->parameters[0]);
        $this->assertInstanceOf(Parameter::class, $usersPath->parameters[1]);
        $this->assertEquals('api-version', $usersPath->parameters[0]->name);

        $result = $userIdPath->validate();
        $this->assertTrue($result);
        $this->assertSame(gettype($userIdPath->parameters), 'array');
        $this->assertInstanceOf(Parameter::class, $userIdPath->parameters[0]);
        $this->assertInstanceOf(Parameter::class, $userIdPath->parameters[1]);
        $this->assertEquals('id', $userIdPath->parameters[2]->name);
    }
}
