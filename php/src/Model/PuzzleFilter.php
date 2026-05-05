<?php

namespace App\Model;

use Symfony\Component\HttpFoundation\Request;

class PuzzleFilter
{
    public ?int $minRating = null;
    public ?int $maxRating = null;
    public string $q = '';
    /** @var list<int> */
    public array $themeIds = [];
    /** @var list<int> */
    public array $openingIds = [];
    public ?string $cursor = null;

    public static function fromRequest(Request $r): self
    {
        $f = new self();
        $min = $r->query->get('min_rating');
        $max = $r->query->get('max_rating');
        $f->minRating = ($min !== null && $min !== '') ? (int) $min : null;
        $f->maxRating = ($max !== null && $max !== '') ? (int) $max : null;
        $f->q = trim((string) $r->query->get('q', ''));
        $f->themeIds = array_values(array_map('intval', (array) $r->query->all('themes')));
        $f->openingIds = array_values(array_map('intval', (array) $r->query->all('openings')));
        $cursor = $r->query->get('after');
        $f->cursor = ($cursor !== null && $cursor !== '') ? (string) $cursor : null;
        return $f;
    }

    /** @return array<string,mixed> */
    public function toQuery(): array
    {
        $q = [];
        if ($this->minRating !== null) $q['min_rating'] = $this->minRating;
        if ($this->maxRating !== null) $q['max_rating'] = $this->maxRating;
        if ($this->q !== '')           $q['q']          = $this->q;
        if ($this->themeIds)           $q['themes']     = $this->themeIds;
        if ($this->openingIds)         $q['openings']   = $this->openingIds;
        return $q;
    }
}
