<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
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

class CategoryResource extends Resource
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $model = Category::class;

    protected static ?string $navigationGroup = 'Restoranlar va menyular';

    protected static ?string $navigationLabel = 'Kategoriyalar';

    protected static ?string $navigationIcon = 'heroicon-o-collection';

//    public static function getNavigationSort(): ?int
//    {
//        return 3;
//    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Hidden::make('user_id')
                    ->label("Yaratuvchi")
                    ->default(auth()->id())
                    ->disabled()
                    ->required(),

                Select::make('restaurant_id')
                    ->label('Restoran')
                    ->options(Restaurant::get()->pluck('name', 'id'))
                    ->required(),

                Select::make('common_category_id')
                    ->label('Umumiy kategoriya')
                    ->options(CommonCategory::with(['translation_uz'])->get()->pluck('translation_uz.name', 'id')),

                Tabs::make('Product Tabs')
                    ->tabs([

                        Tab::make("uz")
                            ->label('O`zbek')
                            ->schema([

                                TextInput::make('translations.uz.name')
                                    ->label('Nomi')
                                    ->required(),


                                MarkdownEditor::make('translations.uz.description')
                                    ->toolbarButtons([
                                        'bold',
                                        'codeBlock',
                                        'edit',
                                        'italic',
                                        'link',
                                        'preview',
                                        'strike',
                                    ]),

                            ]),
                        Tab::make("ru")
                            ->label("Ruscha")
                            ->schema([

                                TextInput::make('translations.ru.name')
                                    ->label('Nomi')
                                    ->required(),


                                MarkdownEditor::make('translations.ru.description')
                                    ->toolbarButtons([
                                        'bold',
                                        'codeBlock',
                                        'edit',
                                        'italic',
                                        'link',
                                        'preview',
                                        'strike',
                                    ]),
                            ])
                    ]),
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
//        dd(Restaurant::all('id', 'name')->pluck('name', 'id'));
        return $table
            ->columns([
//                Tables\Columns\TextColumn::make('translation_ru.name')->searchable(),
                Tables\Columns\TextColumn::make('translation_uz.name')
                    ->label('Nomi(uz)')
                    ->searchable(),
                Tables\Columns\TextColumn::make('restaurant.name')
                    ->label('Restoran')
                    ->searchable(),
                Tables\Columns\TextColumn::make('common_category.translation_uz.name')->label('Umumiy kategoriya'),
//                Tables\Columns\TextColumn::make('parent.translation.name'),
//                Tables\Columns\TextColumn::make('user.name'),
                Tables\Columns\IconColumn::make('status')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
//                Tables\Columns\TextColumn::make('updated_at')
//                    ->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('restaurant')
                    ->relationship('restaurant', 'name')
                    ->label('Restoran')
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'view' => Pages\ViewCategory::route('/{record}'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }    
}
