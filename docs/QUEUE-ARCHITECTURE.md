# PMMS — Queue & Data Processing Architecture

> Phase 7 documentation. Defines async processing patterns, the data processing pipeline, queue configuration, job design conventions, and the gap between current synchronous execution and the target async architecture.

---

## 1. Current State

### 1.1 Infrastructure

| Component | Status | Details |
|---|---|---|
| Queue tables | ✅ Migrated | `jobs`, `job_batches`, `failed_jobs` exist |
| Queue connection | ⚠️ `database` driver | `QUEUE_CONNECTION=database` in `.env`. Redis is wired (`lerd-redis`) but not yet used as the queue backend. Switch to `redis` when throughput warrants it. |
| Redis | ✅ Available | `REDIS_HOST=lerd-redis`, `REDIS_CLIENT=phpredis` — ready to use as queue driver |
| Queue worker | ✅ Running | `lerd queue` worker manages `artisan queue:work` via systemd |
| Job classes | ❌ None | `app/Jobs/` directory does not exist. Zero custom job classes. |
| Events / Listeners | ❌ None | `app/Events/` and `app/Listeners/` directories do not exist. |

### 1.2 What Is Currently Async

| Operation | Async? | Notes |
|---|---|---|
| `TeamInvitation` email | ✅ Yes | `ShouldQueue` — uses queue via `Queueable` trait |
| File uploads (deliverables, attachments) | ❌ No | Synchronous HTTP — file is stored and response returned in same request |
| Media conversions (thumbnails, etc.) | ❌ No | No conversions registered; `spatie/laravel-medialibrary` would queue them automatically if configured |
| Approval notifications | ❌ No | No notification dispatching exists yet — `notification_rules` table has no dispatcher |
| Activity log writes | ❌ Sync | Spatie activitylog writes synchronously — acceptable for now |
| Stage transition notifications | ❌ No | Not implemented |
| PDF generation | ❌ N/A | Not yet needed |
| Reporting / exports | ❌ N/A | Not yet needed |

### 1.3 What Should Be Async (Target)

The rule is: **never block an HTTP request with work that can happen after the response is sent.** File storage that is already offloaded to S3 is the key trigger — once files leave local disk, operations like virus scanning, thumbnail generation, PDF processing, and notification dispatch become queue-suitable.

---

## 2. Data Processing Pipeline (Target)

For file-based operations (the primary async workload in PMMS):

```
HTTP Request (upload)
    │
    ▼
Controller validates request
    │
    ▼
File → Spatie MediaLibrary → Stored to disk / S3
    │  (synchronous — small files, fast path)
    │
    ▼
Response returned to user  ← user sees success immediately
    │
    ▼ (queued)
[ProcessMediaJob] (if conversions are configured)
    ├── Generate thumbnail (images)
    ├── Extract text (PDFs — future: full-text search)
    └── Virus scan (future: ClamAV integration)
```

For notification dispatch (when `notification_rules` dispatcher is implemented):

```
Activity event fired (e.g. 'submittal.approved')
    │
    ▼
[DispatchNotificationJob]
    ├── Query notification_rules WHERE event_key = 'submittal.approved'
    ├── Resolve recipients by project_role + project_user
    └── Dispatch per-channel (email → [SendEmailNotificationJob], in_app → DB write)
```

For future reporting / exports:

```
User requests export (e.g. project status report)
    │
    ▼
Controller dispatches [GenerateReportJob] → returns job ID
    │
    ▼ (queued)
[GenerateReportJob]
    ├── Queries reporting data
    ├── Generates PDF / CSV
    └── Stores to S3, notifies user via in-app notification
```

---

## 3. Queue Configuration

### 3.1 Current Driver

```
QUEUE_CONNECTION=database
```

The `database` driver stores jobs in the `jobs` table. Acceptable for low throughput. Switch to `redis` as job volume grows:

```
QUEUE_CONNECTION=redis
REDIS_QUEUE_CONNECTION=default
```

Redis is already installed and available at `lerd-redis`. No infrastructure change needed — just update `.env`.

### 3.2 Queue Names (Planned)

Use named queues to prioritize work:

| Queue | Purpose | Priority |
|---|---|---|
| `default` | General-purpose jobs | Normal |
| `notifications` | Email and in-app notification dispatch | High — user is waiting |
| `media` | File processing, conversions, virus scan | Low — background |
| `reports` | PDF generation, data exports | Low — background |

Declare priority at worker startup:
```bash
php artisan queue:work --queue=notifications,default,media,reports
```

### 3.3 Retry Strategy

| Setting | Value | Rationale |
|---|---|---|
| `retry_after` | 90s (database), 90s (redis) | Laravel default — fine for most jobs |
| `$tries` | 3 (on job class) | Three attempts before failing |
| `$backoff` | `[10, 60, 300]` | Exponential back-off: 10s, 1m, 5m |
| `$timeout` | 60s for notifications, 300s for media/reports | Set per job class |
| `$failOnTimeout` | `true` | Prevents zombie jobs |

### 3.4 Failed Jobs

Failed jobs land in `failed_jobs` (already migrated, `database-uuids` driver). Inspect with:

```bash
php artisan queue:failed          # list failed jobs
php artisan queue:retry all       # retry all
php artisan queue:retry <uuid>    # retry one
php artisan queue:forget <uuid>   # discard one
php artisan queue:flush            # discard all
```

### 3.5 Job Batching

The `job_batches` table is already migrated. Use batching when a user action produces multiple parallel jobs (e.g., send notifications to 10 recipients for a single approval event):

```php
Bus::batch([
    new SendEmailNotificationJob($recipient1, $event),
    new SendEmailNotificationJob($recipient2, $event),
])->dispatch();
```

---

## 4. Async Operations Inventory

All operations that should use queued jobs when implemented. The jobs directory does not exist yet — create it at `app/Jobs/`.

### 4.1 Notifications (Highest Priority)

| Job class | Trigger | Queue |
|---|---|---|
| `DispatchNotificationJob` | Any workflow event (`submittal.approved`, `change_order.rejected`, etc.) | `notifications` |
| `SendEmailNotificationJob` | Dispatched by `DispatchNotificationJob` per email recipient | `notifications` |
| `SendInAppNotificationJob` | Dispatched by `DispatchNotificationJob` per in-app recipient | `notifications` |

### 4.2 Media Processing

| Job class | Trigger | Queue |
|---|---|---|
| `ProcessUploadedMediaJob` | After any file upload via `HasAttachments::addAttachment()` | `media` |
| (Spatie auto) | `addMediaConversion()` registered — spatie queues automatically | `media` |

Note: `spatie/laravel-medialibrary` automatically queues conversion jobs when `GeneratesMediaConversions` is implemented on a model and `QUEUE_MEDIA_CONVERSIONS=true`. No custom job class needed for conversions — just register them in `registerMediaConversions()`.

### 4.3 Reporting & Exports (Future)

| Job class | Trigger | Queue |
|---|---|---|
| `GenerateProjectReportJob` | PM requests a project status PDF | `reports` |
| `ExportSubmittalsJob` | User exports submittal log to CSV | `reports` |
| `GenerateDashboardSnapshotJob` | Scheduled — refresh materialized reporting view | `reports` |

### 4.4 External Sync (Phase 6 — Future)

| Job class | Trigger | Queue |
|---|---|---|
| `SyncAccountingJob` | Change order approved → push to accounting system | `default` |
| `SyncServiceManagementJob` | Project stage change → notify service management | `default` |

---

## 5. Job Design Conventions

### 5.1 Structure

Every job class must:

```php
class ExampleJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 60;
    public bool $failOnTimeout = true;
    public array $backoff = [10, 60, 300];

    public function __construct(
        // Pass model IDs, not model instances
        public readonly int $modelId,
    ) {}

    public function handle(): void
    {
        // Reload from DB — model state may have changed since dispatch
        $model = SomeModel::findOrFail($this->modelId);
        // ...
    }

    public function failed(\Throwable $e): void
    {
        // Log the failure; optionally notify an admin
        Log::error("ExampleJob failed for model {$this->modelId}: {$e->getMessage()}");
    }
}
```

### 5.2 Pass IDs, Not Models

Always inject model IDs (integers) into the constructor, not model instances. Model instances are serialized to JSON in the `jobs` table — the model may be modified or deleted by the time the job runs.

### 5.3 Idempotency

Jobs must be safe to run more than once (retries happen). Guard against duplicate side-effects:

```php
// Bad — sends the email again on retry
$user->notify(new ApprovalNotification($submittal));

// Good — check if already notified
if (! $submittal->fresh()->notified_at) {
    $user->notify(new ApprovalNotification($submittal));
    $submittal->update(['notified_at' => now()]);
}
```

### 5.4 After-Commit Dispatch

Dispatch jobs **after the database transaction commits** to avoid the job running before the data it depends on exists:

```php
// In queue config (database driver):
'after_commit' => true,

// Or per-dispatch:
SomeJob::dispatch($id)->afterCommit();
```

### 5.5 Unique Jobs

For jobs that should not be queued twice for the same subject (e.g., generating the same report), implement `ShouldBeUnique`:

```php
class GenerateProjectReportJob implements ShouldQueue, ShouldBeUnique
{
    public int $uniqueFor = 3600; // lock for 1 hour

    public function uniqueId(): string
    {
        return "report-{$this->projectId}";
    }
}
```

---

## 6. Current Gaps

| Gap | Impact | Priority |
|---|---|---|
| Zero job classes exist | No async processing possible — all work is synchronous | High |
| No `DispatchNotificationJob` | `notification_rules` table is populated but never evaluated | High |
| Queue driver is `database`, not `redis` | Lower throughput than Redis; acceptable now, but should switch before production load | Medium |
| No media conversions registered | Uploaded files have no thumbnails; MediaLibrary async pipeline unused | Low |
| No `failed()` method pattern established | Failed jobs have no structured error reporting | Medium |
| No monitoring / alerting | No visibility into queue depth, failure rate, job age | Low |

---

## 7. Rules for Developers

### When Adding a New Async Operation

1. Create the job class in `app/Jobs/` implementing `ShouldQueue`
2. Always use `Queueable` trait
3. Set `$tries`, `$timeout`, `$failOnTimeout`, and `$backoff` explicitly
4. Accept model IDs in the constructor — never model instances
5. Assign the job to the appropriate named queue (`notifications`, `media`, `reports`, `default`)
6. Implement `failed(Throwable $e)` to log failures
7. Guard for idempotency — the job may run more than once
8. Dispatch with `->afterCommit()` when the job depends on data written in the same request

### When a Controller Action Is Slow

1. Identify the bottleneck (file I/O, external API, heavy query)
2. Extract the slow work into a job class
3. Dispatch the job and return the response immediately
4. Use an in-app notification or polling endpoint to signal completion to the frontend

### Never

- **Do NOT** dispatch jobs from within another job's `handle()` method if it creates an unbounded chain — prefer batching
- **Do NOT** put business rules inside a job — jobs are infrastructure; business logic belongs in Domain/Application layer classes that the job calls
- **Do NOT** block HTTP responses with work that can be deferred
