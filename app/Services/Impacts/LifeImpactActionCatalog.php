<?php

namespace App\Services\Impacts;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class LifeImpactActionCatalog
{
    public function all(): array
    {
        return (array) config('life_impact.actions', []);
    }

    public function keys(): array
    {
        return array_keys($this->all());
    }

    public function get(string $key): ?array
    {
        $action = Arr::get($this->all(), $key);

        if (! is_array($action)) {
            return null;
        }

        return [
            'key' => $key,
            'label' => (string) ($action['label'] ?? ''),
            'category' => (string) ($action['category'] ?? ''),
            'life_impacted' => max(1, (int) ($action['life_impacted'] ?? 1)),
        ];
    }

    public function toList(): Collection
    {
        return collect($this->all())
            ->map(fn (array $action, string $key) => [
                'key' => $key,
                'label' => (string) ($action['label'] ?? ''),
                'category' => (string) ($action['category'] ?? ''),
                'life_impacted' => max(1, (int) ($action['life_impacted'] ?? 1)),
            ])
            ->values();
    }
}
