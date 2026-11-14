<?php

namespace App\Filament\Resources\Dues;

use App\Enums\DueStatus;
use App\Filament\Resources\Dues\Pages\ManageDues;
use App\Models\Due;
use App\Models\Village;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Suivi des redevances : qui a payé, qui doit encore, combien a été encaissé. Lecture seule :
 * un dû n'est créé que par un passage du camion et ne passe « payé » que par la passerelle.
 */
class DueResource extends Resource
{
    protected static ?string $model = Due::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $slug = 'redevances';

    protected static ?string $navigationLabel = 'Redevances';

    protected static ?string $modelLabel = 'redevance';

    protected static ?string $pluralModelLabel = 'redevances';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user', 'passage.village', 'passage.truck']))
            ->columns([
                TextColumn::make('user.name')->label('Habitant')->description(fn (Due $record): ?string => $record->user?->phone)->searchable(),
                TextColumn::make('passage.village.name')->label('Village'),
                TextColumn::make('passage.passed_on')->label('Passage du')->date('d/m/Y')->description(fn (Due $record): ?string => $record->passage?->truck?->name),
                TextColumn::make('amount')->label('Montant')->suffix(' KMF')->summarize(Sum::make()->label('Total')->suffix(' KMF')),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('receipt_code')->label('Preuve')->placeholder('—')->copyable()->searchable(),
                TextColumn::make('paid_at')->label('Payée le')->dateTime('d/m H:i')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(DueStatus::class),
                SelectFilter::make('village')
                    ->label('Village')
                    ->options(fn () => Village::orderBy('name')->pluck('name', 'id'))
                    ->query(fn (Builder $query, array $data) => $data['value']
                        ? $query->whereHas('passage', fn (Builder $passage) => $passage->where('village_id', $data['value']))
                        : $query),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDues::route('/'),
        ];
    }
}
