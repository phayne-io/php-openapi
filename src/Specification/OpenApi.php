<?php

/**
 * This file is part of phayne-io/php-openapi and is proprietary and confidential.
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 *
 * @see       https://github.com/phayne-io/php-openapi for the canonical source repository
 * @copyright Copyright (c) 2024-2025 Phayne Limited. (https://phayne.io)
 */

declare(strict_types=1);

namespace Phayne\OpenAPI\Specification;

use Override;
use Phayne\OpenAPI\SpecBaseObject;

use function preg_match;

/**
 * Class OpenApi
 *
 * This is the root document object of the OpenAPI document.
 *
 * @link https://github.com/OAI/OpenAPI-Specification/blob/3.0.2/versions/3.0.2.md#openapi-object
 *
 * @property string $openapi
 * @property Info $info
 * @property Server[] $servers
 * @property Paths|PathItem[] $paths
 * @property Components|null $components
 * @property SecurityRequirement[] $security
 * @property Tag[] $tags
 * @property ExternalDocumentation|null $externalDocs
 * @package Phayne\OpenAPI\Specification
 */
class OpenApi extends SpecBaseObject
{
    #[Override]
    public function __get($name): mixed
    {
        $ret = parent::__get($name);
        // Spec: If the servers property is not provided, or is an empty array,
        // the default value would be a Server Object with a url value of /.
        if ($name === 'servers' && $ret === []) {
            return $this->attributeDefaults()['servers'];
        }

        return $ret;
    }

    #[Override]
    protected function attributes(): array
    {
        return [
            'openapi' => Type::STRING,
            'info' => Info::class,
            'servers' => [Server::class],
            'paths' => Paths::class,
            'components' => Components::class,
            'security' => SecurityRequirements::class,
            'tags' => [Tag::class],
            'externalDocs' => ExternalDocumentation::class,
        ];
    }

    #[Override]
    protected function attributeDefaults(): array
    {
        return [
            // Spec: If the servers property is not provided, or is an empty array,
            // the default value would be a Server Object with a url value of /.
            'servers' => [
                new Server(['url' => '/'])
            ],
        ];
    }

    #[Override]
    protected function performValidation(): void
    {
        $this->requireProperties(['openapi', 'info', 'paths']);
        if (!empty($this->openapi) && ! preg_match('/^3\.0\.\d+(-rc\d)?$/i', $this->openapi)) {
            $this->addError('Unsupported openapi version: ' . $this->openapi);
        }
    }
}
