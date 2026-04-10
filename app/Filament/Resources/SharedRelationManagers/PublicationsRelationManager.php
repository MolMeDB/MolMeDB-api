<?php

namespace App\Filament\Resources\SharedRelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Hidden;
use Filament\Actions\CreateAction;
use Filament\Actions\AttachAction;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DetachAction;
use Filament\Actions\BulkActionGroup;
use App\Enums\IconEnums;
use App\Filament\Resources\Publications\PublicationResource;
use App\Models\Dataset;
use App\Models\Membrane;
use App\Models\Method;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PublicationsRelationManager extends RelationManager
{
    protected static string $relationship = 'publications';
    protected static ?string $title = 'References';
    protected static string | \BackedEnum | null $icon = IconEnums::PUBLICATIONS->value;

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

    public function form(Schema $schema): Schema
    {
        return PublicationResource::form($schema)
            ->components([
                ...$schema->getComponents(),
                Hidden::make('model_type')
                    ->default($this->ownerRecord::class),
                Hidden::make('model_id')
                    ->default($this->ownerRecord->id),
            ]);
    }

    public function table(Table $table): Table
    {
        return PublicationResource::table($table)
            ->description($this->getDescription())
            ->query(null)
            ->headerActions([
                CreateAction::make()
                    ->label('Add new publication')
                    ->icon(IconEnums::ADD->value),
                AttachAction::make()
                    ->label('Attach existing')
                    ->recordSelectSearchColumns(['citation', 'pmid'])
                    ->recordTitle(fn (Model $record) => $record->getSelectTitle())
                    ->schema(function (AttachAction $action) 
                    {
                       return [
                            $action->getRecordSelect(),
                            Hidden::make('model_type')
                                ->default($this->ownerRecord::class),
                       ];})
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->url(fn (Model $record) => PublicationResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
                DetachAction::make()
                    ->label('Detach')
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                ]),
            ]);
    }
}
