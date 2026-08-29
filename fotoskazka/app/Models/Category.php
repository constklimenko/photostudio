<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'cover_media_id',
        'name',
        'slug',
        'type',
        'description',
        'price_from',
        'price_note',
        'seo_title',
        'seo_description',
        'is_published',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'price_from' => 'decimal:2',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Цепочка предков от корня до непосредственного родителя.
     * Корневая категория возвращает пустой массив.
     */
    public function ancestors(bool $withSelf = false): array
    {
        $ids = [];
        $current = $withSelf ? $this : $this->parent;
        $visited = [];
        $depth = 0;

        while ($current instanceof self) {
            $key = (int) $current->getKey();

            if (isset($visited[$key])) {
                throw new LogicException("Обнаружен цикл в иерархии категорий: категория {$key} повторяется в цепочке родителей.");
            }

            $visited[$key] = true;
            $ids[] = $current;
            $current = $current->parent;

            if (++$depth > 200) {
                throw new LogicException('Превышена допустимая глубина иерархии категорий.');
            }
        }

        return array_reverse($ids);
    }

    /**
     * Полный путь категории от корня до самой категории.
     */
    public function path(bool $withSelf = false): array
    {
        return $this->ancestors($withSelf);
    }

    /**
     * Иерархический slug-путь для URL каталога: "родитель/подкатегория".
     * Для корневой категории — просто её slug.
     */
    public function catalogPath(): string
    {
        return collect($this->path(true))->pluck('slug')->implode('/');
    }

    /**
     * Все потомки в глубину любых уровней.
     */
    public function descendants(): array
    {
        $result = [];
        $pending = $this->children()->get()->all();
        $visited = [];
        $guard = 0;

        while ($pending !== []) {
            $child = array_shift($pending);

            if ($child === null) {
                continue;
            }

            $key = (int) $child->getKey();

            if (isset($visited[$key])) {
                throw new LogicException("Обнаружен цикл в иерархии категорий: категория {$key} повторяется среди потомков.");
            }

            $visited[$key] = true;
            $result[] = $child;

            foreach ($child->children()->get() as $grandchild) {
                $pending[] = $grandchild;
            }

            if (++$guard > 20000) {
                throw new LogicException('Превышена допустимая глубина иерархии категорий.');
            }
        }

        return $result;
    }

    /**
     * Защита от циклической иерархии (A → B → C → A) и выбора
     * категории в качестве собственного родителя.
     */
    public function assertNotCyclic(): void
    {
        $newParentId = $this->parent_id;

        if (! $newParentId) {
            return;
        }

        $selfKey = $this->getKey() !== null ? (int) $this->getKey() : null;
        $newParentId = (int) $newParentId;

        if ($selfKey !== null && $newParentId === $selfKey) {
            throw new LogicException('Категория не может быть родителем самой себя.');
        }

        $visited = [];
        $cursor = $newParentId;
        $guard = 0;

        while ($cursor) {
            if (in_array($cursor, $visited, true)) {
                throw new LogicException('Обнаружен цикл в иерархии категорий.');
            }

            $visited[] = $cursor;

            if ($selfKey !== null && $cursor === $selfKey) {
                throw new LogicException('Обнаружен цикл в иерархии категорий.');
            }

            $parent = static::query()->find($cursor);

            if (! $parent) {
                break;
            }

            $cursor = (int) $parent->parent_id;

            if (++$guard > 200) {
                throw new LogicException('Превышена допустимая глубина иерархии категорий.');
            }
        }
    }

    /**
     * Корректно ли удалять категорию: у неё не должно быть
     * дочерних категорий или услуг — их требуется предварительно
     * переместить или удалить.
     */
    public function canBeDeleted(): bool
    {
        return ! $this->children()->exists() && ! $this->services()->exists();
    }

    protected static function booted(): void
    {
        static::saving(function (self $category) {
            if ($category->isDirty('parent_id') && $category->parent_id) {
                $category->assertNotCyclic();
            }

            if (! $category->exists) {
                return;
            }

            if ($category->isDirty('type') && $category->type === 'post'
                && ($category->children()->exists() || $category->services()->exists())) {
                throw new LogicException(
                    'Нельзя перевести категорию в «Блог», пока у неё есть дочерние категории или услуги.'
                );
            }
        });

        static::deleting(function (self $category) {
            if (! $category->canBeDeleted()) {
                throw new LogicException(
                    'Нельзя удалить категорию, пока у неё есть дочерние категории или услуги. '
                    .'Сначала переместите или удалите их.'
                );
            }
        });
    }
}
