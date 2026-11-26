# Field Analytics on Field Page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Turn `Field/Show` into a tabbed page (Overview / Data / Analytics) that shows what to do now, the history of
AI analyses and lets the user run a new analysis — with seed data that is complete and consistent for every field.

**Architecture:** A shared `AnalysisResultsBuilder` produces the exact `results` shape the Python AI service returns;
the factory and `AnalyticsSeeder` use it, the seeder feeding it averages of each field's seeded readings.
`FieldAnalyticsSummaryService` turns a field's analytics rows into scalar-only props; `FieldController::show()` adds
them; Vue components render three tabs whose state lives in `?tab=`.

**Tech Stack:** Laravel 11, Inertia 2 + Vue 3, vue-echarts 7 / echarts 5, PHPUnit (MySQL `laravel_testing` DB).

**Spec:** `docs/superpowers/specs/2026-11-25-field-analytics-on-field-page-design.md`

## Global Constraints

- House style from the `laravel-controllers` skill: `final class`, `private readonly` injection, named arguments,
  return types, **no comments in generated code** (PHP, JS, Vue), all code and UI copy in English.
- Never `git commit` — Mario commits himself. Where a task says "Stop", report the changed files instead.
- Tests: `docker exec rolsys php artisan test --filter=...`; frontend build: `docker exec rolsys npm run build`.
- Frontend verification (Tasks 6–8) is done with the Playwright MCP (`mcp__plugin_playwright_playwright__*`) against
  `http://rolsys.localhost` after `migrate:fresh --seed`, logged in as a seeded `rolnik` user: navigate, click, take a
  snapshot/screenshot, and read `browser_console_messages` — zero console errors is part of every check.
- No Inertia prop may contain raw `results` / `parameters` JSON of an analysis.
- Do not modify `ai/`, `Analytics/Show.vue`, `Analytics/Index.vue`, `AppLayout.vue`.
- Moisture is stored 0–1; shown as percent ×100, 1 decimal. NDVI shown with 2 decimals.
- AI thresholds (copy exactly): NDVI `<0.3 Critical`, `<0.5 Poor`, `<0.7 Moderate`, else `Good`;
  moisture `<0.3 Low`, `>0.7 High`, else `Optimal`.
- Priority order: `high` > `medium` > `low` > missing; tie-break `urgency_days` ascending, missing last; stable.

## Review Focus

1. Analysis with `vegetation_health: null` or `{error: "..."}` (real AI output when a field has no NDVI) — summary
   fields `null`, UI shows `—`, no exception. → Task 4 test `test_error_and_null_sub_results_become_null`.
2. Legacy rows where `recommendations` is a plain string (old seeder output already in Mario's DB) — cast yields
   non-array; treated as no recommendations. → Task 4 test `test_non_array_recommendations_are_ignored`.
3. Unknown `?tab=foo` or no `tab` — Overview shown. → Task 6 Playwright check.
4. AI service down — modal stays open showing the flash error instead of closing silently. → Task 1 test + Task 8
   Playwright check.
5. Re-running `db:seed` — no duplicated readings per field. → Task 2 test `test_reseeding_does_not_duplicate`.

---

### Task 1: Share flash messages

**Files:**
- Modify: `app/Http/Middleware/HandleInertiaRequests.php` (`share()`)
- Test: `tests/Feature/SharedFlashTest.php`

**Interfaces:**
- Produces: shared prop `flash` = `['success' => ?string, 'error' => ?string]` (lazy closure reading
  `$request->session()->get('success'|'error')`).

- [ ] **Step 1: Write failing test** `test_flash_error_is_shared_after_redirect`: acting as a user, call
  `$this->withSession(['error' => 'AI down'])->get(route('fields.index'))` and assert
  `->where('flash.error', 'AI down')->where('flash.success', null)`.
- [ ] **Step 2: Run** `--filter=SharedFlashTest` → FAIL (prop `flash` missing).
- [ ] **Step 3: Add `'flash' => fn () => [...]`** next to `notifications` in `share()`.
- [ ] **Step 4: Run** `--filter=SharedFlashTest` and `--filter=SharedNotificationsTest` → PASS.
- [ ] **Step 5: Stop**, report files.

### Task 2: AnalysisResultsBuilder + AnalyticsFactory + readings for every field

**Files:**
- Create: `database/factories/Support/AnalysisResultsBuilder.php` (namespace `Database\Factories\Support`)
- Modify: `database/factories/AnalyticsFactory.php`
- Modify: `database/seeders/FieldDataSeeder.php`
- Test: `tests/Unit/AnalysisResultsBuilderTest.php`, `tests/Feature/FieldDataSeederTest.php`

**Interfaces:**
- Produces: `final class AnalysisResultsBuilder` with
  `public static function build(array $ndvi, array $moisture, string $date, bool $withPrediction, int $predictionDays = 7): array`
  where `$ndvi` / `$moisture` are `['avg' => float, 'min' => float, 'max' => float]` or `null` (no readings).
  Returns the full `results` array:
  - `field_id` omitted; `analysis_date` = `$date`.
  - `vegetation_health`: `null` if `$ndvi === null`, else `{avg_ndvi, min_ndvi, max_ndvi, health_status,
    problem_area_percent, recommendation}`; `problem_area_percent` = `0` if avg ≥ 0.6, else
    `round((0.6 - avg) * 150, 1)` capped at 100; recommendation texts from `ai/analytics/vegetation_health.py`.
  - `soil_moisture`: `null` if `$moisture === null`, else `{avg_moisture, min_moisture, max_moisture,
    predicted_moisture (= avg), moisture_status, dry_area_percent, recommendation}`; `dry_area_percent` = `0` if
    avg ≥ 0.35, else `round((0.35 - avg) * 200, 1)` capped at 100; texts from `ai/analytics/soil_moisture.py`.
  - `anomalies`: one `{date, type: 'ndvi_anomaly', description, value: min_ndvi, expected: avg_ndvi, severity: 'high'}`
    when `avg_ndvi - min_ndvi > 0.25`, else `[]`.
  - `recommendations`: exactly the rules of `generate_basic_recommendations` in `ai/app.py` (same `type`, `priority`,
    `action`, `description` format with 2 decimals, `source`), default "Continue standard procedures" when empty.
    When `$withPrediction`, append one `{type: 'scheduled', priority: 'medium', category: 'monitoring',
    action: 'Plan next field inspection', description, urgency_days: 7, source: 'growth_prediction'}`, and if
    moisture status is `Low` also `{type: 'irrigation', priority: 'high', action: 'Irrigate within 3 days',
    urgency_days: 3, source: 'growth_prediction'}`.
  - `growth_prediction` (only when `$withPrediction`): `current_biomass` (float), `current_status`
    `{growth_stage, health_score (int 0–100 = round(min(1, avg_ndvi/0.8)*70 + moisture_score*30) where
    moisture_score = 1 for Optimal else 0.5), days_since_planting, issues: [], warnings: [], key_metrics:
    {ndvi, soil_moisture, temperature}}`, `predictions` (`$predictionDays` items `{day, date, predicted_biomass,
    daily_growth, confidence}`), `summary {total_predicted_growth, average_daily_growth, confidence_range: {min,max,avg}}`,
    `model_info {model_type: 'RandomForestRegressor', features_used: 12, version: '1.0.0', last_updated}`.
    Check `resources/js/Pages/Analytics/Show.vue` lines ~200–450 for every key read; all must be present.
- Produces: `AnalyticsFactory` default = standard analysis built with NDVI `0.72/0.55/0.85`, moisture
  `0.45/0.30/0.60`; `recommendations` = `results['recommendations']`; `parameters` in `FieldAnalysisService` shape;
  state `withPrediction()` → `analysis_type = 'comprehensive_with_prediction'` and prediction included.
- Produces: `FieldDataSeeder` seeds every `Field` (keeps `ensureFieldExists()` for id 1). Each private
  `seed*Data()` takes `Field $field` and a per-field profile `['ndvi_offset' => float in [-0.30, 0.05],
  'moisture_base' => float in [0.22, 0.55]]` derived deterministically from `$field->id` (e.g. `mt_srand($field->id)`
  before generating it). Delete-then-insert per field. Use bulk inserts per field/type (json-encode `data`/`metadata`,
  set timestamps) so a full seed stays fast.

- [ ] **Step 1: Write failing unit tests** in `AnalysisResultsBuilderTest`:
  - `test_statuses_follow_ai_thresholds`: ndvi avg 0.29 → `Critical`, 0.49 → `Poor`, 0.69 → `Moderate`, 0.7 →
    `Good`; moisture 0.29 → `Low`, 0.71 → `High`, 0.5 → `Optimal`.
  - `test_low_moisture_and_poor_ndvi_produce_high_priority_recommendations`: actions contain
    `Inspect crop condition` and `Increase irrigation`, both `priority` `high`.
  - `test_healthy_field_gets_default_recommendation`: single item, action `Continue standard procedures`.
  - `test_prediction_contains_keys_read_by_show_page`: `withPrediction=true` → `assertArrayHasKey` for
    `current_biomass`, `current_status.health_score`, `current_status.days_since_planting`, `summary.confidence_range.avg`,
    `predictions` count 7, `model_info.model_type`.
  - `test_missing_ndvi_gives_null_vegetation_health`.
- [ ] **Step 2: Write failing feature tests** in `FieldDataSeederTest`: `test_every_field_gets_ndvi_and_moisture`
  (2 factory fields → each has ≥ 90 `ndvi` and ≥ 90 `soil_moisture` rows), `test_reseeding_does_not_duplicate`
  (seed twice → same count).
- [ ] **Step 3: Run** `--filter='AnalysisResultsBuilderTest|FieldDataSeederTest'` → FAIL.
- [ ] **Step 4: Implement** builder, factory, seeder refactor.
- [ ] **Step 5: Run** the two tests plus full suite (`php artisan test`) → PASS (existing tests using
  `Analytics::factory()` must still pass).
- [ ] **Step 6: Stop**, report files.

### Task 3: AnalyticsSeeder derived from readings

**Files:**
- Modify: `database/seeders/AnalyticsSeeder.php` (full rewrite)
- Test: `tests/Feature/AnalyticsSeederTest.php`

**Interfaces:**
- Consumes: `AnalysisResultsBuilder::build(...)` (Task 2); seeded `field_data` with `data.avg_ndvi`,
  `data.avg_moisture`.
- Produces: for each field, delete its analytics, then 3–6 analyses (`mt_srand($field->id)`), analysis dates spread
  evenly between the field's oldest and newest reading (newest = newest reading date). Window for each analysis =
  14 days ending on its date; `$ndvi`/`$moisture` = avg/min/max of `avg_ndvi` / `avg_moisture` of readings in the
  window (null if none). Every other analysis (index odd) is `comprehensive_with_prediction`. Fields without readings
  get no analyses. Row via `Analytics::create` with `recommendations = results['recommendations']` and
  `parameters = {sensitivity: 'medium', data_count, include_prediction, prediction_days: 7, date_range: {from, to}}`.

- [ ] **Step 1: Write failing tests:**
  - `test_each_field_with_readings_gets_three_to_six_analyses`.
  - `test_recommendations_are_non_empty_arrays`.
  - `test_avg_ndvi_matches_readings_in_window`: for one analysis, compute mean of `avg_ndvi` of that field's NDVI
    readings with `collection_date` in `[date-13, date]`, `assertEqualsWithDelta(..., 0.001)`.
  - `test_field_without_readings_gets_no_analyses`.
  Seed via `$this->seed([FieldDataSeeder::class, AnalyticsSeeder::class])` with factory fields.
- [ ] **Step 2: Run** `--filter=AnalyticsSeederTest` → FAIL.
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Run** `--filter='AnalyticsSeederTest|NotificationSeederTest'` → PASS; then
  `docker exec rolsys php artisan migrate:fresh --seed` succeeds.
- [ ] **Step 5: Stop**, report files.

### Task 4: FieldAnalyticsSummaryService

**Files:**
- Create: `app/Services/FieldAnalyticsSummaryService.php`
- Test: `tests/Feature/FieldAnalyticsSummaryServiceTest.php`

**Interfaces:**
- Consumes: `AnalyticsFactory` (Task 2).
- Produces: `final class FieldAnalyticsSummaryService`, `summarize(Field $field): array` returning exactly:

```php
[
    'latestAnalysis' => null | [
        'id' => 7, 'date' => '2026-11-20', 'type' => 'comprehensive_with_prediction', 'type_label' => 'With prediction',
        'vegetation' => null | ['status' => 'Good', 'ndvi' => 0.72],
        'moisture' => null | ['status' => 'Low', 'percent' => 27.5],
        'health_score' => null | 82,
        'anomaly_count' => 1,
        'recommendations' => [['priority' => 'high', 'action' => '...', 'description' => '...', 'urgency_days' => 3, 'source' => 'growth_prediction']],
    ],
    'topRecommendations' => [/* first 3 of latestAnalysis.recommendations, [] when none */],
    'analysisHistory' => [ // newest first
        ['id' => 7, 'date' => '2026-11-20', 'type_label' => 'With prediction', 'vegetation_status' => 'Good',
         'ndvi' => 0.72, 'moisture_status' => 'Low', 'moisture_percent' => 27.5, 'health_score' => 82,
         'recommendation_count' => 2, 'anomaly_count' => 1],
    ],
]
```

  One query: `$field->analytics()` (existing `HasMany` on `Field`) ordered
  `analysis_date desc, id desc`, columns `id, field_id, analysis_type, analysis_date, results, recommendations`.
  Recommendation source: `recommendations` column if array, else `[]`; items that are not arrays or lack a string
  `action` are dropped. Type labels: `standard_analysis` → `Standard`, `comprehensive_with_prediction` →
  `With prediction`, else `Str::headline`.

- [ ] **Step 1: Write failing tests:**
  - `test_field_without_analyses_returns_empty_summary`: `latestAnalysis` null, both arrays `[]`.
  - `test_recommendations_sorted_by_priority_then_urgency`: input `[low, high(urgency 7), medium, high(urgency 3), {no priority}]`
    → order `high(3), high(7), medium, low, none`.
  - `test_top_recommendations_are_first_three`.
  - `test_error_and_null_sub_results_become_null`: `vegetation_health => ['error' => 'x']`, `soil_moisture => null`,
    no `growth_prediction` → `vegetation`, `moisture`, `health_score` null.
  - `test_moisture_scaled_to_percent`: `avg_moisture 0.275` → `percent 27.5`; `avg_ndvi 0.7234` → `ndvi 0.72`.
  - `test_non_array_recommendations_are_ignored`: row inserted via `DB::table` with `recommendations` = JSON string
    `"\"Zalecamy...\""` → `recommendations` `[]`, `recommendation_count` 0.
  - `test_history_is_newest_first_and_latest_is_first_item`.
- [ ] **Step 2: Run** `--filter=FieldAnalyticsSummaryServiceTest` → FAIL.
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Run** → PASS.
- [ ] **Step 5: Stop**, report files.

### Task 5: FieldController wiring

**Files:**
- Modify: `app/Http/Controllers/FieldController.php` (`__construct`, `show()`, `analytics()`)
- Test: `tests/Feature/FieldControllerTest.php`

**Interfaces:**
- Consumes: `FieldAnalyticsSummaryService::summarize` (Task 4).
- Produces: `Field/Show` props = existing + `latestAnalysis`, `topRecommendations`, `analysisHistory`,
  `sensitivities` (`['low', 'medium', 'high']`). `fields.analytics` → redirect to
  `route('fields.show', ['field' => $field, 'tab' => 'analytics'])`.

- [ ] **Step 1: Write failing tests:** `test_show_includes_analytics_summary` (one factory analysis →
  `has('analysisHistory', 1)`, `has('latestAnalysis.recommendations')`, `where('sensitivities', ['low','medium','high'])`),
  `test_show_returns_empty_analytics_for_field_without_analyses` (`where('latestAnalysis', null)`),
  `test_analytics_route_redirects_to_analytics_tab` (`assertRedirect(route('fields.show', ['field' => $field, 'tab' => 'analytics']))`).
- [ ] **Step 2: Run** `--filter=FieldControllerTest` → FAIL.
- [ ] **Step 3: Implement** (use laravel-controllers skill style).
- [ ] **Step 4: Run** `--filter='FieldControllerTest|AnalyticsControllerTest'` → PASS.
- [ ] **Step 5: Stop**, report files.

### Task 6: Tabs on Field/Show + Overview "What to do now"

**Files:**
- Modify: `resources/js/Pages/Field/Show.vue`
- Create: `resources/js/Components/Field/RecommendationList.vue`

**Interfaces:**
- Consumes: props from Task 5.
- Produces: `RecommendationList.vue` props `items: Array` (shape of `latestAnalysis.recommendations`),
  `compact: Boolean` (compact = action + priority badge only, otherwise also description, urgency badge
  `N days`, source). Priority badge classes: high `badge-danger`, medium `badge-warning`, low `badge-success`,
  missing `badge-light`.
- Produces: in `Show.vue` a `activeTab` ref (`'overview' | 'data' | 'analytics'`) and `selectTab(tab)` that sets it
  and calls `history.replaceState` with `?tab=` updated (other query params kept). Initial value from
  `new URLSearchParams(window.location.search).get('tab')`, unknown → `overview`.

Layout (DashLite `nav nav-tabs`, header keeps Back and adds Edit):
- Overview: map, `LatestReadingTiles`, "What to do now" card (`RecommendationList compact` of
  `topRecommendations`, subtitle `From analysis {date}`, button `See all analyses` → `selectTab('analytics')`;
  empty: `No AI analysis yet` + same button), the two info cards. The #33 empty-data card stays on Overview.
- Data: `FieldTrendChart` + `RecentReadingsTable` (or the empty-data card).
- Analytics: placeholder `<div>` filled in Task 7.

- [ ] **Step 1: Implement.**
- [ ] **Step 2: Run** `docker exec rolsys npm run build` → succeeds with no errors.
- [ ] **Step 3: Playwright check** (seeded user): `/fields/1` shows Overview; clicking tabs changes URL
  `?tab=`; refresh keeps tab; `/fields/1?tab=foo` shows Overview; `/fields/1/analytics` lands on Analytics tab.
- [ ] **Step 4: Stop**, report files.

### Task 7: Analytics tab content

**Files:**
- Create: `resources/js/Components/Field/LatestAnalysisCard.vue`, `AnalysisHistoryChart.vue`,
  `AnalysisHistoryTable.vue`
- Modify: `resources/js/Pages/Field/Show.vue` (Analytics tab)

**Interfaces:**
- Consumes: `latestAnalysis`, `analysisHistory`, `RecommendationList` (Task 6).
- Produces:
  - `LatestAnalysisCard` prop `analysis: Object` — date, type label badge, vegetation status + NDVI, moisture status
    + `%`, health score `%`, anomalies count; `—` for nulls; link `View details` → `route('analytics.show', analysis.id)`.
    Status colours: Good/Optimal success, Moderate info, Poor/Low/High warning, Critical danger.
  - `AnalysisHistoryChart` prop `history: Array` (newest first; reverse inside). Hidden (`v-if`) when
    `history.length < 2`. Same echarts registration as `FieldTrendChart.vue` plus `LegendComponent`. Series:
    `NDVI` on left axis (0–1), `Moisture %` and `Health score` on right axis (0–100), `connectNulls: true`.
  - `AnalysisHistoryTable` prop `history: Array` — columns Date | Type | Vegetation | Moisture | Recommendations |
    Anomalies | →; row link `route('analytics.show', row.id)`.
- Analytics tab order: header row with `Run analysis` button (opens modal from Task 8 — wire a `showRunModal` ref
  now), `LatestAnalysisCard`, card "Recommendations" with full `RecommendationList`, `AnalysisHistoryChart`,
  `AnalysisHistoryTable`. Empty (`latestAnalysis === null`): one card `No analyses for this field yet` + Run analysis.

- [ ] **Step 1: Implement.**
- [ ] **Step 2: Run** `npm run build` → succeeds.
- [ ] **Step 3: Playwright check:** seeded field shows chart with 3–6 points, health line with gaps, table rows open
  `analytics.show` without `NaN`/`undefined`; a field with zero analyses shows the empty card.
- [ ] **Step 4: Stop**, report files.

### Task 8: RunAnalysisModal

**Files:**
- Create: `resources/js/Components/Field/RunAnalysisModal.vue`
- Modify: `resources/js/Pages/Field/Show.vue`

**Interfaces:**
- Consumes: shared `flash` (Task 1), `sensitivities` prop (Task 5), existing route `analytics.analyze`.
- Produces: props `show: Boolean`, `fieldId: Number`, `sensitivities: Array`; emits `close`. Uses the project's
  `Modal.vue` / `DialogModal.vue` (Vue-state driven) — no `data-bs-*`, no DOM manipulation.
  `useForm({start_date: '', end_date: '', sensitivity: 'medium', include_growth_prediction: false, prediction_days: 7})`;
  `prediction_days` input visible only when growth prediction checked (1–30); end date `min` = start date.
  Submit: `form.post(route('analytics.analyze', fieldId), {preserveScroll: true})`. While `form.processing`: buttons
  disabled, spinner + `Running analysis… this can take up to a minute`. Validation errors under inputs
  (`InputError`). After finish, if `usePage().props.flash.error` is set, show it in a `alert alert-danger` inside
  the modal (modal stays open). Success redirect to `analytics.show` comes from the controller.

- [ ] **Step 1: Implement** and wire into Analytics tab (header button and empty-state button).
- [ ] **Step 2: Run** `npm run build` → succeeds.
- [ ] **Step 3: Playwright check:** with AI service running → submit lands on the new analysis page, and back on the
  field's Analytics tab the new analysis is first in history; with AI service stopped
  (`docker stop` the ai container) → modal stays open with `Analysis failed: …`; invalid range (end < start) shows
  the validation error.
- [ ] **Step 4: Run full suite** `docker exec rolsys php artisan test` → PASS.
- [ ] **Step 5: Stop**, report files.
