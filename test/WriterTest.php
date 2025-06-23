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

use Phayne\OpenAPI\Specification\Components;
use Phayne\OpenAPI\Specification\OpenApi;
use Phayne\OpenAPI\Specification\Operation;
use Phayne\OpenAPI\Specification\PathItem;
use Phayne\OpenAPI\Specification\Response;
use Phayne\OpenAPI\Specification\Responses;
use Phayne\OpenAPI\Specification\SecurityRequirement;
use Phayne\OpenAPI\Specification\SecurityRequirements;
use Phayne\OpenAPI\Specification\SecurityScheme;
use Phayne\OpenAPI\Writer;
use PHPUnit\Framework\TestCase;

/**
 * Class WriterTest
 *
 * @package PhayneTest\OpenAPI
 */
class WriterTest extends TestCase
{
    private function createOpenAPI(array $merge = []): OpenApi
    {
        return new OpenApi(array_merge([
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [],
        ], $merge));
    }

    public function testWriteJson(): void
    {
        $openapi = $this->createOpenAPI();

        $json = Writer::writeToJson($openapi);

        $this->assertEquals(preg_replace('~\R~', "\n", <<<JSON
{
    "openapi": "3.0.0",
    "info": {
        "title": "Test API",
        "version": "1.0.0"
    },
    "paths": {}
}
JSON
        ),
            $json
        );
    }

    public function testWriteJsonModify(): void
    {
        $openapi = $this->createOpenAPI();

        $openapi->paths['/test'] = new PathItem([
            'description' => 'something'
        ]);

        $json = Writer::writeToJson($openapi);

        $this->assertEquals(preg_replace('~\R~', "\n", <<<JSON
{
    "openapi": "3.0.0",
    "info": {
        "title": "Test API",
        "version": "1.0.0"
    },
    "paths": {
        "\/test": {
            "description": "something"
        }
    }
}
JSON
        ),
            $json
        );
    }

    public function testWriteYaml(): void
    {
        $openapi = $this->createOpenAPI();

        $yaml = Writer::writeToYaml($openapi);


        $this->assertEquals(preg_replace('~\R~', "\n", <<<YAML
openapi: 3.0.0
info:
  title: 'Test API'
  version: 1.0.0
paths: {  }

YAML
        ),
            $yaml
        );
    }

    public function testWriteEmptySecurityJson(): void
    {
        $openapi = $this->createOpenAPI([
            'security' => [],
        ]);

        $json = Writer::writeToJson($openapi);

        $this->assertEquals(preg_replace('~\R~', "\n", <<<JSON
{
    "openapi": "3.0.0",
    "info": {
        "title": "Test API",
        "version": "1.0.0"
    },
    "paths": {},
    "security": []
}
JSON
        ),
            $json
        );
    }


    public function testWriteEmptySecurityYaml(): void
    {
        $openapi = $this->createOpenAPI([
            'security' => [],
        ]);

        $yaml = Writer::writeToYaml($openapi);


        $this->assertEquals(preg_replace('~\R~', "\n", <<<YAML
openapi: 3.0.0
info:
  title: 'Test API'
  version: 1.0.0
paths: {  }
security: []

YAML
        ),
            $yaml
        );
    }

    public function testWriteEmptySecurityPartJson(): void
    {
        $openapi = $this->createOpenAPI([
            'security' => new SecurityRequirements([
                'Bearer' => new SecurityRequirement([])
            ]),
        ]);

        $json = Writer::writeToJson($openapi);

        $this->assertEquals(preg_replace('~\R~', "\n", <<<JSON
{
    "openapi": "3.0.0",
    "info": {
        "title": "Test API",
        "version": "1.0.0"
    },
    "paths": {},
    "security": [
        {
            "Bearer": []
        }
    ]
}
JSON
        ),
            $json
        );
    }


    public function testWriteEmptySecurityPartYaml(): void
    {
        $openapi = $this->createOpenAPI([
            'security' => new SecurityRequirements([
                'Bearer' => new SecurityRequirement([])
            ]),
        ]);

        $yaml = Writer::writeToYaml($openapi);


        $this->assertEquals(preg_replace('~\R~', "\n", <<<YAML
openapi: 3.0.0
info:
  title: 'Test API'
  version: 1.0.0
paths: {  }
security:
  -
    Bearer: []

YAML
        ),
            $yaml
        );
    }

    public function testSecurityAtPathOperationLevel(): void
    {
        $openapi = $this->createOpenAPI([
            'components' => new Components([
                'securitySchemes' => [
                    'BearerAuth' => new SecurityScheme([
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'AuthToken and JWT Format' # optional, arbitrary value for documentation purposes
                    ]),
                ],
            ]),
            'paths' => [
                '/test' => new PathItem([
                    'get' => new Operation([
                        'security' => new SecurityRequirements([
                            'BearerAuth' => new SecurityRequirement([]),
                        ]),
                        'responses' => new Responses([
                            200 => new Response(['description' => 'OK']),
                        ])
                    ])
                ])
            ]
        ]);

        $yaml = Writer::writeToYaml($openapi);


        $this->assertEquals(preg_replace('~\R~', "\n", <<<YAML
openapi: 3.0.0
info:
  title: 'Test API'
  version: 1.0.0
paths:
  /test:
    get:
      responses:
        '200':
          description: OK
      security:
        -
          BearerAuth: []
components:
  securitySchemes:
    BearerAuth:
      type: http
      scheme: bearer
      bearerFormat: 'AuthToken and JWT Format'

YAML
        ),
            $yaml
        );
    }

    public function testSecurityAtGlobalLevel(): void
    {
        $openapi = $this->createOpenAPI([
            'components' => new Components([
                'securitySchemes' => [
                    'BearerAuth' => new SecurityScheme([
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'AuthToken and JWT Format' # optional, arbitrary value for documentation purposes
                    ])
                ],
            ]),
            'security' => new SecurityRequirements([
                'BearerAuth' => new SecurityRequirement([])
            ]),
            'paths' => [],
        ]);

        $yaml = Writer::writeToYaml($openapi);


        $this->assertEquals(preg_replace('~\R~', "\n", <<<YAML
openapi: 3.0.0
info:
  title: 'Test API'
  version: 1.0.0
paths: {  }
components:
  securitySchemes:
    BearerAuth:
      type: http
      scheme: bearer
      bearerFormat: 'AuthToken and JWT Format'
security:
  -
    BearerAuth: []

YAML
        ),
            $yaml
        );
    }
}
