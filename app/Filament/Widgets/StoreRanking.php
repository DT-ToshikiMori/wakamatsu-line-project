<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Visit;

class StoreRanking extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        // ✅ DB::table じゃなくて Visit::query()（Eloquent）で作る
        $query = \App\Models\Visit::query()
            ->join('stores', 'stores.id', '=', 'visits.store_id')
            ->selectRaw('stores.id as id, stores.name as store_name, COUNT(*) as visit_count')
            ->groupBy('stores.id', 'stores.name')
            ->orderByDesc('visit_count');

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('store_name')->label('店舗'),
                Tables\Columns\TextColumn::make('visit_count')->label('来店数')->sortable(),
            ])
            ->paginated(false);
    }
}