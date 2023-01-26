<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OperatorResource\Pages;
use App\Filament\Resources\OperatorResource\RelationManagers;
use App\Models\Operator;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OperatorResource extends Resource
{
    protected static ?string $model = Operator::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected static ?string $navigationGroup = "Xodimlar bo'limi";

    protected static ?string $navigationLabel = 'Operatorlar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
//                Forms\Components\TextInput::make('username')
//                    ->maxLength(255),
//                Forms\Components\TextInput::make('telegram_id')
//                    ->tel(),
                Forms\Components\TextInput::make('activation_code')
                    ->disabled(fn($get) => $get('activation_code_used'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone_number')
                    ->required()
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
                Tables\Columns\TextColumn::make('telegram_id'),
//                Tables\Columns\TextColumn::make('activation_code'),
                Tables\Columns\IconColumn::make('activation_code_used')
                    ->boolean(),
                Tables\Columns\IconColumn::make('status'),
//                Tables\Columns\TextColumn::make('temp_client_id'),
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
            'index' => Pages\ListOperators::route('/'),
            'create' => Pages\CreateOperator::route('/create'),
            'view' => Pages\ViewOperator::route('/{record}'),
            'edit' => Pages\EditOperator::route('/{record}/edit'),
        ];
    }    
}
