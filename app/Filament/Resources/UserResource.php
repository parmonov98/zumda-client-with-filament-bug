<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\Partner;
use App\Models\PartnerOperator;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected static ?string $navigationGroup = "Xodimlar bo'limi";

    protected static ?string $navigationLabel = 'Foydalanuvchilar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('language')
                    ->options([
                        'uz' => "O'zbek",
                        'ru' => "Russian",
                    ])
                    ->required(),
                Forms\Components\Select::make('role')
                    ->options([
                        'operator' => "Operator",
                        'driver' => "Kurer",
                        'partner_operator' => "Restoran xodimi",
                        'partner' => "Restoran egasi",
                    ])
                    ->required(),
                Forms\Components\TextInput::make('email')
//                    ->unique('users')
                    ->email()
                    ->maxLength(255),
//                Forms\Components\DateTimePicker::make('email_verified_at'),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required()
                    ->maxLength(255),
//                Forms\Components\Textarea::make('two_factor_secret')
//                    ->maxLength(65535),
//                Forms\Components\Textarea::make('two_factor_recovery_codes')
//                    ->maxLength(65535),
//                Forms\Components\TextInput::make('current_team_id'),
//                Forms\Components\Select::make('administrator.name'),
                Forms\Components\Select::make('operator.name')
                    ->options(Operator::all(['id', 'name'])->pluck('name', 'id'))
                    ->label('Operator'),
                Forms\Components\Select::make('partner_operator.name')
                    ->options(PartnerOperator::all(['id', 'name'])->pluck('name', 'id'))
                    ->label('Restoran xodimi'),
                Forms\Components\Select::make('driver.name')
                    ->options(Driver::all(['id', 'name'])->pluck('name', 'id'))
                    ->label('Restoran xodimi'),

                Forms\Components\Toggle::make('status')
                    ->required(),
//                Forms\Components\Select::make('partner.name')
//                    ->options(Partner::all(['id', 'name'])->pluck('name', 'name'))
//                    ->label('Restoran egasi'),
//                Forms\Components\TextInput::make('profile_photo_path')
//                    ->maxLength(2048),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('last_step'),
                Tables\Columns\TextColumn::make('last_value'),
                Tables\Columns\TextColumn::make('language'),
                Tables\Columns\IconColumn::make('status')
                    ->boolean(),
                Tables\Columns\TextColumn::make('role'),
//                Tables\Columns\TextColumn::make('email'),
//                Tables\Columns\TextColumn::make('email_verified_at')
//                    ->dateTime(),
//                Tables\Columns\TextColumn::make('two_factor_secret'),
//                Tables\Columns\TextColumn::make('two_factor_recovery_codes'),
//                Tables\Columns\TextColumn::make('current_team_id'),
                Tables\Columns\TextColumn::make('administrator_id')->label('Admin'),
                Tables\Columns\TextColumn::make('operator_id')->label('Operator'),
//                Tables\Columns\TextColumn::make('partner_id')->label('Partnyor'),
                Tables\Columns\TextColumn::make('partner_operator_id')->label('Restoran xodimi'),
                Tables\Columns\TextColumn::make('driver_id')->label('Haydovichi'),
//                Tables\Columns\TextColumn::make('profile_photo_path'),
//                Tables\Columns\TextColumn::make('deleted_at')
//                    ->dateTime(),
//                Tables\Columns\TextColumn::make('created_at')
//                    ->dateTime(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }    
}
