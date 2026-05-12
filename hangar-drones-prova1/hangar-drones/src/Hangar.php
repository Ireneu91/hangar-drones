<?php

declare(strict_types=1);

namespace App;

final class Hangar
{
    private int $capacity;

    /** @var array<string,Drone> */
    private array $docked = [];

    /** @var array<string,Drone> */
    private array $maintenance = [];

    /** @var array<string,true> */
    private array $inFlightIds = [];

    public function __construct(int $capacity)
    {
        if ($capacity < 1) {
            throw new \InvalidArgumentException('capacity must be >= 1');
        }

        $this->capacity = $capacity;
    }

    public function capacity(): int
    {
        return $this->capacity;
    }

    public function insideCount(): int
    {
        return count($this->docked) + count($this->maintenance);
    }

    public function dockedCount(): int
    {
        return count($this->docked);
    }

    public function maintenanceCount(): int
    {
        return count($this->maintenance);
    }

    public function inFlightCount(): int
    {
        return count($this->inFlightIds);
    }

    public function hasFreeSlot(): bool
    {
        return $this->insideCount() < $this->capacity;
    }

    /**
     * Adds a drone to the hangar slots.
     *
     * Allowed statuses:
     * - docked -> goes to docked pool
     * - maintenance -> goes to maintenance pool
     */
    public function addDrone(Drone $drone): void
    {
        $id = $drone->id();
        if (isset($this->docked[$id]) || isset($this->maintenance[$id]) || isset($this->inFlightIds[$id])) {
            throw new \RuntimeException("Drone $id is already known by this hangar");
        }
        if (!$this->hasFreeSlot()) {
            throw new \RuntimeException('No free slots available');
        }

        if ($drone->isDocked()) {
            $this->docked[$id] = $drone;
            return;
        }

        if ($drone->isInMaintenance()) {
            $this->maintenance[$id] = $drone;
            return;
        }

        throw new \RuntimeException("Cannot add drone $id with status {$drone->status()}");
    }

    /**
     * Launches the first docked drone (stable order by insertion).
     */
    public function launchDrone(): Drone
    {
        foreach ($this->docked as $id => $drone) {
            unset($this->docked[$id]);
            $drone->takeOff();
            $this->inFlightIds[$id] = true;
            return $drone;
        }

        throw new \RuntimeException('No drones docked');
    }

    /**
     * A drone lands from a flight.
     *
     * - Adds flight minutes to the drone.
     * - The drone ALWAYS enters maintenance (post-flight inspection).
     */
    public function landDrone(Drone $drone, int $flightMinutes): void
    {
        if ($flightMinutes < 0) {
            throw new \InvalidArgumentException('flightMinutes must be >= 0');
        }

        $id = $drone->id();
        if (!isset($this->inFlightIds[$id])) {
            throw new \RuntimeException("Drone $id is not in flight from this hangar");
        }
        if (!$this->hasFreeSlot()) {
            throw new \RuntimeException('No free slots available');
        }

        // Flight
        $drone->addFlightMinutes($flightMinutes);

        // Landing
        unset($this->inFlightIds[$id]);

        $drone->markDocked();
        $drone->sendToMaintenance();
        $this->maintenance[$id] = $drone;
    }

    /**
     * Moves a drone from a docking slot to maintenance.
     */
    public function sendToMaintenance(string $droneId): void
    {
        $droneId = trim($droneId);
        if ($droneId === '') {
            throw new \InvalidArgumentException('droneId must be a non-empty string');
        }
        if (!isset($this->docked[$droneId])) {
            throw new \RuntimeException("Drone $droneId is not docked");
        }

        $drone = $this->docked[$droneId];
        unset($this->docked[$droneId]);

        $drone->sendToMaintenance();
        $this->maintenance[$droneId] = $drone;
    }

    /**
     * Moves a drone from maintenance back to a docking slot.
     */
    public function releaseFromMaintenance(string $droneId): void
    {
        $droneId = trim($droneId);
        if ($droneId === '') {
            throw new \InvalidArgumentException('droneId must be a non-empty string');
        }
        if (!isset($this->maintenance[$droneId])) {
            throw new \RuntimeException("Drone $droneId is not in maintenance");
        }

        $drone = $this->maintenance[$droneId];
        unset($this->maintenance[$droneId]);

        $drone->returnFromMaintenance();
        $this->docked[$droneId] = $drone;
    }

    /**
     * @return list<string>
     */
    public function dockedDroneIds(): array
    {
        return array_values(array_keys($this->docked));
    }

    /**
     * @return list<string>
     */
    public function maintenanceDroneIds(): array
    {
        return array_values(array_keys($this->maintenance));
    }

    /**
     * @return list<string>
     */
    public function inFlightDroneIds(): array
    {
        return array_values(array_keys($this->inFlightIds));
    }
}
