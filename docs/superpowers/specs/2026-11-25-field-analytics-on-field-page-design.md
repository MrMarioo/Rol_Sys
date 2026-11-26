# Field page: show the field's AI analytics (issue #34)

## Intent

After #33 the field page (`Field/Show.vue`) shows the map, latest-reading tiles, a trend chart and recent records.
AI analyses of the field live only on the generic `/analytics` list; `fields/{field}/analytics` just redirects there
with a filter. Goal: someone opening a field sees, in one place:

1. **what to do now** — the most urgent recommendations from the latest analysis,
2. **what has been happening** — how results of successive analyses changed over time (history matters),
3. **a way to run a new analysis** for this field without leaving it.

The project is a portfolio piece (recruiters browse the seeded demo), so the page has to look concrete and populated
with seed data, not empty.

## Goals / non-goals / anti-goals

- Goals: tabbed field page (Overview / Data / Analytics), "what to do now" block on Overview, Analytics tab with
  latest-analysis summary, all its recommendations, history chart and history table, "Run analysis" modal, seeders
  producing readings for every field and analyses in the real shape derived from those readings.
- Non-goals: changing the AI service, changing `Analytics/Show.vue` / `Analytics/Index.vue` (beyond them benefiting
  from correct seed data), async/queued analysis, comparing two analyses side by side, PDF download changes.
- Anti-goals: shipping raw `results` JSON to the browser; crashing or showing `undefined` when an analysis has
  `vegetation_health: null`, `{error: ...}` sub-results, or no recommendations; an Analytics tab that is empty in the
  seeded demo.

## Approaches considered

1. **One "AI analysis" section appended below the data on the existing page** — least restructuring, but the page
   gets long and the "what to do now" information is far from the top.
2. **Tabs Overview / Data / Analytics** — **chosen by Mario** ("looks more concrete"). Rejected in #33 because tabs
   would have been empty; with analytics there is now enough content. "What to do now" stays visible on Overview so it
   isn't hidden behind a click.
3. Lazy-loaded tab content (Inertia partial reloads) — rejected: payloads are small, eager props keep tab switching
   instant and the code simpler.

## Real analysis shape (source of truth: `ai/app.py`)

`analytics.results` written by `FieldAnalysisService`:

```
vegetation_health: null | {error} | {avg_ndvi (0–1), min_ndvi, max_ndvi, health_status: Critical|Poor|Moderate|Good,
                                     problem_area_percent, recommendation}
soil_moisture:     null | {error} | {avg_moisture (0–1), min_moisture, max_moisture, predicted_moisture,
                                     moisture_status: Low|Optimal|High, dry_area_percent, recommendation}
anomalies:         [{date, type, description, value, expected, severity: low|medium|high}]
recommendations:   [{type, priority: high|medium|low, action, description, source, urgency_days?, category?}]
growth_prediction: absent | {error} | {current_status: {health_score (0–100), growth_stage, ...}, predictions, summary, ...}
```

`analytics.recommendations` = `results.recommendations` (array). `analysis_type` ∈ `standard_analysis`,
`comprehensive_with_prediction`.

## Page layout

```
[ Field: <name>                                   ← Back | Edit ]
[ Overview | Data | Analytics ]

OVERVIEW (default)
[ Map                                                         ]
[ latest-reading tiles (from #33)                             ]
[ What to do now (from analysis YYYY-MM-DD)   → Analytics tab ]
[   ▲high  Increase irrigation — Soil moisture below optimal  ]
[   ●med   …                                                  ]
[ Name/size/location card | Status/description card           ]

DATA
[ Trend chart (from #33) ]  [ Recent readings table (from #33) ]

ANALYTICS                                      [Run analysis]
[ Latest analysis: date · type · Vegetation Good · Moisture Low · Health 82% · → details ]
[ All recommendations of the latest analysis                                   ]
[ History chart: avg NDVI, avg moisture %, health score over analysis dates     ]
[ History table: date | type | vegetation | moisture | #recs | #anomalies | →   ]
```

Empty states:
- No analyses: Overview "what to do now" block shows "No AI analysis yet" + link to the Analytics tab; Analytics tab
  shows one card "No analyses for this field yet" + Run analysis button.
- No field data: existing #33 empty card on Overview/Data stays; Run analysis is still offered (AI service handles
  empty data).

## Design

### Server side

New `App\Services\FieldAnalyticsSummaryService` (same pattern as `FieldDataSummaryService`), `summarize(Field)`
returns props:

- `latestAnalysis`: `null` or `{id, date, type, type_label, vegetation: {status, ndvi} | null,
  moisture: {status, percent} | null, health_score | null, anomaly_count, recommendations: [...]}`.
  `recommendations` items: `{priority, action, description, urgency_days | null, source | null}`, sorted by priority
  (high > medium > low > missing) then `urgency_days` ascending (missing last).
- `topRecommendations`: first 3 of the above (for Overview).
- `analysisHistory`: all analyses of the field, newest first, each `{id, date, type_label, vegetation_status,
  ndvi, moisture_status, moisture_percent, health_score, recommendation_count, anomaly_count}`; chart uses the same
  list reversed (oldest → newest).
- Sub-result that is `null`, missing, or contains `error` → its fields are `null`; frontend shows "—".
  Moisture stored 0–1 → `percent` ×100, 1 decimal; NDVI 2–3 decimals.
- One query: field's analytics ordered by `analysis_date desc, id desc`, selecting only needed columns. Parsing in
  PHP.
- Type labels: `standard_analysis` → "Standard", `comprehensive_with_prediction` → "With prediction", unknown →
  `Str::headline`.

`FieldController::show()` adds `...$this->fieldAnalyticsSummary->summarize(field: $field)` next to the existing
summary, plus `sensitivities` (`['low','medium','high']`) for the modal. House style (laravel-controllers skill).

`FieldController::analytics()` redirects to `fields.show` with `tab=analytics` instead of the generic analytics list.

### Tabs

- Active tab lives in the query string: `?tab=overview|data|analytics`, default `overview`, unknown value →
  `overview`.
- Switching tabs updates the URL with `history.replaceState` (no Inertia visit, no reload); initial tab read from
  `window.location` on mount. Back/forward between pages keeps working; refresh keeps the tab.
- The "→ Analytics tab" link on Overview switches tabs in place.
- DashLite `nav nav-tabs` markup for consistency.

### Frontend components (`resources/js/Components/Field/`)

- `FieldTabs.vue` (or inline in Show.vue — builder's call) — tab nav.
- `RecommendationList.vue` — renders recommendation items with priority badges; used on Overview (top 3) and
  Analytics tab (all).
- `LatestAnalysisCard.vue` — summary row with status badges and link to `analytics.show`.
- `AnalysisHistoryChart.vue` — vue-echarts line chart, same setup style as `FieldTrendChart.vue`; NDVI and health
  score/moisture % on two y-axes (0–1 and 0–100). Hidden when fewer than 2 analyses.
- `AnalysisHistoryTable.vue` — rows link to `analytics.show`.
- `RunAnalysisModal.vue` — form fields as in `Analytics/Index.vue` (start/end date, sensitivity, include growth
  prediction, prediction days) without the field select; posts with Inertia `useForm` to
  `route('analytics.analyze', field.id)`. Opened/closed via Vue state (not Bootstrap `data-bs-*` DOM hacks used in
  `Analytics/Index.vue`). Shows a "running…" state while processing (request is synchronous, up to 60 s) and
  displays validation / flash errors. On success the existing controller redirect to `analytics.show` applies.

### Seeders (decided by Mario: option c — consistent demo for every field)

Today `FieldDataSeeder` seeds readings only for field id 1, so other fields would show empty Overview/Data next to a
full analytics history, and random analysis numbers would contradict field 1's own tiles. Therefore:

- **`FieldDataSeeder`** seeds readings for **every** field (same 90-day pattern and data types it produces today for
  field 1, with per-field variation in base levels so fields don't look identical). Must stay idempotent like today
  (delete-then-insert per field).
- **`AnalyticsSeeder`** runs after `FieldDataSeeder` and **derives** each analysis from that field's seeded readings:
  3–6 analyses per field at intervals over the readings' 90-day span; for each analysis, `avg_ndvi` / `avg_moisture`
  (and min/max) are computed from the NDVI / soil-moisture readings in the window preceding the analysis date, so
  analysis values match the trend chart. Statuses use the AI service thresholds (`vegetation_health.py`,
  `soil_moisture.py`); recommendations follow `generate_basic_recommendations` in `ai/app.py` (always at least one).
- **Full shape for `Analytics/Show.vue`** (which stays untouched and is the target of every "details" link): every
  numeric key it calls `.toFixed()` / `Math.round()` on must be present — `vegetation_health.avg_ndvi`,
  `problem_area_percent`; `soil_moisture.avg_moisture`, `dry_area_percent`. Prediction runs carry a complete
  `growth_prediction`: `current_biomass`, `current_status` (`growth_stage`, `health_score`, `days_since_planting`,
  `issues`, `warnings`, `key_metrics`), `predictions` (one item per prediction day), `summary`
  (`total_predicted_growth`, `average_daily_growth`, `confidence_range.avg`), `model_info`.
- About **half** of analyses are `comprehensive_with_prediction` (so health score has enough points for a trend).
- `recommendations` column stored as a JSON array; `parameters` in the shape `FieldAnalysisService` writes.
- `AnalyticsFactory` gets the same full shape (with a `withPrediction()` state).

### Flash errors (my call)

`AnalyticsController::analyze` reports AI failures via `redirect()->back()->with('error', …)`, but
`HandleInertiaRequests::share()` does not share flash data, so a failure today is silent. Add
`flash: {success, error}` to the shared props (lazy closures reading the session). `RunAnalysisModal` shows
`flash.error` inside the modal after a failed submit. Layout/global toast untouched.

### Health score (my call)

No derived/fallback health score — only the value the AI service produced. In the history chart it is its own series
with gaps (`connectNulls`); the latest-analysis row shows "—" when absent.

### Tests

- Unit/feature test for `FieldAnalyticsSummaryService`: sorting of recommendations, `{error}` / `null` sub-results →
  null fields, moisture scaling, top-3 cut, history order, field without analyses → `latestAnalysis` null and empty
  arrays.
- `FieldControllerTest`: show returns the new props; another user's field → 403 (exists).
- `fields.analytics` redirects to `fields.show?tab=analytics`.
- Seeder tests: every field gets readings; every field gets 3–6 analyses; `recommendations` is a non-empty array;
  `vegetation_health.avg_ndvi` matches the mean of the field's NDVI readings in the analysis window; prediction runs
  contain the keys `Analytics/Show.vue` reads.
- Shared props: `flash.error` is present after a redirect with `error`.

## Found along the way (out of scope, flagged)

- `AnalysisCompleted` notification links to `analytics.show` — fine, unchanged.
- `Analytics/Index.vue` closes its modal via manual DOM manipulation; not touched here.

## Left to the builder

Exact visuals of badges/cards, chart colours and tooltips, component prop shapes, whether the tab nav is its own
component.
