<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class SocialLinksTest extends TestCase
{
    public function test_section_and_compact_variants_render_instagram_and_telegram_with_expected_svg_spacing(): void
    {
        $links = collect([
            (object) [
                'name' => 'Instagram',
                'icon' => 'instagram',
                'url' => 'https://www.instagram.com/example/',
            ],
            (object) [
                'name' => 'Telegram',
                'icon' => 'telegram',
                'url' => 'https://t.me/example',
            ],
        ]);

        foreach (['section', 'compact'] as $variant) {
            $html = Blade::render(
                '<x-site.social-links :variant="$variant" :social-links="$links" />',
                compact('variant', 'links'),
            );

            $document = new \DOMDocument;
            $previousLibxmlErrorSetting = libxml_use_internal_errors(true);
            $document->loadHTML($html);
            libxml_clear_errors();
            libxml_use_internal_errors($previousLibxmlErrorSetting);
            $xpath = new \DOMXPath($document);

            foreach (['instagram.com', 't.me'] as $hrefFragment) {
                $iconPath = $variant === 'section'
                    ? '//a[contains(@href, "'.$hrefFragment.'")]/span/svg'
                    : '//a[contains(@href, "'.$hrefFragment.'")]/svg';
                $icon = $xpath->query($iconPath)->item(0);

                $this->assertNotNull($icon);
                $this->assertSame('w-5 h-5', $icon->getAttribute('class'));
                $this->assertSame($variant === 'section' ? 'margin: 2px' : '', $icon->getAttribute('style'));
            }
        }
    }
}
