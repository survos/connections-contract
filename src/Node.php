<?php

declare(strict_types=1);

namespace Survos\ConnectionsContract;

/**
 * One source-scoped entity. Two nodes with the same caption are two nodes: identity is only ever
 * joined by an edge of kind SameAs.
 */
final readonly class Node implements \JsonSerializable
{
    /**
     * @param string      $id          the entity id, kept as a string so leading zeros survive
     * @param string      $schema      the FtM schema name: Person, Document, Organization, ...
     * @param string      $caption     the name as the source prints it
     * @param string|null $birthDate   as recorded, not normalized
     * @param string|null $deathDate   as recorded, not normalized
     * @param int         $sourceCount distinct sources behind this node
     * @param string|null $scope       the dataset key when the node is outside the graph's own scope
     */
    public function __construct(
        public string $id,
        public string $schema,
        public string $caption,
        public ?string $birthDate = null,
        public ?string $deathDate = null,
        public int $sourceCount = 1,
        public ?string $scope = null,
    ) {
        if ('' === $id || '' === $schema) {
            throw new \InvalidArgumentException('A node needs an id and a schema.');
        }
        if ($sourceCount < 1) {
            throw new \InvalidArgumentException('A node comes from at least one source.');
        }
    }

    public static function fromArray(array $data): self
    {
        return new self((string) $data['id'], (string) $data['schema'], (string) ($data['caption'] ?? ''), $data['birthDate'] ?? null, $data['deathDate'] ?? null, (int) ($data['sourceCount'] ?? 1), $data['scope'] ?? null);
    }

    public function jsonSerialize(): array
    {
        return array_filter(['id' => $this->id, 'schema' => $this->schema, 'caption' => $this->caption, 'birthDate' => $this->birthDate,
            'deathDate' => $this->deathDate, 'sourceCount' => $this->sourceCount, 'scope' => $this->scope], static fn ($v) => null !== $v);
    }
}
