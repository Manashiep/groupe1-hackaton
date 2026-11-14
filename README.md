# EcoCom

**Gestion participative des déchets aux Comores.**
« Une ville propre commence par une communauté engagée. »

Prototype hackathon : une application simple pour les habitants et les agents de collecte,
et un espace de gestion (Filament) pour la mairie. Zone pilote : Moroni et ses environs (Ngazidja).

## Qui fait quoi

| Profil | Ce qu'il fait |
|---|---|
| **Citoyen** (`/carte`) | Voit la carte des dépôts sauvages et des demandes de collecte · **signale** un dépôt (photo + GPS) ou **demande une collecte** (bouton « + ») · **prend en charge** un signalement ou une demande en indiquant le jour et l'heure, prévient s'il n'a pas fini, marque « C'est fait » · consulte le **calendrier du camion** de son village et signale un problème de camion · suit les **associations** (fil d'actualité, annuaire, demande d'adhésion) · peut demander à la mairie de **créer une association**. |
| **Chef d'association** | Tout ce que fait le citoyen + son tableau de bord : demandes d'adhésion, **annonces** (avec date de rendez-vous) et **publications** (photos, vidéos), membres, infos du groupe. Il peut prendre en charge un signalement **au nom de l'association**. |
| **Agent de collecte** (`/agent/tournee`) | Tout ce que fait le citoyen sauf le calendrier + sa **tournée du jour** (villages et horaires, points de collecte à cocher), son **planning** sur 7 jours, et une **alerte aux habitants** (tournée pas terminée, retard, passage annulé) visible 24 h. |
| **Responsable municipal** / **Administrateur** (`/admin`) | Valide ou refuse les **associations** · modère signalements et demandes · traite les problèmes de camion · gère les **camions** (plusieurs villages par camion, créneaux par village, **itinéraire dessiné sur la carte**), les villages et les comptes. |

Les camions de la mairie ne font que le ramassage régulier, à leurs heures et dans leurs villages. Les dépôts sauvages et les collectes sur demande sont pris en charge par les citoyens et les associations.

L'inscription crée toujours un compte citoyen. Le village d'un signalement est déduit de sa position GPS. Les photos sont réduites dans le navigateur avant l'envoi.

> **Vidéos des associations** : WAMP limite par défaut l'envoi de fichiers à 2 Mo. Pour accepter des vidéos, montez `upload_max_filesize` et `post_max_size` (par ex. 64M) dans le `php.ini` de WAMP, puis redémarrez `php artisan serve`.

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
| Chef d'association (Moroni Propre) | `asso@cleancomores.km` |
| Agent (Camion 1 : Badjanani, Hadouja) | `agent@cleancomores.km` |
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
