<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProxyNodeStatus;
use App\Models\ProxyNode;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProxyNode> */
final class ProxyNodeFactory extends Factory
{
    protected $model = ProxyNode::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nodeNumber = fake()->unique()->numberBetween(1, 9999);

        return [
            'node_id' => sprintf('proxy-%04d', $nodeNumber),
            'name' => sprintf('Proxy #%d', $nodeNumber),
            'url' => sprintf('https://proxy-%04d.example.test', $nodeNumber),
            'secret_encrypted' => fake()->regexify('[A-Za-z0-9]{48}'),
            'priority' => 100,
            'enabled' => true,
            'status' => ProxyNodeStatus::Online,
        ];
    }
}
