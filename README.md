# Mini Projet : Application Web de Gestion de Réservations d'Événements (FIA2-GL)

## Description
Une plateforme événementielle premium permettant aux utilisateurs de découvrir des événements, de s'y inscrire via des formulaires sécurisés et à l'administrateur de gérer l'ensemble de la plateforme via un tableau de bord complet.

L'application met l'accent sur la sécurité moderne avec l'intégration de **JWT** pour les API et des **Passkeys (WebAuthn)** pour une authentification sans mot de passe.

## Technologies Utilisées
- **Framework PHP** : Symfony 6.4 (LTS)
- **Base de données** : MySQL 8.0
- **Sécurité** : 
  - JWT (LexikJWTAuthenticationBundle)
  - Passkeys / WebAuthn (giann/webauthn-lib)
- **Frontend** : Twig, Bootstrap 5 (Custom Dark Theme with Glassmorphism)
- **Conteneurisation** : Docker & Docker Compose
- **Gestionnaire de dépendances** : Composer

## Fonctionnalités Principales

### Côté Utilisateur
- Authentification classique et via **Passkeys**.
- Liste dynamique des événements avec images et détails.
- Formulaire de réservation interactif.
- Pages de confirmation animées.

### Côté Administrateur
- Tableau de bord avec statistiques et gestion.
- CRUD complet sur les événements (Ajouter, Modifier, Supprimer).
- Consultation des réservations en temps réel par événement.

## Documentation des Routes (Endpoints)

### 🌍 Interface Utilisateur (Public/User)
| Route | Description |
| :--- | :--- |
| `/` | Page d'accueil (Liste des événements) |
| `/event/{id}` | Consulter les détails d'un événement |
| `/event/{id}/reserve` | Formulaire de réservation |
| `/login` | Connexion utilisateur classique |
| `/register` | Inscription utilisateur classique |
| `/logout` | Déconnexion |

### 🔐 Interface Administration
| Route | Description |
| :--- | :--- |
| `/admin/login` | Connexion sécurisée Administrateur |
| `/admin/events/` | Dashboard : Liste des événements |
| `/admin/events/new` | Créer un nouvel événement |
| `/admin/events/{id}/edit` | Modifier un événement existant |
| `/admin/events/{id}/delete` | Supprimer un événement |
| `/admin/events/{id}/reservations` | Voir la liste des participants par événement |

### 🔑 API & Authentification Moderne (Passkeys/JWT)
| Route | Méthode | Description |
| :--- | :--- | :--- |
| `/api/auth/register` | `POST` | Inscription classique via API (Retourne JWT) |
| `/api/auth/register/options` | `POST` | Génère le challenge pour créer une Passkey |
| `/api/auth/register/verify` | `POST` | Vérifie et enregistre la Passkey (Retourne JWT) |
| `/api/auth/login/options` | `POST` | Génère le challenge pour la connexion WebAuthn |
| `/api/auth/login/verify` | `POST` | Vérifie la signature et connecte l'utilisateur (Retourne JWT) |
| `/api/auth/me` | `GET` | Récupère les informations du profil via le token JWT |

## Installation et Lancement

### Prérequis
- Docker et Docker Compose installés sur votre machine.

### Installation via Docker (Recommandé)
1. Clonez le dépôt :
   ```bash
   git clone https://github.com/votre-depot/MiniProjet2A-EventReservation-RanimRtimi.git
   cd MiniProjet2A-EventReservation-RanimRtimi
   ```
2. Lancez les conteneurs :
   ```bash
   docker-compose up -d --build
   ```
3. Installez les dépendances Symfony :
   ```bash
   docker exec -it event_php composer install
   ```
4. Créez la base de données et les migrations :
   ```bash
   docker exec -it event_php php bin/console doctrine:database:create
   docker exec -it event_php php bin/console doctrine:migrations:migrate
   ```
5. Accédez à l'application : `http://localhost:8080`

### Installation Locale (Optionnel)
1. Installez les dépendances : `composer install`
2. Configurez votre `.env` avec vos accès MySQL.
3. Lancez le serveur Symfony : `symfony server:start`

## Auteur
- **Ranim Rtimi** (FIA2-GL)

## Enseignant référent

Sofiene Ben Ahmed — sofiene.benahmed.issatso@gmail.com — ISSAT Sousse

---
© 2026 - Institut Supérieur des Sciences Appliquées et de Technologie de Sousse (ISSAT).
