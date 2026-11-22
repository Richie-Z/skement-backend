# Trilu backend — technical notes

## Stack

Versions below come from `composer.lock` (parsed), not the manifest. The manifest
states minimums and is often wrong.

- Framework: Laravel **7.25.0** locked; `composer.json:15` asks for `^7.24` but the lockfile is truth.
- Auth: `laravel/passport` **v9.3.2** (pinned identically in manifest and lockfile), issues RS256 bearer JWTs; `fruitcake/laravel-cors` 2.0.1; `laravel/tinker` 2.4.2.
- Runtime: PHP **7.2** in both Docker stages (`php:7.2-cli-alpine`, `php:7.2-fpm-alpine`, `Dockerfile:2,15`). The lockfile platform constraint `php:^7.2.5` is a minimum, not an exact patch; the runtime image fixes the actual version. PHP 7.2 is EOL — no security fixes, and no FrankenPHP image exists for it, so the fpm+nginx pattern is load-bearing.
- Tooling: Composer 2 (`composer:2` builder); npm with `package-lock.json` lockfileVersion 1. Tests: phpunit 8.5.8 (dev).
- Frontend: `npm run production` → webpack / laravel-mix (`package.json:9-10`). The Dockerfile never runs npm: served views use `asset()`, not `mix()`, so no build is required at image time and prebuilt `public/css/app.css` + `public/mix-manifest.json` are committed.

## Architecture

Request flow: `public/index.php` is the front controller under document root
`/app/public`. One container runs two processes under supervisord — nginx serves
static files directly and forwards `\.php$` to PHP-FPM over
`unix:/tmp/php-fpm.sock` (`.containerize/nginx-fpm.conf.template`: `try_files`
then `fastcgi_pass`). The image listens on `PORT=8080` (`Dockerfile:31,33-35`).

The container is multi-stage: `composer-bin` supplies Composer 2; a `vendor`
stage on `php:7.2-cli-alpine` installs `mbstring pdo_mysql pdo_pgsql` + `libpq`,
runs `composer install --no-dev --no-scripts --no-autoloader`, then
`composer dump-autoload --optimize --no-dev` and `php artisan storage:link`
(`Dockerfile:2-13`); the runtime `php:7.2-fpm-alpine` stage adds nginx,
supervisor, gettext, curl and the same PHP extensions, copies `/app`, installs
the `.containerize/` nginx/supervisor/FPM/entrypoint configs, creates runtime
directories, drops to `www-data`, and starts with `ENTRYPOINT /entrypoint.sh`,
`CMD supervisord` (`Dockerfile:15-35`). Both `pdo_mysql` and `pdo_pgsql` are
compiled in, so the image can target either database.

Rendering: the web guard serves stock Blade views (welcome, auth, `/home`); the
product surface is JSON resources (`app/Http/Resources/*`) with no SSR or
Inertia. Background work: none — `QUEUE_CONNECTION=sync`, and `routes/console.php`
only registers `inspire`; no worker or scheduler is required. Build-time app
boot occurs twice: `post-autoload-dump` runs `artisan package:discover`
(`composer.json:54-57`) and the Dockerfile runs `storage:link`; neither queries
the DB at boot (`app/Providers/AppServiceProvider.php:24-27` is empty), but the
runtime directories must exist first (`Dockerfile:29-30`).

## Data model

Two incompatible schemas are present. Laravel's migrator loads only top-level
`database/migrations/*.php` (non-recursive), so `php artisan migrate` creates
**only `users` and `products`**. The Trilu schema sits in
`database/migrations/temp/` and is never loaded.

Active migrations (loaded):

- `users` — `database/migrations/2021_05_04_114345_create_users_table.php:16-23`: `id`, unique `username`, `password`, `full_name` (default "admin"), `role` enum(administrator, cashier, accountant). This is a cashier/POS shape, not Trilu's, and conflicts with the `App\User` fillable `first_name,last_name,username,password` (`app/User.php:19-21`).
- `products` — `database/migrations/2021_05_04_115009_create_product_table.php:16-23`: `id`, `name`, `price`, `is_madeable`, nullable `qty_in_stock`. No model or controller references it; noise.

Trilu entities — models and controller queries, with migrations only in the
**unloaded** `temp/` directory:

- `users` (Trilu variant, `temp/2012_10_12_100000_create_users_table.php:17-25`, `app/User.php`): `first_name`, `last_name`, `username`, `password`, `remember_token`; model sets `public $timestamps = false` (`app/User.php:40`) and `belongsToMany Board` via `board_members`.
- `boards` (`temp/..._create_boards_table.php`, `app/Board.php:9-21`): `creator_id` FK→users, `name`; `belongsToMany User` via `board_members`, `hasMany Board_list`.
- `board_members` (`app/Board_member.php`): `board_id`, `user_id` FKs; join table queried by all three auth middleware.
- `board_lists` (`app/Board_list.php:9-16`): `board_id` FK, `order`, `name`; `hasMany Card`; eager-loaded by `BoardController@show`.
- `cards` (`app/Card.php:9-15`): `list_id` FK, `order`, `task`; `belongsTo Board_list`.
- `password_resets`, `failed_jobs` (queue driver is `sync`, so normally unused), and `oauth_*` (Passport-managed; no repo-local migration).

Gap worth stating plainly: `boards`, `board_members`, `board_lists`, `cards`,
`password_resets`, `failed_jobs` and `oauth_*` are queried with no loaded
migration and no SQL dump — no `*.sql`/`*.sqlite` fixture exists anywhere in the
repo, and `database/seeds/DatabaseSeeder.php:12-15` is empty. After a plain
`migrate` those tables do not exist and every board/card API call fails
"table not found" (`BoardController.php:56,61,72,76,85`,
`ListController.php:20-24,35-47`, `CardController.php:22-28,39-50`). The workspace
PostgreSQL DB `skement-backend-db` is "migrated, zero rows" under this same
layout, i.e. `users` + `products` only.

Two further mismatches: the active `users` table lacks `first_name`,
`last_name` and `remember_token`, but `register` inserts the first two
(`AuthController.php:20-34`) and the model/resources read them — so registration
and web login fail on the active schema. `Card::list()` uses `'App/Board_list'`
with a slash instead of a backslash (`app/Card.php:14`), breaking the inverse
relation if used.

## Auth

Two guards. Web uses a session cookie (`config/auth.php:16-19,38-42`) with stock
laravel/ui controllers. API uses Laravel Passport personal-access bearer tokens
(driver `passport`, `config/auth.php:44-48`; `App\User` uses `HasApiTokens`;
`Passport::routes()` in `app/Providers/AuthServiceProvider.php:28`), applied via
`auth:api` on `routes/api.php:17,24,25,27`.

API login uses `Auth::attempt` on **username** + password and issues a token
(`AuthController.php:51,53`); register bcrypts the password and issues a token
(`AuthController.php:33,35`); logout revokes the token (`AuthController.php:67-73`).
The stock web login form instead posts an **email** field, and the active `users`
table has no `email` column, so the web auth flows are effectively non-functional.

All board/list/card routes also pass domain middleware: `member` (user is a
member of at least one board, `app/Http/Middleware/Member.php:19-27`),
`boardmember` (belongs to this board, `BoardMember.php:19-32`), `cardmember`
(can reach the card via board→list→card, `CardMember.php:20-31`).

Deliberately open: there is **no global auth or API-key middleware** — the global
stack is TrustProxies, CORS, maintenance mode, ValidatePostSize, TrimStrings,
ConvertEmptyStringsToNull (`app/Http/Kernel.php:16-24`). A headerless request to
`/` returns 200; headerless `/api/v1/...` calls reach the route and get a 401
from `auth:api`, not a global gate. Public: `/`, web auth forms,
`POST /api/v1/auth/register`, `POST /api/v1/auth/login`, and Passport's token
endpoint. Passport keys and the personal-access client are not committed and
`passport:install` has not been run; `UNKNOWN — requires human input` whether
the deployed environment has generated them.

## External services

- DB: `DB_CONNECTION` defaults to `mysql` (`config/database.php:18`); `pgsql`/`sqlite`/`sqlsrv` connections defined. The workspace uses PostgreSQL `skement-backend-db`. Env names only: `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
- Cache `file` (`CACHE_DRIVER`), session `file` (`SESSION_DRIVER`, lifetime 120), queue `sync` (`QUEUE_CONNECTION`), failed jobs to `database`.
- Mail `smtp` (`MAIL_*`); local file storage with an `s3` disk (`AWS_*`); broadcast driver `null` with Pusher configured (`PUSHER_*`, `MIX_PUSHER_*`); Redis phpredis configured (`REDIS_*`).
- Laravel Passport for API auth. No third-party HTTP endpoint is hardcoded in app source; `guzzlehttp/guzzle` is a dependency but is not called.
- Full env-var name list (no values): `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`, `LOG_CHANNEL`, `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `BROADCAST_DRIVER`, `CACHE_DRIVER`, `QUEUE_CONNECTION`, `SESSION_DRIVER`, `SESSION_LIFETIME`, `REDIS_HOST`, `REDIS_PASSWORD`, `REDIS_PORT`, `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`, `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_APP_CLUSTER`. Source: `.env.example:1-46` and `config/*`.

## Operational risks

- **High — EOL runtime:** PHP 7.2 (`Dockerfile:2,15`, `CONTAINERIZE.md:28`), no security fixes.
- **High — core schema not installed by `migrate`:** only `users`/`products` are created; boards, board_members, board_lists, cards, password_resets, failed_jobs sit in the unscanned `database/migrations/temp/`. Every board/card call 500s on a fresh DB.
- **High — schema/model mismatch:** active `users` lacks `first_name`/`last_name`/`remember_token` (`app/User.php:19-21,40`, `AuthController.php:20-34`) and the stock web login expects an `email` column that does not exist; register and web login fail.
- **High — DB driver mismatch:** code defaults to `mysql` but there is no MySQL server in the workspace; the schema is PostgreSQL. Requires `DB_CONNECTION=pgsql` plus correct `DB_*` at runtime; smoke skipped DB verification (`CONTAINERIZE.md:25`, `.containerize/validation.env` `SMOKE_DB=skipped`).
- **Medium — Passport keys/client absent:** `Passport::routes()` and `createToken()` need `passport:install` output (`oauth-*.key`, personal access client). Keys are ephemeral state needing a volume or a deliberate init step; `UNKNOWN — requires human input` whether the deployment ran it.
- **Medium — file-backed session/cache** (`config/session.php:21`, `config/cache.php:21`) do not survive more than one replica or a pod restart; no Redis is configured in-cluster.
- **Medium — build-time app boot:** `post-autoload-dump` runs `artisan package:discover` and the Dockerfile runs `storage:link`; runtime directories must exist first (`Dockerfile:29-30`).
- **Medium — hardcoded credentials:** `Trilu.postman_collection.json` contains numerous committed Passport bearer tokens — file and line only, values not recorded: `Trilu.postman_collection.json:81,116,157,201,246,281,315,350,396,439,485,532,569,607,651,699,748,787,823,859`. Expired JWTs, but sensitive strings; rotate/revoke.
- **Low — membership-removal bug:** `DELETE /api/v1/board/{board_id}/member/{user_id}` maps to `deletemember($board_id, $member_id)` which deletes on `board_members.id`, not `user_id` (`BoardController.php:83-85`), so removal by user id does not work as documented.
- **Low — relation typo** `'App/Board_list'` (`app/Card.php:14`).
- **Low — ephemeral/writable state:** `storage/framework/{cache,sessions,views}`, `storage/logs`, `bootstrap/cache`, `/tmp/nginx` must be writable; the `public/storage` symlink target, file sessions/cache, and in-container Passport keys are all lost on container replacement. Above one replica, file sessions/cache, local uploads, and container-local keys need shared storage or external services.
- **Low — no queue/scheduler silently unrun:** `QUEUE_CONNECTION=sync`, `routes/console.php` only registers `inspire`.

## Container layout

Serves via **php:7.2-fpm-alpine + nginx in one container** under supervisord on
`PORT=8080`; nginx serves static assets from `/app/public` and forwards `\.php$`
to FPM over a Unix socket. Repo-local fixes that must not be lost: the PHP 7.2
pin with fpm+nginx (no FrankenPHP image exists for 7.2) and
`--ignore-platform-req=php` when a newer Composer installs
(`CONTAINERIZE.md:28-29`); both `pdo_mysql` and `pdo_pgsql` compiled
(`Dockerfile:6,18`); `storage:link` at build (`Dockerfile:13`); the `.containerize/`
nginx/supervisord/FPM/entrypoint templates (`Dockerfile:23-27`); runtime dir
creation and `www-data` chown (`Dockerfile:29-30`).

This is **not** one of the six never-re-render repos; it re-renders reproducibly
from the `Dockerfile.php-fpm` template. Recorded validation is LINT/BUILD/SMOKE
= PASS with DB verification skipped (`CONTAINERIZE.md:32-33`,
`.containerize/validation.env`).

## Open questions (UNKNOWN — requires human input)

- Which schema the deployment actually uses: MySQL per `.env.example:9` or the migrated workspace PostgreSQL `skement-backend-db`; real DB credentials and host are not stored here.
- Whether the seven `database/migrations/temp/*` files were ever applied manually or via an external SQL dump to any environment, and whether the intended fix is to promote them into `database/migrations/`.
- Why the cashier/POS `users` + `products` migrations replaced the Trilu migrations at top level (leftover template vs deliberate).
- Whether the committed bearer tokens in `Trilu.postman_collection.json` have been revoked and whether their target API is still live.
- True production data shape/volume for boards, lists and cards — the repo ships no fixture.
