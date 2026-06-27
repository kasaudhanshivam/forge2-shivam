<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'subject' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(Ticket::STATUSES),
            'priority' => fake()->randomElement(Ticket::PRIORITIES),
            'tags' => null,
        ];
    }
}
