<?php

declare(strict_types=1);

namespace Survos\ConnectionsContract;

/**
 * One relation group and how much of it was loaded. Totals and paging belong to the group, as in an FtM adjacency
 * response: a group total is never a total for the whole graph.
 */
final readonly class Group implements \JsonSerializable
{
    public function __construct(public string $key, public int $total, public int $offset = 0, public int $limit = 24)
    {
        if ('' === $key || $total < 0 || $offset < 0 || $limit < 1) {
            throw new \InvalidArgumentException('A group needs a key, a non-negative total and offset, and a positive limit.');
        }
    }

    public function hasMore(): bool
    {
        return $this->offset + $this->limit < $this->total;
    }

    public function nextOffset(): ?int
    {
        return $this->hasMore() ? $this->offset + $this->limit : null;
    }

    public static function fromArray(array $data): self
    {
        return new self((string) $data['key'], (int) $data['total'], (int) ($data['offset'] ?? 0), (int) ($data['limit'] ?? 24));
    }

    public function jsonSerialize(): array
    {
        return ['key' => $this->key, 'total' => $this->total, 'offset' => $this->offset, 'limit' => $this->limit];
    }
}
