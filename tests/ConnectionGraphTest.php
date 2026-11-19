<?php

declare(strict_types=1);

namespace Survos\ConnectionsContract\Tests;

use PHPUnit\Framework\TestCase;
use Survos\ConnectionsContract\ConnectionGraph;
use Survos\ConnectionsContract\Edge;
use Survos\ConnectionsContract\EdgeKind;
use Survos\ConnectionsContract\Evidence;
use Survos\ConnectionsContract\Group;
use Survos\ConnectionsContract\Node;
use Survos\ConnectionsContract\Scope;

final class ConnectionGraphTest extends TestCase
{
    private const ARTICLE = '37573aa3-8292-531e-844f-a7f7f8f92db1';

    private function smoot(): ConnectionGraph
    {
        return new ConnectionGraph(
            new Scope('news/rappnews-obits'),
            self::ARTICLE.':wayne-mitchell-smoot',
            [new Node(self::ARTICLE.':wayne-mitchell-smoot', 'Person', 'Wayne Mitchell Smoot'), new Node(self::ARTICLE.':billy-smoot', 'Person', 'Billy Smoot')],
            [new Edge('r:1', 'Family', self::ARTICLE.':wayne-mitchell-smoot', self::ARTICLE.':billy-smoot', 'child of', 'parent of',
                evidence: [new Evidence('urn:ftm:'.self::ARTICLE, 'son of the late Billy and Helen Smoot.', 381, 419, 'ai-obituary-read')])],
            [new Group('Family', 1, 0, 24)],
        );
    }

    public function testJsonRoundTripKeepsEverything(): void
    {
        $graph = $this->smoot();
        $copy = ConnectionGraph::fromArray(json_decode(json_encode($graph, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR));

        self::assertEquals($graph, $copy);
        self::assertSame(381, $copy->edges[0]->evidence[0]->start);
        self::assertSame('parent of', $copy->edges[0]->reverseLabel);
    }

    public function testNodesWithTheSameCaptionStayTwoNodes(): void
    {
        $graph = new ConnectionGraph(new Scope('d'), 'a:joyce', [new Node('a:joyce', 'Person', 'Joyce Bolyard'), new Node('b:joyce', 'Person', 'Joyce Bolyard')]);

        self::assertCount(2, $graph->nodes);
        self::assertSame([], $graph->edgesOf('b:joyce'));
    }

    public function testTheFocusAndEveryEdgeEndpointMustBeNodes(): void
    {
        $node = new Node('a', 'Person', 'A');
        foreach ([
            static fn () => new ConnectionGraph(new Scope('d'), 'missing', [$node]),
            static fn () => new ConnectionGraph(new Scope('d'), 'a', [$node, $node]),
            static fn () => new ConnectionGraph(new Scope('d'), 'a', [$node], [new Edge('e', 'Family', 'a', 'b', 'child of')]),
            static fn () => new ConnectionGraph(new Scope('d'), 'a', [$node, new Node('b', 'Person', 'B')], [new Edge('e', 'Family', 'a', 'b', 'child of'), new Edge('e', 'Family', 'b', 'a', 'parent of')]),
        ] as $build) {
            try {
                $build();
                self::fail('Expected the graph to be rejected.');
            } catch (\InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testAnEdgeJoinsTwoDifferentNodesAndEvidenceNeedsARealSpan(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Edge('e', 'Family', 'a', 'a', 'spouse of');
    }

    public function testEvidenceRejectsAnEmptyOrNegativeSpan(): void
    {
        foreach ([[5, 5], [-1, 3], [7, 2]] as [$start, $end]) {
            try {
                new Evidence('urn:x', 'quote', $start, $end);
                self::fail('Expected the span to be rejected.');
            } catch (\InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testARelationStatedTwiceKeepsBothQuotes(): void
    {
        $edge = new Edge('e', 'Family', 'a', 'b', 'child of', evidence: [new Evidence('urn:x', 'son of Donna', 10, 22), new Evidence('urn:x', 'her son Stephen', 90, 105)]);

        self::assertCount(2, ConnectionGraph::fromArray(json_decode(json_encode(new ConnectionGraph(new Scope('d'), 'a', [new Node('a', 'Person', 'A'), new Node('b', 'Person', 'B')], [$edge])), true))->edges[0]->evidence);
    }

    public function testGroupPagingIsPerGroup(): void
    {
        $group = new Group('Family', 60, 24, 24);

        self::assertTrue($group->hasMore());
        self::assertSame(48, $group->nextOffset());
        self::assertNull((new Group('Associate', 3))->nextOffset());
    }

    public function testEvidenceReadsTheTextEvidenceDocument(): void
    {
        $evidence = Evidence::fromTextEvidence([
            'annotation' => ['id' => 'urn:evidence:r1', 'target' => ['source' => 'urn:ftm:doc.transcript', 'selector' => [
                ['type' => 'TextPositionSelector', 'start' => 381, 'end' => 419], ['type' => 'TextQuoteSelector', 'exact' => 'son of the late Billy and Helen Smoot.']]]],
            'provenance' => ['sourceSha256' => str_repeat('a', 64), 'extractor' => 'ai-obituary-read', 'confidence' => null],
        ]);

        self::assertSame('urn:ftm:doc.transcript', $evidence->source);
        self::assertSame([381, 419], [$evidence->start, $evidence->end]);
        self::assertSame('urn:evidence:r1', $evidence->id);
        self::assertNull($evidence->confidence);
    }

    public function testTheTextEvidenceClassItselfIsAccepted(): void
    {
        if (!class_exists(\Survos\FollowTheMoney\TextEvidence::class)) {
            self::markTestSkipped('survos/followthemoney is not installed.');
        }
        $text = 'He was the son of the late Billy and Helen Smoot.';
        $real = new \Survos\FollowTheMoney\TextEvidence('urn:evidence:1', 'p1', 'urn:ftm:doc', $text, 11, 48, 'ai-obituary-read');
        $evidence = Evidence::fromTextEvidence($real->jsonSerialize());

        self::assertSame(mb_substr($text, 11, 37), $evidence->exact);
        self::assertSame(hash('sha256', $text), $evidence->sourceSha256);
    }

    public function testSameAsIsAnEdgeKindNotAnInference(): void
    {
        $edge = new Edge('d1', 'Decision', 'a', 'b', 'same person as', symmetric: true, kind: EdgeKind::SameAs, status: 'reviewed');

        self::assertSame('same-as', json_decode(json_encode($edge), true)['kind']);
    }
}
