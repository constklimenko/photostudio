<?php

namespace Tests\Unit\Enums;

use App\Enums\ProjectStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProjectStatusTest extends TestCase
{
    public function test_has_exact_cases(): void
    {
        $this->assertSame(['draft', 'shooting_completed', 'reshoot', 'processing', 'layout_approval', 'printing', 'completed', 'archived'], array_column(ProjectStatus::cases(), 'value'));
    }

    public function test_no_legacy_active_case(): void
    {
        $this->assertNotContains('active', array_column(ProjectStatus::cases(), 'value'));
    }

    #[DataProvider('labelProvider')]
    public function test_labels(ProjectStatus $status, string $expected): void
    {
        $this->assertSame($expected, $status->label());
    }

    public static function labelProvider(): array
    {
        return [
            [ProjectStatus::Draft, 'Подготовка'],
            [ProjectStatus::ShootingCompleted, 'Фотосъёмка закончена'],
            [ProjectStatus::Reshoot, 'Пересъёмка'],
            [ProjectStatus::Processing, 'Обработка фотографий'],
            [ProjectStatus::LayoutApproval, 'Согласование макета'],
            [ProjectStatus::Printing, 'Отправка в печать'],
            [ProjectStatus::Completed, 'Проект завершён'],
            [ProjectStatus::Archived, 'Архив'],
        ];
    }

    public function test_options_are_keyed_by_value_and_labeled(): void
    {
        $options = ProjectStatus::options();

        $this->assertSame(count(ProjectStatus::cases()), count($options));
        $this->assertSame('Подготовка', $options['draft']);
        $this->assertArrayNotHasKey('active', $options);
    }

    public function test_every_case_has_a_color(): void
    {
        foreach (ProjectStatus::cases() as $case) {
            $this->assertNotEmpty($case->color());
        }
    }
}
