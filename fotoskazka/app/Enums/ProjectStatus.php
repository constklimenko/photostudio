<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Draft = 'draft';
    case ShootingCompleted = 'shooting_completed';
    case Reshoot = 'reshoot';
    case Processing = 'processing';
    case LayoutApproval = 'layout_approval';
    case Printing = 'printing';
    case Completed = 'completed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Подготовка',
            self::ShootingCompleted => 'Фотосъёмка закончена',
            self::Reshoot => 'Пересъёмка',
            self::Processing => 'Обработка фотографий',
            self::LayoutApproval => 'Согласование макета',
            self::Printing => 'Отправка в печать',
            self::Completed => 'Проект завершён',
            self::Archived => 'Архив',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::ShootingCompleted => 'info',
            self::Reshoot => 'warning',
            self::Processing => 'primary',
            self::LayoutApproval => 'success',
            self::Printing => 'danger',
            self::Completed => 'success',
            self::Archived => 'gray',
        };
    }

    public static function options(): array
    {
        return array_combine(
            array_map(fn ($case) => $case->value, self::cases()),
            array_map(fn ($case) => $case->label(), self::cases()),
        );
    }
}
