<?php

namespace App\Filament\Resources\SubjectResource\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Which boards examine this subject, with each board's specification code. */
class BoardsRelationManager extends RelationManager
{
    protected static string $relationship = 'boards';

    protected static ?string $title = 'Exam boards';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('spec_code')->label('Specification code')->maxLength(40)->placeholder('8461'),
            TextInput::make('spec_url')->label('Specification link')->url()->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->weight('bold'),
                TextColumn::make('spec_code')->label('Specification'),
                TextColumn::make('spec_url')->label('Link')->limit(40)->url(fn ($state) => $state, true),
            ])
            ->headerActions([
                AttachAction::make()->preloadRecordSelect()->schema(fn (AttachAction $action) => [
                    $action->getRecordSelect(),
                    TextInput::make('spec_code')->label('Specification code')->maxLength(40),
                    TextInput::make('spec_url')->label('Specification link')->url(),
                ]),
            ])
            ->recordActions([EditAction::make(), DetachAction::make()]);
    }
}
