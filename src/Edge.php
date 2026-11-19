<?php

declare(strict_types=1);

namespace Survos\ConnectionsContract;

/** A connection between two nodes, backed by the source text it was read from. */
final readonly class Edge implements \JsonSerializable
{
    /**
     * @param string         $id           id of the FtM interval entity (or the link row) this edge was made from
     * @param string         $schema       Family, Associate, Membership, ...
     * @param string         $label        the relationship as recorded, read from $from to $to: "child of"
     * @param string|null    $reverseLabel the same relationship read from $to to $from: "parent of"
     * @param list<Evidence> $evidence     one entry per quotation; a relation stated twice keeps both
     * @param string         $status       'extracted' until a review decision says otherwise; the review bundle owns the vocabulary
     * @param string|null    $sourceUrl    address of the source article or record
     */
    public function __construct(
        public string $id,
        public string $schema,
        public string $from,
        public string $to,
        public string $label,
        public ?string $reverseLabel = null,
        public bool $symmetric = false,
        public EdgeKind $kind = EdgeKind::Relationship,
        public array $evidence = [],
        public string $status = 'extracted',
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?string $sourceUrl = null,
    ) {
        if ('' === $id || '' === $schema || '' === $label) {
            throw new \InvalidArgumentException('An edge needs an id, a schema and a label.');
        }
        if ('' === $from || '' === $to || $from === $to) {
            throw new \InvalidArgumentException('An edge joins two different nodes.');
        }
        foreach ($evidence as $item) {
            if (!$item instanceof Evidence) {
                throw new \InvalidArgumentException('Edge evidence must be Evidence values.');
            }
        }
    }

    public static function fromArray(array $data): self
    {
        return new self((string) $data['id'], (string) $data['schema'], (string) $data['from'], (string) $data['to'], (string) $data['label'],
            $data['reverseLabel'] ?? null, (bool) ($data['symmetric'] ?? false), EdgeKind::from($data['kind'] ?? 'relationship'),
            array_map(Evidence::fromArray(...), $data['evidence'] ?? []), (string) ($data['status'] ?? 'extracted'),
            $data['startDate'] ?? null, $data['endDate'] ?? null, $data['sourceUrl'] ?? null);
    }

    public function jsonSerialize(): array
    {
        return array_filter(['id' => $this->id, 'schema' => $this->schema, 'from' => $this->from, 'to' => $this->to, 'label' => $this->label,
            'reverseLabel' => $this->reverseLabel, 'symmetric' => $this->symmetric ?: null, 'kind' => $this->kind->value,
            'evidence' => [] === $this->evidence ? null : $this->evidence, 'status' => $this->status, 'startDate' => $this->startDate,
            'endDate' => $this->endDate, 'sourceUrl' => $this->sourceUrl], static fn ($v) => null !== $v);
    }
}
