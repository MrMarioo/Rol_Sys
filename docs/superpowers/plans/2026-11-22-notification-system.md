# Notification System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Working top-bar bell fed by real database notifications sent after AI analysis (success/failure) and report generation, refreshed every 60 s.

**Architecture:** Three Laravel `Notification` classes on the `database` channel with one uniform `data` shape. `HandleInertiaRequests` shares `{ unread_count, latest }`; the layout polls it with an Inertia partial reload. A house-style `NotificationController` handles the history page and mark-as-read actions.

**Tech Stack:** Laravel 11, Inertia + Vue 3 (Options API layout), Spatie QueryBuilder 6, PHPUnit, Docker (`rolsys` web container, `rolsys-db` MySQL).

**Spec:** `docs/superpowers/specs/2026-11-22-notification-system-design.md`

## Global Constraints

- Notification `data` shape, always: `title` (string), `message` (string), `level` (`success|warning|danger|info`), `url` (string|null, **relative** path via `route(..., absolute: false)`).
- Channel: `['database']` only. No mail, no broadcast.
- User-facing notification copy in Polish (exact strings in Task 2).
- Every controller under `app/Http/Controllers/` follows the `laravel-controllers` skill (invoke it before writing the controller); `index()` uses Spatie QueryBuilder.
- A user only ever sees or modifies their own notifications.
- Tests run in Docker: `docker exec rolsys php artisan test ...` (start with `docker compose -f .docker/docker-compose.yml --env-file .docker/.env up -d` if the containers are down).

## Review Focus

1. Another user's notification id in read endpoint → 404, nothing changes (Task 5 `test_cannot_read_foreign_notification`).
2. Notification without `url` (legacy/seeded rows) → read redirects back, bell item renders without crashing (Task 5 `test_read_without_url_redirects_back`).
3. Guest pages (login) → shared prop is `null`, no query, no crash (Task 4 `test_guest_gets_null_notifications_prop`).
4. Background job fails → owner still gets `AnalysisFailed` even though the job is marked failed (Task 3 `test_job_failure_notifies_owner`).
5. More than 10 notifications → `latest` capped at 10, `unread_count` counts all unread (Task 4 `test_latest_is_capped_at_ten`).

---

### Task 1: Isolated test database + model factories

Currently `phpunit.xml` has no DB override, so `RefreshDatabase` would wipe the dev DB `laravel`.

**Files:**
- Modify: `phpunit.xml` (add `<env name="DB_DATABASE" value="laravel_testing"/>` next to the commented sqlite lines)
- Create: `database/factories/FieldFactory.php`, `database/factories/ReportFactory.php`, `database/factories/AnalyticsFactory.php`

**Interfaces:**
- Produces: `Field::factory()` (`user_id` → `User::factory()`, `name`, `size`, `status` = `active`), `Report::factory()` (`user_id`, `title`, `type` = `weekly_summary`, `fields_included` = `[]`, `parameters` = `[]`, `content` = `[]`, `status` = `draft`), `Analytics::factory()` (`field_id` → `Field::factory()`, `analysis_type` = `standard_analysis`, `analysis_date` = today, `results` = `[]`).

- [ ] **Step 1:** Create the DB: `docker exec rolsys-db mysql -uroot -p"$DB_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS laravel_testing"` (password from `.env`).
- [ ] **Step 2:** Add the `DB_DATABASE` env to `phpunit.xml`; write the three factories.
- [ ] **Step 3:** Run `docker exec rolsys php artisan test --filter=ProfileInformationTest`. Expected: PASS, and `laravel` dev DB still has its data (`docker exec rolsys php artisan tinker --execute="echo App\Models\User::count();"` > 0).
- [ ] **Step 4:** Commit `test: isolate test database and add model factories`.

### Task 2: Notification classes

**Files:**
- Create: `app/Notifications/AnalysisCompleted.php`, `app/Notifications/AnalysisFailed.php`, `app/Notifications/ReportGenerated.php`
- Test: `tests/Unit/NotificationPayloadTest.php` (extends `Tests\TestCase`, uses `RefreshDatabase`)

**Interfaces:**
- Produces:
  - `new AnalysisCompleted(Analytics $analytics)` → `toArray()`: `title` `Analiza AI zakończona`, `message` `Analiza pola {field.name} jest gotowa.`, `level` `success`, `url` `route('analytics.show', $analytics->id, false)`.
  - `new AnalysisFailed(Field $field)` → `title` `Analiza AI nie powiodła się`, `message` `Nie udało się przeanalizować pola {field.name}. Spróbuj ponownie później.`, `level` `danger`, `url` `route('fields.analytics', $field->id, false)`.
  - `new ReportGenerated(Report $report)` → `title` `Raport wygenerowany`, `message` `Raport „{report.title}” jest gotowy do pobrania.`, `level` `success`, `url` `route('reports.show', $report->id, false)`.
  - All: `via(): ['database']`. No `ShouldQueue` (queue is `sync`).

- [ ] **Step 1: Failing tests**: `test_analysis_completed_payload`, `test_analysis_failed_payload`, `test_report_generated_payload`. Each builds models via factories, calls `toArray($user)`, `assertSame` on all four keys with the exact strings above (e.g. field named `Pole A` → `Analiza pola Pole A jest gotowa.`, url `/analytics/{id}`).
- [ ] **Step 2:** Run `docker exec rolsys php artisan test --filter=NotificationPayloadTest`. Expected: FAIL, class not found.
- [ ] **Step 3:** Implement the three classes.
- [ ] **Step 4:** Re-run. Expected: PASS.
- [ ] **Step 5:** Commit `feat: add analysis and report notification classes`.

### Task 3: Send notifications from analysis and report flows

**Files:**
- Modify: `app/Services/FieldAnalysisService.php` (`analyze()`): after `create` → `$field->user->notify(new AnalysisCompleted($analytics))`; on non-successful response or any `Throwable` from the HTTP call → `$field->user->notify(new AnalysisFailed($field))` then rethrow (controller behaviour unchanged).
- Modify: `app/Jobs/AnalyzeFieldDataJob.php` (`handle()`): capture created `Analytics`, notify owner (`$this->fieldData->field->user`) with `AnalysisCompleted`; in `catch` and on non-successful response notify `AnalysisFailed` before `$this->fail($e)` / return.
- Modify: `app/Http/Controllers/ReportController.php` (`generate()`): after the successful `update`, `$report->user->notify(new ReportGenerated($report))`.
- Test: `tests/Feature/NotificationDispatchTest.php`

**Interfaces:**
- Consumes: Task 2 classes, Task 1 factories. `FieldData` must have a `field()` relation (add `belongsTo(Field::class)` if missing).

- [ ] **Step 1: Failing tests** (all use `Notification::fake()` and `Http::fake()`):
  - `test_successful_analysis_notifies_owner`: fake AI 200 `['recommendations' => []]`, call `app(FieldAnalysisService::class)->analyze($field, [...])` → `Notification::assertSentTo($owner, AnalysisCompleted::class)`.
  - `test_failed_analysis_notifies_owner_and_rethrows`: fake AI 500 → `expectException(RuntimeException::class)` and `assertSentTo($owner, AnalysisFailed::class)` (assert in a `finally`/after `try`).
  - `test_job_success_notifies_owner`: `(new AnalyzeFieldDataJob($fieldData))->handle()` with fake 200 → `AnalysisCompleted` sent.
  - `test_job_failure_notifies_owner`: fake throws `ConnectionException` → `AnalysisFailed` sent.
  - `test_report_generation_notifies_owner`: `actingAs($owner)->post(route('reports.generate', $report))` → `assertSentTo($owner, ReportGenerated::class)`; `assertNotSentTo($otherUser, ...)`.
- [ ] **Step 2:** Run `--filter=NotificationDispatchTest`. Expected: 5 FAIL.
- [ ] **Step 3:** Implement the three modifications.
- [ ] **Step 4:** Re-run. Expected: PASS.
- [ ] **Step 5:** Commit `feat: notify owners after AI analysis and report generation`.

### Task 4: Shared `notifications` prop + resource

**Files:**
- Create: `app/Http/Resources/NotificationResource.php`
- Modify: `app/Http/Middleware/HandleInertiaRequests.php` (`share()`)
- Modify: `app/Http/Controllers/DashboardController.php` (`unreadNotifications()` → `NotificationResource::collection(...)->resolve()`)
- Test: `tests/Feature/SharedNotificationsTest.php`

**Interfaces:**
- Produces: `NotificationResource` → `id` (uuid), `title`, `message`, `level` (default `info`), `url` (nullable), `read` (bool, `read_at !== null`), `created_at` (`diffForHumans()`). Missing `data` keys fall back to `''` / `info` / `null`.
- Produces: shared prop `notifications` = closure returning `null` for guests, otherwise `['unread_count' => int, 'latest' => NotificationResource[] (10 newest, read and unread)]`.

- [ ] **Step 1: Failing tests** (assert via `->assertInertia(fn (AssertableInertia $page) => ...)` on `GET route('dashboard')` or `/`):
  - `test_shared_prop_contains_unread_count_and_latest`: 3 unread + 1 read → `unread_count` 3, `latest` has 4 items with keys above.
  - `test_latest_is_capped_at_ten`: 12 unread → `latest` count 10, `unread_count` 12.
  - `test_only_own_notifications_are_shared`: another user's notifications absent.
  - `test_guest_gets_null_notifications_prop`: `GET /login` → `notifications` null.
  (Create rows with `$user->notify(new ReportGenerated($report))` or direct `notifications()->create([...])` for legacy shapes.)
- [ ] **Step 2:** Run `--filter=SharedNotificationsTest`. Expected: FAIL.
- [ ] **Step 3:** Implement resource, shared prop, dashboard change.
- [ ] **Step 4:** Re-run. Expected: PASS.
- [ ] **Step 5:** Commit `feat: share notifications with Inertia pages`.

### Task 5: `NotificationController` (history + mark as read)

Invoke the `laravel-controllers` skill before writing this file.

**Files:**
- Create: `app/Http/Controllers/NotificationController.php` (`final`)
- Modify: `routes/web.php` (inside `auth` group, `Route::name('notifications.')->prefix('notifications')->controller(NotificationController::class)` like the other groups)
- Test: `tests/Feature/NotificationControllerTest.php`

**Interfaces:**
- Consumes: `NotificationResource` (Task 4).
- Produces routes:
  - `GET /notifications` → `notifications.index` → `Inertia::render('Notifications/Index', ['notifications' => paginated NotificationResource])`. QueryBuilder over `$request->user()->notifications()`, filter `status` (`AllowedFilter::callback`: `unread` → `whereNull('read_at')`, `read` → `whereNotNull('read_at')`), default sort `-created_at`, `per_page` from `config('pagination.list')`.
  - `POST /notifications/{notification}/read` → `notifications.read`: `$request->user()->notifications()->findOrFail($id)` (string uuid param, no implicit binding), `markAsRead()`, redirect to `data.url` if present else `back()`.
  - `POST /notifications/read-all` → `notifications.read-all`: `$request->user()->unreadNotifications->markAsRead()`, `back()`.

- [ ] **Step 1: Failing tests:**
  - `test_index_lists_only_own_notifications`, `test_index_filters_unread` (`?filter[status]=unread`).
  - `test_read_marks_notification_and_redirects_to_url`: `assertRedirect('/reports/{id}')`, `read_at` not null.
  - `test_read_without_url_redirects_back`: legacy row with `data` lacking `url` → redirect back (`from('/')` → `assertRedirect('/')`).
  - `test_cannot_read_foreign_notification`: → 404, foreign row `read_at` still null.
  - `test_read_all_marks_only_own`.
- [ ] **Step 2:** Run `--filter=NotificationControllerTest`. Expected: FAIL.
- [ ] **Step 3:** Implement controller + routes.
- [ ] **Step 4:** Re-run. Expected: PASS.
- [ ] **Step 5:** Commit `feat: add notification history and mark-as-read endpoints`.

### Task 6: Bell in `AppLayout.vue` + `Notifications/Index.vue`

**Files:**
- Modify: `resources/js/Layouts/AppLayout.vue:79-171` (replace the static template dropdown) and its `setup`/`mounted`/add `beforeUnmount`
- Create: `resources/js/Pages/Notifications/Index.vue` (`layout: AppLayout`, follow `Reports/Index.vue` for table/pagination/filter patterns)

**Interfaces:**
- Consumes: `$page.props.notifications` (Task 4), routes `notifications.index|read|read-all` (Task 5) via Ziggy `route()`.

Behaviour:
- Bell icon shows a badge with `unread_count` when > 0; dropdown lists `latest`; unread items visually bold.
- Icon/colour by level: `success` → `bg-success-dim ni-check-circle`, `danger` → `bg-danger-dim ni-alert-circle`, `warning` → `bg-warning-dim ni-alert`, `info` → `bg-info-dim ni-info`.
- Click item → `router.post(route('notifications.read', id))`. "Mark All as Read" → `router.post(route('notifications.read-all'), {}, { preserveScroll: true })`. "View All" → `Link` to `notifications.index`. Empty state text: `Brak powiadomień`.
- Polling: in `mounted`, `setInterval(() => router.reload({ only: ['notifications'] }), 60000)`; store handle; `clearInterval` in `beforeUnmount`. Skip the reload when `document.hidden`.
- Index page: list with title, message, time, read state; filter tabs Wszystkie / Nieprzeczytane / Przeczytane (`filter[status]`); pagination; "Oznacz wszystkie jako przeczytane" button.

- [ ] **Step 1:** Implement both files.
- [ ] **Step 2:** Run `docker exec rolsys npm run build`. Expected: build succeeds without errors.
- [ ] **Step 3:** Manual check in browser (`https://rolsys.localhost`, seeded user): badge count matches DB, clicking item navigates and decrements badge, Mark All clears badge, View All page filters work; create a notification in tinker and confirm it appears within ~60 s without navigating.
- [ ] **Step 4:** Commit `feat: wire notification bell and history page`.

### Task 7: Cleanup — seeder, broken helper

**Files:**
- Modify: `database/seeders/NotificationSeeder.php`: replace the fake types with the three real classes (`App\Notifications\AnalysisCompleted|AnalysisFailed|ReportGenerated`) and write the uniform `data` shape (Polish copy as in Task 2; `url` to existing analytics/report ids of that user when present, else `null`). Keep ~70% read ratio and random `created_at` over 14 days.
- Modify: `app/helpers.php`: delete `notifyDevelopers()` (unused anywhere; references nonexistent `App\Enums\NotificationType` and `User::developer()`), and remove now-unused imports (`NotificationType`, `DevsNotification`, `Notification`, plus `Mail`/`Cache` only if unused elsewhere in the file).
- Test: `tests/Feature/NotificationSeederTest.php`

- [ ] **Step 1: Failing test** `test_seeded_notifications_have_uniform_shape`: create 2 users, `$this->seed(NotificationSeeder::class)`, assert every row's `type` is one of the three classes and `data` has keys `title, message, level, url`.
- [ ] **Step 2:** Run. Expected: FAIL.
- [ ] **Step 3:** Implement seeder + helper cleanup.
- [ ] **Step 4:** Run full suite `docker exec rolsys php artisan test`. Expected: all PASS.
- [ ] **Step 5:** Commit `chore: align notification seeder and drop broken notifyDevelopers helper`.
