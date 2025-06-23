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
 * Class OAuthFlows
 *
 * Allows configuration of the supported OAuth Flows.
 *
 * @link https://github.com/OAI/OpenAPI-Specification/blob/3.0.2/versions/3.0.2.md#oauthFlowsObject
 *
 * @property OAuthFlow|null $implicit
 * @property OAuthFlow|null $password
 * @property OAuthFlow|null $clientCredentials
 * @property OAuthFlow|null $authorizationCode
 * @package Phayne\OpenAPI\Specification
 */
class OAuthFlows extends SpecBaseObject
{
    #[Override]
    protected function attributes(): array
    {
        return [
            'implicit' => OAuthFlow::class,
            'password' => OAuthFlow::class,
            'clientCredentials' => OAuthFlow::class,
            'authorizationCode' => OAuthFlow::class,
        ];
    }

    #[Override]
    protected function performValidation(): void
    {
    }
}
