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
use Phayne\OpenAPI\Specification\Server;
use Phayne\OpenAPI\Specification\ServerVariable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class ServerTest
 *
 * @package PhayneTest\OpenAPI\Specification
 */
#[CoversClass(Server::class)]
#[CoversClass(ServerVariable::class)]
class ServerTest extends TestCase
{
    public function testRead(): void
    {
        /** @var $server Server */
        $server = Reader::readFromJson(<<<JSON
{
  "url": "https://{username}.gigantic-server.com:{port}/{basePath}",
  "description": "The production API server",
  "variables": {
    "username": {
      "default": "demo",
      "description": "this value is assigned by the service provider, in this example `gigantic-server.com`"
    },
    "port": {
      "enum": [
        "8443",
        "443"
      ],
      "default": "8443"
    },
    "basePath": {
      "default": "v2"
    }
  }
}
JSON
            , Server::class);

        $result = $server->validate();
        $this->assertEquals([], $server->errors());
        $this->assertTrue($result);

        $this->assertEquals('https://{username}.gigantic-server.com:{port}/{basePath}', $server->url);
        $this->assertEquals('The production API server', $server->description);
        $this->assertCount(3, $server->variables);
        $this->assertEquals('demo', $server->variables['username']->default);
        $this->assertEquals(
            'this value is assigned by the service provider, in this example `gigantic-server.com`',
            $server->variables['username']->description
        );
        $this->assertEquals('8443', $server->variables['port']->default);

        /** @var $server Server */
        $server = Reader::readFromJson(<<<JSON
{
  "description": "The production API server"
}
JSON
            , Server::class);

        $result = $server->validate();
        $this->assertEquals(['Server is missing required property: url'], $server->errors());
        $this->assertFalse($result);


        /** @var $server Server */
        $server = Reader::readFromJson(<<<JSON
{
  "url": "https://{username}.gigantic-server.com:{port}/{basePath}",
  "description": "The production API server",
  "variables": {
    "username": {
      "description": "this value is assigned by the service provider, in this example `gigantic-server.com`"
    }
  }
}
JSON
            , Server::class);

        $result = $server->validate();
        $this->assertEquals(['ServerVariable is missing required property: default'], $server->errors());
        $this->assertFalse($result);
    }
}
