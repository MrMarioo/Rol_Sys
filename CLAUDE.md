# Rol_Sys

Precision agriculture system (master's thesis, now polished as a recruiter showcase). Laravel 11 + Inertia 2 + Vue 3
frontend, Python/Flask AI service in `ai/` (NDVI + soil moisture analysis, growth prediction).
`README.md` is Mario's original copyright notice — leave it unchanged.

## Working with Mario

- Mario writes to you in Polish; code and all UI copy stay in English.
- Mario commits himself. Leave changes in the working tree and finish by listing the changed files.
- Tasks are GitHub issues in `MrMarioo/Rol_Sys`; the branch name starts with the issue number
  (`35-add-searchbar-...` → issue #35). `gh` is not installed — read issues with
  `curl -s https://api.github.com/repos/MrMarioo/Rol_Sys/issues/<n>`.
- Plans and specs live in `docs/superpowers/plans/` and `docs/superpowers/specs/`, dated `YYYY-MM-DD-<topic>.md`.

## Code style

- Controllers (`app/Http/Controllers/`): follow the `laravel-controllers` skill strictly; `index()` uses Spatie
  QueryBuilder, with `allowedFilters`/`allowedSorts` called before `with`/`where` (Larastan needs that order).
- PHP: `final class`, `private readonly` constructor injection, named arguments, explicit return types.
- Generated code (PHP, JS, Vue) carries no comments; model `@property` / relation `@return` generics are the exception.
- Business logic goes to `app/Services/`; controllers stay thin. AI HTTP calls go only through `AiAnalysisClient`.
- Ownership: every Field / FieldData / Analytics / Report lookup is scoped to the current user — policies for route
  models, `Rule::exists(...)->where('user_id', ...)` for submitted ids, `ownedBy(user:)` scopes for queries.
- Domain values are enums in `app/Enums` (`FieldStatus`, `FieldDataType`, `ReportType`, `ReportStatus`,
  `NdviStatus`, `Sensitivity`); validate with `Rule::enum`.

## Running

Dev environment is Docker (`.docker/docker-compose.yml`, container `rolsys`, app at `http://rolsys.localhost`).

- Checks: `docker exec rolsys composer test` (MySQL `laravel_testing`), `composer lint` (Pint), `composer analyse`
  (Larastan level 5). All three must pass before you finish.
- Frontend build: `docker exec rolsys npm run build`.
- AI service has no bind mount: after editing `ai/`, rebuild with
  `docker compose -f .docker/docker-compose.yml --env-file .docker/.env up -d --build --no-deps ai-service`;
  its tests run with `pytest` + `ruff` inside `ai/`.
- Frontend verification: Playwright MCP against `http://rolsys.localhost`, logged in as `demo@rolsys.test` /
  `password` (create it with `php artisan db:seed --class=UserSeeder` if missing); zero browser console errors is
  part of every check. `migrate:fresh --seed` wipes
  Mario's dev data — ask first.

## Domain gotchas

- Field boundaries are stored as an array of `[lon, lat]` (WGS84); maps render in EPSG:3857 (`FieldMap.vue`
  converts with `fromLonLat` / `toLonLat`).
- Soil moisture is stored 0–1 and shown as percent (×100, 1 decimal); NDVI is shown with 2 decimals.
- AI service thresholds: NDVI `<0.3 Critical`, `<0.5 Poor`, `<0.7 Moderate`, else `Good`;
  moisture `<0.3 Low`, `>0.7 High`, else `Optimal`. Laravel reports use `NdviStatus` on the same cut points with
  report labels `poor / fair / good / excellent`.
- The growth model (`ai/models/growth_model.pkl`) is trained on synthetic data by `ai/train_growth_model.py`.
