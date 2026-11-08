<?php

namespace Tests\Feature\Http;

use App\Models\Category;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class SchemaMarkupTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_emits_professional_service_and_website_schema(): void
    {
        config(['contacts.phone' => '+7 987 484 7476']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('"@type":"ProfessionalService"', false);
        $response->assertSee('"@type":"WebSite"', false);
        $response->assertSee('"streetAddress":"ул. 50 лет СССР"', false);
        $response->assertSee('"addressLocality":"Уфа"', false);
        $response->assertSee('"telephone":"+79874847476"', false);
    }

    public function test_organization_schema_includes_social_profiles_as_same_as(): void
    {
        $socialLinks = collect([
            (object) ['icon' => 'instagram', 'url' => 'https://www.instagram.com/fotoskazkaufa/'],
            (object) ['icon' => 'vk', 'url' => 'https://vk.com/fotoskazkaufa'],
        ]);

        $html = Blade::render('<x-site.organization-schema :social-links="$socialLinks" />', compact('socialLinks'));

        $this->assertStringContainsString('"@type":"ProfessionalService"', $html);
        $this->assertStringContainsString('https://www.instagram.com/fotoskazkaufa/', $html);
        $this->assertStringContainsString('https://vk.com/fotoskazkaufa', $html);
    }

    public function test_service_page_uses_service_schema_instead_of_product(): void
    {
        $category = Category::factory()->create([
            'type' => 'service',
            'slug' => 'semeinye',
            'parent_id' => null,
            'is_published' => true,
            'name' => 'Семейные фотосессии',
        ]);

        $service = Service::factory()->create([
            'slug' => 'semeinye-fotosessii',
            'category_id' => $category->id,
            'is_published' => true,
            'title' => 'Семейные фотосессии',
        ]);

        $response = $this->get('/services/'.$service->catalogPath());

        $response->assertOk();
        $response->assertSee('"@type":"Service"', false);
        $response->assertSee('"@type":"ProfessionalService"', false);
        $response->assertDontSee('"@type":"Product"', false);
    }

    public function test_category_page_uses_collection_page_schema_instead_of_product(): void
    {
        $category = Category::factory()->create([
            'type' => 'service',
            'slug' => 'semeinye',
            'parent_id' => null,
            'is_published' => true,
            'name' => 'Семейные фотосессии',
        ]);

        Service::factory()->create([
            'slug' => 'semeinye-fotosessii',
            'category_id' => $category->id,
            'is_published' => true,
            'title' => 'Семейные фотосессии',
        ]);

        $response = $this->get('/services/'.$category->catalogPath());

        $response->assertOk();
        $response->assertSee('"@type":"CollectionPage"', false);
        $response->assertSee('"@type":"ItemList"', false);
        $response->assertDontSee('"@type":"Product"', false);
        $response->assertDontSee('"AggregateOffer"', false);
    }
}
