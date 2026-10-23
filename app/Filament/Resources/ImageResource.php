<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ImageResource\Pages;
use App\Models\Content\Image;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Number;

/** Diagrams. Files go straight into the app subdomain's media folder. */
class ImageResource extends Resource
{
    protected static ?string $model = Image::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $navigationLabel = 'Diagrams';

    protected static ?string $modelLabel = 'diagram';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(120)->disabledOn('edit')
                    ->helperText('Used in lessons as [IMAGE] name | Caption. Cannot be changed later.'),
                TextInput::make('alt')->label('Description')->maxLength(250)
                    ->helperText('Read out by screen readers when a lesson gives no caption.'),
                FileUpload::make('file')->label(fn (string $operation) => $operation === 'edit' ? 'Replace the file' : 'File')
                    ->required(fn (string $operation) => $operation === 'create')
                    ->disk('local')->directory('uploads')->visibility('private')
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/gif', 'image/svg+xml'])
                    ->maxSize(4096)
                    ->columnSpanFull()
                    ->helperText('PNG, JPEG, WebP, GIF or SVG, up to 4 MB. Large photos are resized; scripts are removed from SVGs.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('preview')->state(fn (Image $i) => $i->url())->imageHeight(48),
                TextColumn::make('name')->searchable()->weight('bold')->description(fn (Image $i) => $i->alt),
                TextColumn::make('tag')->label('Tag for lessons')->state(fn (Image $i) => $i->tag())->copyable()->fontFamily('mono')->size('sm'),
                TextColumn::make('size')->state(fn (Image $i) => trim(($i->width ? "{$i->width}×{$i->height} · " : '').Number::fileSize($i->size_bytes))),
                TextColumn::make('updated_at')->since()->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListImages::route('/'),
            'create' => Pages\CreateImage::route('/create'),
            'edit' => Pages\EditImage::route('/{record}/edit'),
        ];
    }
}
