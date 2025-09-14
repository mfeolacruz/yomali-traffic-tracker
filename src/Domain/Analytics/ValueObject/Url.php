<?php

declare(strict_types=1);

namespace Yomali\Tracker\Domain\Analytics\ValueObject;

final readonly class Url
{
    public function __construct(
        private string $value,
        private string $domain,
        private string $path,
    ) {
        if (empty(trim($value))) {
            throw new \InvalidArgumentException('URL cannot be empty');
        }

        if (empty(trim($domain))) {
            throw new \InvalidArgumentException('Domain cannot be empty');
        }
    }

    public static function fromString(string $url): self
    {
        $url = trim($url);

        if (empty($url)) {
            throw new \InvalidArgumentException('URL cannot be empty');
        }

        $parsedUrl = parse_url($url);

        if ($parsedUrl === false || !isset($parsedUrl['host'])) {
            throw new \InvalidArgumentException('Invalid URL format');
        }

        $domain = $parsedUrl['host'];
        $path = $parsedUrl['path'] ?? '/';

        return new self($url, $domain, $path);
    }

    public static function create(string $url, string $domain, string $path): self
    {
        return new self($url, $domain, $path);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function isSameDomain(self $other): bool
    {
        return $this->domain === $other->domain;
    }

    public function isSamePath(self $other): bool
    {
        return $this->path === $other->path;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
