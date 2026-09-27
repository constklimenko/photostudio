<?php

namespace App\Filament\Resources\FaqItems\Schemas;

use App\Models\Category;
use App\Services\CategoryTreeService;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FaqItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('question')
                            ->required()
                            ->maxLength(255)
                            ->label('Вопрос'),
                        Toggle::make('is_active')
                            ->default(true)
                            ->label('Активно'),
                        TextInput::make('sort_order')
                            ->integer()
                            ->default(0)
                            ->label('Порядок'),
                    ]),
                RichEditor::make('answer')
                    ->required()
                    ->label('Ответ')
                    ->columnSpanFull(),
                Section::make('Привязка к каталогу услуг')
                    ->schema([
                        Select::make('services')
                            ->multiple()
                            ->relationship('services', 'title')
                            ->preload()
                            ->searchable()
                            ->label('Услуги')
                            ->columnSpanFull(),
                        Select::make('categories')
                            ->multiple()
                            ->relationship('categories', 'name')
                            ->getOptionLabelFromRecordUsing(fn (Category $record): string => self::categoryLabel($record))
                            ->preload()
                            ->searchable()
                            ->label('Категории услуг')
                            ->helperText('Доступны только категории типа «Услуга»')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Метка категории с отступом по уровню дерева — как в выборе
     * родительской категории (CategoryForm → CategoryTreeService::options()).
     */
    private static function categoryLabel(Category $record): string
    {
        static $labels = null;

        $labels ??= collect(app(CategoryTreeService::class)->flatten('service'))
            ->mapWithKeys(fn (array $node): array => [$node['id'] => $node['indent'].$node['name']]);

        return $labels[$record->getKey()] ?? $record->name;
    }
}
