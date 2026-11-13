<?php

namespace App\Filament\Resources\Trucks\Pages;

use App\Filament\Resources\Trucks\TruckResource;
use App\Models\RouteStop;
use App\Models\Truck;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Le responsable dessine la tournée du camion : il ajoute, déplace, réordonne et supprime
 * les points de collecte directement sur la carte. L'agent voit ces points dans « Ma tournée ».
 */
class EditTruckRoute extends Page
{
    use InteractsWithRecord;

    protected static string $resource = TruckResource::class;

    protected string $view = 'filament.resources.trucks.pages.edit-truck-route';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Itinéraire du '.$this->truck()->name;
    }

    public function addStop(float $latitude, float $longitude): void
    {
        $this->truck()->stops()->create([
            'position' => (int) $this->truck()->stops()->max('position') + 1,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);

        $this->refreshMap();
    }

    public function moveStop(int $stopId, float $latitude, float $longitude): void
    {
        $this->findStop($stopId)->update(['latitude' => $latitude, 'longitude' => $longitude]);

        $this->refreshMap();
    }

    public function renameStop(int $stopId, string $label): void
    {
        $this->findStop($stopId)->update(['label' => mb_substr(trim($label), 0, 100) ?: null]);

        $this->refreshMap();
    }

    public function moveStopUp(int $stopId): void
    {
        $this->swapWithNeighbour($this->findStop($stopId), -1);
    }

    public function moveStopDown(int $stopId): void
    {
        $this->swapWithNeighbour($this->findStop($stopId), 1);
    }

    public function deleteStop(int $stopId): void
    {
        $this->findStop($stopId)->delete();

        // Renumérote pour garder un ordre continu 1, 2, 3…
        $this->truck()->stops()->get()->each(fn (RouteStop $stop, int $index) => $stop->update(['position' => $index + 1]));

        $this->refreshMap();
    }

    /**
     * @return list<array{id: int, n: int, label: string, lat: float, lng: float}>
     */
    public function stopsPayload(): array
    {
        return $this->truck()->stops()->get()
            ->map(fn (RouteStop $stop): array => [
                'id' => $stop->id,
                'n' => $stop->position,
                'label' => $stop->displayName(),
                'lat' => $stop->latitude,
                'lng' => $stop->longitude,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'truck' => $this->truck(),
            'stops' => $this->truck()->stops()->get(),
        ];
    }

    private function truck(): Truck
    {
        /** @var Truck */
        return $this->getRecord();
    }

    private function findStop(int $stopId): RouteStop
    {
        return $this->truck()->stops()->findOrFail($stopId);
    }

    private function swapWithNeighbour(RouteStop $stop, int $direction): void
    {
        $neighbour = $this->truck()->stops()->where('position', $stop->position + $direction)->first();

        if ($neighbour) {
            [$a, $b] = [$stop->position, $neighbour->position];
            $stop->update(['position' => $b]);
            $neighbour->update(['position' => $a]);
        }

        $this->refreshMap();
    }

    private function refreshMap(): void
    {
        $this->dispatch('route-updated', stops: $this->stopsPayload());
    }
}
