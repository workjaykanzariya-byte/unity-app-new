<?php

namespace App\Services\Zoho;

use App\Enums\CircleBillingTerm;
use App\Models\Circle;

class CircleAddonPayloadBuilder
{
    public function build(Circle $circle, CircleBillingTerm $term, string $addonCode, float $amount): array
    {
        return [
            'name' => trim(($circle->name ?: 'Circle') . ' - ' . $term->label()),
            'description' => trim(($circle->description ?: $circle->purpose ?: 'Circle paid subscription') . ' (' . $term->label() . ')'),
            'addon_code' => $addonCode,
            'product_id' => (string) env('ZOHO_CIRCLE_ADDON_PRODUCT_ID', ''),
            'price' => round($amount, 2),
            'currency_code' => 'INR',
            'type' => 'recurring',
        ];
    }

    public function fallbackBuild(Circle $circle, CircleBillingTerm $term, string $addonCode, float $amount): array
    {
        return [
            'name' => trim(($circle->name ?: 'Circle') . ' - ' . $term->label()),
            'description' => trim(($circle->description ?: $circle->purpose ?: 'Circle paid subscription') . ' (' . $term->label() . ')'),
            'addon_code' => $addonCode,
            'product_id' => (string) env('ZOHO_CIRCLE_ADDON_PRODUCT_ID', ''),
            'recurring_price' => round($amount, 2),
            'currency_code' => 'INR',
            'type' => 'recurring',
        ];
    }

    public function syncHash(Circle $circle, CircleBillingTerm $term, float $amount, string $name, string $description, bool $active): string
    {
        return hash('sha256', implode('|', [
            (string) env('ZOHO_CIRCLE_ADDON_PRODUCT_ID', ''),
            (string) $circle->id,
            $term->value,
            (string) round($amount, 2),
            $name,
            $description,
            $active ? '1' : '0',
        ]));
    }
}
