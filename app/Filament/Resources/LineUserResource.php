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
                Forms\Components\Select::make('gender')
                    ->label('性別')
                    ->options([
                        'male' => '男性',
                        'female' => '女性',
                        'other' => 'その他',
                    ])
                    ->nullable(),

                Forms\Components\TextInput::make('birth_year')
                    ->label('生まれ年')
                    ->numeric()
                    ->minValue(1920)
                    ->maxValue(date('Y'))
                    ->nullable(),

                Forms\Components\Select::make('birth_month')
                    ->label('誕生月')
                    ->options(collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => $m . '月']))
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('profile_image_url')
                    ->label('アイコン')
                    ->circular()
                    ->size(36)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('display_name')
                    ->label('表示名')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('store.name')
                    ->label('店舗')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('line_user_id')
                    ->label('LINE ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('gender')
                    ->label('性別')
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'male' => '男性',
                        'female' => '女性',
                        'other' => 'その他',
                        default => '未登録',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('birth_month')
                    ->label('誕生月')
                    ->formatStateUsing(fn (?int $state) => $state ? $state . '月' : '未登録')
                    ->toggleable(isToggledHiddenByDefault: true),

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

                            fputcsv($out, ['store', 'display_name', 'line_user_id', 'gender', 'birth_year', 'birth_month', 'visit_count', 'last_visit_at']);

                            $query->chunk(500, function ($rows) use ($out) {
                                foreach ($rows as $u) {
                                    $last = $u->last_visit_at ?? $u->created_at;
                                    $genderLabel = match ($u->gender) {
                                        'male' => '男性',
                                        'female' => '女性',
                                        'other' => 'その他',
                                        default => '',
                                    };

                                    fputcsv($out, [
                                        $u->store?->name ?? '',
                                        $u->display_name ?? '',
                                        $u->line_user_id ?? '',
                                        $genderLabel,
                                        (string)($u->birth_year ?? ''),
                                        $u->birth_month ? $u->birth_month . '月' : '',
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
