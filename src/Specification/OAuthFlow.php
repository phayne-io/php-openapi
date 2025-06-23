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

/**
 * Class OAuthFlow
 *
 * Configuration details for a supported OAuth Flow.
 *
 * @link https://github.com/OAI/OpenAPI-Specification/blob/3.0.2/versions/3.0.2.md#oauthFlowObject
 *
 * @property string $authorizationUrl
 * @property string $tokenUrl
 * @property string $refreshUrl
 * @property string[] $scopes
 * @package Phayne\OpenAPI\Specification
 */
class OAuthFlow extends SpecBaseObject
{
    #[Override]
    protected function attributes(): array
    {
        return [
            'authorizationUrl' => Type::STRING,
            'tokenUrl' => Type::STRING,
            'refreshUrl' => Type::STRING,
            'scopes' => [Type::STRING, Type::STRING],
        ];
    }

    #[Override]
    protected function performValidation(): void
    {
        $this->requireProperties(['scopes']);
        // TODO: Validation in context of the parent object
        // authorizationUrl is required if this object is in "implicit", "authorizationCode"
        // tokenUrl is required if this object is in "password", "clientCredentials", "authorizationCode"
    }
}
