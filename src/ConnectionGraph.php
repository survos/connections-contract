<?php

declare(strict_types=1);

namespace Survos\ConnectionsContract;

/** The records connected to one focus record, with the evidence for each connection. Values only: it loads nothing. */
final readonly class ConnectionGraph implements \JsonSerializable
{
    /**
     * @param list<Node>  $nodes
     * @param list<Edge>  $edges
     * @param list<Group> $groups
     * @param bool        $complete false when any group has more connections than were loaded
     */
    public function __construct(
        public Scope $scope,
        public string $focus,
        public array $nodes,
        public array $edges = [],
        public array $groups = [],
        public bool $complete = true,
    ) {
        $ids = [];
        foreach ($nodes as $node) {
            if (isset($ids[$node->id])) {
                throw new \InvalidArgumentException('Duplicate node id: '.$node->id);
            }
            $ids[$node->id] = true;
        }
        if (!isset($ids[$focus])) {
            throw new \InvalidArgumentException('The focus must be one of the nodes.');
        }
        $edgeIds = [];
        foreach ($edges as $edge) {
            if (!isset($ids[$edge->from], $ids[$edge->to])) {
                throw new \InvalidArgumentException('Edge '.$edge->id.' joins a node that is not in the graph.');
            }
            if (isset($edgeIds[$edge->id])) {
                throw new \InvalidArgumentException('Duplicate edge id: '.$edge->id);
            }
            $edgeIds[$edge->id] = true;
        }
    }

    public function node(string $id): Node
    {
        foreach ($this->nodes as $node) {
            if ($node->id === $id) {
                return $node;
            }
        }

        throw new \OutOfBoundsException('No node '.$id);
    }

    /** @return list<Edge> */
    public function edgesOf(string $id): array
    {
        return array_values(array_filter($this->edges, static fn (Edge $e): bool => $e->from === $id || $e->to === $id));
    }

    public static function fromArray(array $data): self
    {
        return new self(Scope::fromArray($data['scope']), (string) $data['focus'], array_map(Node::fromArray(...), $data['nodes']),
            array_map(Edge::fromArray(...), $data['edges'] ?? []), array_map(Group::fromArray(...), $data['groups'] ?? []), (bool) ($data['complete'] ?? true));
    }

    public function jsonSerialize(): array
    {
        return ['scope' => $this->scope, 'focus' => $this->focus, 'nodes' => $this->nodes, 'edges' => $this->edges, 'groups' => $this->groups, 'complete' => $this->complete];
    }
}
