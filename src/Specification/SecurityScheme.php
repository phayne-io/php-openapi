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

use function in_array;

/**
 * Class SecurityScheme
 *
 * @package Phayne\OpenAPI\Specification
 */
class SecurityScheme extends SpecBaseObject
{
    private array $knownTypes = [
        "apiKey",
        "http",
        "oauth2",
        "openIdConnect"
    ];

    #[Override]
    protected function attributes(): array
    {
        return [
            'type' => Type::STRING,
            'description' => Type::STRING,
            'name' => Type::STRING,
            'in' => Type::STRING,
            'scheme' => Type::STRING,
            'bearerFormat' => Type::STRING,
            'flows' => OAuthFlows::class,
            'openIdConnectUrl' => Type::STRING,
        ];
    }

    #[Override]
    protected function performValidation(): void
    {
        $this->requireProperties(['type']);

        if (isset($this->type)) {
            if (! in_array($this->type, $this->knownTypes)) {
                $this->addError("Unknown Security Scheme type: $this->type");
            } else {
                switch ($this->type) {
                    case "apiKey":
                        $this->requireProperties(['name', 'in']);
                        if (isset($this->in)) {
                            if (! in_array($this->in, ["query", "header", "cookie"])) {
                                $this->addError("Invalid value for Security Scheme property 'in': $this->in");
                            }
                        }
                        break;
                    case "http":
                        $this->requireProperties(['scheme']);
                        break;
                    case "oauth2":
                        $this->requireProperties(['flows']);
                        break;
                    case "openIdConnect":
                        $this->requireProperties(['openIdConnectUrl']);
                        break;
                }
            }
        }
    }
}
