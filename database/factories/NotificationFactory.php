<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'notifiable_id' => User::factory(),
            'notifiable_type' => User::class,
            'type' => 'App\\Notifications\\GenericNotification',
            'data' => ['message' => $this->faker->sentence()],
            'read_at' => null,
        ];
    }
}
