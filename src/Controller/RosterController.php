<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Roster;
use App\Repository\RosterRepository;
use App\Service\TimeService;
use App\Value\Status;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RosterController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RosterRepository $rosterRepository,
        private TimeService $timeService,
    ) {
    }

    #[Route('/v1/roster', name: 'create_roster', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $payload = $request->getPayload()->all();
        if (!array_key_exists('dates', $payload)) {
            return new JsonResponse(['error' => 'dates are missing'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if (!array_key_exists('people', $payload)) {
            return new JsonResponse(['error' => 'people are missing'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $roster = new Roster();
        $roster->setSlug(Uuid::uuid4()->toString());
        $roster->setStatus(Status::NOT_STARTED->value);
        $roster->setPreconditions($payload);
        $roster->setCreatedAt($this->timeService->now());

        $this->entityManager->persist($roster);
        $this->entityManager->flush();

        $result = [
            'id' => $roster->getSlug(),
            'status' => $roster->getStatus(),
            'created_at' => $roster->getCreatedAt()->format('c'),
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
        ];

        return new JsonResponse($result);
    }
}
