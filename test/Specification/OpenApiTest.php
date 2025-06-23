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
use Phayne\OpenAPI\Json\JsonPointer;
use Phayne\OpenAPI\Specification\Components;
use Phayne\OpenAPI\Specification\ExternalDocumentation;
use Phayne\OpenAPI\Specification\Info;
use Phayne\OpenAPI\Specification\License;
use Phayne\OpenAPI\Specification\OpenApi;
use Phayne\OpenAPI\Specification\Paths;
use Phayne\OpenAPI\Specification\SecurityRequirement;
use Phayne\OpenAPI\Specification\SecurityRequirements;
use Phayne\OpenAPI\Specification\Server;
use Phayne\OpenAPI\Specification\Tag;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Yaml\Yaml;

/**
 * Class OpenApiTest
 *
 * @package PhayneTest\OpenAPI\Specification
 */
#[CoversClass(OpenApi::class)]
class OpenApiTest extends TestCase
{
    public function testEmpty(): void
    {
        $openapi = new OpenApi([]);

        $this->assertFalse($openapi->validate());
        $this->assertEquals([
            'OpenApi is missing required property: openapi',
            'OpenApi is missing required property: info',
            'OpenApi is missing required property: paths',
        ], $openapi->errors());

        // check default value of servers
        // https://github.com/OAI/OpenAPI-Specification/blob/3.0.2/versions/3.0.2.md#openapiObject
        // If the servers property is not provided, or is an empty array,
        // the default value would be a Server Object with a url value of /.
        $this->assertCount(1, $openapi->servers);
        $this->assertEquals('/', $openapi->servers[0]->url);
    }

    public function testReadPetStore(): void
    {
        $openApiFile = __DIR__ . '/../../vendor/oai/openapi-specification/examples/v3.0/petstore.yaml';

        $yaml = Yaml::parse(file_get_contents($openApiFile));
        $openapi = new OpenApi($yaml);

        $result = $openapi->validate();
        $this->assertEquals([], $openapi->errors());
        $this->assertTrue($result);

        // openapi
        $this->assertEquals('3.0.0', $openapi->openapi);

        // info
        $this->assertInstanceOf(Info::class, $openapi->info);
        $this->assertEquals('1.0.0', $openapi->info->version);
        $this->assertEquals('Swagger Petstore', $openapi->info->title);
        // info.license
        $this->assertInstanceOf(License::class, $openapi->info->license);
        $this->assertEquals('MIT', $openapi->info->license->name);
        // info.contact
        $this->assertNull($openapi->info->contact);


        // servers
        $this->assertIsArray($openapi->servers);

        $this->assertCount(1, $openapi->servers);
        foreach ($openapi->servers as $server) {
            $this->assertInstanceOf(Server::class, $server);
            $this->assertEquals('http://petstore.swagger.io/v1', $server->url);
        }

        // paths
        $this->assertInstanceOf(Paths::class, $openapi->paths);

        // components
        $this->assertInstanceOf(Components::class, $openapi->components);

        // security
        $this->assertNull($openapi->security); # since it is not present in spec

        // tags
        $this->assertAllInstanceOf(Tag::class, $openapi->tags);

        // externalDocs
        $this->assertNull($openapi->externalDocs);
    }

    public function assertAllInstanceOf($className, $array): void
    {
        foreach ($array as $k => $v) {
            $this->assertInstanceOf(
                $className,
                $v,
                "Asserting that item with key '$k' is instance of $className"
            );
        }
    }

    public static function specProvider(): Generator
    {
        // examples from https://github.com/OAI/OpenAPI-Specification/tree/master/examples/v3.0
        $oaiExamples = [
            // TODO symfony/yaml can not read this file!?
//            __DIR__ . '/../../vendor/oai/openapi-specification/examples/v3.0/api-with-examples.yaml',
            __DIR__ . '/../../vendor/oai/openapi-specification/examples/v3.0/callback-example.yaml',
            __DIR__ . '/../../vendor/oai/openapi-specification/examples/v3.0/link-example.yaml',
            __DIR__ . '/../../vendor/oai/openapi-specification/examples/v3.0/petstore.yaml',
            __DIR__ . '/../../vendor/oai/openapi-specification/examples/v3.0/petstore-expanded.yaml',
            __DIR__ . '/../../vendor/oai/openapi-specification/examples/v3.0/uspto.yaml',
        ];

        // examples from https://github.com/Mermade/openapi3-examples
        $mermadeExamples = [
            __DIR__ . '/../../vendor/mermade/openapi3-examples/pass/externalPathItemRef.yaml',
            __DIR__ . '/../../vendor/mermade/openapi3-examples/pass/deprecated.yaml',
            __DIR__ . '/../../vendor/mermade/openapi3-examples/pass/swagger2openapi/openapi.json',
            __DIR__ . '/../../vendor/mermade/openapi3-examples/pass/gluecon/example1_from_._Different_parameters.md.yaml', //phpcs:ignore
            __DIR__ . '/../../vendor/mermade/openapi3-examples/pass/gluecon/example1_from_._Fixed_file.md.yaml',
            __DIR__ . '/../../vendor/mermade/openapi3-examples/pass/gluecon/example1_from_._Different_parameters.md.yaml', //phpcs:ignore
            __DIR__ . '/../../vendor/mermade/openapi3-examples/pass/gluecon/example1_from_._Fixed_file.md.yaml',
            __DIR__ . '/../../vendor/mermade/openapi3-examples/pass/gluecon/example1_from_._Fixed_multipart.md.yaml',
            __DIR__ . '/../../vendor/mermade/openapi3-examples/pass/gluecon/example1_from_._Improved_examples.md.yaml',
            __DIR__ . '/../../vendor/mermade/openapi3-examples/pass/gluecon/example1_from_._Improved_pathdescriptions.md.yaml', //phpcs:ignore
            __DIR__ . '/../../vendor/mermade/openapi3-examples/pass/gluecon/example1_from_._Improved_securityschemes.md.yaml', //phpcs:ignore
            __DIR__ . '/../../vendor/mermade/openapi3-examples/pass/gluecon/example1_from_._Improved_serverseverywhere.md.yaml', //phpcs:ignore
            __DIR__ . '/../../vendor/mermade/openapi3-examples/pass/gluecon/example1_from_._New_callbacks.md.yaml',
            __DIR__ . '/../../vendor/mermade/openapi3-examples/pass/gluecon/example1_from_._New_links.md.yaml',
            __DIR__ . '/../../vendor/mermade/openapi3-examples/pass/gluecon/example2_from_._Different_parameters.md.yaml', //phpcs:ignore
            __DIR__ . '/../../vendor/mermade/openapi3-examples/pass/gluecon/example2_from_._Different_requestbody.md.yaml', //phpcs:ignore
            __DIR__ . '/../../vendor/mermade/openapi3-examples/pass/gluecon/example2_from_._Different_servers.md.yaml',
            __DIR__ . '/../../vendor/mermade/openapi3-examples/pass/gluecon/example2_from_._Fixed_multipart.md.yaml',
            __DIR__ . '/../../vendor/mermade/openapi3-examples/pass/gluecon/example2_from_._Improved_securityschemes.md.yaml', //phpcs:ignore
            __DIR__ . '/../../vendor/mermade/openapi3-examples/pass/gluecon/example2_from_._New_callbacks.md.yaml',
            __DIR__ . '/../../vendor/mermade/openapi3-examples/pass/gluecon/example2_from_._New_links.md.yaml',
            __DIR__ . '/../../vendor/mermade/openapi3-examples/pass/gluecon/example3_from_._Different_parameters.md.yaml', //phpcs:ignore
            __DIR__ . '/../../vendor/mermade/openapi3-examples/pass/gluecon/example3_from_._Different_servers.md.yaml',
            __DIR__ . '/../../vendor/mermade/openapi3-examples/pass/gluecon/example4_from_._Different_parameters.md.yaml', //phpcs:ignore
            __DIR__ . '/../../vendor/mermade/openapi3-examples/pass/gluecon/example5_from_._Different_parameters.md.yaml', //phpcs:ignore
            // TODO symfony/yaml can not read this file!?
//            __DIR__ . '/../../vendor/mermade/openapi3-examples/pass/OAI/api-with-examples.yaml',
            __DIR__ . '/../../vendor/mermade/openapi3-examples/pass/OAI/petstore-expanded.yaml',
            __DIR__ . '/../../vendor/mermade/openapi3-examples/pass/OAI/petstore.yaml',
            __DIR__ . '/../../vendor/mermade/openapi3-examples/pass/OAI/uber.yaml',

            __DIR__ . '/../../vendor/mermade/openapi3-examples/malicious/rapid7-html.json',
            __DIR__ . '/../../vendor/mermade/openapi3-examples/malicious/rapid7-java.json',
            __DIR__ . '/../../vendor/mermade/openapi3-examples/malicious/rapid7-js.json',
            __DIR__ . '/../../vendor/mermade/openapi3-examples/malicious/rapid7-php.json',
            __DIR__ . '/../../vendor/mermade/openapi3-examples/malicious/rapid7-ruby.json',
//            __DIR__ . '/../../vendor/mermade/openapi3-examples/malicious/yamlbomb.yaml',
        ];

        // examples from https://github.com/APIs-guru/openapi-directory/tree/openapi3.0.0/APIs
        $apisGuruExamples = [];
        /** @var $it RecursiveDirectoryIterator|RecursiveIteratorIterator */
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__ . '/../../vendor/apis-guru/openapi-directory/APIs')
        );
        $it->rewind();
        while ($it->valid()) {
            if ($it->getBasename() === 'openapi.yaml') {
                $apisGuruExamples[] = $it->key();
            }
            $it->next();
        }

        // examples from https://github.com/Nexmo/api-specification/tree/master/definitions
        $nexmoExamples = [];
        /** @var $it RecursiveDirectoryIterator|RecursiveIteratorIterator */
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__ . '/../../vendor/nexmo/api-specification/definitions')
        );
        $it->rewind();
        while ($it->valid()) {
            if (
                $it->getExtension() === 'yml' &&
                ! str_contains($it->getSubPath(), 'common') &&
                $it->getBasename() !== 'voice.v2.yml' // contains invalid references
            ) {
                $nexmoExamples[] = $it->key();
            }
            $it->next();
        }

        $all = array_merge(
            $oaiExamples,
            $mermadeExamples,
            $apisGuruExamples,
            $nexmoExamples
        );

        foreach ($all as $path) {
            yield [
                substr($path, strlen(__DIR__ . '/../../vendor/')),
                basename(dirname($path, 2)) .
                DIRECTORY_SEPARATOR .
                basename(dirname($path, 1)) .
                DIRECTORY_SEPARATOR .
                basename($path)
            ];
        }
    }

    #[DataProvider('specProvider')]
    public function testSpecs($openApiFile): void
    {
        if (strtolower(substr($openApiFile, -5, 5)) === '.json') {
            $json = json_decode(file_get_contents(__DIR__ . '/../../vendor/' . $openApiFile), true);
            $openapi = new OpenApi($json);
        } else {
            $yaml = Yaml::parse(file_get_contents(__DIR__ . '/../../vendor/' . $openApiFile));
            $openapi = new OpenApi($yaml);
        }
        $openapi->setDocumentContext($openapi, new JsonPointer(''));

        $result = $openapi->validate();
        $this->assertEquals([], $openapi->errors(), print_r($openapi->errors(), true));
        $this->assertTrue($result);

        // openapi
        $this->assertStringStartsWith('3.0.', $openapi->openapi);

        // info
        $this->assertInstanceOf(Info::class, $openapi->info);

        // servers
        $this->assertAllInstanceOf(Server::class, $openapi->servers);

        // paths
        if ($openapi->components !== null) {
            $this->assertInstanceOf(Paths::class, $openapi->paths);
        }

        // components
        if ($openapi->components !== null) {
            $this->assertInstanceOf(Components::class, $openapi->components);
        }

        // security
        $openapi->security !== null &&
        $this->assertInstanceOf(SecurityRequirements::class, $openapi->security);
        $openapi->security !== null &&
        $this->assertAllInstanceOf(SecurityRequirement::class, $openapi->security->requirements());

        // tags
        $this->assertAllInstanceOf(Tag::class, $openapi->tags);

        // externalDocs
        if ($openapi->externalDocs !== null) {
            $this->assertInstanceOf(ExternalDocumentation::class, $openapi->externalDocs);
        }
    }
}
