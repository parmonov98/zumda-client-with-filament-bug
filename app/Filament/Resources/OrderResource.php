<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Category;
use App\Models\Client;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Product;
use App\Models\Restaurant;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected static ?string $navigationGroup = "Sotuv va mijozlar bo'limi";

    protected static ?string $navigationLabel = 'Buyurtmalar';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Orders')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Malumotlari')
                            ->schema([
                                Forms\Components\Hidden::make('user_id')
                                    ->default(auth()->id())
                                    ->disabled()
                                    ->required(),
                                Forms\Components\Select::make('client_id')
                                    ->label('Mijoz')
                                    ->options(Client::all()->pluck('name', 'id'))
                                    ->searchable(),

                                Forms\Components\Select::make('driver_id')
                                    ->label('Haydovchi')
                                    ->options(Driver::all()->pluck('name', 'id'))
                                    ->searchable(),
                                Forms\Components\Select::make('payment_type')
                                    ->options([
                                        'cash' => 'Naqd',
                                        'credit_card' => 'Karta'
                                    ])
                                    ->label("To'lov turi")
                                    ->required(),

                                Forms\Components\TextInput::make('per_km_price')
                                    ->label('Kilometr narx')
                                    ->disabled()
                                    ->default(config('bot.PRICE_DELIVERY_PER_KM')),

                                Forms\Components\TextInput::make('distance')
                                    ->rules(['numeric'])
                                    ->afterStateUpdated(function (\Closure $set, $state) {
                                        $newShippingPrice = intval($state) * intval(config('bot.PRICE_DELIVERY_PER_KM'));
                                        $remaining_price = $newShippingPrice % 1000;
                                        if ($remaining_price >= 500) {
                                            $shipping_price = $newShippingPrice - $remaining_price + 1000;
                                        }else{
                                            $shipping_price = $newShippingPrice - $remaining_price;
                                        }
                                        $set('shipping_price', $shipping_price);
                                    })
                                    ->reactive()
                                    ->label("Masofa")
                                    ->required(),

                                Forms\Components\TextInput::make('shipping_price')
                                    ->label('Yetkazish narxi')
                                    ->required(),
//                Forms\Components\TextInput::make('summary'),
                                Forms\Components\TextInput::make('phone_number')
                                    ->tel()
                                    ->label("Telefon")
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('address')
                                    ->label("Manzil")
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('longitude')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('latitude')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('customer_note')
                                    ->label("Mijoz xohshi")
                                    ->label('')
                                    ->maxLength(255),

//                Forms\Components\Toggle::make('is_assigned_by_operator')
//                    ->required(),
//                Forms\Components\Toggle::make('is_accepted_order_by_driver')
//                    ->required(),

                                Forms\Components\Select::make('shipment')
                                    ->label("Yetkazish turi")
                                    ->options([
                                        'delivery' => 'Yetkazish',
                                        'pickup' => 'Ob ketadi'
                                    ])
                                    ->required(),

                                Forms\Components\Select::make('status')
                                    ->label('Holat')
                                    ->options([
                                        'created' => 'Yatarilgan',
                                        'accepted' => 'Qabul qilingan',
                                        'preparing' => 'Tayyorlanmoqda',
                                        'prepared' => 'Tayyor',
                                        'delivering' => 'Yetkazilmoqda',
                                        'delivered' => 'Yetkazildi',
                                        'cancelled' => 'Bekor qilingan',
                                        'completed' => 'Tugatilgan'
                                    ])
                                    ->default('active'),
                                Forms\Components\Toggle::make('is_sent_to_drivers')->label("Haydovchilarga chiqarilsinmi?"),

                            ])->columns(2),
                        Forms\Components\Tabs\Tab::make('Ovqatlar')
                            ->schema([

                                Forms\Components\Select::make('restaurant_id')
                                    ->label('Restoran')
                                    ->reactive()
                                    ->options(Restaurant::all()->pluck('name', 'id'))
                                    ->searchable(),

                                Forms\Components\Repeater::make('items')
                                    ->label("Ovqatlar")
                                    ->createItemButtonLabel("Yana +")
                                    ->schema([
                                        Select::make('category_id')
                                            ->label('Kategoriya')
                                            ->searchable()
                                            ->reactive()
                                            ->options(function (callable $get){
                                                $restaurant = Restaurant::find($get('../../restaurant_id'));
                                                if (!$restaurant) return [];
                                                return Category::where('restaurant_id', $restaurant->id)->with('translation_uz')->get()->pluck('translation_uz.name', 'id');
                                            })
                                            ->required(),
                                        Forms\Components\Select::make('product_id')
                                            ->searchable()
                                            ->options(function (callable $get){
                                                $restaurant = Restaurant::find($get('../../restaurant_id'));
                                                if (!$restaurant) return [];
                                                $category = Category::find($get('category_id'));
                                                if ($restaurant && !$category){
                                                    return Product::whereHas('category', function($q) use($restaurant) {
                                                        return $q->where('restaurant_id', $restaurant->id)->toSql();
                                                    })->with(['translation_uz'])->get()->pluck('translation_uz.name', 'id');
                                                }
                                                if ($restaurant && $category){
                                                    return Product::where('category_id', $category->id)->with(['translation_uz'])->get()->pluck('translation_uz.name', 'id');
                                                }
                                            })
                                            ->label('Mahsulot'),
                                        Forms\Components\TextInput::make('quantity')->label('Soni')->numeric(),
                                    ])
                                    ->columns(2)
                            ])
                    ])
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Yaratuvchi'),
                Tables\Columns\TextColumn::make('client.name')->label("Mijoz")->searchable(),
                Tables\Columns\TextColumn::make('driver.name')->label('Yetkazuvchi')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('restaurant.name')->label('Restoran')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('restaurant_operator.name')->label('Restoran xodimi')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('shipment')->label("Turi"),
                Tables\Columns\TextColumn::make('distance')->label('Masofa'),
                Tables\Columns\TextColumn::make('per_km_price')->label("Km($)"),
                Tables\Columns\TextColumn::make('shipping_price')->label("Yetkazish narxi"),

                Tables\Columns\TextColumn::make('status')->label('Holati')->sortable(),
                Tables\Columns\TextColumn::make('payment_type')->label("To'lov turi"),

                Tables\Columns\TextColumn::make('summary')->label("Umumiy summa"),
                Tables\Columns\TextColumn::make('phone_number')->label("Telefon")->searchable(),
                Tables\Columns\TextColumn::make('address')->label("Manzil")->searchable(),
//                Tables\Columns\TextColumn::make('longitude'),
//                Tables\Columns\TextColumn::make('latitude'),
                Tables\Columns\TextColumn::make('customer_note')->label("Mijoz xohshi"),
//                Tables\Columns\IconColumn::make('is_assigned_by_operator')
//                    ->boolean(),
//                Tables\Columns\IconColumn::make('is_accepted_order_by_driver')
//                    ->boolean(),
//                Tables\Columns\IconColumn::make('is_sent_to_drivers')
//                    ->boolean(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }    
}
