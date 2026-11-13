<?php

namespace Database\Seeders;

use App\Enums\ComplaintReason;
use App\Enums\PickupStatus;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Models\PickupRequest;
use App\Models\Report;
use App\Models\RouteStopVisit;
use App\Models\Truck;
use App\Models\TruckComplaint;
use App\Models\User;
use App\Models\Village;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Données de démonstration autour de Moroni (coordonnées OpenStreetMap).
 * Mot de passe de tous les comptes : « password ».
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        $villages = collect([
            ['Badjanani', -11.70378, 43.25198],
            ['Hadouja', -11.69306, 43.25821],
            ['Itsandra', -11.67706, 43.26470],
            ['Ntsoudjini', -11.65574, 43.27820],
            ['Mvouni', -11.71993, 43.27467],
            ['Mdé', -11.73868, 43.25103],
            ['Iconi', -11.74519, 43.23776],
            ['Vouvouni', -11.75095, 43.25189],
        ])->mapWithKeys(fn (array $v): array => [$v[0] => Village::create(['name' => $v[0], 'latitude' => $v[1], 'longitude' => $v[2]])]);

        // Un camion par village choisi par la mairie (Ntsoudjini et Vouvouni n'en ont pas encore).
        $trucks = collect([
            ['Camion 1', 'Badjanani', '#0f766e', [1, 2, 3, 4, 5, 6], '06:00', '09:00', 'Ali Mohamed', 'agent'],
            ['Camion 2', 'Hadouja', '#2563eb', [1, 3, 5], '06:30', '08:30', 'Saïd Ahmed', 'agent2'],
            ['Camion 3', 'Itsandra', '#9333ea', [2, 4, 6], '07:00', '09:00', 'Moussa Soilihi', 'agent3'],
            ['Camion 4', 'Mvouni', '#c2410c', [1, 4], '08:00', '10:00', 'Youssouf Mbaé', 'agent4'],
            ['Camion 5', 'Mdé', '#0891b2', [2, 5], '06:30', '08:00', 'Hamidou Ali', 'agent5'],
            ['Camion 6', 'Iconi', '#be185d', [3, 6], '07:00', '09:30', 'Abdou Hassani', 'agent6'],
        ])->mapWithKeys(function (array $t, int $i) use ($villages, $password): array {
            [$name, $villageName, $color, $days, $start, $end, $agentName, $login] = $t;
            $village = $villages[$villageName];
            $truck = Truck::create([
                'name' => $name, 'plate' => 'CMR-'.(101 + $i), 'village_id' => $village->id, 'color' => $color,
            ]);
            foreach ($days as $day) {
                $truck->schedules()->create(['day_of_week' => $day, 'start_time' => $start, 'end_time' => $end]);
            }
            // Tournée : une boucle de points de collecte autour du village.
            foreach ([[0.0016, -0.0010], [0.0022, 0.0012], [0.0006, 0.0026], [-0.0012, 0.0020], [-0.0020, 0.0002], [-0.0008, -0.0016]] as $n => [$dLat, $dLng]) {
                $truck->stops()->create([
                    'position' => $n + 1,
                    'label' => ['Place du village', 'Mosquée', 'École', 'Marché', 'Grand carrefour', 'Bord de mer'][$n],
                    'latitude' => $village->latitude + $dLat,
                    'longitude' => $village->longitude + $dLng,
                ]);
            }
            User::create([
                'name' => $agentName, 'email' => "{$login}@cleancomores.km", 'password' => $password,
                'role' => Role::Agent, 'truck_id' => $truck->id, 'phone' => '+269 34'.(10 + $i).' 20 30',
            ]);

            return [$villageName => $truck];
        });

        User::create(['name' => 'Administrateur', 'email' => 'admin@cleancomores.km', 'password' => $password, 'role' => Role::Admin]);
        User::create(['name' => 'Fatima Saïd', 'email' => 'responsable@cleancomores.km', 'password' => $password, 'role' => Role::Responsable]);

        $citizen = User::create([
            'name' => 'Nadia Abdallah', 'email' => 'citoyen@cleancomores.km', 'password' => $password,
            'role' => Role::Citoyen, 'village_id' => $villages['Badjanani']->id, 'phone' => '+269 321 45 67',
        ]);
        $neighbours = collect(['Ahmed Combo', 'Faïza Mohamed', 'Ibrahim Mzé', 'Anrifa Saïd', 'Kamal Bacar'])
            ->map(fn (string $name): User => User::create([
                'name' => $name, 'email' => str($name)->slug('.').'@exemple.km', 'password' => $password, 'role' => Role::Citoyen,
            ]));

        // Signalements : [village, auteur, statut, décalage lat/lng, âge en jours, nettoyage prévu (jours, heure)]
        $reports = [
            ['Badjanani', $citizen, ReportStatus::Nouveau, [0.0011, 0.0019], 0, null],
            ['Badjanani', $citizen, ReportStatus::PrisEnCharge, [-0.0017, 0.0008], 1, [0, '14:30']],
            ['Badjanani', $neighbours[0], ReportStatus::EnCours, [0.0004, -0.0021], 1, [0, '08:00']],
            ['Badjanani', $citizen, ReportStatus::Nettoye, [-0.0006, 0.0028], 6, [-4, '09:00']],
            ['Hadouja', $neighbours[1], ReportStatus::Nouveau, [0.0012, -0.0009], 0, null],
            ['Hadouja', $neighbours[1], ReportStatus::PrisEnCharge, [-0.0010, 0.0015], 2, [1, '09:00']],
            ['Itsandra', $neighbours[2], ReportStatus::Nouveau, [0.0008, 0.0012], 1, null],
            ['Itsandra', $neighbours[2], ReportStatus::Nettoye, [-0.0014, -0.0006], 9, [-7, '10:00']],
            ['Mvouni', $neighbours[3], ReportStatus::PrisEnCharge, [0.0009, -0.0013], 1, [2, '08:30']],
            ['Mdé', $neighbours[4], ReportStatus::Nouveau, [-0.0011, 0.0010], 0, null],
            ['Iconi', $neighbours[3], ReportStatus::EnCours, [0.0013, 0.0007], 2, [0, '07:30']],
            ['Ntsoudjini', $neighbours[4], ReportStatus::Nouveau, [0.0006, -0.0012], 3, null],
            ['Vouvouni', $citizen, ReportStatus::Rejete, [0.0010, 0.0010], 5, null],
        ];
        $descriptions = [
            'Tas d\'ordures au bord de la route depuis plusieurs jours.',
            'Sacs éventrés par les chats, odeurs fortes.',
            'Dépôt sauvage derrière l\'école.',
            'Déchets accumulés près de la plage.',
        ];

        foreach ($reports as $i => [$villageName, $author, $status, [$dLat, $dLng], $age, $cleanup]) {
            $village = $villages[$villageName];
            $truck = $trucks[$villageName] ?? null;
            $cleanupAt = $cleanup ? today()->addDays($cleanup[0])->setTimeFromTimeString($cleanup[1]) : null;
            $created = now()->subDays($age)->subHours(2 + $i);

            Report::forceCreate([
                'user_id' => $author->id,
                'village_id' => $village->id,
                'description' => $descriptions[$i % count($descriptions)],
                'latitude' => $village->latitude + $dLat,
                'longitude' => $village->longitude + $dLng,
                'status' => $status,
                'truck_id' => $cleanup ? $truck?->id : null,
                'cleanup_at' => $cleanupAt,
                'cleaned_at' => $status === ReportStatus::Nettoye ? $cleanupAt?->copy()->addHour() : null,
                'admin_note' => $status === ReportStatus::Rejete ? 'Doublon d\'un signalement déjà traité.' : null,
                'created_at' => $created,
                'updated_at' => $created,
            ]);
        }

        PickupRequest::create([
            'user_id' => $citizen->id, 'village_id' => $villages['Badjanani']->id,
            'description' => 'Déménagement : vieux canapé et deux matelas.',
            'latitude' => -11.7031, 'longitude' => 43.2531, 'preferred_date' => today()->addDays(2),
            'status' => PickupStatus::Acceptee, 'truck_id' => $trucks['Badjanani']->id,
            'appointment_at' => today()->addDay()->setTime(10, 0),
            'admin_note' => 'Merci de sortir les objets devant le portail.',
        ]);
        PickupRequest::create([
            'user_id' => $citizen->id, 'village_id' => $villages['Badjanani']->id,
            'description' => 'Branches après l\'élagage de deux manguiers.',
            'latitude' => -11.7045, 'longitude' => 43.2528, 'preferred_date' => today()->addDays(4),
            'status' => PickupStatus::EnAttente,
        ]);
        PickupRequest::create([
            'user_id' => $neighbours[0]->id, 'village_id' => $villages['Hadouja']->id,
            'description' => 'Une trentaine de sacs après une fête de mariage.',
            'latitude' => -11.6925, 'longitude' => 43.2590, 'status' => PickupStatus::EnAttente,
        ]);

        TruckComplaint::create([
            'user_id' => $neighbours[1]->id, 'truck_id' => $trucks['Hadouja']->id,
            'reason' => ComplaintReason::Retard, 'message' => 'Le camion n\'est pas passé mercredi.',
        ]);
        TruckComplaint::create([
            'user_id' => $citizen->id, 'truck_id' => $trucks['Badjanani']->id,
            'reason' => ComplaintReason::Comportement, 'message' => 'Des sacs sont tombés du camion et n\'ont pas été ramassés.',
        ]);

        // L'agent du camion 1 a déjà validé les deux premiers points de sa tournée aujourd'hui.
        $agent = User::where('email', 'agent@cleancomores.km')->first();
        if ($trucks['Badjanani']->scheduleOn(today())) {
            foreach ($trucks['Badjanani']->stops()->limit(2)->get() as $stop) {
                RouteStopVisit::create(['route_stop_id' => $stop->id, 'user_id' => $agent->id, 'visit_date' => today()]);
            }
        }
    }
}
