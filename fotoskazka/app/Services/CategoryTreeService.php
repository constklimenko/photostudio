<?php

namespace App\Services;

use App\Models\Category;
use LogicException;

class CategoryTreeService
{
    /**
     * Плоское представление дерева категорий заданного типа в порядке
     * «родитель → дочерние»: id, name, depth, indent, pathLabel, parent_id.
     *
     * @return array<int, array<string, mixed>>
     */
    public function flatten(?string $type = 'service'): array
    {
        $query = Category::query();

        if ($type !== null) {
            $query->where('type', $type);
        }

        $nodes = $query
            ->orderBy('sort_order')
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'parent_id', 'name', 'type']);

        $byId = [];
        $roots = [];

        foreach ($nodes as $node) {
            $byId[$node->getKey()] = [
                'id' => (int) $node->getKey(),
                'parent_id' => $node->parent_id ? (int) $node->parent_id : null,
                'name' => $node->name,
                'children' => [],
            ];
        }

        foreach ($byId as $id => $node) {
            if ($node['parent_id'] !== null && isset($byId[$node['parent_id']])) {
                $byId[$node['parent_id']]['children'][] = $id;
            } else {
                $roots[] = $id;
            }
        }

        $flat = [];

        $walk = function (array $ids, int $depth, string $path) use (&$walk, &$flat, &$byId): void {
            foreach ($ids as $id) {
                $node = $byId[$id];
                $label = $path !== '' ? $path.' → '.$node['name'] : $node['name'];

                $flat[$id] = [
                    'id' => $id,
                    'parent_id' => $node['parent_id'],
                    'name' => $node['name'],
                    'depth' => $depth,
                    'indent' => str_repeat('— ', $depth),
                    'pathLabel' => $label,
                ];

                $walk($node['children'], $depth + 1, $label);
            }
        };

        $walk($roots, 0, '');

        return $flat;
    }

    /**
     * Список категорий услуг для выпадающего списка «Родительская категория».
     * Исключает саму категорию и всё её поддерево, чтобы исключить циклы.
     *
     * @return array<int, string>
     */
    public function options(?Category $exclude = null): array
    {
        $excluded = $exclude !== null
            ? array_merge([(int) $exclude->getKey()], $this->subtreeIds($exclude))
            : [];

        $options = [];

        foreach ($this->flatten('service') as $id => $node) {
            if ($exclude !== null && in_array($id, $excluded, true)) {
                continue;
            }

            $options[$id] = $node['indent'].$node['name'];
        }

        return $options;
    }

    /**
     * Идентификаторы всех потомков категории (все уровни).
     *
     * @return array<int>
     */
    public function subtreeIds(Category $category): array
    {
        return array_map(
            fn (Category $child): int => (int) $child->getKey(),
            $category->descendants(),
        );
    }

    /**
     * Перенос категории внутри списка братьев на offset позиций
     * с перерасчётом sort_order для всего списка.
     */
    public function move(Category $category, int $offset): void
    {
        $siblings = Category::query()
            ->where('type', $category->type)
            ->where('parent_id', $category->parent_id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'sort_order']);

        $keys = $siblings->pluck('id')->all();
        $index = array_search((int) $category->getKey(), $keys, true);

        if ($index === false) {
            throw new LogicException('Не удалось определить позицию категории в дереве.');
        }

        $target = $index + $offset;

        if ($target < 0 || $target >= count($keys)) {
            throw new LogicException('Категория находится на границе списка сортировки.');
        }

        $order = $keys;
        [$order[$index], $order[$target]] = [$order[$target], $order[$index]];

        foreach (array_values($order) as $rank => $id) {
            Category::query()->whereKey($id)->update([
                'sort_order' => ($rank + 1) * 10,
            ]);
        }
    }
}
