# Audit Fixes (issue #35 branch)

**Goal:** Fix every finding of the 2026-11-26 four-part audit (backend security, PHP quality, frontend, AI + infra).

**Global constraints:** house style (`laravel-controllers` skill), no comments in generated code, English code/UI copy,
never `git commit`. Tests: `docker exec rolsys php artisan test`. Build: `docker exec rolsys npm run build`.
Baseline before work: 92 passed, 7 skipped, 2 failed (`ExampleTest`, `RegistrationTest` — both fixed by this plan).

Ownership split: PHP + README/CI/env = main session; `resources/js`, `resources/sass`, `resources/css`, `package.json`,
`vite.config.js`, `tailwind.config.js`, `resources/views/app.blade.php` = frontend agent; `ai/`, `.docker/` = AI/infra agent.

## Batch 1 — Field store
- [x] `FieldController::store` sets `user_id` from the authenticated user; remove `merge` in `StoreFieldRequest`
- [x] Feature tests: store creates field for user; status validated with enum
- [x] Playwright check of #35 search bar + create field end to end

## Batch 2 — Security
- [x] Drone endpoint behind `auth:sanctum` + field ownership + `data` size cap + `data_type` rule
- [x] API `show` / `forField` authorize; return resource instead of raw model
- [x] API store request: `field_id` scoped to user
- [x] Report `fields_included.*` scoped to user; `ReportGenerationService` queries scoped to report owner
- [x] Remove custom login controller/route; Fortify login (throttle + 2FA); `fortify.home = /`
- [x] Report PDF: drop `content` from store/update requests; escape output; dompdf `enable_remote => false`
- [x] API throttling (`throttleApi`), Sanctum token expiration
- [x] Clamp `per_page` everywhere
- [x] AI client: config URL, connect timeout, retry, generic user-facing errors (extract `AiAnalysisClient`)
- [x] `.env.example`: blank `APP_KEY`, add `AI_SERVICE_URL`, `AI_SERVICE_TIMEOUT`

## Batch 3 — PHP quality
- [x] Delete `app/helpers.php` + autoload entry; `Kernel.php`, `Middleware/Authenticate.php`, `RouteServiceProvider`, `HomeController`, `Notifable`, `Notification` model, unused `querySearch` scopes, `CropController`/`crops.find`/`Select2AjaxDto`/`OrderableTrait` (verify no callers first)
- [x] Jetstream leftovers: User traits duplicated, profile photo, `withPersonalTeam`, skipped API token tests, ExampleTests
- [x] Enums: `ReportType`, `ReportStatus`, `Sensitivity`; rename `Statuses` → `FieldStatus`; `Rule::enum`; single NDVI classification
- [x] Analytics download → real PDF; comparative report → real metrics; ReportSeeder via service, English
- [x] N+1 in `ReportGenerationService`
- [x] `final` everywhere, unused injections, `ownedBy` scopes, resources instead of raw models, house-style flash errors
- [x] Notifications copy in English (+ `NotificationPayloadTest`); seeders English; role `rolnik` → `farmer`
- [x] Schema: `analytics.recommendations` → json (convert legacy strings), indexes, `casts()` methods
- [x] Composer: rename package, remove unused packages, delete unrelated configs, `Route::resource` without `create`
- [x] Tests for Reports, Analytics actions, API, Field update/destroy, FieldData store/update/destroy

## Batch 4 — Frontend (agent)
- [x] Local OpenLayers / VChart registration (bundle split), unused npm deps, sass cleanup, Bootstrap source
- [x] Dead pages/components, SearchForm partials out of `Pages/`
- [x] AppLayout cleanup + flash toast, `ConfirmModal` everywhere, shared `format.js`, `FieldStatusBadge`
- [x] Analytics pages split, `<script setup>` migration, Tailwind vs dashlite, `route()` + `<Link>`, console logs, a11y

## Batch 5 — AI service + Docker (agent)
- [x] Validation, generic errors, clamp `prediction_days`, no CORS wildcard, gunicorn, `/health`
- [x] `requirements.txt` trimmed, pytest tests, dead modules/models removed, offline training, deterministic inference
- [x] Compose: AI internal only, no literal secret defaults, pinned images, no host MySQL port, `.docker/.env.example`
- [x] Dockerfiles slimmed, non-root AI, `XDG_CONFIG_HOME`, `ai/.idea` untracked, `.gitignore` fixes, `tools/` removed

## Batch 6 — Showcase
- [ ] ~~README rewrite + `LICENSE`~~ — reverted on Mario's request: README stays the original notice
- [x] GitHub Actions: pint, larastan, phpunit, npm build, ruff + pytest; composer scripts
- [x] Update `CLAUDE.md` facts (role, thresholds, commands)
