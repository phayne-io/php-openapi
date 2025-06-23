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
use Phayne\OpenAPI\Specification\PathItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class CallbackTest
 *
 * @package PhayneTest\OpenAPI\Specification
 */
#[CoversClass(Callback::class)]
class CallbackTest extends TestCase
{
    public function testRead(): void
    {
        /** @var $callback Callback */
        $callback = Reader::readFromYaml(<<<'YAML'
'http://notificationServer.com?transactionId={$request.body#/id}&email={$request.body#/email}':
  post:
    requestBody:
      description: Callback payload
      content: 
        'application/json':
          schema:
            $ref: '#/components/schemas/SomePayload'
    responses:
      '200':
        description: webhook successfully processed and no retries will be performed
YAML
            , Callback::class);

        $result = $callback->validate();
        $this->assertEquals([], $callback->errors());
        $this->assertTrue($result);

        $this->assertEquals(
            'http://notificationServer.com?transactionId={$request.body#/id}&email={$request.body#/email}',
            $callback->url
        );
        $this->assertInstanceOf(PathItem::class, $callback->request);
    }
}
