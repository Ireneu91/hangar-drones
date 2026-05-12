<?php

declare(strict_types=1);

namespace Tests;

use App\Drone;
use App\Hangar;
use PHPUnit\Framework\TestCase;

final class DroneTest extends TestCase
{
    public function testFlightMinutesAccumulateAcrossMultipleFlights(): void
    {
        $hangar = new Hangar(1);
        $drone = new Drone('D1');

        $hangar->addDrone($drone);

        $firstFlight = $hangar->launchDrone();
        $hangar->landDrone($firstFlight, 240);
        $hangar->releaseFromMaintenance('D1');

        $secondFlight = $hangar->launchDrone();
        $hangar->landDrone($secondFlight, 150);

        $this->assertSame(390, $secondFlight->flightMinutes());
        $this->assertSame($firstFlight, $secondFlight);
    }
}
