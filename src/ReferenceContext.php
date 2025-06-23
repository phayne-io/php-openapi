<?php

/**
 * This file is part of phayne-io/php-openapi and is proprietary and confidential.
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 *
 * @see       https://github.com/phayne-io/php-openapi for the canonical source repository
 * @copyright Copyright (c) 2024-2025 Phayne Limited. (https://phayne.io)
 */

declare(strict_types=1);

namespace Phayne\OpenAPI;

use Phayne\OpenAPI\Exception\IOException;
use Phayne\OpenAPI\Exception\UnresolvableReferenceException;
use Phayne\OpenAPI\Json\JsonPointer;
use Phayne\OpenAPI\Specification\Reference;
use Symfony\Component\Yaml\Yaml;

use function count;
use function explode;
use function file_get_contents;
use function implode;
use function ltrim;
use function mb_strrpos;
use function mb_substr;
use function parse_url;
use function rtrim;
use function str_contains;
use function str_replace;
use function stripos;
use function strncmp;
use function str_starts_with;
use function strtr;
use function substr;

/**
 * Class ReferenceContext
 *
 * @package Phayne\OpenAPI
 */
class ReferenceContext
{
    public const string RESOLVE_MODE_INLINE = 'inline';
    public const string RESOLVE_MODE_ALL = 'all';

    public bool $throwException = true;

    public string $mode = self::RESOLVE_MODE_ALL;

    private(set) string $uri;

    private(set) ReferenceContextCache $cache;

    public function __construct(
        public readonly ?SpecObjectInterface $baseSpecification,
        string $uri,
        ?ReferenceContextCache $cache = null,
    ) {
        $this->uri = $this->normalizeUri($uri);
        $this->cache = $cache ?? new ReferenceContextCache();

        if ($cache === null && $this->baseSpecification !== null) {
            $this->cache->set($this->uri, null, $baseSpecification);
        }
    }

    private function normalizeUri(string $uri): string
    {
        if (str_contains($uri, '://')) {
            $parts = parse_url($uri);
            if (isset($parts['path'])) {
                $parts['path'] = $this->reduceDots($parts['path']);
            }
            return $this->buildUri($parts);
        }

        if (strncmp($uri, '/', 1) === 0) {
            $uri = $this->reduceDots($uri);
            return "file://$uri";
        }

        if (stripos(PHP_OS, 'WIN') === 0 && strncmp(substr($uri, 1), ':\\', 2) === 0) {
            $uri = $this->reduceDots($uri);
            return "file://" . strtr($uri, [' ' => '%20', '\\' => '/']);
        }

        throw new UnresolvableReferenceException(
            'Can not resolve references for a specification given as a relative path.'
        );
    }

    private function buildUri(array $parts): string
    {
        $scheme   = !empty($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $host     = $parts['host'] ?? '';
        $port     = !empty($parts['port']) ? ':' . $parts['port'] : '';
        $user     = $parts['user'] ?? '';
        $pass     = !empty($parts['pass']) ? ':' . $parts['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = $parts['path'] ?? '';
        $query    = !empty($parts['query']) ? '?' . $parts['query'] : '';
        $fragment = !empty($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    private function reduceDots(string $path): string
    {
        $parts = explode('/', ltrim($path, '/'));
        $c = count($parts);
        $parentOffset = 1;

        for ($i = 0; $i < $c; $i++) {
            if ($parts[$i] === '.') {
                unset($parts[$i]);
                continue;
            }

            if ($i > 0 && $parts[$i] === '..') {
                $parent = $i - $parentOffset;
                //Make sure parent exists, if not, check the next parent etc
                while ($parent >= 0 && empty($parts[$parent])) {
                    $parent--;
                }
                //Confirm parent is valid
                if (! empty($parts[$parent]) && $parts[$parent] !== '..') {
                    unset($parts[$parent]);
                }
                unset($parts[$i]);
            }
        }

        return '/' . implode('/', $parts);
    }

    public function resolveRelativeUri(string $uri): string
    {
        $parts = parse_url($uri);

        if (isset($parts['scheme'])) {
            if (isset($parts['path'])) {
                $parts['path'] = $this->reduceDots($parts['path']);
            }
            return $this->buildUri($parts);
        }

        if (stripos(PHP_OS, 'WIN') === 0 && strncmp(substr($uri, 1), ':\\', 2) === 0) {
            $absoluteUri = "file:///" . strtr($uri, [' ' => '%20', '\\' => '/']);
            return $absoluteUri . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
        }

        $baseUri = $this->uri;
        $baseParts = parse_url($baseUri);

        if (isset($parts['path'][0]) && $parts['path'][0] === '/') {
            // absolute path
            $baseParts['path'] = $this->reduceDots($parts['path']);
        } elseif (isset($parts['path'])) {
            // relative path
            $baseParts['path'] = $this->reduceDots(
                rtrim($this->dirname($baseParts['path'] ?? ''), '/') . '/' . $parts['path']
            );
        } else {
            throw new UnresolvableReferenceException("Invalid URI: '$uri'");
        }

        $baseParts['query'] = $parts['query'] ?? null;
        $baseParts['fragment'] = $parts['fragment'] ?? null;

        return $this->buildUri($baseParts);
    }

    public function fetchReferencedFile(string $uri): mixed
    {
        if ($this->cache->has('FILE_CONTENT://' . $uri, 'FILE_CONTENT')) {
            return $this->cache->get('FILE_CONTENT://' . $uri, 'FILE_CONTENT');
        }

        $content = file_get_contents($uri);

        if ($content === false) {
            $e = new IOException("Failed to read file: '$uri'");
            $e->fileName = $uri;
            throw $e;
        }
        // TODO lazy content detection, should be improved
        if (str_starts_with(ltrim($content), '{')) {
            $parsedContent = json_decode($content, true);
        } else {
            $parsedContent = Yaml::parse($content);
        }
        $this->cache->set('FILE_CONTENT://' . $uri, 'FILE_CONTENT', $parsedContent);
        return $parsedContent;
    }

    public function resolveReferenceData($uri, JsonPointer $pointer, $data, $toType)
    {
        $ref = $uri . '#' . $pointer->pointer;
        if ($this->cache->has($ref, $toType)) {
            return $this->cache->get($ref, $toType);
        }

        $referencedData = $pointer->evaluate($data);

        if ($referencedData === null) {
            return null;
        }

        // transitive reference
        if (isset($referencedData['$ref'])) {
            return new Reference($referencedData, $toType);
        } else {
            /** @var SpecObjectInterface|array $referencedObject */
            $referencedObject = $toType !== null ? new $toType($referencedData) : $referencedData;
        }

        $this->cache->set($ref, $toType, $referencedObject);

        return $referencedObject;
    }

    private function dirname($path): string
    {
        $pos = mb_strrpos(str_replace('\\', '/', $path), '/');

        if ($pos !== false) {
            return mb_substr($path, 0, $pos);
        }

        return '';
    }
}
