<?php

/**
 * This file is part of phayne-io/php-openapi and is proprietary and confidential.
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 *
 * @see       https://github.com/phayne-io/php-openapi for the canonical source repository
 * @copyright Copyright (c) 2024-2025 Phayne Limited. (https://phayne.io)
 */

declare(strict_types=1);

namespace PhayneTest\OpenAPI\Json;

use Phayne\OpenAPI\Json\JsonPointer;
use Phayne\OpenAPI\Json\JsonReference;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Class JsonPointerTest
 *
 * @package PhayneTest\OpenAPI\Json
 */
class JsonPointerTest extends TestCase
{
    public static function encodeDecodeData(): array
    {
        return [
            ['~0', '~'],
            ['~1', '/'],
            ['something', 'something'],
            ['~01', '~1'],
            ['~1~0', '/~'],
            ['~0~1', '~/'],
            ['~0~0', '~~'],
            ['~1~1', '//'],
            ['some~1path~1', 'some/path/'],
            ['1some0~11path0~1', '1some0/1path0/'],
            ['1some0~11path~00', '1some0/1path~0'],
        ];
    }

    #[DataProvider('encodeDecodeData')]
    public function testEncode($encoded, $decoded): void
    {
        $this->assertEquals($encoded, JsonPointer::encode($decoded));
    }

    #[DataProvider('encodeDecodeData')]
    public function testDecode($encoded, $decoded): void
    {
        $this->assertEquals($decoded, JsonPointer::decode($encoded));
    }

    /**
     * @link https://tools.ietf.org/html/rfc6901#section-5
     */
    public static function rfcJsonDocument(): string
    {
        return <<<JSON
{
      "foo": ["bar", "baz"],
      "": 0,
      "a/b": 1,
      "c%d": 2,
      "e^f": 3,
      "g|h": 4,
      "i\\\\j": 5,
      "k\"l": 6,
      " ": 7,
      "m~n": 8
}
JSON;
    }

    /**
     * @link https://tools.ietf.org/html/rfc6901#section-5
     */
    public static function rfcExamples(): iterable
    {
        $return = [
            [""      , "#"      , json_decode(self::rfcJsonDocument())],
            ["/foo"  , "#/foo"  , ["bar", "baz"]],
            ["/foo/0", "#/foo/0", "bar"],
            ["/"     , "#/"     , 0],
            ["/a~1b" , "#/a~1b" , 1],
            ["/c%d"  , "#/c%25d", 2],
            ["/e^f"  , "#/e%5Ef", 3],
            ["/g|h"  , "#/g%7Ch", 4],
            ["/i\\j" , "#/i%5Cj", 5],
            ["/k\"l" , "#/k%22l", 6],
            ["/ "    , "#/%20"  , 7],
            ["/m~0n" , "#/m~0n" , 8],
        ];
        foreach ($return as $example) {
            $example[3] = self::rfcJsonDocument();
            yield $example;
        }
    }

    public static function allExamples(): iterable
    {
        yield from self::rfcExamples();

        yield ["/a#b" , "#/a%23b" , 16, '{"a#b": 16}'];
    }

    #[DataProvider('allExamples')]
    public function testUriEncoding($jsonPointer, $uriJsonPointer, $expectedEvaluation): void
    {
        $pointer = new JsonPointer($jsonPointer);
        $this->assertSame($jsonPointer, $pointer->pointer);
        $this->assertSame($uriJsonPointer, JsonReference::createFromUri('', $pointer)->reference);

        $reference = JsonReference::createFromReference($uriJsonPointer);
        $this->assertSame($jsonPointer, $reference->jsonPointer->pointer);
        $this->assertSame('', $reference->documentUri);
        $this->assertSame($uriJsonPointer, $reference->reference);

        $reference = JsonReference::createFromReference("somefile.json$uriJsonPointer");
        $this->assertSame($jsonPointer, $reference->jsonPointer->pointer);
        $this->assertSame("somefile.json", $reference->documentUri);
        $this->assertSame("somefile.json$uriJsonPointer", $reference->reference);
    }

    #[DataProvider('rfcExamples')]
    public function testEvaluation($jsonPointer, $uriJsonPointer, $expectedEvaluation)
    {
        $document = json_decode($this->rfcJsonDocument());
        $pointer = new JsonPointer($jsonPointer);
        $this->assertEquals($expectedEvaluation, $pointer->evaluate($document));

        $document = json_decode($this->rfcJsonDocument());
        $reference = JsonReference::createFromReference($uriJsonPointer);
        $this->assertEquals($expectedEvaluation, $reference->jsonPointer->evaluate($document));
    }

    public function testEvaluationCases(): void
    {
        $document = (object) [
            "" => (object) [
                "" => 42
            ]
        ];
        $pointer = new JsonPointer('//');
        $this->assertSame(42, $pointer->evaluate($document));

        $document = [
            "1" => null,
        ];
        $pointer = new JsonPointer('/1');
        $this->assertNull($pointer->evaluate($document));

        $document = (object) [
            "k" => null,
        ];
        $pointer = new JsonPointer('/k');
        $this->assertNull($pointer->evaluate($document));
    }

    public function testParent(): void
    {
        $this->assertNull(new JsonPointer('')->parent());
        $this->assertSame('', new JsonPointer('/')->parent()?->pointer);
        $this->assertSame('/', new JsonPointer('//')->parent()?->pointer);
        $this->assertSame('', new JsonPointer('/some')->parent()?->pointer);
        $this->assertSame('/some', new JsonPointer('/some/path')->parent()?->pointer);
        $this->assertSame('', new JsonPointer('/a~1b')->parent()?->pointer);
        $this->assertSame('/a~1b', new JsonPointer('/a~1b/path')->parent()?->pointer);
        $this->assertSame('/some', new JsonPointer('/some/a~1b')->parent()?->pointer);
    }

    public function testAppend(): void
    {
        $this->assertSame('/some', new JsonPointer('')->append('some')?->pointer);
        $this->assertSame('/~1some', new JsonPointer('')->append('/some')?->pointer);
        $this->assertSame('/~0some', new JsonPointer('')->append('~some')?->pointer);
        $this->assertSame('/path/some', new JsonPointer('/path')->append('some')?->pointer);
        $this->assertSame('/path/~1some', new JsonPointer('/path')->append('/some')?->pointer);
        $this->assertSame('/path/~0some', new JsonPointer('/path')->append('~some')?->pointer);
        $this->assertSame('/a~1b/some', new JsonPointer('/a~1b')->append('some')?->pointer);
        $this->assertSame('/a~1b/~1some', new JsonPointer('/a~1b')->append('/some')?->pointer);
        $this->assertSame('/a~1b/~0some', new JsonPointer('/a~1b')->append('~some')?->pointer);
    }
}
