# CleanComores

**Gestion participative des déchets aux Comores.**
« Une ville propre commence par une communauté engagée. »

Prototype hackathon : une application simple pour les habitants et les agents de collecte,
et un espace de gestion (Filament) pour la mairie. Zone pilote : Moroni et ses environs (Ngazidja).

## Qui fait quoi

| Profil | Ce qu'il voit |
|---|---|
| **Citoyen** (`/carte`) | La carte des dépôts sauvages (nouveau, pris en charge avec jour et heure du nettoyage, nettoyage en cours) et un bouton **Signaler** · ses signalements · le calendrier du camion de son village, avec un bouton pour signaler un problème de camion (retard, comportement…) · ses demandes de collecte. |
| **Agent de collecte** (`/agent/tournee`) | Tout ce que fait le citoyen (sauf le calendrier) + sa **tournée du jour** (points de collecte à cocher, nettoyages et collectes à faire) et son **planning** sur 7 jours. |
| **Responsable municipal** / **Administrateur** (`/admin`) | Signalements (prise en charge par un camion à un jour et une heure, ou rejet) · demandes de collecte (rendez-vous ou refus) · problèmes de camion · camions (village, jours de passage, **itinéraire dessiné sur la carte**) · villages · utilisateurs. |

L'inscription crée toujours un compte citoyen. Le village d'un signalement est déduit de sa position GPS.

## Installation (Windows + WAMP)

Prérequis : PHP 8.4, Composer, MySQL 8 (WAMP) et une connexion internet
(Bootstrap, Leaflet et les fonds de carte OpenStreetMap sont chargés depuis des CDN).

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Créez la base `cleancomores` (utf8mb4), vérifiez `DB_*` dans `.env`, puis :

```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Ouvrez http://127.0.0.1:8000. Le projet force le moteur InnoDB (WAMP utilise MyISAM par défaut).

### Comptes de démonstration (mot de passe : `password`)

| Profil | E-mail |
|---|---|
| Citoyen | `citoyen@cleancomores.km` |
| Agent (Camion 1, Badjanani) | `agent@cleancomores.km` |
| Responsable municipal | `responsable@cleancomores.km` |
| Administrateur | `admin@cleancomores.km` |

Avant une démo : `php artisan migrate:fresh --seed`.

## Tests

Les tests utilisent une base MySQL dédiée `cleancomores_test` (à créer) :

```bash
php artisan test --compact
```

## Stack

Laravel 13 · PHP 8.4 · MySQL 8 · Filament 5 (espace mairie) · Blade + Bootstrap 5.3 (application) · Leaflet + OpenStreetMap.
