<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Route;

final class CasinoCatalog
{
    /**
     * @return list<array{
     *     slug: string,
     *     route: string,
     *     name: string,
     *     code: string,
     *     category: string,
     *     tag: string,
     *     label: string,
     *     blurb: string,
     *     icon: string
     * }>
     */
    public static function games(): array
    {
        /** @var list<array<string, mixed>> $games */
        $games = config('casino.catalog', []);

        return array_values(array_map(function (array $game): array {
            return [
                'slug' => (string) ($game['slug'] ?? ''),
                'route' => (string) ($game['route'] ?? ''),
                'name' => (string) ($game['name'] ?? ''),
                'code' => (string) ($game['code'] ?? ''),
                'category' => (string) ($game['category'] ?? 'sorte'),
                'tag' => (string) ($game['tag'] ?? 'ORIGINAL'),
                'label' => (string) ($game['label'] ?? ''),
                'blurb' => (string) ($game['blurb'] ?? ''),
                'icon' => (string) ($game['icon'] ?? '✦'),
            ];
        }, $games));
    }

    /**
     * @return array<string, string>
     */
    public static function categories(): array
    {
        return [
            'all' => 'Todos',
            'originais' => 'Originais',
            'mesas' => 'Mesas',
            'cartas' => 'Cartas',
            'sorte' => 'Sorte',
            'dados' => 'Dados',
        ];
    }

    /**
     * @return array{
     *     slug: string,
     *     route: string,
     *     name: string,
     *     code: string,
     *     category: string,
     *     tag: string,
     *     label: string,
     *     blurb: string,
     *     icon: string
     * }|null
     */
    public static function find(string $slug): ?array
    {
        foreach (self::games() as $game) {
            if ($game['slug'] === $slug) {
                return $game;
            }
        }

        return null;
    }

    public static function playUrl(string $routeName): string
    {
        if (auth()->check() && Route::has($routeName)) {
            return route($routeName);
        }

        return route('login');
    }
}
