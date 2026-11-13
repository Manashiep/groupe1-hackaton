# CleanComores

**Plateforme intelligente de gestion des déchets urbains aux Comores.**
« Une ville propre commence par une communauté engagée. »

Prototype hackathon (24 h) : une seule plateforme pour les habitants, les agents de collecte et la mairie.
Commune pilote : **Moroni** et ses environs (Ngazidja).

---

## Ce que fait le prototype

| Module | Fonctionnalités livrées |
|---|---|
| **1. Collectes régulières** | Calendrier par quartier (semaine type + 14 prochains jours), statut en direct *programmée / en cours / effectuée / non effectuée*, l'agent démarre et confirme son passage, l'habitant signale « la camionnette n'est pas passée », rappel la veille et alerte « la camionnette arrive ». |
| **2. Signalement citoyen** | Photo (prise ou importée), position sur carte Leaflet ou GPS du téléphone, quartier détecté automatiquement, description, suivi pas à pas (timeline), carte publique des signalements. |
| **3. Interventions & demandes** | Vérification par le responsable, affectation à une équipe (date, priorité, consignes), démarrage / clôture par l'agent avec photo « après », itinéraires de tournée ordonnés sur carte, demandes de collecte exceptionnelle (particuliers, restaurants, commerces) avec rendez-vous ou refus motivé, rattrapage d'une collecte manquée. |
| **4. Pilotage & statistiques** | KPI, signalements reçus/résolus par semaine, zones les plus touchées, collectes du jour par équipe, gestion des quartiers, équipes, calendrier et comptes. |

**Innovations incluses :** carte interactive de propreté, **indice de propreté** par quartier (0–100, calculé uniquement sur des données vérifiées), **points citoyens** (+10 par signalement vérifié, classement), **points noirs récurrents** (≥ 3 dépôts vérifiés à moins de 150 m en 90 jours), historique des interventions par quartier, notifications in-app.

### Les 4 profils

| Profil | Espace | Accès |
|---|---|---|
| Citoyen | `/espace` | inscription libre |
| Agent de collecte | `/agent` (pensé pour le téléphone) | créé par l'administrateur |
| Responsable municipal | `/gestion` | créé par l'administrateur |
| Administrateur | `/gestion` + comptes utilisateurs | — |

---

## Installation (Windows + WAMP)

Prérequis : PHP ≥ 8.3 (extensions `pdo_mysql`, `gd`, `fileinfo`), Composer, MySQL 8 (WAMP), une connexion internet (Bootstrap, Leaflet, Chart.js et les fonds de carte OpenStreetMap sont chargés depuis des CDN).

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Créez la base `cleancomores` (utf8mb4) dans MySQL, vérifiez `DB_*` dans `.env`, puis :

```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Ouvrez http://127.0.0.1:8000.

> WAMP utilise MyISAM par défaut : le projet force InnoDB (`DB_ENGINE`, config/database.php), nécessaire pour les clés étrangères.

### Comptes de démonstration

Mot de passe commun : `password` (affichés aussi sur la page de connexion en environnement local).

| Profil | E-mail |
|---|---|
| Citoyen (Badjanani) | `citoyen@cleancomores.km` |
| Agent – Équipe Centre | `agent.centre@cleancomores.km` (aussi `agent.nord@…`, `agent.sud@…`) |
| Responsable municipal | `responsable@cleancomores.km` |
| Administrateur | `admin@cleancomores.km` |

Pour repartir de données propres avant une démo : `php artisan migrate:fresh --seed`
(les passages du jour sont semés en *effectuée / en cours / programmée* pour montrer les trois statuts).

### Scénario de démo conseillé (5 min)

1. **Accueil** → « Quand passe la camionnette ? » → calendrier d'un quartier.
2. **Citoyen** : signaler un dépôt (photo + clic sur la carte) → suivi du signalement.
3. **Responsable** : tableau de bord → « À vérifier » → Valider (+10 pts) → affecter à une équipe.
4. **Agent** (sur téléphone) : Ma tournée → démarrer / terminer l'intervention avec photo.
5. **Citoyen** : notification « Zone nettoyée » ; **carte** : indice de propreté et points noirs.

---

## Tâches planifiées

```bash
php artisan collectes:generer   # crée les passages des 14 prochains jours (tous les jours à 00h10)
php artisan collectes:rappels   # prévient les habitants des collectes du lendemain (tous les jours à 18h00)
php artisan schedule:work       # exécute la planification en local
```

## Tests

Les tests utilisent une base MySQL dédiée `cleancomores_test` (à créer) :

```bash
php artisan test
```

16 tests couvrent l'affichage de chaque page par rôle, le cloisonnement des espaces et les parcours complets
(signalement → vérification → intervention → zone nettoyée, collectes, rattrapage, demandes, calendrier).

---

## Architecture

```
app/
  Enums/            Statuts et rôles (libellés, couleurs, icônes en français)
  Models/           Quartier, Team, CollectionSchedule (calendrier), Collection (passage daté),
                    CollectionComplaint, Report, ReportEvent (historique), Intervention, PickupRequest, User
  Services/         CollectionPlanner  calendrier → passages datés
                    CleanlinessIndex   indice de propreté
                    HotspotDetector    points noirs récurrents (haversine)
                    RouteBuilder       itinéraire ordonné d'une équipe
                    Notifier           destinataires des notifications
  Http/Controllers/ Citizen/ Agent/ Admin/ + Home, Auth, Notification
resources/views/    Blade + Bootstrap 5.3 (layouts public et gestion, partials réutilisables)
public/css/app.css  Thème CleanComores ; public/js/maps.js : aides Leaflet partagées
```

**Stack :** Laravel 13 · PHP 8.4 · MySQL 8 · Blade · Bootstrap 5.3 · Bootstrap Icons · Leaflet 1.9 + OpenStreetMap · Chart.js 4.

### Limites connues du prototype

- Les coordonnées des quartiers sont **approximatives** : à valider avec la mairie (Gestion → Quartiers, clic sur la carte).
- Notifications in-app uniquement ; SMS / WhatsApp à brancher dans `App\Notifications\CleanNotification::via()`.
- Pas de géolocalisation temps réel des camionnettes ; itinéraire ordonné par horaire (distance estimée).
- Les ressources front viennent de CDN : prévoir une copie locale pour une démo sans internet.

## Pistes pour la suite

Application mobile (ou PWA hors-ligne) · SMS/WhatsApp · suivi GPS des camionnettes · optimisation des tournées ·
récompenses locales pour les points citoyens · extension à Mutsamudu (Ndzuwani) et Fomboni (Mwali).
