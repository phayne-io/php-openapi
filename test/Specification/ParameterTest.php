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
use Phayne\OpenAPI\Specification\MediaType;
use Phayne\OpenAPI\Specification\Parameter;
use Phayne\OpenAPI\Specification\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class ParameterTest
 *
 * @package PhayneTest\OpenAPI\Specification
 */
#[CoversClass(Parameter::class)]
class ParameterTest extends TestCase
{
    public function testRead(): void
    {
        /** @var $parameter Parameter */
        $parameter = Reader::readFromYaml(<<<'YAML'
name: token
in: header
description: token to be passed as a header
required: true
schema:
  type: array
  items:
    type: integer
    format: int64
style: simple
YAML
            , Parameter::class);

        $result = $parameter->validate();
        $this->assertEquals([], $parameter->errors());
        $this->assertTrue($result);

        $this->assertEquals('token', $parameter->name);
        $this->assertEquals('header', $parameter->in);
        $this->assertEquals('token to be passed as a header', $parameter->description);
        $this->assertTrue($parameter->required);

        $this->assertInstanceOf(Schema::class, $parameter->schema);
        $this->assertEquals('array', $parameter->schema->type);

        $this->assertEquals('simple', $parameter->style);

        /** @var $parameter Parameter */
        $parameter = Reader::readFromYaml(<<<'YAML'
in: query
name: coordinates
content:
  application/json:
    schema:
      type: object
      required:
        - lat
        - long
      properties:
        lat:
          type: number
        long:
          type: number
YAML
            , Parameter::class);

        $result = $parameter->validate();
        $this->assertEquals([], $parameter->errors());
        $this->assertTrue($result);

        $this->assertEquals('coordinates', $parameter->name);
        $this->assertEquals('query', $parameter->in);
        // required default value is false.
        $this->assertFalse($parameter->required);
        // deprecated default value is false.
        $this->assertFalse($parameter->deprecated);
        // allowEmptyValue default value is false.
        $this->assertFalse($parameter->allowEmptyValue);

        $this->assertInstanceOf(MediaType::class, $parameter->content['application/json']);
        $this->assertInstanceOf(Schema::class, $parameter->content['application/json']->schema);
    }

    public function testDefaultValuesQuery(): void
    {
        /** @var $parameter Parameter */
        $parameter = Reader::readFromYaml(<<<'YAML'
name: token
in: query
YAML
            , Parameter::class);

        $result = $parameter->validate();
        $this->assertEquals([], $parameter->errors());
        $this->assertTrue($result);

        // default value for style parameter in query param
        $this->assertEquals('form', $parameter->style);
        $this->assertTrue($parameter->explode);
        $this->assertFalse($parameter->allowReserved);
    }

    public function testDefaultValuesPath(): void
    {
        /** @var $parameter Parameter */
        $parameter = Reader::readFromYaml(<<<'YAML'
name: token
in: path
required: true
YAML
            , Parameter::class);

        $result = $parameter->validate();
        $this->assertEquals([], $parameter->errors());
        $this->assertTrue($result);

        // default value for style parameter in query param
        $this->assertEquals('simple', $parameter->style);
        $this->assertFalse($parameter->explode);
    }

    public function testDefaultValuesHeader(): void
    {
        /** @var $parameter Parameter */
        $parameter = Reader::readFromYaml(<<<'YAML'
name: token
in: header
YAML
            , Parameter::class);

        $result = $parameter->validate();
        $this->assertEquals([], $parameter->errors());
        $this->assertTrue($result);

        // default value for style parameter in query param
        $this->assertEquals('simple', $parameter->style);
        $this->assertFalse($parameter->explode);
    }

    public function testDefaultValuesCookie(): void
    {
        /** @var $parameter Parameter */
        $parameter = Reader::readFromYaml(<<<'YAML'
name: token
in: cookie
YAML
            , Parameter::class);

        $result = $parameter->validate();
        $this->assertEquals([], $parameter->errors());
        $this->assertTrue($result);

        // default value for style parameter in query param
        $this->assertEquals('form', $parameter->style);
        $this->assertTrue($parameter->explode);
    }

    public function testItValidatesSchemaAndContentCombination(): void
    {
        /** @var $parameter Parameter */
        $parameter = Reader::readFromYaml(<<<'YAML'
name: token
in: cookie
schema:
  type: object
content:
  application/json:
    schema:
      type: object
YAML
            , Parameter::class);

        $result = $parameter->validate();
        $this->assertEquals(['A Parameter Object MUST contain either a schema property, or a content property, but not both.'], $parameter->errors());
        $this->assertFalse($result);
    }

    public function testItValidatesContentCanHaveOnlySingleKey(): void
    {
        /** @var $parameter Parameter */
        $parameter = Reader::readFromYaml(<<<'YAML'
name: token
in: cookie
content:
  application/json:
    schema:
      type: object
  application/xml:
    schema:
      type: object
YAML
            , Parameter::class);

        $result = $parameter->validate();
        $this->assertEquals(['A Parameter Object with Content property MUST have A SINGLE content type.'], $parameter->errors());
        $this->assertFalse($result);
    }


    public function testItValidatesSupportedSerializationStyles(): void
    {
        // 1. Prepare test inputs
        $specTemplate = <<<YAML
name: token
required: true
in: %s
style: %s
YAML;
        $goodCombinations = [
            'path' => ['simple', 'label', 'matrix'],
            'query' => ['form', 'spaceDelimited', 'pipeDelimited', 'deepObject'],
            'header' => ['simple'],
            'cookie' => ['form'],
        ];
        $badCombinations = [
            'path' => ['unknown', 'form', 'spaceDelimited', 'pipeDelimited', 'deepObject'],
            'query' => ['unknown', 'simple', 'label', 'matrix'],
            'header' => ['unknown', 'form', 'spaceDelimited', 'pipeDelimited', 'deepObject', 'matrix'],
            'cookie' => ['unknown', 'spaceDelimited', 'pipeDelimited', 'deepObject', 'matrix', 'label', 'matrix'],
        ];

        // 2. Run tests for valid input
        foreach ($goodCombinations as $in=>$styles) {
            foreach ($styles as $style) {
                /** @var $parameter Parameter */
                $parameter = Reader::readFromYaml(sprintf($specTemplate, $in, $style) , Parameter::class);
                $result = $parameter->validate();
                $this->assertEquals([], $parameter->errors());
                $this->assertTrue($result);
            }
        }

        // 2. Run tests for invalid input
        foreach ($badCombinations as $in=>$styles) {
            foreach ($styles as $style) {
                /** @var $parameter Parameter */
                $parameter = Reader::readFromYaml(sprintf($specTemplate, $in, $style) , Parameter::class);
                $result = $parameter->validate();
                $this->assertEquals(['A Parameter Object DOES NOT support this serialization style.'], $parameter->errors());
                $this->assertFalse($result);
            }
        }
    }
}
