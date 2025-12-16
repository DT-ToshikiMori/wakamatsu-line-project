<?php

namespace App\Filament\Widgets;

use App\Models\StoreCycleRow;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class StoreCycle extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $sql = "
            WITH diffs AS (
              SELECT
                v.store_id,
                v.user_id,
                (julianday(v.visited_at) - julianday(
                  LAG(v.visited_at) OVER (PARTITION BY v.store_id, v.user_id ORDER BY v.visited_at)
                )) AS diff_days
              FROM visits v
            )
            SELECT
              s.id AS id,
              s.name AS store_name,
              ROUND(AVG(d.diff_days), 1) AS avg_cycle_days,
              COUNT(d.diff_days) AS samples
            FROM diffs d
            JOIN stores s ON s.id = d.store_id
            WHERE d.diff_days IS NOT NULL
            GROUP BY s.id, s.name
            ORDER BY avg_cycle_days ASC
        ";

        $query = StoreCycleRow::query()
            ->fromSub($sql, 't')
            ->select(['t.id', 't.store_name', 't.avg_cycle_days', 't.samples']);

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('store_name')->label('店舗'),
                Tables\Columns\TextColumn::make('avg_cycle_days')->label('平均来店周期（日）')->sortable(),
                Tables\Columns\TextColumn::make('samples')->label('サンプル数')->sortable(),
            ])
            ->paginated(false);
    }
}