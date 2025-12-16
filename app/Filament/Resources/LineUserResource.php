<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LineUserResource\Pages;
use App\Filament\Resources\LineUserResource\RelationManagers;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use App\Models\LineUser;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LineUserResource extends Resource
{
    protected static ?string $model = LineUser::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'LINEユーザー';
    protected static ?string $pluralLabel = 'LINEユーザー';
    protected static ?string $label = 'LINEユーザー';
    protected static ?string $navigationGroup = '顧客管理';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('store.name')
                    ->label('店舗')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('line_user_id')
                    ->label('LINEユーザー')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('visit_count')
                    ->label('来店回数')
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_visit_at')
                    ->label('最終来店')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('exportCsv')
                    ->label('CSV出力')
                    ->action(function () {
                        $filename = 'line_users_' . now()->format('Ymd_His') . '.csv';

                        $query = LineUser::query()
                            ->with(['store'])
                            ->orderByDesc('last_visit_at');

                        return response()->streamDownload(function () use ($query) {
                            $out = fopen('php://output', 'w');

                            // Excel文字化け対策（UTF-8 BOM）
                            fwrite($out, "\xEF\xBB\xBF");

                            fputcsv($out, ['store', 'line_user_id', 'visit_count', 'last_visit_at']);

                            $query->chunk(500, function ($rows) use ($out) {
                                foreach ($rows as $u) {
                                    $last = $u->last_visit_at ?? $u->created_at;

                                    fputcsv($out, [
                                        $u->store?->name ?? '',
                                        $u->line_user_id ?? '',
                                        (string)($u->visit_count ?? 0),
                                        $last ? $last->format('Y-m-d H:i:s') : '',
                                    ]);
                                }
                            });

                            fclose($out);
                        }, $filename, [
                            'Content-Type' => 'text/csv; charset=UTF-8',
                        ]);
                    }),
            ])
            ->filters([
                SelectFilter::make('store_id')
                    ->label('店舗')
                    ->relationship('store', 'name'),
            ])
            ->defaultSort('last_visit_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLineUsers::route('/'),
            'create' => Pages\CreateLineUser::route('/create'),
            'edit' => Pages\EditLineUser::route('/{record}/edit'),
        ];
    }
}
