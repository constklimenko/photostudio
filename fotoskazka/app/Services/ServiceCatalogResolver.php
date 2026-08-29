<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Service;

class ServiceCatalogResolver
{
    /**
     * Разрешает иерархический путь каталога услуг.
     *
     * Сегменты разбираются как цепочка категорий (type = service, is_published);
     * каждый следующий сегмент ищется среди детей предыдущего. Если последний
     * сегмент не является дочерней категорией, он может быть услугой
     * непосредственного уровня последней категории.
     *
     * Сущность НЕ определяется по количеству сегментов: каждый сегмент сначала
     * проверяется как категория, и только последний — как услуга.
     *
     * Возвращает Category, Service или null при некорректной/неопубликованной цепочке.
     */
    public function resolve(array $segments): Category|Service|null
    {
        if ($segments === []) {
            return null;
        }

        $parent = null;

        foreach ($segments as $index => $slug) {
            $isLast = $index === count($segments) - 1;

            $child = Category::query()
                ->where('type', 'service')
                ->where('is_published', true)
                ->where('slug', $slug)
                ->where('parent_id', $parent?->id)
                ->first();

            if ($child instanceof Category) {
                $parent = $child;

                if ($isLast) {
                    return $child;
                }

                continue;
            }

            if ($isLast) {
                return Service::query()
                    ->where('is_published', true)
                    ->where('slug', $slug)
                    ->where('category_id', $parent?->id)
                    ->first();
            }

            return null;
        }

        return null;
    }
}
