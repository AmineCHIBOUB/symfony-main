# YouMusic — application Symfony 7

[![CI](https://github.com/AmineCHIBOUB/symfony-main/actions/workflows/ci.yml/badge.svg)](https://github.com/AmineCHIBOUB/symfony-main/actions/workflows/ci.yml)

Application web communautaire de partage de musique : chaque membre ajoute ses morceaux (lien YouTube ou autre), les classe par catégorie, met ses favoris en avant et compose des playlists publiques ou privées. Un back-office permet d'administrer l'ensemble du catalogue.

## Fonctionnalités

| Visiteur | Membre connecté | Administrateur |
|---|---|---|
| Page d'accueil : statistiques, derniers ajouts, top des favoris | Ajout de morceaux (titre, artiste, lien, catégorie) | Back-office EasyAdmin : tableau de bord chiffré |
| Catalogue avec recherche, filtre par catégorie, tri et pagination | Modification / suppression **de ses propres** morceaux | CRUD morceaux, catégories (avec couleur), playlists |
| Fiche morceau avec lecteur YouTube intégré et suggestions | Favoris (cœur) et espace personnel | Gestion des rôles des utilisateurs |
| Playlists publiques de la communauté | Playlists publiques ou privées, ajout / retrait de titres | Peut modifier tout contenu |

## Points techniques

- **Symfony 7.2**, PHP 8.2+, Doctrine ORM 3, Twig, Bootstrap 5, EasyAdmin 4.
- **Modèle de données** : `User`, `Music`, `Category`, `Playlist`, avec relations ManyToOne, OneToMany et deux ManyToMany (favoris, contenu des playlists). Les clés étrangères utilisent `ON DELETE CASCADE / SET NULL`.
- **Sécurité** :
  - authentification par formulaire avec utilisateurs en base, mots de passe hachés, CSRF et « se souvenir de moi » ;
  - **Voters** (`MusicVoter`, `PlaylistVoter`) : seul le propriétaire ou un admin peut modifier un contenu, et une playlist privée n'est visible que par son propriétaire ;
  - toutes les actions (suppression, favori, ajout à une playlist) passent par des formulaires POST protégés par jeton CSRF.
- **Requêtes optimisées** dans les repositories : recherche multi-critères paginée (`Paginator`), classement par popularité (`COUNT` + `GROUP BY`) et suggestions de la même catégorie.
- **Validation** des formulaires : URL valide, e-mail unique, mot de passe de 8 caractères minimum avec confirmation.
- **Tests** (PHPUnit) :
  - tests unitaires des entités et des voters ;
  - tests fonctionnels du parcours complet : inscription, connexion, ajout, droits, favoris, playlists et administration.
- **Intégration continue** (GitHub Actions) :
  - lint des templates, puis tests sur SQLite ;
  - migration et validation du schéma sur un vrai MySQL 8, puis chargement des données de démo.

## Installation (Docker)

Prérequis : Docker et Docker Compose.

```bash
docker compose up -d --build
docker compose exec web composer install
docker compose exec web php bin/console doctrine:migrations:migrate -n
docker compose exec web php bin/console doctrine:fixtures:load -n
```

| Service | URL |
|---|---|
| Application | http://localhost:8001 |
| phpMyAdmin | http://localhost:8080 |

### Comptes de démonstration

Ces comptes sont créés par les fixtures. Leurs mots de passe sont réservés à la démo locale.

| Rôle | E-mail | Mot de passe |
|---|---|---|
| Administrateur | `admin@youmusic.local` | `demo1234` |
| Membre | `user@youmusic.local` | `demo1234` |

## Lancer les tests

```bash
docker compose exec web php bin/phpunit --testdox
```

Les tests utilisent une base SQLite jetable : aucune configuration n'est nécessaire.

## Structure

```
app/
├── src/
│   ├── Controller/         Home, Music, Playlist, Profile, Security, Registration
│   │   └── Admin/          Tableau de bord et CRUD EasyAdmin
│   ├── Entity/             User, Music, Category, Playlist
│   ├── Form/               MusicType, PlaylistType, RegistrationFormType
│   ├── Repository/         Requêtes de recherche, de popularité et de suggestions
│   ├── Security/Voter/     Règles d'accès aux contenus
│   └── DataFixtures/       Jeu de démonstration (18 morceaux, 6 catégories)
├── templates/              Vues Twig (Bootstrap 5, thème sombre)
├── migrations/             Schéma MySQL
└── tests/                  Unitaires (Entity, Security) et fonctionnels (Controller)
```

---

Projet réalisé par **Amine Chiboub** — [GitHub](https://github.com/AmineCHIBOUB)
