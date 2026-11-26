# Field Data on Field Page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Show a field's latest readings, a trend chart and recent records on `Field/Show`.

**Architecture:** A `FieldDataSummaryService` turns the field's `field_data` rows into three scalar-only props
(`latestReadings`, `trendSeries`, `recentReadings`) using one metric map; `FieldController::show()` passes them to
Inertia; three small Vue components render them on the existing page.

**Tech Stack:** Laravel 11, Inertia 2 + Vue 3, vue-echarts 7 / echarts 5, PHPUnit (MySQL `laravel_testing` DB).

**Spec:** `docs/superpowers/specs/2026-11-23-field-data-on-field-page-design.md`

## Global Constraints

- House style from the `laravel-controllers` skill: `final class`, `private readonly` injection, named arguments,
  return types, **no comments in generated code** (PHP, JS, Vue), all code and UI copy in English.
- Never `git commit` — Mario commits himself. Where a task ends, stop and report the changed files.
- Tests run in Docker: `docker exec rolsys php artisan test --filter=...`; frontend build: `docker exec rolsys npm run build`.
- No prop may contain the raw `data` / `metadata` JSON of a record.
- Value extraction happens in PHP, not with MySQL JSON operators.

## Review Focus

1. User-entered record without the expected key, or with a non-numeric value (`"avg_ndvi": "abc"`) — skipped for
   tiles and chart, shown as `—` in recent table, no exception. → Task 1 tests.
2. Seed data that is weeks old — chart window anchored to the newest record of that type, not to today. → Task 1 test.
3. Two records of the same type on the same date — averaged into one chart point. → Task 1 test.
4. Field whose only data is text kinds (growth stage, fertilization) — tiles shown, chart card hidden. → Task 3 manual check.
5. Another user's field — 403, no data leaked. → Task 2 test.

---

### Task 1: FieldDataFactory + FieldDataSummaryService

**Files:**
- Create: `database/factories/FieldDataFactory.php`
- Create: `app/Services/FieldDataSummaryService.php`
- Test: `tests/Feature/FieldDataSummaryServiceTest.php` (feature, because it needs the DB)

**Interfaces:**
- Produces: `FieldDataSummaryService::summarize(Field $field): array` returning exactly:

```php
[
    'latestReadings' => [ // one entry per known data_type with at least one valid record, in metric-map order
        ['type' => 'ndvi', 'label' => 'NDVI', 'unit' => null, 'kind' => 'numeric',
         'display' => '0.720', 'date' => '2026-11-20', 'trend' => 'up'|'down'|'flat'|null],
    ],
    'trendSeries' => [ // numeric kinds only, keyed by data_type, points ascending by date
        'ndvi' => ['label' => 'NDVI', 'unit' => null, 'points' => [['date' => '2026-11-01', 'value' => 0.71]]],
    ],
    'recentReadings' => [ // 5 newest records of any type, newest first
        ['id' => 12, 'date' => '2026-11-20', 'label' => 'NDVI', 'display' => '0.720'],
    ],
]
```

- Produces: `FieldDataFactory` default state: `field_id => Field::factory()`, `collection_date => now()->toDateString()`,
  `data_type => 'ndvi'`, `data => ['avg_ndvi' => 0.5]`.

Metric map (private const in the service, order = tile order):

| data_type | key | label | unit | kind | decimals / format |
|---|---|---|---|---|---|
| ndvi | avg_ndvi | NDVI | null | numeric | 3 |
| soil_moisture | avg_moisture | Soil moisture | % | numeric | 1, value ×100 |
| weather | temperature_avg | Air temperature | °C | numeric | 1 |
| soil_temperature | avg_temperature | Soil temperature | °C | numeric | 1 |
| biomass_measurement | dry_biomass_kg_per_ha | Dry biomass | kg/ha | numeric | 0 |
| sunlight | sunshine_hours | Sunshine | h | numeric | 1 |
| growth_stage | growth_stage | Growth stage | null | text | `"{growth_stage} (BBCH {bbch_code})"`, without the bracket when no `bbch_code` |
| fertilizer_application | fertilizer_type | Last fertilization | null | text | `"{fertilizer_type} {amount_per_hectare} kg/ha"`, without amount part when missing |

Rules:
- Numeric value valid only when the key exists and `is_numeric()`; `display` = `number_format($value, $decimals, '.', '')`.
- Text value valid only when the key exists and is a non-empty string.
- Invalid or unknown-type record: excluded from `latestReadings` / `trendSeries`; in `recentReadings` with
  `display => '—'` and `label => Str::headline($dataType)` for unknown types.
- `trend`: compare displayed (rounded) latest vs previous valid value of the same type: `up` / `down` / `flat`;
  `null` when there is no previous valid value or kind is `text`.
- Series window: from (newest valid date of that type − 90 days) to that date, inclusive; same-date values averaged
  and rounded to the type's decimals; `value` is a float.
- One query: the field's records ordered by `collection_date desc, id desc`.

- [ ] **Step 1: Write the factory and the failing tests**

```php
test_soil_moisture_is_shown_as_percent
    // soil_moisture, data ['avg_moisture' => 0.312]
    // latestReadings[0]: type 'soil_moisture', unit '%', display '31.2', trend null
    // trendSeries['soil_moisture']['points'][0]['value'] === 31.2

test_trend_compares_latest_with_previous_reading
    // ndvi 0.5 on 2026-01-01, 0.7 on 2026-01-02 -> trend 'up', display '0.700'
    // second field: ndvi 0.7, 0.7 -> trend 'flat'

test_invalid_records_are_skipped_but_listed_as_recent
    // ndvi ['avg_ndvi' => 0.6] on 2026-01-01, ['foo' => 1] on 2026-01-02, ['avg_ndvi' => 'abc'] on 2026-01-03
    // latestReadings[0]['display'] === '0.600', date '2026-01-01', trend null
    // trendSeries['ndvi']['points'] has 1 item
    // recentReadings has 3 items, [0]['display'] === '—', [1]['display'] === '—'

test_unknown_type_appears_only_in_recent_readings
    // data_type 'custom_sensor', data ['value' => 3]
    // latestReadings === [], trendSeries === [], recentReadings[0]['label'] === 'Custom Sensor', display '—'

test_series_window_is_anchored_to_newest_record
    // ndvi on 2025-01-10, 2025-01-11, 2025-04-11 (all valid)
    // points dates === ['2025-01-11', '2025-04-11']

test_same_date_values_are_averaged
    // ndvi 0.4 and 0.6 on 2025-05-01 -> one point, value 0.5

test_text_kinds_have_display_but_no_series
    // growth_stage ['growth_stage' => 'flowering', 'bbch_code' => 61] -> display 'flowering (BBCH 61)', kind 'text'
    // fertilizer_application ['fertilizer_type' => 'nitrogen', 'amount_per_hectare' => 120] -> display 'nitrogen 120 kg/ha'
    // trendSeries === []

test_recent_readings_are_limited_to_five_newest
    // 7 ndvi records on consecutive dates -> recentReadings has 5, [0]['date'] is the newest
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `docker exec rolsys php artisan test --filter=FieldDataSummaryServiceTest`
Expected: FAIL, `FieldDataSummaryService` class not found.

- [ ] **Step 3: Implement `FieldDataSummaryService::summarize(Field $field): array`** per the table and rules above.
  Keep private helpers small (`extractValue`, `trendFor`, `seriesFor`); no comments.

- [ ] **Step 4: Run tests to verify they pass**

Run: `docker exec rolsys php artisan test --filter=FieldDataSummaryServiceTest`
Expected: 8 tests PASS.

- [ ] **Step 5: Stop — report changed files to Mario (no commit).**

---

### Task 2: Pass summary props from FieldController::show()

**Files:**
- Modify: `app/Http/Controllers/FieldController.php` (constructor, `show()`)
- Test: `tests/Feature/FieldControllerTest.php`

**Interfaces:**
- Consumes: `FieldDataSummaryService::summarize(Field $field): array` (Task 1).
- Produces: `Field/Show` props `field`, `latestReadings`, `trendSeries`, `recentReadings`.

- [ ] **Step 1: Write the failing tests**

```php
test_show_includes_field_data_summary
    // own field + ndvi record ['avg_ndvi' => 0.72]
    // assertInertia: component 'Field/Show', has('latestReadings', 1),
    //   where('latestReadings.0.display', '0.720'), has('trendSeries.ndvi'), has('recentReadings', 1)

test_show_returns_empty_summary_for_field_without_data
    // has('latestReadings', 0), has('recentReadings', 0), where('trendSeries', [])

test_show_forbids_other_users_field
    // assertForbidden()
```

- [ ] **Step 2: Run to verify failure**

Run: `docker exec rolsys php artisan test --filter=FieldControllerTest`
Expected: first two FAIL (missing props), third PASS already.

- [ ] **Step 3: Inject `private readonly FieldDataSummaryService $fieldDataSummary`** (one dependency per line) and
  spread `$this->fieldDataSummary->summarize(field: $field)` into `show()` props after `'field'`.

- [ ] **Step 4: Run to verify pass**

Run: `docker exec rolsys php artisan test --filter="FieldControllerTest|FieldDataSummaryServiceTest"`
Expected: all PASS.

- [ ] **Step 5: Stop — report changed files to Mario (no commit).**

---

### Task 3: Vue components on Field/Show

**Files:**
- Create: `resources/js/Components/Field/LatestReadingTiles.vue`
- Create: `resources/js/Components/Field/FieldTrendChart.vue`
- Create: `resources/js/Components/Field/RecentReadingsTable.vue`
- Modify: `resources/js/Pages/Field/Show.vue`

**Interfaces:**
- Consumes: props from Task 2.
- `LatestReadingTiles` props: `readings: Array` (latestReadings shape). Tile: label, `display` + `unit`, date, trend icon
  (`ni-arrow-up` green / `ni-arrow-down` red / `ni-minus` grey / nothing for `null`). Responsive grid `col-6 col-md-4 col-xl-3`.
- `FieldTrendChart` props: `series: Object` (trendSeries shape). `<select>` of `series` keys showing `label`;
  default `ndvi` when present, else first key. vue-echarts line chart (register `CanvasRenderer, LineChart,
  GridComponent, TooltipComponent` like `DataVisualization.vue`), x = dates (category), y name = unit, smooth line,
  height 320px.
- `RecentReadingsTable` props: `readings: Array`, `fieldId: Number`. Columns Date / Type / Value; each row links to
  `route('field-data.show', id)`; header link "See all" to `route('field-data.index', { 'filter[field_id]': fieldId })`.
- `Show.vue` order: map → tiles → chart → recent table → existing two info cards → buttons. Chart card only when
  `Object.keys(trendSeries).length`. When `recentReadings.length === 0`: one card "No data collected for this field
  yet" with button "Add data" → `route('field-data.create')`, instead of tiles/chart/table.
- Script style: `<script setup>` in new components; keep `Show.vue` Options-API style as it is, adding the three props.

- [ ] **Step 1: Create the three components and wire them into `Show.vue`.**

- [ ] **Step 2: Build**

Run: `docker exec rolsys npm run build`
Expected: build succeeds, no errors.

- [ ] **Step 3: Manual check in the browser** (seeded field 1, a field with no data, a field with only a
  `growth_stage` record):
  - field 1: 8 tiles, chart switches between 6 numeric types, table shows 5 rows, "See all" lists only field 1 data;
  - empty field: only the "No data" card under the map;
  - growth-stage-only field: one tile, no chart card, table with one row;
  - no console errors; layout OK at phone width.

- [ ] **Step 4: Run the full test suite**

Run: `docker exec rolsys php artisan test`
Expected: all PASS.

- [ ] **Step 5: Stop — report changed files to Mario (no commit).**
