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

use Phayne\OpenAPI\Exception\UnresolvableReferenceException;
use Phayne\OpenAPI\Reader;
use Phayne\OpenAPI\ReferenceContext;
use Phayne\OpenAPI\Specification\Example;
use Phayne\OpenAPI\Specification\OpenApi;
use Phayne\OpenAPI\Specification\Parameter;
use Phayne\OpenAPI\Specification\Reference;
use Phayne\OpenAPI\Specification\RequestBody;
use Phayne\OpenAPI\Specification\Response;
use Phayne\OpenAPI\Specification\Schema;
use Phayne\OpenAPI\Writer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class ReferenceTest
 *
 * @package PhayneTest\OpenAPI\Specification
 */
#[CoversClass(Reference::class)]
class ReferenceTest extends TestCase
{
    public function testResolveInDocument(): void
    {
        /** @var $openapi OpenApi */
        $openapi = Reader::readFromYaml(<<<'YAML'
openapi: 3.0.0
info:
  title: test api
  version: 1.0.0
components:
  schemas:
    Pet:
      type: object
      properties:
        id:
          type: integer
  examples:
    frog-example:
      description: a frog
  responses:
    Pet:
      description: returns a pet
paths:
  '/pet':
    get:
      responses:
        200:
          description: return a pet
          content:
            'application/json':
              schema:
                $ref: "#/components/schemas/Pet"
              examples:
                frog:
                  $ref: "#/components/examples/frog-example"
  '/pet/1':
    get:
      responses:
        200:
          $ref: "#/components/responses/Pet"
YAML
            , OpenApi::class);

        $result = $openapi->validate();
        $this->assertEquals([], $openapi->errors());
        $this->assertTrue($result);

        /** @var $petResponse Response */
        $petResponse = $openapi->paths->path('/pet')->get->responses['200'];
        $this->assertInstanceOf(Reference::class, $petResponse->content['application/json']->schema);
        $this->assertInstanceOf(Reference::class, $petResponse->content['application/json']->examples['frog']);
        $this->assertInstanceOf(Reference::class, $openapi->paths->path('/pet/1')->get->responses['200']);

        $openapi->resolveReferences(new ReferenceContext($openapi, 'file:///tmp/openapi.yaml'));

        $this->assertInstanceOf(
            Schema::class,
            $refSchema = $petResponse->content['application/json']->schema
        );
        $this->assertInstanceOf(
            Example::class,
            $refExample = $petResponse->content['application/json']->examples['frog']
        );
        $this->assertInstanceOf(
            Response::class,
            $refResponse = $openapi->paths->path('/pet/1')->get->responses['200']
        );

        $this->assertSame($openapi->components->schemas['Pet'], $refSchema);
        $this->assertSame($openapi->components->examples['frog-example'], $refExample);
        $this->assertSame($openapi->components->responses['Pet'], $refResponse);
    }

    public function testResolveCyclicReferenceInDocument(): void
    {
        /** @var $openapi OpenApi */
        $openapi = Reader::readFromYaml(<<<'YAML'
openapi: 3.0.0
info:
  title: test api
  version: 1.0.0
components:
  schemas:
    Pet:
      type: object
      properties:
        id:
          type: array
          items:
            $ref: "#/components/schemas/Pet"
      example:
        $ref: "#/components/examples/frog-example"
  examples:
    frog-example:
      description: a frog
paths:
  '/pet':
    get:
      responses:
        200:
          description: return a pet
          content:
            'application/json':
              schema:
                $ref: "#/components/schemas/Pet"
              examples:
                frog:
                  $ref: "#/components/examples/frog-example"
YAML
            , OpenApi::class);

        $result = $openapi->validate();
        $this->assertEquals([], $openapi->errors());
        $this->assertTrue($result);

        /** @var $response Response */
        $response = $openapi->paths->path('/pet')->get->responses['200'];
        $this->assertInstanceOf(Reference::class, $response->content['application/json']->schema);
        $this->assertInstanceOf(Reference::class, $response->content['application/json']->examples['frog']);

        $openapi->resolveReferences(new ReferenceContext($openapi, 'file:///tmp/openapi.yaml'));

        $this->assertInstanceOf(
            Schema::class,
            $petItems = $openapi->components->schemas['Pet']->properties['id']->items
        );
        $this->assertInstanceOf(
            Schema::class,
            $refSchema = $response->content['application/json']->schema
        );
        $this->assertInstanceOf(
            Example::class,
            $refExample = $response->content['application/json']->examples['frog']
        );

        $this->assertSame($openapi->components->schemas['Pet'], $petItems);
        $this->assertSame($openapi->components->schemas['Pet'], $refSchema);
        $this->assertSame($openapi->components->examples['frog-example'], $refExample);
    }

    private function createFileUri($file): string
    {
        if (stripos(PHP_OS, 'WIN') === 0) {
            return 'file:///' . strtr($file, [' ' => '%20', '\\' => '/']);
        } else {
            return 'file://' . $file;
        }
    }

    public function testResolveFile(): void
    {
        $file = __DIR__ . '/data/reference/base.yaml';
        $yaml = str_replace('##ABSOLUTEPATH##', $this->createFileUri(dirname($file)), file_get_contents($file));

        /** @var $openapi OpenApi */
        $openapi = Reader::readFromYaml($yaml);

        $result = $openapi->validate();
        $this->assertEquals([], $openapi->errors());
        $this->assertTrue($result);

        $this->assertInstanceOf(Reference::class, $petItems = $openapi->components->schemas['Pet']);
        $this->assertInstanceOf(Reference::class, $petItems = $openapi->components->schemas['Dog']);

        $openapi->resolveReferences(new ReferenceContext($openapi, $file));

        $this->assertInstanceOf(Schema::class, $petItems = $openapi->components->schemas['Pet']);
        $this->assertInstanceOf(Schema::class, $petItems = $openapi->components->schemas['Dog']);
        $this->assertArrayHasKey('id', $openapi->components->schemas['Pet']->properties);
        $this->assertArrayHasKey('name', $openapi->components->schemas['Dog']->properties);

        // second level reference inside of definitions.yaml
        $this->assertArrayHasKey('food', $openapi->components->schemas['Dog']->properties);
        $this->assertInstanceOf(Schema::class, $openapi->components->schemas['Dog']->properties['food']);
        $this->assertArrayHasKey('id', $openapi->components->schemas['Dog']->properties['food']->properties);
        $this->assertArrayHasKey('name', $openapi->components->schemas['Dog']->properties['food']->properties);
        $this->assertEquals(1, $openapi->components->schemas['Dog']->properties['food']->properties['id']->example);
    }

    public function testResolveFileInSubdir(): void
    {
        $file = __DIR__ . '/Data/reference/subdir.yaml';
        /** @var $openapi OpenApi */
        $openapi = Reader::readFromYamlFile($file, OpenApi::class, false);

        $result = $openapi->validate();
        $this->assertEquals([], $openapi->errors());
        $this->assertTrue($result);

        $this->assertInstanceOf(Reference::class, $openapi->components->schemas['Pet']);
        $this->assertInstanceOf(Reference::class, $openapi->components->schemas['Dog']);
        $this->assertInstanceOf(Reference::class, $openapi->components->parameters['Parameter.PetId']);

        $openapi->resolveReferences(new ReferenceContext($openapi, $file));

        $this->assertInstanceOf(Schema::class, $openapi->components->schemas['Pet']);
        $this->assertInstanceOf(Schema::class, $openapi->components->schemas['Dog']);
        $this->assertInstanceOf(Parameter::class, $openapi->components->parameters['Parameter.PetId']);
        $this->assertArrayHasKey('id', $openapi->components->schemas['Pet']->properties);
        $this->assertArrayHasKey('name', $openapi->components->schemas['Dog']->properties);
        $this->assertEquals('petId', $openapi->components->parameters['Parameter.PetId']->name);
        $this->assertInstanceOf(Schema::class, $openapi->components->parameters['Parameter.PetId']->schema);
        $this->assertEquals('integer', $openapi->components->parameters['Parameter.PetId']->schema->type);

        // second level references
        $this->assertArrayHasKey('food', $openapi->components->schemas['Dog']->properties);
        $this->assertInstanceOf(Schema::class, $openapi->components->schemas['Dog']->properties['food']);
        $this->assertArrayHasKey('id', $openapi->components->schemas['Dog']->properties['food']->properties);
        $this->assertArrayHasKey('name', $openapi->components->schemas['Dog']->properties['food']->properties);
        $this->assertEquals(1, $openapi->components->schemas['Dog']->properties['food']->properties['id']->example);

        $this->assertEquals('return a pet', $openapi->paths->path('/pets')->get->responses[200]->description);
        $responseContent = $openapi->paths->path('/pets')->get->responses[200]->content['application/json'];
        $this->assertInstanceOf(Schema::class, $responseContent->schema);
        $this->assertEquals('A Pet', $responseContent->schema->description);

        // third level reference back to original file
        $this->assertCount(1, $parameters = $openapi->paths->path('/pets')->get->parameters);
        $parameter = reset($parameters);
        $this->assertEquals('petId', $parameter->name);
        $this->assertInstanceOf(Schema::class, $parameter->schema);
        $this->assertEquals('integer', $parameter->schema->type);
    }

    public function testResolveFileInSubdirWithMultipleRelativePaths(): void
    {
        $file = __DIR__ . '/Data/reference/InlineRelativeResolve/sub/dir/Pathfile.json';
        /** @var $openapi OpenApi */
        $openapi = Reader::readFromJsonFile($file, OpenApi::class, true);

        $result = $openapi->validate();
        $this->assertEmpty($openapi->errors());
        $this->assertTrue($result);
    }

    public function testResolveFileHttp(): void
    {
        $file = 'https://raw.githubusercontent.com/cebe/php-openapi/290389bbd337cf4d70ecedfd3a3d886715e19552/tests/spec/data/reference/base.yaml'; //phpcs:ignore
        /** @var $openapi OpenApi */
        $openapi = Reader::readFromYaml(str_replace('##ABSOLUTEPATH##', dirname($file), file_get_contents($file)));

        $result = $openapi->validate();
        $this->assertEquals([], $openapi->errors());
        $this->assertTrue($result);

        $this->assertInstanceOf(Reference::class, $petItems = $openapi->components->schemas['Pet']);
        $this->assertInstanceOf(Reference::class, $petItems = $openapi->components->schemas['Dog']);

        $openapi->resolveReferences(new ReferenceContext($openapi, $file));

        $this->assertInstanceOf(Schema::class, $petItems = $openapi->components->schemas['Pet']);
        $this->assertInstanceOf(Schema::class, $petItems = $openapi->components->schemas['Dog']);
        $this->assertArrayHasKey('id', $openapi->components->schemas['Pet']->properties);
        $this->assertArrayHasKey('name', $openapi->components->schemas['Dog']->properties);
    }

    public function testResolvePaths(): void
    {
        /** @var $openapi OpenApi */
        $openapi = Reader::readFromJsonFile(
            __DIR__ . '/Data/reference/playlist.json',
            OpenApi::class,
            false
        );

        $result = $openapi->validate();
        $this->assertEquals([], $openapi->errors());
        $this->assertTrue($result);

        $playlistsBody = $openapi->paths['/playlist']->post->requestBody;
        $playlistBody = $openapi->paths['/playlist/{id}']->patch->requestBody;

        $this->assertInstanceOf(RequestBody::class, $playlistsBody);
        $this->assertInstanceOf(Reference::class, $playlistBody);

        $openapi->resolveReferences();

        $newPlaylistBody = $openapi->paths['/playlist/{id}']->patch->requestBody;
        $this->assertInstanceOf(RequestBody::class, $playlistsBody);
        $this->assertInstanceOf(RequestBody::class, $newPlaylistBody);
        $this->assertSame($playlistsBody, $newPlaylistBody);
    }

    public function testReferenceToArray(): void
    {
        $schema = <<<'YAML'
openapi: 3.0.0
components:
  schemas:
    Pet:
      type: object
      properties:
        id:
          type: integer
        typeA:
          type: string
          enum:
            - "One"
            - "Two"
        typeB:
          type: string
          enum:
            $ref: '#/components/schemas/Pet/properties/typeA/enum'
        typeC:
          type: string
          enum:
            - "Three"
            - $ref: '#/components/schemas/Pet/properties/typeA/enum/1'
        typeD:
          type: string
          enum:
            $ref: 'definitions.yaml#/Dog/properties/typeD/enum'
        typeE:
          type: string
          enum:
            - $ref: 'definitions.yaml#/Dog/properties/typeD/enum/1'
            - "Six"

YAML;
        $openapi = Reader::readFromYaml($schema);
        $openapi->resolveReferences(new ReferenceContext(
            $openapi,
            $this->createFileUri(__DIR__ . '/Data/reference/definitions.yaml')
        ));

        $this->assertTrue(isset($openapi->components->schemas['Pet']));
        $this->assertEquals(['One', 'Two'], $openapi->components->schemas['Pet']->properties['typeA']->enum);
        $this->assertEquals(['One', 'Two'], $openapi->components->schemas['Pet']->properties['typeB']->enum);
        $this->assertEquals(['Three', 'Two'], $openapi->components->schemas['Pet']->properties['typeC']->enum);
        $this->assertEquals(['Four', 'Five'], $openapi->components->schemas['Pet']->properties['typeD']->enum);
        $this->assertEquals(['Five', 'Six'], $openapi->components->schemas['Pet']->properties['typeE']->enum);
    }

    public function testTransitiveReference(): void
    {
        $schema = <<<'YAML'
openapi: 3.0.2
info:
  title: 'City API'
  version: dev
paths:
  '/city':
    get:
      description: 'Get City'
      responses:
        '200':
          description: 'success'
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/City'
components:
  schemas:
    City:
      $ref: '#/components/schemas/Named'
    Named:
      type: string

YAML;

        $openapi = Reader::readFromYaml($schema);
        $openapi->resolveReferences(new ReferenceContext($openapi, 'file:///tmp/openapi.yaml'));

        $this->assertTrue(isset($openapi->components->schemas['City']));
        $this->assertTrue(isset($openapi->components->schemas['Named']));
        $this->assertEquals('string', $openapi->components->schemas['Named']->type);
        $this->assertEquals('string', $openapi->components->schemas['City']->type);
        $this->assertEquals(
            'string',
            $openapi->paths['/city']->get->responses[200]->content['application/json']->schema->type
        );
    }

    public function testTransitiveReferenceToFile(): void
    {
        $schema = <<<'YAML'
openapi: 3.0.2
info:
  title: 'Dog API'
  version: dev
paths:
  '/dog':
    get:
      description: 'Get Dog'
      responses:
        '200':
          description: 'success'
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Dog'
components:
  schemas:
    Dog:
      $ref: 'definitions.yaml#/Dog'

YAML;

        $openapi = Reader::readFromYaml($schema);
        $openapi->resolveReferences(new ReferenceContext(
            $openapi,
            $this->createFileUri(__DIR__ . '/data/reference/definitions.yaml')
        ));

        $this->assertTrue(isset($openapi->components->schemas['Dog']));
        $this->assertEquals('object', $openapi->components->schemas['Dog']->type);
        $this->assertEquals('object', $openapi->components->schemas['Dog']->type);
        $this->assertEquals(
            'object',
            $openapi->paths['/dog']->get->responses[200]->content['application/json']->schema->type
        );
    }

    public function testTransitiveReferenceCyclic(): void
    {
        $schema = <<<'YAML'
openapi: 3.0.2
info:
  title: 'City API'
  version: dev
paths:
  '/city':
    get:
      description: 'Get City'
      responses:
        '200':
          description: 'success'
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/City'
components:
  schemas:
    City:
      $ref: '#/components/schemas/City'

YAML;

        $openapi = Reader::readFromYaml($schema);

        $this->expectException(UnresolvableReferenceException::class);
        $this->expectExceptionMessage('Cyclic reference detected on a Reference Object.');

        $openapi->resolveReferences(new ReferenceContext($openapi, 'file:///tmp/openapi.yaml'));
    }

    public function testTransitiveReferenceOverTwoFiles(): void
    {
        $openapi = Reader::readFromYamlFile(
            __DIR__ . '/Data/reference/structure.yaml',
            OpenApi::class,
            ReferenceContext::RESOLVE_MODE_INLINE
        );

        $yaml = Writer::writeToYaml($openapi);

        $expected = <<<YAML
openapi: 3.0.0
info:
  title: 'Ref Example'
  version: 1.0.0
paths:
  /pet:
    get:
      responses:
        '200':
          description: 'return a pet'
  /cat:
    get:
      responses:
        '200':
          description: 'return a cat'

YAML;
        // remove line endings to make string equal on windows
        $expected = preg_replace('~\R~', "\n", $expected);
        if (PHP_VERSION_ID < 70200) {
            // PHP <7.2 returns numeric properties in yaml maps as integer, since 7.2 these are string
            // probably related to
            // https://www.php.net/manual/de/migration72.incompatible.php#migration72.incompatible.object-array-casts
            $this->assertEquals(str_replace("'200':", "200:", $expected), $yaml, $yaml);
        } else {
            $this->assertEquals($expected, $yaml, $yaml);
        }
    }

    public function testReferencedCommonParamsInReferencedPath(): void
    {
        $openapi = Reader::readFromYamlFile(
            __DIR__ . '/Data/reference/ReferencedCommonParamsInReferencedPath.yml',
            OpenApi::class,
            ReferenceContext::RESOLVE_MODE_INLINE
        );
        $yaml = Writer::writeToYaml($openapi);
        $expected = <<<YAML
openapi: 3.0.0
info:
  title: 'Nested reference with common path params'
  version: 1.0.0
paths:
  /example:
    get:
      responses:
        '200':
          description: 'OK if common params can be references'
      request:
        content:
          application/json:
            examples:
              user:
                summary: 'User Example'
                externalValue: ./paths/examples/user-example.json
              userex:
                summary: 'External User Example'
                externalValue: 'https://api.example.com/examples/user-example.json'
    parameters:
      -
        name: test
        in: header
        description: 'Test parameter to be referenced'
        required: true
        schema:
          enum:
            - test
          type: string
    x-something: something
  /something:
    get:
      responses:
        '200':
          description: 'OK if common params can be references'
    parameters:
      -
        name: test
        in: header
        description: 'Test parameter to be referenced'
        required: true
        schema:
          enum:
            - test
          type: string
    x-something: something

YAML;
        // remove line endings to make string equal on windows
        $expected = preg_replace('~\R~', "\n", $expected);
        if (PHP_VERSION_ID < 70200) {
            // PHP <7.2 returns numeric properties in yaml maps as integer, since 7.2 these are string
            // probably related to
            // https://www.php.net/manual/de/migration72.incompatible.php#migration72.incompatible.object-array-casts
            $this->assertEquals(str_replace("'200':", "200:", $expected), $yaml, $yaml);
        } else {
            $this->assertEquals($expected, $yaml, $yaml);
        }
    }

    public function testResolveRelativePathInline(): void
    {
        $openapi = Reader::readFromYamlFile(
            __DIR__ . '/Data/reference/openapi_models.yaml',
            OpenApi::class,
            ReferenceContext::RESOLVE_MODE_INLINE
        );

        $yaml = Writer::writeToYaml($openapi);

        $expected = <<<YAML
openapi: 3.0.3
info:
  title: 'Link Example'
  version: 1.0.0
paths:
  /pet:
    get:
      responses:
        '200':
          description: 'return a pet'
components:
  schemas:
    Pet:
      type: object
      properties:
        id:
          type: integer
          format: int64
        cat:
          \$ref: '#/components/schemas/Cat'
      description: 'A Pet'
    Cat:
      type: object
      properties:
        id:
          type: integer
          format: int64
        name:
          type: string
          description: 'the cats name'
        pet:
          \$ref: '#/components/schemas/Pet'
      description: 'A Cat'

YAML;
        // remove line endings to make string equal on windows
        $expected = preg_replace('~\R~', "\n", $expected);
        if (PHP_VERSION_ID < 70200) {
            // PHP <7.2 returns numeric properties in yaml maps as integer, since 7.2 these are string
            // probably related to
            // https://www.php.net/manual/de/migration72.incompatible.php#migration72.incompatible.object-array-casts
            $this->assertEquals(str_replace("'200':", "200:", $expected), $yaml, $yaml);
        } else {
            $this->assertEquals($expected, $yaml, $yaml);
        }
    }

    public function testResolveRelativePathAll(): void
    {
        $openapi = Reader::readFromYamlFile(
            __DIR__ . '/data/reference/openapi_models.yaml',
            OpenApi::class,
            ReferenceContext::RESOLVE_MODE_ALL
        );

        $yaml = Writer::writeToYaml($openapi);

        $expected = <<<YAML
openapi: 3.0.3
info:
  title: 'Link Example'
  version: 1.0.0
paths:
  /pet:
    get:
      responses:
        '200':
          description: 'return a pet'
components:
  schemas:
    Pet:
      type: object
      properties:
        id:
          type: integer
          format: int64
        cat:
          type: object
          properties:
            id:
              type: integer
              format: int64
            name:
              type: string
              description: 'the cats name'
            pet:
              \$ref: '#/components/schemas/Pet'
          description: 'A Cat'
      description: 'A Pet'
    Cat:
      type: object
      properties:
        id:
          type: integer
          format: int64
        name:
          type: string
          description: 'the cats name'
        pet:
          type: object
          properties:
            id:
              type: integer
              format: int64
            cat:
              \$ref: '#/components/schemas/Cat'
          description: 'A Pet'
      description: 'A Cat'

YAML;
        // remove line endings to make string equal on windows
        $expected = preg_replace('~\R~', "\n", $expected);
        if (PHP_VERSION_ID < 70200) {
            // PHP <7.2 returns numeric properties in yaml maps as integer, since 7.2 these are string
            // probably related to
            // https://www.php.net/manual/de/migration72.incompatible.php#migration72.incompatible.object-array-casts
            $this->assertEquals(str_replace("'200':", "200:", $expected), $yaml, $yaml);
        } else {
            $this->assertEquals($expected, $yaml, $yaml);
        }
    }
}
