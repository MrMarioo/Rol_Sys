# Field page: show the field's data (issue #33)

## Intent

`Field/Show.vue` today shows only the map and field metadata. The field's measurements (`field_data`) live on a
separate list. Goal: when a farmer (or a recruiter browsing the demo) opens a field, they immediately see what is
happening on it — latest readings, how they trend, and the most recent records.

The project is moving from thesis to portfolio piece, so the page has to look good within a few seconds and show
competent data handling.

## Goals / non-goals

- Goals: latest-reading tiles, one trend chart with a data-type switch, a short table of recent records, a clean empty
  state.
- Non-goals: measurement points on the map (separate follow-up), tabs, editing data from this page, AI analytics.
- Anti-goals: shipping raw 100-value arrays to the browser; crashing or showing `undefined` when a user-entered record
  lacks the expected JSON keys.

## Approaches considered

1. **Tiles + trend chart + recent table** — chosen. Best impact for the effort; all aggregation server-side.
2. Same plus coloured measurement points on the map — strongest visual, roughly double the work (FieldMap changes,
   per-type colour scales). Deferred to a follow-up.
3. Tabs Overview / Data / Analytics — structure for later; with this little content the tabs would look empty.

## Page layout

```
[ Field Details                                  ← Back ]
[ Map (unchanged)                                       ]
[ NDVI 0.72 ▲ ][ Soil moisture 31% ▼ ][ Air temp 18.4°C ]...   ← one tile per data type the field has
[ Trend: (select: NDVI ▾)            line chart          ]
[ Recent readings: date | type | value | →  [See all]   ]
[ Name/size/location card ][ Status/description card    ]   (existing, unchanged)
[                               Back to List | Edit     ]
```

No data → tiles/chart/table are replaced by a single card: "No data collected for this field yet" + button to
`field-data.create`.

## Design

### Metric map (one source of truth)

`App\Services\FieldDataSummaryService` holds a map `data_type → definition`:

| data_type | key in `data` | label | unit | kind | note |
|---|---|---|---|---|---|
| ndvi | avg_ndvi | NDVI | — | numeric | 3 decimals |
| soil_moisture | avg_moisture | Soil moisture | % | numeric | stored 0–1, ×100 |
| weather | temperature_avg | Air temperature | °C | numeric | |
| soil_temperature | avg_temperature | Soil temperature | °C | numeric | |
| biomass_measurement | dry_biomass_kg_per_ha | Dry biomass | kg/ha | numeric | |
| sunlight | sunshine_hours | Sunshine | h | numeric | |
| growth_stage | growth_stage | Growth stage | — | text | shows BBCH code if present |
| fertilizer_application | fertilizer_type + amount_per_hectare | Last fertilization | kg/ha | event | text tile, no chart |

- Unknown `data_type` or missing/non-numeric key → the record is skipped for tiles and chart, still appears in the
  recent table with value "—".
- Extraction happens in PHP, not with MySQL JSON operators.

### Server side

- `FieldController::show()` keeps `authorize('view')` (same rule as `viewData`) and adds three props built by the
  service:
  - `latestReadings`: per known type present on the field — label, unit, kind, display value, collection date, and
    for numeric kinds `trend` (`up`/`down`/`flat`) versus the previous reading.
  - `trendSeries`: per numeric type — list of `{date, value}` scalars, newest record per type as the anchor, last
    90 days back from that anchor (seed data is relative to seed time, so "now" is not a safe anchor).
  - `recentReadings`: 5 newest records — id, date, type label, display value.
- One query for the field's records ordered by `collection_date desc`; no raw `data` arrays in any prop.
- Controller follows house style (named args, `Inertia::render(component:, props:)`).

### Frontend

- `Field/Show.vue` stays the page; new components in `resources/js/Components/Field/`:
  `LatestReadingTiles.vue`, `FieldTrendChart.vue` (vue-echarts line chart, same echarts setup style as
  `DataVisualization.vue`, `<select>` of numeric types, defaults to NDVI if present), `RecentReadingsTable.vue`
  (rows link to `field-data.show`, "See all" link to the field's data list).
- Styling: existing DashLite classes (`card`, `nk-*`, badges) for consistency with the rest of the app.

### Tests

- `FieldDataFactory` (new).
- Feature `FieldControllerTest`: field with data returns populated props; field without data returns empty props;
  another user's field → 403.
- Unit test for the service: soil moisture scaling, trend direction, record with missing key is skipped, unknown type
  only in recent table.

## Found along the way (out of scope, flagged)

`FieldData/Index.vue` sends plain `field_id` / `data_type` query params, while `FieldDataController::index()` filters
via Spatie `filter[field_id]` / `filter[data_type]`. Result: the filters on the data list (and the
`fields/{field}/data` redirect) don't actually filter. The "See all" link will use `filter[field_id]` so it works;
fixing the list page itself is a separate small task.

## Left to the builder

Exact tile visuals, chart colours/tooltip formatting, component prop shapes.
