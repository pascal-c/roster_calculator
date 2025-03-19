<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\RosterRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RosterRepository::class)]
class Roster
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\Column]
    private array $preconditions = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    private array $locations = [];
    private array $shifts = [];
    private array $people = [];

    private int $shiftCount = 0;
    private array $weekIds = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getPreconditions(): array
    {
        return $this->preconditions;
    }

    public function setPreconditions(array $preconditions): static
    {
        $this->preconditions = $preconditions;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function addPerson(Person $person): static
    {
        $this->people[$person->id] = $person;

        return $this;
    }

    public function getPerson(string $id): Person
    {
        return $this->people[$id];
    }

    public function getPeople(): array
    {
        return $this->people;
    }

    public function addShift(Shift $shift): static
    {
        $this->shifts[] = $shift;
        ++$this->shiftCount;
        if (!in_array($shift->timeSlotPeriod->weekId, $this->weekIds)) {
            $this->weekIds[] = $shift->timeSlotPeriod->weekId;
        }

        return $this;
    }

    public function getShifts(): array
    {
        return $this->shifts;
    }

    public function countShifts(): int
    {
        return $this->shiftCount;
    }

    public function getWeekIds(): array
    {
        return $this->weekIds;
    }

    public function addLocation(Location $location): static
    {
        $this->locations[$location->id] = $location;

        return $this;
    }

    public function getLocation(?string $id): ?Location
    {
        if (is_null($id)) {
            return null;
        }

        return $this->locations[$id];
    }
}
