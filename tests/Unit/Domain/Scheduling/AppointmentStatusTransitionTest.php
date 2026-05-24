<?php

namespace Tests\Unit\Domain\Scheduling;

use App\Domain\Scheduling\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduled_can_transition_to_confirmed(): void
    {
        $appointment = new Appointment(['status' => Appointment::STATUS_SCHEDULED]);
        $this->assertTrue($appointment->canTransitionTo(Appointment::STATUS_CONFIRMED));
    }

    public function test_scheduled_can_transition_to_cancelled(): void
    {
        $appointment = new Appointment(['status' => Appointment::STATUS_SCHEDULED]);
        $this->assertTrue($appointment->canTransitionTo(Appointment::STATUS_CANCELLED));
    }

    public function test_scheduled_can_transition_to_no_show(): void
    {
        $appointment = new Appointment(['status' => Appointment::STATUS_SCHEDULED]);
        $this->assertTrue($appointment->canTransitionTo(Appointment::STATUS_NO_SHOW));
    }

    public function test_confirmed_can_transition_to_in_progress(): void
    {
        $appointment = new Appointment(['status' => Appointment::STATUS_CONFIRMED]);
        $this->assertTrue($appointment->canTransitionTo(Appointment::STATUS_IN_PROGRESS));
    }

    public function test_confirmed_can_transition_to_cancelled(): void
    {
        $appointment = new Appointment(['status' => Appointment::STATUS_CONFIRMED]);
        $this->assertTrue($appointment->canTransitionTo(Appointment::STATUS_CANCELLED));
    }

    public function test_in_progress_can_transition_to_completed(): void
    {
        $appointment = new Appointment(['status' => Appointment::STATUS_IN_PROGRESS]);
        $this->assertTrue($appointment->canTransitionTo(Appointment::STATUS_COMPLETED));
    }

    public function test_completed_cannot_transition_to_anything(): void
    {
        $appointment = new Appointment(['status' => Appointment::STATUS_COMPLETED]);
        $this->assertFalse($appointment->canTransitionTo(Appointment::STATUS_CONFIRMED));
        $this->assertFalse($appointment->canTransitionTo(Appointment::STATUS_CANCELLED));
        $this->assertFalse($appointment->canTransitionTo(Appointment::STATUS_SCHEDULED));
    }

    public function test_cancelled_cannot_transition_to_anything(): void
    {
        $appointment = new Appointment(['status' => Appointment::STATUS_CANCELLED]);
        $this->assertFalse($appointment->canTransitionTo(Appointment::STATUS_CONFIRMED));
        $this->assertFalse($appointment->canTransitionTo(Appointment::STATUS_SCHEDULED));
    }

    public function test_invalid_transition_returns_false(): void
    {
        $appointment = new Appointment(['status' => Appointment::STATUS_SCHEDULED]);
        $this->assertFalse($appointment->canTransitionTo(Appointment::STATUS_COMPLETED));
        $this->assertFalse($appointment->canTransitionTo(Appointment::STATUS_IN_PROGRESS));
    }
}