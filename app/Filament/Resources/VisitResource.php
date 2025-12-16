<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitResource\Pages;
use App\Filament\Resources\VisitResource\RelationManagers;
use Filament\Tables\Actions\Action;
use App\Models\Visit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VisitResource extends Resource
{
    protected static ?string $model = Visit::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = '来店ログ';
    protected static ?string $pluralLabel = '来店ログ';
    protected static ?string $label = '来店ログ';
    protected static ?string $navigationGroup = '分析';

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
                Tables\Columns\TextColumn::make('visited_at')
                    ->label('来店日時')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.line_user_id')
                    ->label('LINEユーザー')
                    ->searchable(),

                Tables\Columns\TextColumn::make('store.name')
                    ->label('店舗')
                    ->sortable(),

                Tables\Columns\TextColumn::make('request_id')
                    ->label('request_id')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Action::make('exportCsv')
                    ->label('CSV出力')
                    ->action(function () {
                        $filename = 'visits_' . now()->format('Ymd_His') . '.csv';

                        $query = Visit::query()
                            ->with(['user', 'store'])
                            ->orderByDesc('visited_at');

                        return response()->streamDownload(function () use ($query) {
                            $out = fopen('php://output', 'w');

                            // Excelで日本語が文字化けしにくいようにUTF-8 BOM
                            fwrite($out, "\xEF\xBB\xBF");

                            // ヘッダ
                            fputcsv($out, ['visited_at', 'store', 'line_user_id', 'request_id']);

                            // 件数多くても耐えるようにchunk
                            $query->chunk(500, function ($rows) use ($out) {
                                foreach ($rows as $v) {
                                    $visitedAt = $v->visited_at ?? $v->created_at;

                                    fputcsv($out, [
                                        $visitedAt ? $visitedAt->format('Y-m-d H:i:s') : '',
                                        $v->store?->name ?? '',
                                        $v->user?->line_user_id ?? '',
                                        $v->request_id ?? '',
                                    ]);
                                }
                            });

                            fclose($out);
                        }, $filename, [
                            'Content-Type' => 'text/csv; charset=UTF-8',
                        ]);
                    }),
            ])
            ->defaultSort('visited_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
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
            'index' => Pages\ListVisits::route('/'),
            'create' => Pages\CreateVisit::route('/create'),
            'edit' => Pages\EditVisit::route('/{record}/edit'),
        ];
    }
}
