<?php

declare(strict_types=1);

namespace Docsmith\Config;

use LogicException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final class DocsConfiguration
{
    /** @return array{navigation: list<string>, labels: array<string, string>}|null */
    public static function load(string $sourcePath): ?array
    {
        $path = rtrim($sourcePath, '/\\') . '/docs.yml';

        if (! is_file($path)) {
            return null;
        }

        try {
            $configuration = Yaml::parseFile($path);
        } catch (ParseException $exception) {
            throw new LogicException(sprintf('Unable to parse docs configuration [%s]: %s', $path, $exception->getMessage()), 0, $exception);
        }

        if (! is_array($configuration) || ! is_array($configuration['navigation'] ?? null)) {
            return null;
        }

        $labels = [];
        $items = self::flattenNavigation($configuration['navigation'], $labels);

        return $items === [] ? null : ['navigation' => $items, 'labels' => $labels];
    }

    /**
     * @param  array<array-key, mixed>  $navigation
     * @param  array<string, string>  $labels
     * @return list<string>
     */
    private static function flattenNavigation(array $navigation, array &$labels): array
    {
        $items = [];

        foreach ($navigation as $entry) {
            if (is_string($entry) && trim($entry) !== '') {
                $items[] = trim($entry);
                continue;
            }

            if (is_array($entry) && is_string($entry['page'] ?? null) && trim($entry['page']) !== '') {
                $page = trim($entry['page']);
                $items[] = $page;

                if (is_string($entry['label'] ?? null) && trim($entry['label']) !== '') {
                    $labels[strtolower(trim($page, '/'))] = trim($entry['label']);
                }

                continue;
            }

            if (is_array($entry) && is_array($entry['children'] ?? null)) {
                array_push($items, ...self::flattenNavigation($entry['children'], $labels));
            }
        }

        return $items;
    }
}
