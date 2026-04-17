<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitScenarioResource\Pages;
use App\Models\StampCardDefinition;
use App\Models\VisitScenario;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VisitScenarioResource extends Resource
{
    protected static ?string $model = VisitScenario::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'クーポン管理';
    protected static ?string $navigationLabel = '来店シナリオ';
    protected static ?string $modelLabel = '来店シナリオ';
    protected static ?string $pluralModelLabel = '来店シナリオ';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('stamp_card_definition_id')
                ->label('スタンプカード')
                ->options(StampCardDefinition::pluck('display_name', 'id'))
                ->searchable()
                ->required(),

            Forms\Components\TextInput::make('stamp_number')
                ->label('スタンプ目（空欄の場合は来店回数指定）')
                ->numeric()
                ->minValue(1)
                ->nullable(),

            Forms\Components\TextInput::make('from_visit_count')
                ->label('N回目以降ずっと（スタンプ目が空欄の時に使用）')
                ->numeric()
                ->minValue(1)
                ->nullable(),

            Forms\Components\Select::make('segment_filter')
                ->label('対象セグメント（空欄=全員）')
                ->options([
                    'new' => 'はじめて',
                    '2_3' => '2〜3回',
                    '4plus' => '4回以上',
                ])
                ->nullable(),

            Forms\Components\Select::make('coupon_template_id')
                ->label('クーポンテンプレート')
                ->relationship('couponTemplate', 'title')
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\TextInput::make('delay_hours')
                ->label('何時間後に送るか')
                ->numeric()
                ->default(0)
                ->minValue(0)
                ->required(),

            Forms\Components\TextInput::make('expires_days')
                ->label('有効期限（日数）空欄=無期限')
                ->numeric()
                ->minValue(1)
                ->nullable(),

            Forms\Components\Toggle::make('is_active')
                ->label('有効')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('stampCardDefinition.display_name')
                    ->label('カード名')
                    ->searchable(),

                Tables\Columns\TextColumn::make('stamp_number')
                    ->label('スタンプ目 / N回以降')
                    ->formatStateUsing(function ($state, $record) {
                        if ($state !== null) {
                            return "{$state}スタンプ目";
                        }
                        if ($record->from_visit_count !== null) {
                            return "{$record->from_visit_count}回目以降";
                        }
                        return '-';
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('segment_filter')
                    ->label('セグメント')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'new' => 'はじめて',
                        '2_3' => '2〜3回',
                        '4plus' => '4回以上',
                        default => '全員',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('couponTemplate.title')
                    ->label('クーポン')
                    ->searchable(),

                Tables\Columns\TextColumn::make('delay_hours')
                    ->label('遅延（時間）')
                    ->suffix('h')
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('有効'),
            ])
            ->defaultSort('stamp_card_definition_id')
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVisitScenarios::route('/'),
            'create' => Pages\CreateVisitScenario::route('/create'),
            'edit' => Pages\EditVisitScenario::route('/{record}/edit'),
        ];
    }
}
