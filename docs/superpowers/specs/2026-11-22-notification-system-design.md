# Notification system — design (issue #18)

## Intent
The bell in the top bar is static template HTML and nothing in the app sends real
notifications. Goal: a working bell that tells the farmer when an AI analysis finished
(or failed) and when a report was generated — including analyses triggered in the
background by the sensor API. Part of the recruiter-showcase polish.

## Goals
- Real notifications sent after: AI analysis completed, AI analysis failed, report generated.
- Bell shows unread count + latest 10, each item links to its target (analysis / report).
- Mark one as read (on click), "Mark All as Read", "View All" page with history.
- Bell refreshes itself every ~60 s without a full page reload.
- Fix existing inconsistencies: seeder, dashboard mapping, broken `notifyDevelopers` helper.

## Non-goals
- Email / SMS / push channels.
- Realtime websockets (Reverb/Echo) — possible later by adding a `broadcast` channel to the same classes.
- Per-user notification preferences.
- Weather / sensor-threshold alerts (seeder fakes them; no real source exists).

## Anti-goals
- New infrastructure (containers, queue workers) required just for the bell.
- Notifications from other users visible to the current user.

## Approaches considered
- **A. Inertia shared props only** — simplest, but background-job results appear only after navigation.
- **B. A + partial reload polling every ~60 s** — **chosen**: tiny frontend addition, no new endpoint, background results arrive on their own.
- **C. Reverb + Echo websockets** — instant, but new container + broadcast/queue config; too much risk for the gain.

## Design
**Backend**
- `app/Notifications/AnalysisCompleted`, `AnalysisFailed`, `ReportGenerated` — `via(): ['database']`,
  `toArray()` returns uniform shape: `title`, `message`, `level` (`success|warning|danger|info`), `url`.
- Sent from `FieldAnalysisService` / `AnalyzeFieldDataJob` (success + failure) and from
  `ReportController::generate` (or the service behind it) to the field/report owner.
- `HandleInertiaRequests::share()` adds lazy `notifications` prop: `{ unread_count, latest: [10 items] }`
  (only for authenticated users).
- `NotificationController` (house style, Spatie QueryBuilder in `index()`):
  `index` (Inertia page, paginated, filter read/unread), `markAsRead` (redirect to `data.url`),
  `markAllAsRead`. Routes under `notifications.` scoped to `auth()->user()->notifications()`.
- `DashboardController::unreadNotifications()` uses the same shape.
- Fix `notifyDevelopers` (missing `App\Enums\NotificationType`) — rewrite to use a notification class
  or remove the broken enum reference.
- `NotificationSeeder` writes the uniform shape with real notification class names.

**Frontend**
- `AppLayout.vue` bell rendered from `$page.props.notifications`: badge with unread count,
  icon/colour by `level`, `diffForHumans` time, click → mark read + navigate, "Mark All as Read", "View All".
- `setInterval` (60 s) → `router.reload({ only: ['notifications'] })`, cleared on unmount.
- `Pages/Notifications/Index.vue` — list with read/unread filter and pagination.

**Tests**
- Feature tests: notification sent after analysis success/failure and report generation (AI HTTP faked),
  shared prop shape, mark-as-read / mark-all scoped to own notifications, index page.

## Left to the builder
- Exact icons/colours per level, copy of titles/messages (Polish UI strings consistent with app).
- Whether `markAsRead` is a POST + Inertia visit or a GET redirect.
