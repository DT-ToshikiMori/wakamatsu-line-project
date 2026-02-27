<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StoreQrLinkResource\Pages;
use App\Filament\Resources\StoreQrLinkResource\RelationManagers;
use App\Models\StoreQrLink;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QROutputInterface;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StoreQrLinkResource extends Resource
{
    protected static ?string $model = StoreQrLink::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = '店舗別QR';
    protected static ?string $pluralLabel = '店舗別QR';
    protected static ?string $label = '店舗別QR';
    protected static ?string $navigationGroup = '店舗管理';


    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('shop_id')
                ->default(1),

            Forms\Components\Select::make('store_id')
                ->label('店舗')
                ->relationship('store', 'name')
                ->searchable()
                ->required(),

            Forms\Components\TextInput::make('name')
                ->label('QR名')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('slug')
                ->label('Slug（URL識別子）')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->helperText('例：dt-test-front（英数字とハイフン推奨）'),

            Forms\Components\TextInput::make('stamp_count')
                ->label('スタンプ付与数')
                ->numeric()
                ->default(1)
                ->minValue(1)
                ->required()
                ->helperText('このQRコードで付与するスタンプ数'),

            Forms\Components\Toggle::make('is_active')
                ->label('有効')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('QR名')
                    ->searchable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->copyable()
                    ->badge(),

                Tables\Columns\TextColumn::make('store.name')
                    ->label('店舗')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stamp_count')
                    ->label('スタンプ数')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('有効')
                    ->boolean(),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('downloadQr')
                    ->label('QR')
                    ->icon('heroicon-o-qr-code')
                    ->action(function (StoreQrLink $record) {
                        $url = config('app.url') . '/r/' . $record->slug;
                        $options = new QROptions([
                            'outputType' => QROutputInterface::GDIMAGE_PNG,
                            'scale' => 10,
                            'outputBase64' => false,
                        ]);
                        $image = (new QRCode($options))->render($url);
                        return response()->streamDownload(
                            fn () => print($image),
                            "qr-{$record->slug}.png",
                            ['Content-Type' => 'image/png']
                        );
                    }),
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
            'index' => Pages\ListStoreQrLinks::route('/'),
            'create' => Pages\CreateStoreQrLink::route('/create'),
            'edit' => Pages\EditStoreQrLink::route('/{record}/edit'),
        ];
    }
}
