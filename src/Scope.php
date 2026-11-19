<?php

declare(strict_types=1);

namespace Survos\ConnectionsContract;

/** The dataset a graph was read from, and the immutable export version when the source has one. */
final readonly class Scope implements \JsonSerializable
{
    public function __construct(public string $dataset, public ?string $version = null)
    {
        if ('' === $dataset) {
            throw new \InvalidArgumentException('A scope needs a dataset key.');
        }
    }

    public static function fromArray(array $data): self
    {
        return new self((string) ($data['dataset'] ?? ''), isset($data['version']) ? (string) $data['version'] : null);
    }

    public function jsonSerialize(): array
    {
        return ['dataset' => $this->dataset, 'version' => $this->version];
    }
}
