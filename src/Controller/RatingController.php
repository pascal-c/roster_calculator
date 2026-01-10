<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Calculator\RosterCalculator\ShiftCalculator\Rater;
use App\Service\ResultService;
use App\Service\RosterBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RatingController extends AbstractController
{
    public function __construct(
        private RosterBuilder $rosterBuilder,
        private ResultService $resultService,
        private Rater $rater,
    ) {
    }

    #[Route('/v1/rating', name: 'rate_roster', methods: ['POST'])]
    public function rating(Request $request): Response
    {
        $payload = $request->getPayload()->all();
        if (!array_key_exists('shifts', $payload)) {
            return new JsonResponse(['error' => 'shifts are missing'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if (!array_key_exists('people', $payload)) {
            return new JsonResponse(['error' => 'people are missing'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        try {
            $roster = $this->rosterBuilder->buildNew($payload);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $result = $this->resultService->buildEmptyResult($roster);
        foreach ($roster->getShifts() as $shift) {
            $result = $this->resultService->add($result, $shift, null);
        }
        $rating = $this->rater->calculatePoints($result, $roster);

        return new JsonResponse($rating, Response::HTTP_CREATED);
    }
}
