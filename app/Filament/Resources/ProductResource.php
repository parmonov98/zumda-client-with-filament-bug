<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Category;
use App\Models\CommonCategory;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantDish;
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

class ProductResource extends Resource
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected static ?string $navigationGroup = 'Restoranlar va menyular';

    protected static ?string $navigationLabel = 'Mahsulotlar';

//    public static function getNavigationSort(): ?int
//    {
//        return 4;
//    }

    public static function form(Form $form): Form
    {
        app()->setLocale('uz');
        return $form
            ->schema([
                Tabs::make('Product Page')->tabs([
                    Tab::make('Ovqat ma`lumotlari')
                        ->schema([
                            Hidden::make('user_id')
                                ->label("Yaratuvchi")
                                ->default(auth()->id())
                                ->disabled()
                                ->required(),

                            Forms\Components\Grid::make()->schema([

                                Select::make('restaurant_id')
                                    ->label('Restoran')
                                    ->options(Restaurant::get()->pluck('name', 'id'))
                                    ->reactive()
                                    ->required(),

                                Select::make('category_id')
                                    ->label('Kategoriya')
                                    ->options(function (callable $get){
                                        $restaurant = Restaurant::find($get('restaurant_id'));
                                        if (!$restaurant) return [];
                                        return Category::where('restaurant_id', $restaurant->id)->with('translation_uz')->get()->pluck('translation_uz.name', 'id');
                                    })
                                    ->required(),

                            ])->columns(2),

                            Tabs::make('translations')
                                ->tabs([

                                    Tab::make("uz")
                                        ->label('O`zbek')
                                        ->schema([

                                            TextInput::make('translations.uz.name')
                                                ->label('Nomi')
                                                ->required(),


                                            MarkdownEditor::make('translations.uz.description')
                                                ->label("Ta'rifi")
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
                                                ->label("Ta'rifi")
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

                            Forms\Components\Grid::make()->schema([


                                Forms\Components\Checkbox::make('has_required_dish')
                                    ->reactive()
                                    ->label('Majburiy idish bormi?'),

                                Forms\Components\Checkbox::make('has_options')
                                    ->reactive()
                                    ->label('Turlari bormi?'),

                            ])->columns(2),


                            Forms\Components\Grid::make()->schema([
                                TextInput::make('price')
                                    ->label('Narx')
                                    ->required(),
                                TextInput::make('preparation_time')
                                    ->label('Tayyorlash vaqti(min)')
                                    ->required(),
                                TextInput::make('profit_in_percentage')
                                    ->label('Foyda %')
                                    ->required(),
                            ])->columns(2),


                            TextInput::make('photo_id')
                                ->maxLength(255),

                            Select::make('status')
                                ->label('Holat')
                                ->options([
                                    true => 'active',
                                    false => 'inactive'
                                ])
                                ->rules(['boolean'])
                                ->default(true),


                        ]),
                    Tab::make('Idishlar')->schema([
                        Forms\Components\Repeater::make('dishes')
                            ->schema([
                                Select::make('dish_id')->options(
                                    RestaurantDish::all()->pluck('name', 'id')
                                )->required(),
                                TextInput::make('quantity')
                                    ->default(1)
                                    ->required(),
                            ])
                            ->columns(2)

                    ])->visible(function ($get){
                        return $get('has_required_dish');
                    }),
                    Tab::make('Turlari')->schema([
                        Forms\Components\Repeater::make('options')
                            ->schema([
                                TextInput::make('name')->required(),
                                TextInput::make('price')
                                    ->default(1)
                                    ->rules(['numeric'])
                                    ->required(),
                                TextInput::make('photo_id')
                                    ->required(),
                            ])
                            ->columns(2)

                    ])->visible(function ($get){
                        return $get('has_options');
                    })
                ])
            ])
            ->columns(1);

    }

    public static function table(Table $table): Table
    {
        app()->setLocale('uz');
        return $table
            ->columns([
//                Tables\Columns\TextColumn::make('user.name')->label('Yaratuvchi'),
                Tables\Columns\TextColumn::make('restaurant.name')->label('Restoran')->sortable(),
                Tables\Columns\TextColumn::make('category.translation.name')->label('Kategoriya'),
                Tables\Columns\TextColumn::make('translation_uz.name')->label('Nomi')->searchable(),
                Tables\Columns\TextColumn::make('price')->label('Narxi')->sortable(),

                Tables\Columns\IconColumn::make('status')
                    ->label('Holati')
                    ->sortable()
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('restaurant')
                    ->relationship('restaurant', 'name')
                    ->label('Restoran'),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }    
}
