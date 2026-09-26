<?php

namespace App\Models;

use App\Enums\ShootingAlbumDisplay;
use App\Models\Concerns\HasShootingAlbumDisplay;
use App\Observers\SitemapCacheObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[ObservedBy(SitemapCacheObserver::class)]
class Category extends Model
{
    use HasFactory;
    use HasShootingAlbumDisplay;

    protected $fillable = [
        'parent_id',
        'cover_media_id',
        'name',
        'slug',
        'type',
        'description',
        'examples_title',
        'price_from',
        'price_note',
        'seo_title',
        'seo_description',
        'is_published',
        'is_graduation_albums',
        'show_on_home',
        'sort_order',
        'show_album_photos',
        'featured_album_id',
        'cta_album_id',
        'cta_button_text',
        'shooting_album_id',
        'shooting_album_display',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'is_graduation_albums' => 'boolean',
            'show_on_home' => 'boolean',
            'show_album_photos' => 'boolean',
            'price_from' => 'decimal:2',
            'shooting_album_display' => ShootingAlbumDisplay::class,
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

    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class, 'category_video')
            ->orderBy('videos.sort_order');
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(ServiceItem::class, 'category_service_item')
            ->withPivot('is_included', 'sort_order')
            ->orderByPivot('sort_order');
    }

    public function albums(): BelongsToMany
    {
        return $this->belongsToMany(Album::class, 'category_album');
    }

    public function featuredAlbum(): BelongsTo
    {
        return $this->belongsTo(Album::class, 'featured_album_id');
    }

    public function ctaAlbum(): BelongsTo
    {
        return $this->belongsTo(Album::class, 'cta_album_id');
    }

    public function shootingAlbum(): BelongsTo
    {
        return $this->belongsTo(Album::class, 'shooting_album_id');
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
     *
     * Требует загруженной цепочки предков: либо `with('parent.…')`,
     * либо `Category::loadAncestorChains()` для дерева любой глубины.
     */
    public function catalogPath(): string
    {
        return collect($this->path(true))->pluck('slug')->implode('/');
    }

    /**
     * Загрузить цепочки предков указанных категорий целиком, без ограничения
     * глубины: каждый уровень дочитывается одним запросом на все категории
     * сразу, поэтому `catalogPath()` не порождает N+1 в глубоком дереве.
     *
     * Из моделей выбираются только поля, нужные для построения пути.
     */
    public static function loadAncestorChains(iterable $categories): void
    {
        $instances = [];
        $byId = [];

        foreach ($categories as $category) {
            if (! $category instanceof self) {
                continue;
            }

            $instances[spl_object_id($category)] = $category;
            $byId[(int) $category->getKey()] ??= $category;
        }

        $requested = [];

        while (true) {
            $missing = [];

            foreach ($byId as $parentId => $category) {
                $candidate = $category->parent_id;

                if ($candidate === null) {
                    continue;
                }

                $candidate = (int) $candidate;

                if (isset($byId[$candidate]) || isset($requested[$candidate])) {
                    continue;
                }

                $missing[$candidate] = true;
            }

            if ($missing === []) {
                break;
            }

            $requested += $missing;

            $parents = self::query()
                ->whereIn('id', array_keys($missing))
                ->get(['id', 'parent_id', 'slug']);

            foreach ($parents as $parent) {
                $id = (int) $parent->getKey();

                if (isset($byId[$id])) {
                    continue;
                }

                $byId[$id] = $parent;
                $instances[spl_object_id($parent)] = $parent;
            }
        }

        foreach ($instances as $category) {
            $parentId = $category->parent_id;

            $category->setRelation(
                'parent',
                $parentId === null ? null : ($byId[(int) $parentId] ?? null),
            );
        }
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
