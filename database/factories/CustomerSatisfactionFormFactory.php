<?php

namespace Database\Factories;

use App\Models\CustomerSatisfactionForm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerSatisfactionFormFactory extends Factory
{
    protected $model = CustomerSatisfactionForm::class;

    public function definition(): array
    {
        return [
            'submitted_at' => now()->toDateString(),
            'customer_name' => fake()->firstName(),
            'customer_family' => fake()->lastName(),
            'shipping_method' => 'hozori',
            'satisfaction_status' => null,
            'assigned_to_user_id' => null,
            'created_by_user_id' => User::factory(),
            'purchase_status' => 'not_purchased',
            'no_purchase_reason' => 'price',
        ];
    }
}
