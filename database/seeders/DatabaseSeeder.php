<?php

namespace Database\Seeders;

use App\Enums\CollectionStatus;
use App\Enums\InterventionStatus;
use App\Enums\PickupStatus;
use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Enums\WasteType;
use App\Models\Collection;
use App\Models\CollectionComplaint;
use App\Models\CollectionSchedule;
use App\Models\Intervention;
use App\Models\PickupRequest;
use App\Models\Quartier;
use App\Models\Report;
use App\Models\Team;
use App\Models\User;
use App\Notifications\CleanNotification;
use App\Services\CollectionPlanner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Données de démonstration : commune pilote de Moroni et ses environs.
 * Mot de passe de tous les comptes de démo : « password ».
 * Les coordonnées des quartiers sont approximatives et doivent être validées par la mairie.
 */
class DatabaseSeeder extends Seeder
{
    /** @var array<string, Quartier> */
    private array $quartiers = [];

    /** @var array<string, Team> */
    private array $teams = [];

    /** @var array<string, string> quartier => équipe */
    private array $teamOf = [];

    /** @var array<string, User> */
    private array $agents = [];

    private User $staff;

    public function run(): void
    {
        mt_srand(2026);

        $this->seedQuartiersAndTeams();
        $this->seedUsers();
        $this->seedSchedules();
        $this->seedCollections();
        $this->seedReports();
        $this->seedPickupRequests();
        $this->seedNotifications();
    }

    private function seedQuartiersAndTeams(): void
    {
        $data = [
            // nom, ville, lat, lng, population, équipe
            ['Badjanani', 'Moroni', -11.6985, 43.2556, 4200, 'nord'],
            ['Magoudjou', 'Moroni', -11.6930, 43.2575, 5100, 'nord'],
            ['Mtsangani', 'Moroni', -11.6955, 43.2625, 3800, 'nord'],
            ['Itsandra', 'Itsandra', -11.6660, 43.2630, 4000, 'nord'],
            ['Volo-Volo', 'Moroni', -11.7035, 43.2588, 3500, 'centre'],
            ['Caltex', 'Moroni', -11.7075, 43.2603, 2900, 'centre'],
            ['Coulée', 'Moroni', -11.7110, 43.2640, 4600, 'centre'],
            ['Mvouni', 'Mvouni', -11.7170, 43.2900, 5500, 'centre'],
            ['Hadoudja', 'Moroni', -11.7150, 43.2575, 3100, 'sud'],
            ['Oasis', 'Moroni', -11.7185, 43.2625, 2700, 'sud'],
            ['Zilimadjou', 'Moroni', -11.7235, 43.2660, 3300, 'sud'],
            ['Hamramba', 'Moroni', -11.7265, 43.2575, 2500, 'sud'],
            ['Iconi', 'Iconi', -11.7470, 43.2470, 6000, 'sud'],
        ];

        $this->teams = [
            'nord' => Team::create(['name' => 'Équipe Nord', 'vehicle_plate' => 'CMR-101', 'color' => '#0f766e']),
            'centre' => Team::create(['name' => 'Équipe Centre', 'vehicle_plate' => 'CMR-102', 'color' => '#2563eb']),
            'sud' => Team::create(['name' => 'Équipe Sud', 'vehicle_plate' => 'CMR-103', 'color' => '#9333ea']),
        ];

        foreach ($data as [$name, $ville, $lat, $lng, $population, $team]) {
            $this->quartiers[$name] = Quartier::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'ville' => $ville,
                'ile' => 'Ngazidja',
                'latitude' => $lat,
                'longitude' => $lng,
                'population' => $population,
            ]);
            $this->teamOf[$name] = $team;
        }
    }

    private function seedUsers(): void
    {
        $password = Hash::make('password');

        User::create([
            'name' => 'Administrateur CleanComores', 'email' => 'admin@cleancomores.km',
            'password' => $password, 'role' => Role::Admin, 'phone' => '+269 330 00 01',
        ]);

        $this->staff = User::create([
            'name' => 'Fatima Saïd', 'email' => 'responsable@cleancomores.km',
            'password' => $password, 'role' => Role::Responsable, 'phone' => '+269 330 00 02',
        ]);

        $agents = [
            'nord' => [['Ali Mohamed', 'agent.nord'], ['Youssouf Mbaé', 'agent.nord2']],
            'centre' => [['Saïd Ahmed', 'agent.centre'], ['Hamidou Ali', 'agent.centre2']],
            'sud' => [['Moussa Soilihi', 'agent.sud'], ['Abdou Hassani', 'agent.sud2']],
        ];
        foreach ($agents as $team => $members) {
            foreach ($members as $i => [$name, $login]) {
                $agent = User::create([
                    'name' => $name, 'email' => "{$login}@cleancomores.km", 'password' => $password,
                    'role' => Role::Agent, 'team_id' => $this->teams[$team]->id,
                    'phone' => '+269 34'.mt_rand(10, 99).' '.mt_rand(10, 99).' '.mt_rand(10, 99),
                ]);
                if ($i === 0) {
                    $this->agents[$team] = $agent;
                }
            }
        }

        User::create([
            'name' => 'Nadia Abdallah', 'email' => 'citoyen@cleancomores.km', 'password' => $password,
            'role' => Role::Citoyen, 'quartier_id' => $this->quartiers['Badjanani']->id, 'phone' => '+269 321 45 67',
        ]);

        $citizens = [
            ['Ahmed Combo', 'Volo-Volo'], ['Faïza Mohamed', 'Coulée'], ['Ibrahim Mzé', 'Magoudjou'],
            ['Hadidja Youssouf', 'Caltex'], ['Anrifa Saïd', 'Iconi'], ['Soulaimana Hamadi', 'Hamramba'],
            ['Zaïnaba Ali', 'Mtsangani'], ['Nassim Abdou', 'Itsandra'], ['Echat Mohamed', 'Zilimadjou'],
            ['Housna Ahmed', 'Hadoudja'], ['Kamal Bacar', 'Oasis'], ['Roukia Madi', 'Mvouni'],
        ];
        foreach ($citizens as [$name, $quartier]) {
            User::create([
                'name' => $name,
                'email' => Str::slug($name, '.').'@exemple.km',
                'password' => $password,
                'role' => Role::Citoyen,
                'quartier_id' => $this->quartiers[$quartier]->id,
            ]);
        }
    }

    private function seedSchedules(): void
    {
        $mwf = [1, 3, 5];
        $tts = [2, 4, 6];
        $plan = [
            // quartier, jours, début, fin, type
            ['Itsandra', $mwf, '06:30', '07:30', WasteType::Menagers],
            ['Magoudjou', $mwf, '08:00', '09:30', WasteType::Menagers],
            ['Mtsangani', $tts, '06:30', '07:30', WasteType::Menagers],
            ['Badjanani', $tts, '08:00', '09:30', WasteType::Menagers],
            ['Badjanani', [6], '10:00', '11:00', WasteType::Recyclables],
            ['Volo-Volo', [1, 2, 3, 4, 5, 6], '06:00', '07:00', WasteType::Menagers],
            ['Caltex', $mwf, '07:30', '08:30', WasteType::Menagers],
            ['Coulée', $tts, '07:30', '08:30', WasteType::Menagers],
            ['Mvouni', $tts, '09:00', '10:30', WasteType::Menagers],
            ['Mvouni', [5], '09:00', '10:00', WasteType::Verts],
            ['Hadoudja', $mwf, '06:30', '07:30', WasteType::Menagers],
            ['Oasis', $mwf, '08:00', '09:00', WasteType::Menagers],
            ['Iconi', $mwf, '09:30', '11:00', WasteType::Menagers],
            ['Zilimadjou', $tts, '06:30', '07:30', WasteType::Menagers],
            ['Hamramba', $tts, '08:00', '09:00', WasteType::Menagers],
        ];

        foreach ($plan as [$quartier, $days, $start, $end, $type]) {
            foreach ($days as $day) {
                CollectionSchedule::create([
                    'quartier_id' => $this->quartiers[$quartier]->id,
                    'team_id' => $this->teams[$this->teamOf[$quartier]]->id,
                    'day_of_week' => $day,
                    'start_time' => $start,
                    'end_time' => $end,
                    'waste_type' => $type,
                ]);
            }
        }
    }

    private function seedCollections(): void
    {
        $planner = app(CollectionPlanner::class);
        $planner->generate(today()->subDays(35), today()->addDays(CollectionPlanner::HORIZON_DAYS));

        // Quartiers où la collecte est moins régulière (pour un indice de propreté contrasté).
        $missRate = ['Coulée' => 30, 'Hamramba' => 25, 'Volo-Volo' => 15];
        $teamBySlug = collect($this->teamOf)->mapWithKeys(fn ($team, $name) => [$this->quartiers[$name]->id => $team]);

        Collection::with('quartier')->whereDate('scheduled_date', '<', today())->get()
            ->each(function (Collection $collection) use ($missRate, $teamBySlug) {
                $rate = $missRate[$collection->quartier->name] ?? 5;
                $agent = $this->agents[$teamBySlug[$collection->quartier_id]];
                if (mt_rand(1, 100) <= $rate) {
                    $collection->update([
                        'status' => CollectionStatus::NonEffectuee,
                        'agent_note' => collect([
                            'Camionnette en panne (pneu crevé).',
                            'Route bloquée par des travaux.',
                            'Retard sur la tournée, quartier non couvert.',
                        ])->random(),
                    ]);

                    return;
                }
                $start = $collection->startsAt()->addMinutes(mt_rand(-5, 25));
                $collection->update([
                    'status' => CollectionStatus::Effectuee,
                    'started_at' => $start,
                    'completed_at' => $start->copy()->addMinutes(mt_rand(35, 80)),
                    'confirmed_by' => $agent->id,
                ]);
            });

        // Aujourd'hui : 1er arrêt effectué, 2e en cours, le reste programmé (pour la démo).
        Collection::whereDate('scheduled_date', today())->orderBy('start_time')->get()
            ->groupBy('team_id')
            ->each(function ($stops) use ($teamBySlug) {
                $stops->values()->each(function (Collection $collection, int $i) use ($teamBySlug) {
                    $agent = $this->agents[$teamBySlug[$collection->quartier_id]];
                    if ($i === 0) {
                        $collection->update([
                            'status' => CollectionStatus::Effectuee, 'started_at' => $collection->startsAt(),
                            'completed_at' => $collection->startsAt()->addMinutes(50), 'confirmed_by' => $agent->id,
                        ]);
                    } elseif ($i === 1) {
                        $collection->update(['status' => CollectionStatus::EnCours, 'started_at' => now()->subMinutes(20)]);
                    }
                });
            });

        // Quelques habitants signalent des passages manqués.
        $citizens = User::where('role', Role::Citoyen)->get();
        Collection::where('status', CollectionStatus::NonEffectuee)->whereDate('scheduled_date', '>=', today()->subDays(10))
            ->get()
            ->each(function (Collection $collection) use ($citizens) {
                $residents = $citizens->where('quartier_id', $collection->quartier_id);
                foreach ($residents->isEmpty() ? $citizens->random(1) : $residents as $resident) {
                    CollectionComplaint::forceCreate([
                        'collection_id' => $collection->id,
                        'user_id' => $resident->id,
                        'comment' => 'Les sacs sont toujours devant les maisons.',
                        'created_at' => $collection->startsAt()->addHours(3),
                    ]);
                }
            });
    }

    private function seedReports(): void
    {
        $weights = [
            'Volo-Volo' => 9, 'Coulée' => 7, 'Caltex' => 5, 'Badjanani' => 4, 'Magoudjou' => 4,
            'Hamramba' => 4, 'Iconi' => 3, 'Zilimadjou' => 3, 'Mtsangani' => 2, 'Hadoudja' => 2,
            'Oasis' => 2, 'Itsandra' => 2, 'Mvouni' => 1,
        ];

        $descriptions = [
            ReportCategory::DepotSauvage->value => [
                'Tas d\'ordures ménagères abandonné depuis plusieurs jours, odeurs fortes et mouches.',
                'Dépôt sauvage au bord de la route, les sacs ont été éventrés par les chats.',
                'Des habitants déposent leurs déchets sur ce terrain vague, le tas grossit chaque jour.',
            ],
            ReportCategory::PoubelleDebordante->value => [
                'Le point de collecte déborde, les déchets se répandent sur la chaussée.',
                'Bac plein depuis le week-end, les sacs s\'entassent autour.',
            ],
            ReportCategory::Encombrants->value => [
                'Vieux matelas et frigo abandonnés sur le trottoir.',
                'Carcasses de meubles et tôles laissées après des travaux.',
            ],
            ReportCategory::Plastiques->value => [
                'Beaucoup de bouteilles plastiques accumulées dans la ravine, risque d\'inondation à la prochaine pluie.',
                'La plage est couverte de plastiques et de sacs après la marée.',
            ],
            ReportCategory::DechetsDangereux->value => [
                'Batteries de voiture et bidons d\'huile abandonnés près des habitations.',
            ],
            ReportCategory::Autre->value => [
                'Déchets brûlés à ciel ouvert chaque soir, fumée gênante pour les enfants.',
            ],
        ];
        $categoryPool = [
            ...array_fill(0, 6, ReportCategory::DepotSauvage), ...array_fill(0, 3, ReportCategory::PoubelleDebordante),
            ...array_fill(0, 2, ReportCategory::Encombrants), ...array_fill(0, 2, ReportCategory::Plastiques),
            ReportCategory::DechetsDangereux, ReportCategory::Autre,
        ];
        $places = [
            'Près du marché', 'Derrière la mosquée', 'À côté de l\'école primaire', 'Bord de la ravine',
            'Route principale, en face de la pharmacie', 'Ruelle derrière la boulangerie', 'Près de l\'arrêt des taxis',
            'Terrain vague à l\'entrée du quartier', 'Bord de mer', 'Carrefour principal',
        ];

        $citizens = User::where('role', Role::Citoyen)->get();
        $demo = $citizens->firstWhere('email', 'citoyen@cleancomores.km');

        foreach ($weights as $name => $count) {
            $quartier = $this->quartiers[$name];
            $team = $this->teams[$this->teamOf[$name]];
            $residents = $citizens->where('quartier_id', $quartier->id)->values();

            for ($i = 0; $i < $count; $i++) {
                // Les signalements les plus récents sont encore en cours de traitement.
                $age = $i === 0 ? mt_rand(0, 2) : mt_rand(0, 58);
                $createdAt = now()->subDays($age)->setTime(mt_rand(6, 19), mt_rand(0, 59));
                $category = $categoryPool[array_rand($categoryPool)];
                $author = ($name === 'Badjanani' || mt_rand(1, 6) === 1) ? $demo : ($residents->isNotEmpty() ? $residents->random() : $citizens->random());

                $status = match (true) {
                    $age <= 1 => mt_rand(0, 1) ? ReportStatus::Nouveau : ReportStatus::Valide,
                    $age <= 4 => [ReportStatus::Valide, ReportStatus::Planifie, ReportStatus::EnCours][mt_rand(0, 2)],
                    $age <= 12 => mt_rand(1, 10) <= 6 ? ReportStatus::Resolu : ReportStatus::Planifie,
                    default => mt_rand(1, 10) <= 9 ? ReportStatus::Resolu : ReportStatus::Rejete,
                };

                $report = Report::forceCreate([
                    'user_id' => $author->id,
                    'quartier_id' => $quartier->id,
                    'category' => $category,
                    'description' => collect($descriptions[$category->value])->random(),
                    'latitude' => $quartier->latitude + mt_rand(-22, 22) / 10000,
                    'longitude' => $quartier->longitude + mt_rand(-22, 22) / 10000,
                    'address' => $places[array_rand($places)].', '.$name,
                    'status' => ReportStatus::Nouveau,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
                $this->playReportHistory($report, $status, $createdAt, $team);
            }
        }

        // Garantit des missions pour chaque équipe cet après-midi (démo agent).
        foreach ($this->teams as $key => $team) {
            $report = Report::where('status', ReportStatus::Valide)
                ->whereIn('quartier_id', collect($this->teamOf)->filter(fn ($t) => $t === $key)->keys()->map(fn ($n) => $this->quartiers[$n]->id))
                ->first();
            if ($report) {
                $this->planIntervention($report, $team, today()->setTime(14, 30), now()->subHour());
            }
        }
    }

    private function playReportHistory(Report $report, ReportStatus $target, Carbon $createdAt, Team $team): void
    {
        $report->events()->create([
            'user_id' => $report->user_id, 'status' => ReportStatus::Nouveau,
            'note' => 'Signalement envoyé par un habitant.', 'created_at' => $createdAt, 'updated_at' => $createdAt,
        ]);

        if ($target === ReportStatus::Nouveau) {
            return;
        }

        $at = $createdAt->copy()->addHours(mt_rand(2, 20));
        if ($target === ReportStatus::Rejete) {
            $this->event($report, ReportStatus::Rejete, 'Doublon d\'un signalement déjà traité.', $at);
            $report->update(['status' => ReportStatus::Rejete]);

            return;
        }

        $this->event($report, ReportStatus::Valide, 'Signalement vérifié par le service propreté. +10 points pour l\'auteur.', $at);
        $report->update(['status' => ReportStatus::Valide, 'points_awarded' => true]);
        $report->user?->increment('points', 10);

        if ($target === ReportStatus::Valide) {
            return;
        }

        $scheduled = $at->copy()->addDay()->setTime([8, 10, 14][mt_rand(0, 2)], 0);
        if ($scheduled->isPast() && $target === ReportStatus::Planifie) {
            $scheduled = today()->addDays(mt_rand(1, 3))->setTime(14, 0);
        }
        $intervention = $this->planIntervention($report, $team, $scheduled, $at->copy()->addHour());

        if ($target === ReportStatus::Planifie) {
            return;
        }

        $started = $target === ReportStatus::EnCours ? now()->subMinutes(mt_rand(15, 90)) : $scheduled;
        $this->event($report, ReportStatus::EnCours, "L'{$team->name} est sur place.", $started);
        $intervention->update(['status' => InterventionStatus::EnCours, 'started_at' => $started]);
        $report->update(['status' => ReportStatus::EnCours]);

        if ($target === ReportStatus::EnCours) {
            return;
        }

        $done = $started->copy()->addMinutes(mt_rand(40, 150));
        $this->event($report, ReportStatus::Resolu, "Zone nettoyée par l'{$team->name}.", $done);
        $intervention->update([
            'status' => InterventionStatus::Terminee, 'completed_at' => $done,
            'completion_note' => 'Déchets évacués vers le centre de transit. Zone propre.',
        ]);
        $report->update(['status' => ReportStatus::Resolu, 'resolved_at' => $done]);
    }

    private function planIntervention(Report $report, Team $team, Carbon $scheduled, Carbon $plannedAt): Intervention
    {
        $intervention = Intervention::forceCreate([
            'report_id' => $report->id,
            'quartier_id' => $report->quartier_id,
            'team_id' => $team->id,
            'assigned_by' => $this->staff->id,
            'title' => $report->category->label().' – '.$report->quartier->name,
            'notes' => 'Prévoir gants, pelles et sacs renforcés.',
            'priority' => $report->category === ReportCategory::DechetsDangereux ? 'urgente' : (mt_rand(0, 3) ? 'normale' : 'haute'),
            'scheduled_at' => $scheduled,
            'status' => InterventionStatus::Planifiee,
            'latitude' => $report->latitude,
            'longitude' => $report->longitude,
            'created_at' => $plannedAt,
        ]);

        $this->event($report, ReportStatus::Planifie, "Intervention planifiée le {$scheduled->translatedFormat('l j F à H\hi')} – {$team->name}.", $plannedAt);
        $report->update(['status' => ReportStatus::Planifie]);

        return $intervention;
    }

    private function event(Report $report, ReportStatus $status, string $note, Carbon $at): void
    {
        $report->events()->create([
            'user_id' => $this->staff->id, 'status' => $status, 'note' => $note, 'created_at' => $at, 'updated_at' => $at,
        ]);
    }

    private function seedPickupRequests(): void
    {
        $demo = User::where('email', 'citoyen@cleancomores.km')->first();
        $others = User::where('role', Role::Citoyen)->where('id', '!=', $demo->id)->get();

        $requests = [
            [$demo, 'particulier', WasteType::Encombrants, 'moyen', 'Déménagement : vieux canapé, deux matelas et une armoire cassée.', PickupStatus::Acceptee],
            [$demo, 'particulier', WasteType::Verts, 'grand', 'Élagage de deux manguiers, branches et feuilles à évacuer.', PickupStatus::EnAttente],
            [$others->firstWhere('name', 'Ahmed Combo'), 'restaurant', WasteType::Menagers, 'grand', 'Restaurant près du port : surplus de déchets après un mariage (≈ 30 sacs).', PickupStatus::EnAttente],
            [$others->firstWhere('name', 'Hadidja Youssouf'), 'commerce', WasteType::Recyclables, 'moyen', 'Boutique : cartons et emballages plastiques après réception de marchandises.', PickupStatus::Effectuee],
            [$others->firstWhere('name', 'Anrifa Saïd'), 'association', WasteType::Menagers, 'grand', 'Journée de nettoyage citoyen de la plage d\'Iconi, sacs à récupérer.', PickupStatus::EnAttente],
            [$others->firstWhere('name', 'Kamal Bacar'), 'particulier', WasteType::Encombrants, 'grand', 'Gravats et parpaings après rénovation de la maison.', PickupStatus::Refusee],
        ];

        foreach ($requests as $i => [$user, $type, $waste, $volume, $description, $status]) {
            $quartier = $user->quartier;
            $created = now()->subDays([1, 0, 2, 9, 1, 6][$i])->setTime(mt_rand(7, 18), 0);
            $request = PickupRequest::forceCreate([
                'user_id' => $user->id,
                'quartier_id' => $quartier->id,
                'requester_type' => $type,
                'waste_type' => $waste,
                'volume' => $volume,
                'description' => $description,
                'address' => 'Domicile / local, '.$quartier->name,
                'latitude' => $quartier->latitude + mt_rand(-10, 10) / 10000,
                'longitude' => $quartier->longitude + mt_rand(-10, 10) / 10000,
                'preferred_date' => $created->copy()->addDays(3),
                'status' => $status,
                'created_at' => $created,
                'updated_at' => $created,
            ]);

            if (in_array($status, [PickupStatus::Acceptee, PickupStatus::Effectuee], true)) {
                $appointment = $status === PickupStatus::Acceptee ? today()->addDay()->setTime(10, 0) : $created->copy()->addDays(2)->setTime(15, 0);
                $request->update(['appointment_at' => $appointment, 'admin_note' => 'Merci de sortir les objets devant le portail avant le passage.']);
                Intervention::create([
                    'pickup_request_id' => $request->id,
                    'quartier_id' => $quartier->id,
                    'team_id' => $this->teams[$this->teamOf[$quartier->name]]->id,
                    'assigned_by' => $this->staff->id,
                    'title' => 'Collecte exceptionnelle – '.$quartier->name,
                    'notes' => $description,
                    'priority' => 'normale',
                    'scheduled_at' => $appointment,
                    'status' => $status === PickupStatus::Effectuee ? InterventionStatus::Terminee : InterventionStatus::Planifiee,
                    'completed_at' => $status === PickupStatus::Effectuee ? $appointment->copy()->addHour() : null,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                ]);
            }
            if ($status === PickupStatus::Refusee) {
                $request->update(['admin_note' => 'Les gravats de chantier ne sont pas pris en charge : contactez un transporteur agréé.']);
            }
        }
    }

    private function seedNotifications(): void
    {
        $demo = User::where('email', 'citoyen@cleancomores.km')->first();
        $next = Collection::where('quartier_id', $demo->quartier_id)->upcoming()->first();
        $resolved = $demo->reports()->where('status', ReportStatus::Resolu)->latest()->first();
        $verified = $demo->reports()->where('status', ReportStatus::Valide)->latest()->first();

        $items = [];
        if ($resolved) {
            $items[] = [new CleanNotification('Zone nettoyée', "Votre signalement {$resolved->reference()} a été traité. Merci pour votre vigilance !", route('citizen.reports.show', $resolved), 'bi-check-circle', 'success'), 3, true];
        }
        $items[] = [new CleanNotification('Rendez-vous fixé', 'Votre demande de collecte exceptionnelle est acceptée : passage demain à 10h00.', route('citizen.pickups.index'), 'bi-calendar-check', 'info'), 1, false];
        if ($verified) {
            $items[] = [new CleanNotification('Signalement vérifié (+10 points)', "Votre signalement {$verified->reference()} a été vérifié par le service propreté.", route('citizen.reports.show', $verified), 'bi-patch-check', 'warning'), 0, false];
        }
        if ($next) {
            $items[] = [new CleanNotification('Rappel de collecte', "La camionnette passe {$next->scheduled_date->translatedFormat('l j F')} {$next->timeLabel()} à {$demo->quartier->name}. Pensez à sortir vos sacs.", route('citizen.dashboard'), 'bi-truck', 'primary'), 0, false];
        }

        foreach ($items as [$notification, $daysAgo, $read]) {
            $demo->notify($notification);
            $demo->notifications()->latest()->first()->forceFill([
                'created_at' => now()->subDays($daysAgo)->subMinutes(mt_rand(5, 300)),
                'read_at' => $read ? now() : null,
            ])->save();
        }
    }
}
