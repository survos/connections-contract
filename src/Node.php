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
     * @param string       $id          the entity id, kept as a string so leading zeros survive
     * @param string       $schema      the FtM schema name: Person, Document, Organization, ...
     * @param string       $caption     the name as the source prints it
     * @param string|null  $birthDate   as recorded, not normalized
     * @param string|null  $deathDate   as recorded, not normalized
     * @param int          $sourceCount distinct sources behind this node
     * @param string|null  $scope       the dataset key when the node is outside the graph's own scope
     * @param string|null  $kind        the name a reader knows the node by when the schema is too general: "Stone record" for a Document
     * @param list<string> $roles       what the node is in this view, read against the focus: "Obituary subject", "Spouse", "Pallbearer"
     */
    public function __construct(
        public string $id,
        public string $schema,
        public string $caption,
        public ?string $birthDate = null,
        public ?string $deathDate = null,
        public int $sourceCount = 1,
        public ?string $scope = null,
        public ?string $kind = null,
        public array $roles = [],
    ) {
        if ('' === $id || '' === $schema) {
            throw new \InvalidArgumentException('A node needs an id and a schema.');
        }
        if ($sourceCount < 1) {
            throw new \InvalidArgumentException('A node comes from at least one source.');
        }
        foreach ($roles as $role) {
            if (!\is_string($role) || '' === $role) {
                throw new \InvalidArgumentException('Node roles are non-empty strings.');
            }
        }
    }

    /**
     * The years of a life from the dates as recorded: "1941–1999", "b. 1940", "d. 1999". Several years from several
     * sources stay visible: "1940 / 1941–1999". A date without a four-digit year contributes nothing.
     */
    public function lifeYears(): string
    {
        $years = static fn (?string $dates): string => implode(' / ', array_unique(preg_match_all('/\b\d{4}\b/', (string) $dates, $m) ? $m[0] : []));
        $born = $years($this->birthDate);
        $died = $years($this->deathDate);

        return match (true) {
            '' !== $born && '' !== $died => $born.'–'.$died,
            '' !== $born => 'b. '.$born,
            default => '' !== $died ? 'd. '.$died : '',
        };
    }

    public static function fromArray(array $data): self
    {
        return new self((string) $data['id'], (string) $data['schema'], (string) ($data['caption'] ?? ''), $data['birthDate'] ?? null, $data['deathDate'] ?? null, (int) ($data['sourceCount'] ?? 1), $data['scope'] ?? null, $data['kind'] ?? null, $data['roles'] ?? []);
    }

    public function jsonSerialize(): array
    {
        return array_filter(['id' => $this->id, 'schema' => $this->schema, 'caption' => $this->caption, 'kind' => $this->kind, 'birthDate' => $this->birthDate,
            'deathDate' => $this->deathDate, 'roles' => $this->roles ?: null, 'sourceCount' => $this->sourceCount, 'scope' => $this->scope], static fn ($v) => null !== $v);
    }
}
