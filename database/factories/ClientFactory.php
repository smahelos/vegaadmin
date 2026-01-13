<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        $rand = random_int(1000,9999);
        return [
            'user_id' => User::factory(),
            'name' => 'Client '.$rand,
            'email' => 'client'.$rand.'@example.test',
            'street' => 'Client Street '.$rand,
            'city' => 'ClientCity'.$rand,
            'zip' => str_pad((string)random_int(10000,99999),5,'0',STR_PAD_LEFT),
            'country' => 'CZ',
            'ico' => (string)random_int(10000000,99999999),
            'dic' => 'CZ'.random_int(10000000,99999999),
            'phone' => '+420'.random_int(100000000,999999999),
            'description' => 'Test client '.$rand,
            'is_default' => false,
        ];
    }
}
