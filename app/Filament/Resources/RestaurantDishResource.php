<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RestaurantDishResource\Pages;
use App\Filament\Resources\RestaurantDishResource\RelationManagers;
use App\Models\PartnerOperator;
use App\Models\Restaurant;
use App\Models\RestaurantDish;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RestaurantDishResource extends Resource
{
    protected static ?string $model = RestaurantDish::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected static ?string $navigationGroup = 'Restoranlar va menyular';

    protected static ?string $navigationLabel = 'Restoran idishlari';

//    public static function getNavigationSort(): ?int
//    {
//        return 1;
//    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->minLength(1)
                    ->maxLength(255),
                Forms\Components\Select::make('restaurant_id')
                    ->label('Restoran')
                    ->required()
                    ->options(Restaurant::all()->pluck('name', 'id')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Idish')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('restaurant.name')->label('Restoran')->sortable(),
//                Tables\Columns\TextColumn::make('created_at')->sortable()
//                    ->dateTime(),
//                Tables\Columns\TextColumn::make('updated_at')
//                    ->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('restaurant')
                    ->relationship('restaurant', 'name')
                    ->label('Restoran')
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageRestaurantDishes::route('/'),
        ];
    }    
}
