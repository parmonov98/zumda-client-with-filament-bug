<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommonCategoryResource\Pages;
use App\Filament\Resources\CommonCategoryResource\RelationManagers;
use App\Models\CommonCategory;
use App\Models\Restaurant;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CommonCategoryResource extends Resource
{
    protected static ?string $model = CommonCategory::class;

    protected static ?string $navigationGroup = 'Restoranlar va menyular';

    protected static ?string $navigationLabel = 'Umumiy kategoriyalar';

    protected static ?string $navigationIcon = 'heroicon-o-collection';


//    public static function getNavigationSort(): ?int
//    {
//        return 2;
//    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Tabs::make('Kategoriya Tabs')
                    ->tabs([

                        Tab::make("uz")
                            ->label('O`zbek')
                            ->schema([

                                TextInput::make('translations.uz.name')
                                    ->label('Nomi')
                                    ->required(),


                            ]),
                        Tab::make("ru")
                            ->label("Ruscha")
                            ->schema([

                                TextInput::make('translations.ru.name')
                                    ->label('Nomi')
                                    ->required(),


                            ])
                    ]),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('translation_uz.name'),
                Tables\Columns\TextColumn::make('translation_ru.name'),
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
            'index' => Pages\ListCommonCategories::route('/'),
            'create' => Pages\CreateCommonCategory::route('/create'),
            'view' => Pages\ViewCommonCategory::route('/{record}'),
            'edit' => Pages\EditCommonCategory::route('/{record}/edit'),
        ];
    }    
}
