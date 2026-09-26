<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Category;
use App\Models\FaqItem;
use App\Models\Media;
use App\Models\Post;
use App\Models\Service;
use App\Models\SocialLink;
use App\Models\Testimonial;
use App\Models\Video;
use Illuminate\Support\Collection;

/**
 * Слой данных главной страницы.
 *
 * Главная выводит Hero, социальные ссылки и блок «Избранные работы».
 * Методы остальных блоков (услуги, видео, отзывы, блог, вопросы, о студии,
 * форма заявки) сохранены: блоки лежат в компонентах `x-site.home.*`,
 * для их включения достаточно вызвать нужный метод в `HomeController`
 * и вывести компонент в `home.blade.php`.
 */
class HomeContentService
{
    private const FEATURED_WORKS_PAGE = 'portfolio';

    public function __construct(
        private readonly PageContentService $pageContent,
    ) {}

    /**
     * Фон Hero: media первой фотографии первого опубликованного альбома
     * типа `homepage`.
     *
     * Компонент `x-site.hero` использует только первое изображение, поэтому
     * загружается одна фотография вместо всех фотографий альбома.
     *
     * @return Collection<int, Media>
     */
    public function heroImages(): Collection
    {
        $album = Album::query()
            ->where('type', 'homepage')
            ->where('is_published', true)
            ->first(['id']);

        if ($album === null) {
            return collect();
        }

        $photo = $album->photos()
            ->with('media')
            ->orderBy('sort_order')
            ->first(['media_id']);

        return $photo?->media instanceof Media
            ? collect([$photo->media])
            : collect();
    }

    /**
     * Кнопки Hero из настроек самих сущностей: опубликованные категории услуг
     * и услуги с `show_on_home = true`.
     *
     * Сортировка предсказуемая и общая для обоих типов сущностей:
     * `sort_order` по возрастанию, при равенстве — сначала категории,
     * затем услуги, при равенстве типа — по названию (алфавит, без учёта
     * регистра).
     *
     * @return array<int, array{label: string, url: string, type: string, sort_order: int}>
     */
    public function heroButtons(): array
    {
        $categories = $this->heroCategoryButtons();
        $services = $this->heroServiceButtons();

        Category::loadAncestorChains(
            $categories
                ->concat($services->map(fn (Service $service): ?Category => $service->category))
                ->filter()
                ->all()
        );

        $buttons = $categories
            ->map(fn (Category $category): array => [
                'label' => $category->name,
                'url' => route('services.show', $category->catalogPath()),
                'type' => 'category',
                'sort_order' => (int) $category->sort_order,
            ])
            ->concat($services->map(fn (Service $service): array => [
                'label' => $service->title,
                'url' => route('services.show', $service->catalogPath()),
                'type' => 'service',
                'sort_order' => (int) $service->sort_order,
            ]))
            ->all();

        usort($buttons, function (array $left, array $right): int {
            return [$left['sort_order'], $left['type'], mb_strtolower($left['label'])]
                <=> [$right['sort_order'], $right['type'], mb_strtolower($right['label'])];
        });

        return $buttons;
    }

    /**
     * Блок «Избранные работы».
     *
     * `null` — блок выключен: страница «Портфолио» не отмечена в
     * `Pages → Отображать на главной`. Пустая коллекция — блок включён,
     * но подходящих альбомов нет.
     */
    public function featuredWorks(): ?Collection
    {
        if (! $this->pageContent->isHomeSection(self::FEATURED_WORKS_PAGE)) {
            return null;
        }

        return Album::query()
            ->where('type', 'portfolio')
            ->where('is_featured', true)
            ->where('is_published', true)
            ->with('cover')
            ->get(['id', 'cover_media_id', 'title', 'slug']);
    }

    /**
     * Тексты блока главной: `Pages → Отображать на главной` задаёт
     * `home_title` / `home_subtitle` / `home_content`, иначе используются
     * заголовок, подзаголовок и описание самой страницы, иначе — значения
     * по умолчанию.
     *
     * @return array{title: string, subtitle: ?string, content: ?string}
     */
    public function blockText(string $slug, string $defaultTitle, ?string $defaultSubtitle = null): array
    {
        $page = $this->pageContent->getHomeSections()[$slug] ?? null;

        return [
            'title' => $page?->home_title ?: $page?->title ?: $defaultTitle,
            'subtitle' => $page?->home_subtitle ?: $page?->subtitle ?: $defaultSubtitle,
            'content' => $this->plainText($page?->home_content ?: $page?->content),
        ];
    }

    /**
     * Корневые опубликованные категории услуг — блок «Наши услуги».
     */
    public function servicesGrid(): Collection
    {
        return Category::query()
            ->where('type', 'service')
            ->whereNull('parent_id')
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->with('cover')
            ->get(['id', 'cover_media_id', 'name', 'slug', 'description', 'price_from', 'sort_order']);
    }

    public function videos(): Collection
    {
        return Video::query()
            ->where('is_active', true)
            ->where('show_on_home', true)
            ->orderBy('sort_order')
            ->get(['id', 'title', 'url', 'file_path', 'type', 'rotation', 'has_sound', 'sort_order']);
    }

    public function testimonials(): Collection
    {
        return Testimonial::query()
            ->where('is_published', true)
            ->with('photo')
            ->get(['id', 'media_id', 'client_name', 'content']);
    }

    public function latestPosts(): Collection
    {
        return Post::query()
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->take(3)
            ->with('cover')
            ->get(['id', 'cover_media_id', 'title', 'slug', 'excerpt', 'published_at']);
    }

    public function faqItems(): Collection
    {
        return FaqItem::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'question', 'answer']);
    }

    /**
     * Активные социальные ссылки.
     *
     * Данные нужны свежими на каждый запрос, поэтому блок не полагается
     * на `$socialLinks` из `ViewComposerServiceProvider`: он собирается
     * один раз при старте приложения и не видит правки из админки.
     */
    public function socialLinks(): Collection
    {
        return SocialLink::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function inquiryServices(): Collection
    {
        return Service::query()
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->get(['id', 'title']);
    }

    private function heroCategoryButtons(): Collection
    {
        return Category::query()
            ->where('type', 'service')
            ->where('is_published', true)
            ->where('show_on_home', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name', 'slug', 'sort_order']);
    }

    private function heroServiceButtons(): Collection
    {
        return Service::query()
            ->where('is_published', true)
            ->where('show_on_home', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->with(['category' => fn ($query) => $query->select(['id', 'parent_id', 'slug'])])
            ->get(['id', 'category_id', 'title', 'slug', 'sort_order']);
    }

    private function plainText(?string $html): ?string
    {
        if (! $html) {
            return null;
        }

        $text = trim(strip_tags($html));

        return $text === '' ? null : $text;
    }
}
