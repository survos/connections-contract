<?php

declare(strict_types=1);

namespace Survos\ConnectionsContract;

enum EdgeKind: string
{
    /** A relationship asserted by a source: "child of", "spouse of", "named on". */
    case Relationship = 'relationship';
    /** A reviewed decision that two source-scoped nodes are the same person. Never inferred from names. */
    case SameAs = 'same-as';
}
