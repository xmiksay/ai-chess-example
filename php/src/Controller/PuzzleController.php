<?php

namespace App\Controller;

use App\Model\PuzzleFilter;
use App\Repository\OpeningRepository;
use App\Repository\PuzzleRepository;
use App\Repository\ThemeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PuzzleController extends AbstractController
{
    #[Route('/', name: 'puzzle_index', methods: ['GET'])]
    public function index(
        Request $request,
        PuzzleRepository $puzzles,
        ThemeRepository $themes,
        OpeningRepository $openings,
    ): Response {
        $filter = PuzzleFilter::fromRequest($request);
        $page   = $puzzles->search($filter, 20);

        return $this->render('puzzle/index.html.twig', [
            'filter'   => $filter,
            'page'     => $page,
            'themes'   => $themes->findAllOrdered(),
            'openings' => $openings->findAllOrdered(),
        ]);
    }

    #[Route('/puzzle/{id}', name: 'puzzle_show', methods: ['GET'], requirements: ['id' => '[A-Za-z0-9]+'])]
    public function show(string $id, PuzzleRepository $puzzles): Response
    {
        $puzzle = $puzzles->find($id);
        if (!$puzzle) {
            throw $this->createNotFoundException('Puzzle not found');
        }
        return $this->render('puzzle/show.html.twig', ['puzzle' => $puzzle]);
    }
}
