<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DriverResource\Pages;
use App\Filament\Resources\DriverResource\RelationManagers;
use App\Models\Driver;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DriverResource extends Resource
{
    protected static ?string $model = Driver::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected static ?string $navigationGroup = "Xodimlar bo'limi";

    protected static ?string $navigationLabel = 'Haydovchilar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('plate')
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone_number')
                    ->tel(),
                Forms\Components\TextInput::make('activation_code')
                    ->maxLength(255),
//                Forms\Components\Toggle::make('activation_code_used')
//                    ->required(),
                Select::make('status')
                    ->label('Holat')
                    ->options([
                        true => 'active',
                        false => 'inactive'
                    ])
                    ->rules(['boolean'])
                    ->default(true),
//                Forms\Components\TextInput::make('temp_client_id'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('username'),
//                Tables\Columns\TextColumn::make('telegram_id'),
                Tables\Columns\TextColumn::make('phone_number'),
                Tables\Columns\IconColumn::make('activation_code_used')
                    ->label("Aktivlashganmi?")
                    ->boolean(),
                Tables\Columns\IconColumn::make('status')->label("Holati")->boolean(),
                Tables\Columns\IconColumn::make('self_status')->label("Ishlayaptimi?")->boolean(),
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
            'index' => Pages\ListDrivers::route('/'),
            'create' => Pages\CreateDriver::route('/create'),
            'view' => Pages\ViewDriver::route('/{record}'),
            'edit' => Pages\EditDriver::route('/{record}/edit'),
        ];
    }    
}
