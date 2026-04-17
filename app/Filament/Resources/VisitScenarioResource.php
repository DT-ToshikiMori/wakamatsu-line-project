<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitScenarioResource\Pages;
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
            Forms\Components\Select::make('visit_number')
                ->label('来店回数')
                ->options([
                    0 => '0回目（初回登録）',
                    1 => '1回目来店',
                    2 => '2回目来店',
                    3 => '3回目来店',
                    4 => '4回目来店',
                    999 => '4回目以降ずっと（ゴールド）',
                ])
                ->required(),

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
                Tables\Columns\TextColumn::make('visit_number')
                    ->label('来店回数')
                    ->formatStateUsing(fn (int $state) => match ($state) {
                        0 => '0回目（初回登録）',
                        999 => '4回目以降ずっと（ゴールド）',
                        default => "{$state}回目来店",
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('couponTemplate.title')
                    ->label('クーポン')
                    ->searchable(),

                Tables\Columns\TextColumn::make('delay_hours')
                    ->label('遅延（時間）')
                    ->suffix('h')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expires_days')
                    ->label('有効期限（日）')
                    ->suffix('日')
                    ->placeholder('無期限')
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('有効'),
            ])
            ->defaultSort('visit_number')
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
