<?php

/**
 * This file is part of phayne-io/php-openapi and is proprietary and confidential.
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 *
 * @see       https://github.com/phayne-io/php-openapi for the canonical source repository
 * @copyright Copyright (c) 2024-2025 Phayne Limited. (https://phayne.io)
 */

declare(strict_types=1);

namespace PhayneTest\OpenAPI;

use Phayne\OpenAPI\Reader;
use Phayne\OpenAPI\Specification\OpenApi;
use Phayne\OpenAPI\SpecObjectInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class ReaderTest
 *
 * @package PhayneTest\OpenAPI
 */
class ReaderTest extends TestCase
{
    public function testReadJson(): void
    {
        $openapi = Reader::readFromJson(<<<JSON
{
  "openapi": "3.0.0",
  "info": {
    "title": "Test API",
    "version": "1.0.0"
  },
  "paths": {

  }
}
JSON
        );

        $this->assertApiContent($openapi);
    }

    public function testReadYaml(): void
    {
        $openapi = Reader::readFromYaml(<<<YAML
openapi: 3.0.0
info:
  title: "Test API"
  version: "1.0.0"
paths:
  /somepath:
YAML
        );

        $this->assertApiContent($openapi);
    }

    /**
     * Test if reading YAML file with anchors works
     */
    public function testReadYamlWithAnchors(): void
    {
        $openApiFile = __DIR__ . '/Specification/Data/traits-mixins.yaml';
        $openapi = Reader::readFromYamlFile($openApiFile);

        $this->assertApiContent($openapi);

        $putOperation = $openapi->paths['/foo']->put;
        $this->assertEquals('create foo', $putOperation->description);
        $this->assertTrue($putOperation->responses->hasResponse('200'));
        $this->assertTrue($putOperation->responses->hasResponse('404'));
        $this->assertTrue($putOperation->responses->hasResponse('428'));
        $this->assertTrue($putOperation->responses->hasResponse('default'));

        $respOk = $putOperation->responses->response('200');
        $this->assertEquals('request succeeded', $respOk->description);
        $this->assertEquals('the request id', $respOk->headers['X-Request-Id']->description);

        $resp404 = $putOperation->responses->response('404');
        $this->assertEquals('resource not found', $resp404->description);
        $this->assertEquals('the request id', $resp404->headers['X-Request-Id']->description);

        $resp428 = $putOperation->responses->response('428');
        $this->assertEquals('resource not found', $resp428->description);
        $this->assertEquals('the request id', $resp428->headers['X-Request-Id']->description);

        $respDefault = $putOperation->responses->response('default');
        $this->assertEquals('resource not found', $respDefault->description);
        $this->assertEquals('the request id', $respDefault->headers['X-Request-Id']->description);

        $foo = $openapi->components->schemas['Foo'];
        $this->assertArrayHasKey('uuid', $foo->properties);
        $this->assertArrayHasKey('name', $foo->properties);
        $this->assertArrayHasKey('id', $foo->properties);
        $this->assertArrayHasKey('description', $foo->properties);
        $this->assertEquals('uuid of the resource', $foo->properties['uuid']->description);
    }

    private function assertApiContent(SpecObjectInterface|OpenApi $openapi): void
    {
        $result = $openapi->validate();
        $this->assertEquals([], $openapi->errors());
        $this->assertTrue($result);


        $this->assertEquals("3.0.0", $openapi->openapi);
        $this->assertEquals("Test API", $openapi->info->title);
        $this->assertEquals("1.0.0", $openapi->info->version);
    }

    public function testGetRawSpecData(): void
    {
        $spec = <<<YML
openapi: "3.0.0"
info:
  version: 1.0.0
  title: Check storage of raw spec data

paths:
  /:
    get:
      summary: List
      operationId: list
      responses:
        '200':
          description: The information

components:
  schemas:
    User:
      type: object
      properties:
        id:
          type: integer
        name:
          type: string
    
    Post:
      type: object
      properties:
        id:
          type: integer
        title:
          type: string
        user:
          \$ref: "#/components/schemas/User"

YML;

        /** @var OpenApi $openapi */
        $openapi = Reader::readFromYaml($spec);
        $this->assertSame($openapi->rawSpecificationData, [
            'openapi' => '3.0.0',
            'info' => [
                'version' => '1.0.0',
                'title' => 'Check storage of raw spec data',
            ],
            'paths' => [
                '/' => [
                    'get' => [
                        'summary' => 'List',
                        'operationId' => 'list',
                        'responses' => [
                            '200' => [
                                'description' => 'The information',
                            ]
                        ]
                    ]
                ]
            ],
            'components' => [
                'schemas' => [
                    'User' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => [
                                'type' => 'integer',
                            ],
                            'name' => [
                                'type' => 'string',
                            ]
                        ]
                    ],
                    'Post' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => [
                                'type' => 'integer',
                            ],
                            'title' => [
                                'type' => 'string',
                            ],
                            'user' => [
                                '$ref' => '#/components/schemas/User',
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $this->assertSame($openapi->components->schemas['User']->rawSpecificationData, [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                ],
                'name' => [
                    'type' => 'string',
                ]
            ]
        ]);

        $this->assertSame($openapi->components->schemas['Post']->properties['user']->rawSpecificationData, [
            '$ref' => '#/components/schemas/User',
        ]);
    }
    // TODO: test invalid JSON
    // TODO: test invalid YAML
}
