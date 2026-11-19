# survos/connections-contract

Immutable, JSON-serializable values for a connections view: one focus record, the records connected to it, and the
source text behind each connection. It loads nothing, builds no addresses and draws nothing. Producers build a
`ConnectionGraph`; `survos/connections-bundle` draws it.

The values follow the FtM conventions in `survos/followthemoney` (see its `docs/ecosystem.md`):

- A `Node` is one source-scoped entity (`Person`, `Document`, ...). Two nodes with the same caption stay two nodes.
  `kind` is the name a reader knows it by when the schema is too general (a `Document` that is a "Stone record"), `roles`
  say what it is in this view ("Obituary subject", "Spouse", "Pallbearer"), and `lifeYears()` reads the years from the
  recorded dates (`1941–1999`, `b. 1940`, `1940 / 1941–1999`).
- An `Edge` is made from an FtM interval entity (`Family`, `Associate`, `Membership`, ...). It keeps that entity's id
  and schema, the relationship as recorded (`child of`), and optionally the reverse label (`parent of`).
- `Evidence` has the fields of `TextEvidence` (source, exact quote, code-point start and end, sha256, extractor,
  confidence). `Evidence::fromTextEvidence()` accepts the array `TextEvidence::jsonSerialize()` returns.
- An edge of kind `SameAs` is a reviewed decision that two nodes are one person. Nothing in this package infers it.
- A `Group` carries the total and paging of one relation group, as in an FtM adjacency response. A group total is
  never a total for the whole graph. `ConnectionGraph::$complete` is false when any group has more than was loaded.

```php
use Survos\ConnectionsContract\{ConnectionGraph, Edge, Evidence, Group, Node, Scope};

$graph = new ConnectionGraph(
    new Scope('news/rappnews-obits'),
    'a1:wayne-smoot',
    [new Node('a1:wayne-smoot', 'Person', 'Wayne Mitchell Smoot'), new Node('a1:billy-smoot', 'Person', 'Billy Smoot')],
    [new Edge('r:1', 'Family', 'a1:wayne-smoot', 'a1:billy-smoot', 'child of', 'parent of',
        evidence: [new Evidence('urn:ftm:a1', 'son of the late Billy and Helen Smoot.', 381, 419, 'ai-obituary-read')])],
    [new Group('Family', 1)],
);
$copy = ConnectionGraph::fromArray(json_decode(json_encode($graph), true));
```

The constructor rejects a focus that is not a node, duplicate node or edge ids, an edge to a node that is not in the
graph, an edge from a node to itself, and an evidence span that is empty or negative.

## Producers

Each producer lives outside this package and returns a `ConnectionGraph`:

1. FtM adjacency (`survos/ftm-resolver`: `AdjacentGraph`).
2. Folio or dfolio `per` and `link` rows, with the link type labels and the link extras `quote`, `spanStart`, `spanEnd`.
3. FtM export files (entities and evidence JSONL).

## Tests

```sh
composer test
```
