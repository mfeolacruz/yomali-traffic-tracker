<?php

declare(strict_types=1);

namespace Yomali\Tracker\Domain\Tracking\ValueObject;

/**
 * URL value object that extracts domain and path components
 */
final readonly class Url implements \Stringable
{
    public string $fullUrl;
    public string $domain;
    public string $path;

    public function __construct(string $url)
    {
        $this->validate($url);
        $this->fullUrl = $url;

        $parsed = parse_url($url);
        $this->domain = $parsed['host'] ?? '';
        $this->path = ($parsed['path'] ?? '/')
            . (isset($parsed['query']) ? '?' . $parsed['query'] : '');
    }

    private function validate(string $url): void
    {
        match (true) {
            !filter_var($url, FILTER_VALIDATE_URL) =>
            throw new \InvalidArgumentException("Invalid URL: {$url}"),
            strlen($url) > 2048 =>
            throw new \InvalidArgumentException("URL too long (max 2048 characters)"),
            default => null
        };
    }

    public function __toString(): string
    {
        return $this->fullUrl;
    }
}
