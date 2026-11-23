# SpotMe

Plateforme communautaire de musculation : création et partage de programmes, journal de séances et suivi de progression.
Projet « The Hobbyist Hub » : Symfony 8.1, Doctrine, MySQL.

## Prérequis

- PHP 8.4 ou plus, avec les extensions `pdo_mysql` et `intl`
- Composer 2
- MySQL 8 (ou XAMPP)

## Installation

```bash
git clone https://github.com/JordyEPSI/The-Hobbyist-Hub-SpotMe-.git
cd The-Hobbyist-Hub-SpotMe-
composer install
```

Créer le fichier `.env.local` (jamais versionné) avec ses identifiants MySQL :

```dotenv
DATABASE_URL="mysql://root:@127.0.0.1:3306/spotme?serverVersion=8.4&charset=utf8mb4"
```

Créer la base et les tables, puis un premier administrateur :

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console app:creer-admin email@exemple.fr Pseudo
```

Remplir le catalogue avec les 15 exercices classiques (squat, développé couché, soulevé de terre, tractions…) depuis l'API [wger](https://wger.de) :

```bash
php bin/console app:wger:importer
php bin/console app:wger:importer --par-groupe=5 --anglais   # en plus : 5 exercices par groupe musculaire, même non traduits
```

On peut aussi importer exercice par exercice depuis le Back-Office (« Import wger »).

Les images des exercices sont copiées sur le serveur en WebP allégé lors de l'import (éco-conception). Pour des exercices importés avant cette fonctionnalité :

```bash
php bin/console app:images:copier
```

L'extension PHP GD avec le support WebP est nécessaire (incluse dans XAMPP). Sans elle, les images restent chargées depuis wger.

Lancer le serveur, puis ouvrir http://127.0.0.1:8000 :

```bash
php -S 127.0.0.1:8000 -t public
```

## Tests

Créer `.env.test.local` (jamais versionné) avec le même `DATABASE_URL` : Symfony ajoute lui-même le suffixe `_test` au nom de la base.

```bash
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test -n
php bin/phpunit
```

## Organisation Git

- `main` : version stable.
- `travail` / `work` : branches de développement, fusionnées dans `main` quand c'est stable.
- Messages de commit : `feat:`, `fix:`, `docs:`, `chore:`.
- Après un `git pull` qui ajoute des migrations : `php bin/console doctrine:migrations:migrate`.

## Mise en production

- Définir `APP_ENV=prod` et une clé `APP_SECRET` propre au serveur (`php -r "echo bin2hex(random_bytes(16));"`), dans `.env.local` sur le serveur.
- `composer install --no-dev --optimize-autoloader`
- `php bin/console asset-map:compile`
- `php bin/console doctrine:migrations:migrate`
