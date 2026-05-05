<?php

namespace App\Entity;

use App\Repository\PuzzleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PuzzleRepository::class)]
#[ORM\Table(name: 'puzzle')]
#[ORM\Index(name: 'idx_puzzle_rating', columns: ['rating'])]
#[ORM\Index(name: 'idx_puzzle_popularity', columns: ['popularity'])]
class Puzzle
{
    #[ORM\Id]
    #[ORM\Column(length: 16)]
    private string $id;

    #[ORM\Column(length: 100)]
    private string $fen;

    #[ORM\Column(type: 'text')]
    private string $moves;

    #[ORM\Column]
    private int $rating;

    #[ORM\Column(name: 'rating_deviation')]
    private int $ratingDeviation;

    #[ORM\Column]
    private int $popularity;

    #[ORM\Column(name: 'nb_plays')]
    private int $nbPlays;

    #[ORM\Column(name: 'game_url', length: 255, nullable: true)]
    private ?string $gameUrl = null;

    /** @var Collection<int, Theme> */
    #[ORM\ManyToMany(targetEntity: Theme::class)]
    #[ORM\JoinTable(name: 'puzzle_theme')]
    private Collection $themes;

    /** @var Collection<int, Opening> */
    #[ORM\ManyToMany(targetEntity: Opening::class)]
    #[ORM\JoinTable(name: 'puzzle_opening')]
    private Collection $openings;

    public function __construct()
    {
        $this->themes = new ArrayCollection();
        $this->openings = new ArrayCollection();
    }

    public function getId(): string { return $this->id; }
    public function setId(string $id): self { $this->id = $id; return $this; }

    public function getFen(): string { return $this->fen; }
    public function setFen(string $fen): self { $this->fen = $fen; return $this; }

    public function getMoves(): string { return $this->moves; }
    public function setMoves(string $moves): self { $this->moves = $moves; return $this; }

    public function getRating(): int { return $this->rating; }
    public function setRating(int $rating): self { $this->rating = $rating; return $this; }

    public function getRatingDeviation(): int { return $this->ratingDeviation; }
    public function setRatingDeviation(int $rd): self { $this->ratingDeviation = $rd; return $this; }

    public function getPopularity(): int { return $this->popularity; }
    public function setPopularity(int $popularity): self { $this->popularity = $popularity; return $this; }

    public function getNbPlays(): int { return $this->nbPlays; }
    public function setNbPlays(int $nbPlays): self { $this->nbPlays = $nbPlays; return $this; }

    public function getGameUrl(): ?string { return $this->gameUrl; }
    public function setGameUrl(?string $url): self { $this->gameUrl = $url; return $this; }

    /** @return Collection<int, Theme> */
    public function getThemes(): Collection { return $this->themes; }

    /** @return Collection<int, Opening> */
    public function getOpenings(): Collection { return $this->openings; }

    /**
     * Side to move from FEN. In Lichess puzzles this is the OPPONENT;
     * the user plays the opposite color (the first move in `moves` is
     * the opponent's setup move).
     */
    public function getSideToMove(): string
    {
        $parts = explode(' ', $this->fen);
        return $parts[1] ?? 'w';
    }

    public function getUserSide(): string
    {
        return $this->getSideToMove() === 'w' ? 'b' : 'w';
    }
}
