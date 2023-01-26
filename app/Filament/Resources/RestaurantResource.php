<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RestaurantResource\Pages;
use App\Filament\Resources\RestaurantResource\RelationManagers;
use App\Models\Partner;
use App\Models\PartnerOperator;
use App\Models\Restaurant;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RestaurantResource extends Resource
{
    protected static ?string $model = Restaurant::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected static ?string $navigationGroup = 'Restoranlar va menyular';

    protected static ?string $navigationLabel = 'Restoranlar';
//
//    public static function getNavigationSort(): ?int
//    {
//        return 1;
//    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
//                Forms\Components\Select::make('partner_id')
//                    ->label('Restaurant Owner')
//                    ->options(Partner::all()->pluck('name', 'id')),
//                Forms\Components\Select::make('operator_id')
//                    ->label('Primary operator')
//                    ->options(PartnerOperator::all()->pluck('name', 'id')),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('address')
                    ->maxLength(255),
                Forms\Components\TextInput::make('latitude')
                    ->maxLength(255),
                Forms\Components\TextInput::make('longitude')
                    ->maxLength(255),
                Forms\Components\TextInput::make('payment_card')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('expiration_date')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('phone_number')
                    ->tel()
                    ->maxLength(255),
                Select::make('status')
                    ->label('Holat')
                    ->options([
                        true => 'active',
                        false => 'inactive'
                    ])
                    ->rules(['boolean'])
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
//                Tables\Columns\TextColumn::make('partner_id'),
//                Tables\Columns\TextColumn::make('operator_id'),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('address')->searchable(),
//                Tables\Columns\TextColumn::make('latitude'),
//                Tables\Columns\TextColumn::make('longitude'),
//                Tables\Columns\TextColumn::make('phone_number'),
                Tables\Columns\IconColumn::make('status')
                    ->label('Holati')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
//                Tables\Columns\TextColumn::make('updated_at')
//                    ->dateTime(),
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
            'index' => Pages\ListRestaurants::route('/'),
            'create' => Pages\CreateRestaurant::route('/create'),
            'view' => Pages\ViewRestaurant::route('/{record}'),
            'edit' => Pages\EditRestaurant::route('/{record}/edit'),
        ];
    }
}
