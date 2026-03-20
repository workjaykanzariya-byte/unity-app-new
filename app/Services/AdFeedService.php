<?php

namespace App\Services;

use App\Http\Resources\V1\AdResource;
use App\Models\Ad;
use Illuminate\Support\Collection;

class AdFeedService
{
    public function timelineAds(?int $limit = null): Collection
    {
        $query = Ad::query()
            ->currentlyVisible()
            ->forPlacement('timeline')
            ->orderByRaw('CASE WHEN timeline_position IS NULL THEN 1 ELSE 0 END')
            ->orderBy('timeline_position')
            ->orderBy('sort_order')
            ->orderBy('created_at');
    private const MIN_AUTO_AD_GAP = 6;

    private const MAX_AUTO_AD_GAP = 12;

    public function timelineAds(?int $limit = null): Collection
    {
        $now = now();

        $query = Ad::query()
            ->whereRaw('LOWER(placement) = ?', ['timeline'])
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->where(function ($builder) use ($now) {
                $builder->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($builder) use ($now) {
                $builder->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            })
            ->orderByRaw('CASE WHEN timeline_position IS NULL THEN 1 ELSE 0 END')
            ->orderBy('timeline_position')
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function mergeTimelineFeed(Collection $posts, Collection $ads): Collection
    public function mergeTimelineFeed(Collection $posts, Collection $ads, int $page = 1): Collection
    {
        if ($ads->isEmpty()) {
            return $posts->values();
        }

        $items = $posts->values()->all();
        $placedAds = [];
        $floatingAds = [];

        foreach ($ads as $ad) {
            $position = $ad->timeline_position;
            if ($position && $position > 0) {
                $placedAds[(int) $position][] = $ad;
            } else {
                $floatingAds[] = $ad;
            }
        }

        if (! empty($placedAds)) {
            ksort($placedAds);
            $offset = 0;

            foreach ($placedAds as $position => $bucket) {
                $index = max(0, min(count($items), $position - 1 + $offset));
                array_splice($items, $index, 0, $bucket);
                $offset += count($bucket);
            }
        }

        if (! empty($floatingAds)) {
            $basePostsCount = max(1, $posts->count());
            $gap = max(2, (int) ceil($basePostsCount / count($floatingAds)));
            $offset = 0;

            foreach ($floatingAds as $i => $ad) {
                $index = min(count($items), (($i + 1) * $gap) - 1 + $offset);
                array_splice($items, $index, 0, [$ad]);
                $offset++;
            }
        }

        return collect($items)->map(function ($item) {
            if ($item instanceof Ad) {
                return AdResource::make($item)->resolve();
            }

            return $item;
        })->values();
        $postItems = $posts->values()->all();

        $manualPositionAds = $ads
            ->filter(fn (Ad $ad) => ! is_null($ad->timeline_position) && (int) $ad->timeline_position > 0)
            ->groupBy(fn (Ad $ad) => (int) $ad->timeline_position)
            ->map(function (Collection $group) {
                return $group->sortBy([
                    ['sort_order', 'asc'],
                    ['created_at', 'asc'],
                    ['id', 'asc'],
                ])->values()->all();
            })
            ->sortKeys();

        $automaticAds = $ads
            ->filter(fn (Ad $ad) => is_null($ad->timeline_position) || (int) $ad->timeline_position <= 0)
            ->sortBy([
                ['sort_order', 'asc'],
                ['created_at', 'asc'],
                ['id', 'asc'],
            ])
            ->values()
            ->all();

        $manualMerged = $this->mergeManualPositionAds($postItems, $manualPositionAds);

        return $this->distributeAutomaticAds($manualMerged, $automaticAds, $page);
    }

    private function mergeManualPositionAds(array $postItems, Collection $manualPositionAds): array
    {
        $result = [];
        $postIndex = 0;
        $slot = 1;

        $maxSlot = max(
            count($postItems) + $manualPositionAds->flatten(1)->count(),
            (int) ($manualPositionAds->keys()->max() ?? 0)
        );

        while ($slot <= $maxSlot || $postIndex < count($postItems)) {
            if ($manualPositionAds->has($slot)) {
                foreach ($manualPositionAds->get($slot) as $ad) {
                    $result[] = $this->transformAd($ad);
                }
                $manualPositionAds->forget($slot);
            }

            if ($postIndex < count($postItems)) {
                $result[] = $postItems[$postIndex];
                $postIndex++;
            }

            $slot++;
        }

        foreach ($manualPositionAds as $group) {
            foreach ($group as $ad) {
                $result[] = $this->transformAd($ad);
            }
        }

        return $result;
    }

    private function distributeAutomaticAds(array $items, array $automaticAds, int $page): Collection
    {
        if (empty($automaticAds)) {
            return collect($items)->values();
        }

        $result = [];
        $postCountSinceLastAuto = 0;
        $autoAdIndex = 0;
        $seed = $this->buildSeed($automaticAds, $page, count($items));
        $nextGap = $this->nextGap($seed);

        foreach ($items as $item) {
            $result[] = $item;

            if (($item['type'] ?? null) !== 'post') {
                continue;
            }

            $postCountSinceLastAuto++;

            if ($postCountSinceLastAuto >= $nextGap && isset($automaticAds[$autoAdIndex])) {
                $result[] = $this->transformAd($automaticAds[$autoAdIndex]);
                $autoAdIndex++;
                $postCountSinceLastAuto = 0;
                $nextGap = $this->nextGap($seed);
            }
        }

        return collect($result)->values();
    }

    private function buildSeed(array $automaticAds, int $page, int $itemCount): int
    {
        $adIds = collect($automaticAds)->pluck('id')->implode('|');
        $seedBase = sprintf('page:%d|items:%d|ads:%s', $page, $itemCount, $adIds);

        return (int) sprintf('%u', crc32($seedBase));
    }

    private function nextGap(int &$seed): int
    {
        $seed = (int) (($seed * 1103515245 + 12345) & 0x7fffffff);
        $range = self::MAX_AUTO_AD_GAP - self::MIN_AUTO_AD_GAP + 1;

        return self::MIN_AUTO_AD_GAP + ($seed % $range);
    }

    private function transformAd(Ad $ad): array
    {
        return AdResource::make($ad)->resolve();
    }
}
