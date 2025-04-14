<?php

namespace App\Filament\Resources\SharedRelationManagers;

use App\Enums\IconEnums;
use App\Filament\Resources\PublicationResource;
use App\Models\Dataset;
use App\Models\Membrane;
use App\Models\Method;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PublicationsRelationManager extends RelationManager
{
    protected static string $relationship = 'publications';
    protected static ?string $title = 'References';
    protected static ?string $icon = IconEnums::PUBLICATIONS->value;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return match($ownerRecord::class) {
            Dataset::class => 'Secondary publications',
            default => self::$title
        };
    }

    private function getDescription() : string {
        return match($this->ownerRecord::class) {
            Method::class => 'Method\'s references. Warning! Not related to assigned datasets or interactions.',
            Membrane::class => 'Membrane\'s references. Warning! Not related to assigned datasets or interactions.',
            default => ''
        };
    }

    public function form(Form $form): Form
    {
        return PublicationResource::form($form)
            ->schema([
                ...$form->getComponents(),
                Forms\Components\Hidden::make('model_type')
                    ->default($this->ownerRecord::class),
                Forms\Components\Hidden::make('model_id')
                    ->default($this->ownerRecord->id),
            ]);
    }

    public function table(Table $table): Table
    {
        return PublicationResource::table($table)
            ->description($this->getDescription())
            ->query(null)
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add new publication')
                    ->icon(IconEnums::ADD->value),
                Tables\Actions\AttachAction::make()
                    ->label('Attach existing')
                    ->recordSelectSearchColumns(['citation', 'pmid'])
                    ->recordTitle(fn (Model $record) => $record->getSelectTitle())
                    ->form(function (AttachAction $action) 
                    {
                       return [
                            $action->getRecordSelect(),
                            Forms\Components\Hidden::make('model_type')
                                ->default($this->ownerRecord::class),
                       ];})
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->url(fn (Model $record) => PublicationResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
                Tables\Actions\DetachAction::make()
                    ->label('Detach')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ]);
    }
}
