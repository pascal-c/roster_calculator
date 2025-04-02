<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\RosterRepository;
use App\Serializer\ResultSerializer;
use App\Service\Calculator;
use App\Service\ResultService;
use App\Service\RosterBuilder;
use App\Service\TimeService;
use App\Value\Status;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RosterController extends AbstractController
{
    public function __construct(
        private RosterRepository $rosterRepository,
        private TimeService $timeService,
        private RosterBuilder $rosterBuilder,
        private Calculator $calculator,
        private ResultSerializer $resultSerializer,
        private ResultService $resultService,
    ) {
    }

    #[Route('/v1/roster', name: 'create_roster', methods: ['POST'])]
    public function create(Request $request): Response
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

        $roster->setStartedAt($this->timeService->now());
        $result = $this->calculator->calculate($roster);
        $roster->setResult($result);
        $roster->setCompletedAt($this->timeService->now());
        $roster->setStatus(Status::COMPLETED->value);

        $result = [
            'id' => $roster->getSlug(),
            'status' => $roster->getStatus(),
            'created_at' => $roster->getCreatedAt()->format('c'),
            'assignments' => $this->resultSerializer->serializeAssignments($result),
            'personalResults' => $this->resultService->getAllCalculatedShifts($result),
            'rating' => $this->resultService->getRating($result),
            'counter' => $this->resultService->getCounter($result),
            'isTimedOut' => $this->resultService->isTimedOut($result),
            'firstResultTotalPoints' => $this->resultService->getFirstResultTotalPoints($result),
        ];

        return new JsonResponse($result, Response::HTTP_CREATED, ['location' => $this->generateUrl('show_roster', ['id' => $roster->getSlug()])]);
    }

    #[Route('/v1/roster/{id}', name: 'show_roster', methods: ['GET'])]
    public function show(string $id): Response
    {
        $roster = $this->rosterRepository->findOneBySlug($id);
        if (null === $roster) {
            return new JsonResponse(['error' => 'roster not found'], Response::HTTP_NOT_FOUND);
        }

        $result = [
            'id' => $roster->getSlug(),
            'status' => $roster->getStatus(),
            'created_at' => $roster->getCreatedAt()->format('c'),
            'result' => $roster->getResult(),
        ];

        return new JsonResponse($result);
    }
}
