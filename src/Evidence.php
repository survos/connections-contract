<?php

declare(strict_types=1);

namespace Survos\ConnectionsContract;

/**
 * The source text behind an edge. The fields are those of survos/followthemoney's TextEvidence, so a producer
 * can pass TextEvidence::jsonSerialize() to fromTextEvidence(). Offsets are Unicode code points.
 */
final readonly class Evidence implements \JsonSerializable
{
    public function __construct(
        public string $source,
        public string $exact,
        public int $start,
        public int $end,
        public ?string $extractor = null,
        public ?string $sourceSha256 = null,
        public ?float $confidence = null,
        public ?string $id = null,
    ) {
        if ('' === $source || '' === $exact) {
            throw new \InvalidArgumentException('Evidence needs a source and the exact quoted text.');
        }
        if ($start < 0 || $end <= $start) {
            throw new \InvalidArgumentException('Evidence span must be a non-empty range from zero.');
        }
        if (null !== $confidence && (!is_finite($confidence) || $confidence < 0 || $confidence > 1)) {
            throw new \InvalidArgumentException('Confidence must be between zero and one.');
        }
    }

    /** @param array<string, mixed> $json the array TextEvidence::jsonSerialize() returns */
    public static function fromTextEvidence(array $json): self
    {
        $target = $json['annotation']['target'] ?? [];
        $start = $end = $exact = null;
        foreach ($target['selector'] ?? [] as $selector) {
            if ('TextPositionSelector' === ($selector['type'] ?? null)) {
                $start = $selector['start'];
                $end = $selector['end'];
            }
            if ('TextQuoteSelector' === ($selector['type'] ?? null)) {
                $exact = $selector['exact'];
            }
        }
        if (null === $start || null === $exact) {
            throw new \InvalidArgumentException('Not a TextEvidence document: a position and a quote selector are required.');
        }

        return new self((string) ($target['source'] ?? ''), $exact, (int) $start, (int) $end, $json['provenance']['extractor'] ?? null,
            $json['provenance']['sourceSha256'] ?? null, $json['provenance']['confidence'] ?? null, $json['annotation']['id'] ?? null);
    }

    public static function fromArray(array $data): self
    {
        return new self((string) $data['source'], (string) $data['exact'], (int) $data['start'], (int) $data['end'], $data['extractor'] ?? null,
            $data['sourceSha256'] ?? null, isset($data['confidence']) ? (float) $data['confidence'] : null, $data['id'] ?? null);
    }

    public function jsonSerialize(): array
    {
        return array_filter(['id' => $this->id, 'source' => $this->source, 'exact' => $this->exact, 'start' => $this->start, 'end' => $this->end,
            'extractor' => $this->extractor, 'sourceSha256' => $this->sourceSha256, 'confidence' => $this->confidence], static fn ($v) => null !== $v);
    }
}
