<?php

namespace Database\Seeders;

use App\Enums\AssociationStatus;
use App\Enums\ComplaintReason;
use App\Enums\MembershipStatus;
use App\Enums\NoticeKind;
use App\Enums\PickupStatus;
use App\Enums\PostType;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Models\Association;
use App\Models\PickupRequest;
use App\Models\Report;
use App\Models\RouteStopVisit;
use App\Models\Truck;
use App\Models\TruckComplaint;
use App\Models\TruckNotice;
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

        // Chaque camion dessert plusieurs villages, à heures fixes.
        $plan = [
            ['Camion 1', '#1d6b47', 'Ali Mohamed', 'agent', [
                ['Badjanani', [1, 2, 3, 4, 5, 6], '06:00', '07:30'],
                ['Hadouja', [1, 2, 3, 4, 5, 6], '08:00', '09:30'],
            ]],
            ['Camion 2', '#b4532a', 'Saïd Ahmed', 'agent2', [
                ['Itsandra', [2, 4, 6], '07:00', '08:30'],
                ['Ntsoudjini', [2, 4, 6], '09:00', '10:00'],
            ]],
            ['Camion 3', '#2563a8', 'Moussa Soilihi', 'agent3', [
                ['Mvouni', [1, 4], '08:00', '10:00'],
            ]],
            ['Camion 4', '#0e7490', 'Youssouf Mbaé', 'agent4', [
                ['Mdé', [2, 5], '06:30', '08:00'],
                ['Iconi', [2, 5], '08:30', '10:00'],
                ['Vouvouni', [3, 6], '07:00', '08:30'],
            ]],
        ];

        $trucks = [];
        foreach ($plan as $i => [$name, $color, $agentName, $login, $slots]) {
            $truck = Truck::create(['name' => $name, 'plate' => 'CMR-'.(101 + $i), 'color' => $color]);
            $position = 1;
            foreach ($slots as [$villageName, $days, $start, $end]) {
                $village = $villages[$villageName];
                $truck->villages()->syncWithoutDetaching([$village->id]);
                foreach ($days as $day) {
                    $truck->schedules()->create(['village_id' => $village->id, 'day_of_week' => $day, 'start_time' => $start, 'end_time' => $end]);
                }
                foreach ([['Place du village', 0.0012, -0.0008], ['Mosquée', 0.0004, 0.0018], ['École', -0.0014, 0.0006]] as [$label, $dLat, $dLng]) {
                    $truck->stops()->create([
                        'position' => $position++,
                        'label' => "{$label} · {$villageName}",
                        'latitude' => $village->latitude + $dLat,
                        'longitude' => $village->longitude + $dLng,
                    ]);
                }
            }
            User::create([
                'name' => $agentName, 'email' => "{$login}@cleancomores.km", 'password' => $password,
                'role' => Role::Agent, 'truck_id' => $truck->id, 'phone' => '+269 34'.(10 + $i).' 20 30',
            ]);
            $trucks[$name] = $truck;
        }

        User::create(['name' => 'Administrateur', 'email' => 'admin@cleancomores.km', 'password' => $password, 'role' => Role::Admin]);
        User::create(['name' => 'Fatima Saïd', 'email' => 'responsable@cleancomores.km', 'password' => $password, 'role' => Role::Responsable]);

        $citizen = User::create([
            'name' => 'Nadia Abdallah', 'email' => 'citoyen@cleancomores.km', 'password' => $password,
            'village_id' => $villages['Badjanani']->id, 'phone' => '+269 321 45 67',
        ]);
        $ahmed = User::create(['name' => 'Ahmed Combo', 'email' => 'asso@cleancomores.km', 'password' => $password, 'phone' => '+269 332 10 10']);
        [$faiza, $ibrahim, $anrifa, $kamal] = collect(['Faïza Mohamed', 'Ibrahim Mzé', 'Anrifa Saïd', 'Kamal Bacar'])
            ->map(fn (string $name): User => User::create(['name' => $name, 'email' => str($name)->slug('.').'@exemple.km', 'password' => $password]))
            ->all();

        // Associations : deux actives, une demande en attente de la mairie.
        $moroniPropre = Association::create([
            'name' => 'Moroni Propre', 'leader_id' => $ahmed->id, 'village_id' => $villages['Badjanani']->id,
            'status' => AssociationStatus::Active, 'color' => '#1d6b47',
            'description' => 'Des voisins de Badjanani et Hadouja qui nettoient les rues et les ravines chaque samedi matin. Gants et sacs fournis.',
        ]);
        $itsandra = Association::create([
            'name' => 'Itsandra Plage Nette', 'leader_id' => $faiza->id, 'village_id' => $villages['Itsandra']->id,
            'status' => AssociationStatus::Active, 'color' => '#0e7490',
            'description' => 'On garde la plage d\'Itsandra belle : ramassage des plastiques après chaque marée et sensibilisation des enfants.',
        ]);
        Association::create([
            'name' => 'Les Jeunes de Mvouni', 'leader_id' => $kamal->id, 'village_id' => $villages['Mvouni']->id,
            'status' => AssociationStatus::EnAttente,
            'description' => 'Un groupe de jeunes du village pour nettoyer les abords du marché et la route de la Grande Comore.',
            'motivation' => 'Nous sommes déjà 12 volontaires et nous nettoyons chaque mois depuis un an.',
        ]);

        foreach ([[$moroniPropre, $ahmed, 'membre'], [$moroniPropre, $ibrahim, 'membre'], [$moroniPropre, $anrifa, 'membre'], [$moroniPropre, $citizen, 'en_attente'],
            [$itsandra, $faiza, 'membre'], [$itsandra, $citizen, 'membre'], [$itsandra, $kamal, 'en_attente']] as [$association, $user, $status]) {
            $association->memberships()->create(['user_id' => $user->id, 'status' => MembershipStatus::from($status)]);
        }

        $saturday = today()->next('Saturday')->setTime(8, 0);
        $posts = [
            [$moroniPropre, $ahmed, PostType::Annonce, 'Grand nettoyage de la place Badjanani ce samedi ! Rendez-vous devant la mosquée, on fournit gants et sacs. Venez avec vos enfants 💪', $saturday, 3],
            [$moroniPropre, $ahmed, PostType::Publication, 'Samedi dernier : 45 sacs ramassés derrière l\'école primaire en deux heures. Merci aux 18 volontaires présents, le quartier respire !', null, 30],
            [$itsandra, $faiza, PostType::Annonce, 'Nettoyage de la plage dimanche à 7h, avant la chaleur. Point de rencontre : le parking de l\'hôtel.', today()->next('Sunday')->setTime(7, 0), 20],
            [$itsandra, $faiza, PostType::Publication, 'Après la grande marée, on a retiré plus de 300 bouteilles plastiques de la plage. Pensez à garder vos déchets jusqu\'à une poubelle 🙏', null, 50],
        ];
        foreach ($posts as [$association, $author, $type, $body, $eventAt, $hoursAgo]) {
            $association->posts()->forceCreate([
                'association_id' => $association->id, 'user_id' => $author->id, 'type' => $type, 'body' => $body,
                'event_at' => $eventAt, 'created_at' => now()->subHours($hoursAgo), 'updated_at' => now()->subHours($hoursAgo),
            ]);
        }

        // Signalements : [village, auteur, statut, décalage, âge (jours), preneur, association, nettoyage (jours, heure)]
        $reports = [
            ['Badjanani', $citizen, ReportStatus::Nouveau, [0.0011, 0.0019], 0, null, null, null],
            ['Badjanani', $citizen, ReportStatus::PrisEnCharge, [-0.0017, 0.0008], 1, $ahmed, $moroniPropre, [1, '08:00']],
            ['Badjanani', $anrifa, ReportStatus::EnCours, [0.0004, -0.0021], 1, $ibrahim, null, [0, '16:00']],
            ['Badjanani', $citizen, ReportStatus::Nettoye, [-0.0006, 0.0028], 6, $ahmed, $moroniPropre, [-4, '09:00']],
            ['Hadouja', $faiza, ReportStatus::Nouveau, [0.0012, -0.0009], 0, null, null, null],
            ['Hadouja', $ibrahim, ReportStatus::PrisEnCharge, [-0.0010, 0.0015], 2, $citizen, null, [2, '17:00']],
            ['Itsandra', $kamal, ReportStatus::Nouveau, [0.0008, 0.0012], 1, null, null, null],
            ['Itsandra', $faiza, ReportStatus::Nettoye, [-0.0014, -0.0006], 9, $faiza, $itsandra, [-7, '07:00']],
            ['Mvouni', $kamal, ReportStatus::PrisEnCharge, [0.0009, -0.0013], 1, $anrifa, null, [2, '08:30']],
            ['Mdé', $anrifa, ReportStatus::Nouveau, [-0.0011, 0.0010], 0, null, null, null],
            ['Iconi', $kamal, ReportStatus::Nouveau, [0.0013, 0.0007], 2, null, null, null],
            ['Ntsoudjini', $ibrahim, ReportStatus::Nouveau, [0.0006, -0.0012], 3, null, null, null],
            ['Vouvouni', $citizen, ReportStatus::Rejete, [0.0010, 0.0010], 5, null, null, null],
        ];
        $descriptions = [
            'Tas d\'ordures au bord de la route depuis plusieurs jours.',
            'Sacs éventrés par les chats, odeurs fortes.',
            'Dépôt sauvage derrière l\'école.',
            'Plastiques accumulés dans la ravine.',
        ];
        foreach ($reports as $i => [$villageName, $author, $status, [$dLat, $dLng], $age, $taker, $association, $cleanup]) {
            $village = $villages[$villageName];
            $cleanupAt = $cleanup ? today()->addDays($cleanup[0])->setTimeFromTimeString($cleanup[1]) : null;
            $created = now()->subDays($age)->subHours(2 + $i);
            Report::forceCreate([
                'user_id' => $author->id, 'village_id' => $village->id, 'description' => $descriptions[$i % count($descriptions)],
                'latitude' => $village->latitude + $dLat, 'longitude' => $village->longitude + $dLng, 'status' => $status,
                'taken_by_id' => $taker?->id, 'association_id' => $association?->id, 'cleanup_at' => $cleanupAt,
                'cleaned_at' => $status === ReportStatus::Nettoye ? $cleanupAt?->copy()->addHours(2) : null,
                'admin_note' => $status === ReportStatus::Rejete ? 'Doublon d\'un signalement déjà traité.' : null,
                'created_at' => $created, 'updated_at' => $created,
            ]);
        }

        PickupRequest::create([
            'user_id' => $citizen->id, 'village_id' => $villages['Badjanani']->id,
            'description' => 'Déménagement : un vieux canapé et deux matelas à évacuer.',
            'latitude' => -11.7031, 'longitude' => 43.2531, 'preferred_date' => today()->addDays(2),
            'status' => PickupStatus::EnAttente,
        ]);
        PickupRequest::create([
            'user_id' => $kamal->id, 'village_id' => $villages['Hadouja']->id,
            'description' => 'Une trentaine de sacs après une fête de mariage.',
            'latitude' => -11.6925, 'longitude' => 43.2590, 'status' => PickupStatus::PriseEnCharge,
            'taken_by_id' => $ahmed->id, 'association_id' => $moroniPropre->id,
            'appointment_at' => today()->addDay()->setTime(10, 0), 'taker_note' => 'On passe avec une charrette, sortez les sacs devant le portail.',
        ]);

        TruckComplaint::create([
            'user_id' => $faiza->id, 'truck_id' => $trucks['Camion 2']->id,
            'reason' => ComplaintReason::Retard, 'message' => 'Le camion n\'est pas passé jeudi à Itsandra.',
        ]);
        TruckComplaint::create([
            'user_id' => $citizen->id, 'truck_id' => $trucks['Camion 1']->id,
            'reason' => ComplaintReason::Comportement, 'message' => 'Des sacs sont tombés du camion et n\'ont pas été ramassés.',
        ]);

        TruckNotice::forceCreate([
            'truck_id' => $trucks['Camion 2']->id, 'user_id' => User::where('email', 'agent2@cleancomores.km')->value('id'),
            'kind' => NoticeKind::NonTerminee, 'message' => 'Panne de benne : Ntsoudjini n\'a pas été fait. On repasse demain à 7h.',
            'created_at' => now()->subHours(2), 'updated_at' => now()->subHours(2),
        ]);

        // L'agent du camion 1 a déjà validé les deux premiers points de sa tournée aujourd'hui.
        $agent = User::where('email', 'agent@cleancomores.km')->first();
        if ($trucks['Camion 1']->schedulesOn(today())->isNotEmpty()) {
            foreach ($trucks['Camion 1']->stops()->limit(2)->get() as $stop) {
                RouteStopVisit::create(['route_stop_id' => $stop->id, 'user_id' => $agent->id, 'visit_date' => today()]);
            }
        }
    }
}
