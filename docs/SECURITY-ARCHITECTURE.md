# PMMS — Security Architecture

> Phase 8 documentation. Defines the full security posture: authentication, authorization, file access, API security, sensitive data handling, audit logging, AI data handling, and per-module authorization boundaries.

---

## 1. Current State

### 1.1 Authentication

PMMS uses **Laravel Fortify** for session-based authentication. Sanctum is **not installed** — `config/sanctum.php` does not exist and `spatie/laravel-sanctum` is not in `composer.json`. The stack description in `PMMS-ARCHITECTURE.md` and `AGENTS.md` reference Sanctum; this is a documentation discrepancy.

| Feature | Status | Details |
|---|---|---|
| Session auth | ✅ Active | `web` guard, `session` driver, database-backed sessions (`SESSION_DRIVER=database`) |
| Fortify | ✅ Active | Handles registration, login, password reset, email verification, 2FA, passkeys |
| Passkeys (WebAuthn) | ✅ Active | Configured in `config/fortify.php`; `User` model implements `PasskeyUser` and `PasskeyAuthenticatable` |
| Two-factor auth | ✅ Active | TOTP-based; confirm + confirm-password required; recovery codes stored |
| Email verification | ✅ Active | Required via `verified` middleware on protected routes |
| Token auth (API) | ❌ Not implemented | No API surface exists; no Sanctum token guard configured |

**Password policy** (`AppServiceProvider:40-47`):

| Environment | Rules |
|---|---|
| Production | min 12 chars, mixedCase, letters, numbers, symbols, HaveIBeenPwned check |
| Non-production | No complexity enforced (dev-mode relaxation) |

Password hashing: `bcrypt` with `BCRYPT_ROUNDS=12`. Password reset tokens expire after 60 minutes with a 60-second throttle between generations. Password re-confirmation timeout: 3 hours (`AUTH_PASSWORD_TIMEOUT=10800`).

**Session security** (`config/session.php`):

| Setting | Value | Risk |
|---|---|---|
| `driver` | `database` | Good — session data not sent to client |
| `encrypt` | `false` | ⚠️ Session data not encrypted at rest |
| `secure` | `env('SESSION_SECURE_COOKIE')` | ⚠️ Not enforced `true` by default — cookies sent over HTTP |
| `http_only` | `true` | Good — prevents JS access to session cookie |
| `same_site` | `lax` | Good — CSRF mitigation |
| `serialization` | `json` | Good — avoids PHP serialization gadget chain attacks |
| `lifetime` | 120 min | Reasonable |

### 1.2 Authorization

PMMS uses a **custom, two-tier role-based access control** system. Spatie `laravel-permission` is **not installed** — `config/permission.php` does not exist.

#### Team-Level RBAC

Roles are PHP enums, not database-stored permissions:

| Role | Level | Permissions |
|---|---|---|
| `TeamRole::Owner` | 3 | All permissions (implicit) |
| `TeamRole::Admin` | 2 | `team:update`, `invitation:create`, `invitation:cancel` |
| `TeamRole::Member` | 1 | None (view only) |

Stored in `team_members.role` (string column, cast to `TeamRole` enum). Enforced via `TeamPolicy` and `EnsureTeamMembership` middleware.

**`TeamPermission` enum** — 7 granular flags: `team:update`, `team:delete`, `member:add`, `member:update`, `member:remove`, `invitation:create`, `invitation:cancel`.

#### Project-Level Scoping

| Role | Project Abilities |
|---|---|
| `pm` | View, update, delete |
| `admin` | View, update |
| `field_tech` | View only |
| `estimator` | View only |
| `exec` | View only |

Stored in `project_user.project_role` (string column, cast to `ProjectRole` enum). Enforced via `ProjectPolicy` and `Gate::authorize()` in controllers.

#### Policies

| Policy | File | Abilities |
|---|---|---|
| `ProjectPolicy` | `app/Policies/ProjectPolicy.php` | `view` (membership), `update` (pm/admin), `delete` (pm only) |
| `UserPolicy` | `app/Policies/UserPolicy.php` | `viewAny`, `view` (same team), `create` (admin+), `update` (hierarchy), `delete` (hierarchy) |
| `TeamPolicy` | `app/Policies/TeamPolicy.php` | 11 abilities delegated to `TeamPermission` enum checks |

**No other policies exist.** Submittals, RFIs, shop drawings, equipment, POs, change orders, punch lists, and quotations have **no dedicated policies** — all access is gated through the parent `ProjectPolicy`.

#### Authorization Enforcement

All authorization is enforced via `Gate::authorize()` calls in controllers (99 occurrences across 15 controllers). No `$this->authorize()`, no `Gate::define()`, no Blade `@can`/`@cannot` directives. The `DeleteTeamRequest` FormRequest is the only request class with an `authorize()` method.

**`EnsureTeamMembership` middleware** (`app/Http/Middleware/EnsureTeamMembership.php`): verifies user belongs to the team resolved from the route parameter. Supports an optional minimum-role argument, but this is **not used at the route level** — team admin-only actions rely solely on `TeamPolicy` Gate checks in controllers.

### 1.3 File Access

Files are managed via **spatie/laravel-medialibrary** on the **`public` disk** (`config/media-library.php:36`).

| Aspect | Current State | Risk |
|---|---|---|
| Storage disk | `public` → `storage/app/public` | Files are publicly accessible via `/storage/...` URLs |
| URL generation | `$media->getUrl()` — permanent, unsigned | ⚠️ Anyone with the URL can access files without authentication |
| Per-file auth | None | ⚠️ No signed URLs, no per-file authorization check on download |
| S3 | Configured in `config/filesystems.php` but not used | Empty env vars in `.env.example` |
| Max file size | 10 MB (media library) | |
| Disallowed extensions | Default list (blocks `php`, etc.) | |
| Upload validation | Per-module Form Requests (e.g., `max:20480` for deliverables) | |

**Upload flow**: Controller validates via Form Request → `addMedia()` to Spatie MediaLibrary → stored to `public` disk → `$media->getUrl()` returned to frontend. No virus scanning, no content-type verification beyond MIME detection.

### 1.4 API Security

**There is no API surface.** No `routes/api.php`, no API controllers, no API middleware group. The application is purely web + Inertia.js.

| Control | Status | Details |
|---|---|---|
| Rate limiting | ⚠️ Auth endpoints only | `login`: 5/min per email\|ip; `two-factor`: 5/min per login.id; `passkeys`: 10/min per credential; password update: 6/min |
| CSRF | ✅ Active | Laravel default — CSRF validation on all POST/PUT/PATCH/DELETE in web group |
| Input validation | ✅ Active | 23 Form Request classes across all modules |
| SQL injection | ✅ Protected | All queries use Eloquent/Query Builder with parameter binding; raw queries use parameterized statements |
| Global API throttle | ❌ Missing | No rate limiting on module endpoints |

**Cookie encryption exceptions**: `appearance` and `sidebar_state` cookies are excluded from encryption (non-sensitive UI preferences).

### 1.5 Sensitive Data Handling

| Data Type | Storage | Encrypted at Rest? | Notes |
|---|---|---|---|
| Passwords | `users.password` | ✅ Hashed (bcrypt, 12 rounds) | `hashed` cast on `User` model |
| 2FA secret | `users.two_factor_secret` | ❌ Plaintext | Hidden from serialization via `#[Hidden]` |
| 2FA recovery codes | `users.two_factor_recovery_codes` | ❌ Plaintext | Hidden from serialization via `#[Hidden]` |
| Remember token | `users.remember_token` | ❌ Plaintext | Hidden from serialization via `#[Hidden]` |
| Session data | `sessions` table | ❌ Not encrypted (`SESSION_ENCRYPT=false`) | Database-backed, not cookie |
| Financial data | `change_orders.cost_impact`, `purchase_orders.cost` | ❌ Plaintext decimals | No column-level encryption |
| Project data | `projects.*` | ❌ Plaintext | Business data, not PII |
| User PII | `users.name`, `users.email` | ❌ Plaintext | Internal tool — acceptable if DB is secured |

**Encryption config**: `config/app.php` uses `AES-256-CBC` cipher with `APP_KEY`. Supports key rotation via `APP_PREVIOUS_KEYS`. No `encrypted` casts are used on any model column.

### 1.6 Audit Logging

Uses **spatie/laravel-activitylog** (installed and active).

| Aspect | Configuration |
|---|---|
| Table | `activity_log` — `log_name`, `description`, `subject` (morphs), `event`, `causer` (morphs), `attribute_changes` (JSON), `properties` (JSON) |
| Retention | 365 days (`clean_after_days` in `config/activitylog.php`) |
| Default log name | `default` |
| Buffering | Disabled |
| Serialization | JSON |

**What is logged:**

| Source | Events | Mechanism |
|---|---|---|
| Model `LogsActivity` trait | Attribute changes (dirty fields only) | `Project`, `EquipmentInspection`, `SupplierQuotation`, `EquipmentItem`, `ShopDrawing`, `ApprovalStep`, `PurchaseOrder`, `ChangeOrder`, `Submittal`, `PunchListItem` |
| Controller `activity()` calls | `created`, `updated`, `deleted`, `submitted`, `approved`, `rejected`, `revision_requested`, `revision_created`, `status_changed` | All 15 feature controllers |
| User/team events | `password_changed`, `user_created`, `user_updated`, `user_deleted`, `team_created`, `team_updated`, `team_left`, `member_removed`, `member_role_updated`, `invitation_sent`, `invitation_cancelled`, `invitation_accepted`, `profile_updated`, `account_deleted` | UserController, TeamController, TeamMemberController, TeamInvitationController, ProfileController, SecurityController |
| Trait events | `approval_requested`, `comment_added`, `comment_removed`, `attachment_added`, `attachment_removed` | `HasApprovals`, `HasComments`, `HasAttachments` traits |
| Deliverables | `deliverable_added`, `deliverable_removed` | DeliverableController |

**Who can view audit logs**: Any authenticated, verified user. The `ActivityLogController` has **no Gate check** — it exposes the global activity log across all projects and teams.

**Tamper evidence**: None. The `activity_log` table uses standard Eloquent updates — no hash chains, no append-only enforcement, no digital signatures.

### 1.7 AI Data Handling

**No AI integration exists.** No AI packages in `composer.json`, no AI controllers, no AI config in `config/services.php`, no AI code in `app/` or `resources/`.

AI is documented only as future architecture:
- `docs/PMMS-ARCHITECTURE.md` §3.4 — AI as recommendation-only
- `docs/ROADMAP.md` Phase 6 — AI provider abstraction, recommendation schema, safety rules
- `docs/adr/ADR-007-ai-recommendations.md` — ratified: AI is advisory only

**Target safety rules** (from Phase 6 roadmap):
- AI must NEVER directly change stages, approve documents, modify financial records, modify permissions, delete records, bypass validation, or execute arbitrary DB operations
- Always: AI Recommendation → Human/System Validation → Business Rule Validation → Authorized Action

### 1.8 Module Authorization Boundaries

| Module | Authorization Check | Boundary | Gap |
|---|---|---|---|
| **Project** | `ProjectPolicy` (view/update/delete) | Membership + `project_role` | None |
| **Deliverables** | `Gate::authorize('update', $project)` | pm/admin only | None |
| **Submittals** | `Gate::authorize('view'\|'update', $project)` | Membership view; pm/admin write | No SubmittalPolicy; IDOR risk (see §2) |
| **RFIs** | `Gate::authorize('view'\|'update', $project)` | Same | No RfiPolicy; IDOR risk |
| **Shop Drawings** | `Gate::authorize('view'\|'update', $project)` | Same | No ShopDrawingPolicy; IDOR risk |
| **Equipment** | `Gate::authorize('view'\|'update', $project)` | Same | No EquipmentPolicy; IDOR risk |
| **Quotations** | `Gate::authorize('view'\|'update', $project)` | Same | No policy; IDOR risk |
| **Purchase Orders** | `Gate::authorize('view'\|'update', $project)` | Same | No policy; IDOR risk |
| **Inspections** | `Gate::authorize('view'\|'update', $project)` | Same | No policy; IDOR risk |
| **Punch List** | `Gate::authorize('view'\|'update', $project)` | Same | No PunchListItemPolicy; IDOR risk |
| **Change Orders** | `Gate::authorize('view'\|'update', $project)` | Same | No ChangeOrderPolicy; IDOR risk |
| **Users** | `UserPolicy` (team-scoped) | Team role hierarchy | None |
| **Teams** | `TeamPolicy` (permission enum) | Team membership + role | None |
| **Activity Log** | None (auth+verified only) | ⚠️ Global — cross-tenant exposure | No authorization boundary |
| **Changelog** | None (auth+verified) | Public feature content | Acceptable |
| **Dashboard** | Auth+verified, project queries filtered by membership | Per-user | Acceptable |

---

## 2. Current Gaps

| # | Gap | Severity | Impact |
|---|---|---|---|
| 1 | **IDOR on project child resources** — Routes bind `{submittal}`, `{rfi}`, etc. globally by ID without verifying `$resource->project_id === $project->id`. A user with `update` access to one project could access/modify resources in another project by passing the wrong resource ID. | Critical | Cross-project data access |
| 2 | **Global activity log exposure** — `ActivityLogController` has no `Gate::authorize()`. Any verified user can view activity across all projects and teams. | High | Cross-tenant information leak |
| 3 | **No signed URLs for file access** — Files on `public` disk are accessible via permanent, unsigned URLs. Any authenticated user who discovers/inspects a URL can access the file. | High | Unauthorized file access |
| 4 | **No module-specific policies** — Submittals, RFIs, shop drawings, equipment, POs, change orders, punch lists, and quotations have no dedicated policies. Authorization is delegated entirely to the parent project, preventing granular control (e.g., "field techs can view but not update submittals"). | Medium | Coarse authorization granularity |
| 5 | **2FA secrets stored in plaintext** — `two_factor_secret` and `two_factor_recovery_codes` are not encrypted at rest. If the database is compromised, 2FA is bypassable. | Medium | 2FA circumvention on DB breach |
| 6 | **Session data not encrypted** — `SESSION_ENCRYPT=false`. Session payload is stored in plaintext in the `sessions` table. | Medium | Session data exposure on DB breach |
| 7 | **No rate limiting on module endpoints** — Only auth endpoints are throttled. Module CRUD operations have no rate limiting. | Medium | Abuse/DoS on feature endpoints |
| 8 | **`assigned_to` validation gap** — Form Requests validate `exists:users,id` but not that the assignee is a member of the project/team. | Low | Assignment to non-members |
| 9 | **Session secure cookie not enforced** — `SESSION_SECURE_COOKIE` is not set to `true` by default; cookies may be sent over HTTP. | Low | Session hijacking on HTTP |
| 10 | **No audit log tamper evidence** — Activity log uses standard Eloquent writes; no hash chains or append-only enforcement. | Low | Log manipulation on DB access |

---

## 3. Target Architecture

### 3.1 Authentication (Target)

| Change | Priority | Details |
|---|---|---|
| Install Sanctum | Medium | If API/mobile access is planned, add `laravel/sanctum` with SPA mode + token auth. Not needed until external consumers exist. |
| Enforce `SESSION_SECURE_COOKIE=true` | High | Set in `.env` for production; prevents cookie transmission over HTTP |
| Enable `SESSION_ENCRYPT=true` | Medium | Encrypts session payload at rest in the database |
| Encrypt 2FA secrets | High | Add `encrypted` cast to `two_factor_secret` and `two_factor_recovery_codes` columns on the `User` model |

### 3.2 Authorization (Target)

| Change | Priority | Details |
|---|---|---|
| Fix IDOR on project children | **Critical** | Every project-child controller must verify `$resource->project_id === $project->id` before authorizing. Consider scoped model binding: `Route::model('submittal', Submittal::class)` with a custom resolver, or explicit checks in controllers. |
| Restrict ActivityLog access | High | Add `Gate::authorize()` to `ActivityLogController` — require team membership or admin role. Consider per-project activity log views. |
| Add module-specific policies | Medium | Create policies for `Submittal`, `Rfi`, `ShopDrawing`, `Equipment`, `PurchaseOrder`, `ChangeOrder`, `PunchListItem`, `SupplierQuotation`, `EquipmentInspection`. Start with project-scoped `view`/`update` delegates, then add granular abilities as needed (e.g., `submit`, `approve`, `request_revision`). |
| Enforce `assigned_to` membership | Medium | Add a custom validation rule or scope the `exists` check to `project_user` pivot: the assignee must be a member of the project. |
| Use `EnsureTeamMembership` minimum-role | Low | Wire the minimum-role argument at the route level for team admin-only routes instead of relying solely on `TeamPolicy` Gate checks. |

### 3.3 File Access (Target)

| Change | Priority | Details |
|---|---|---|
| Switch to signed URLs | High | Use Spatie MediaLibrary's `getTemporaryUrl()` with S3 or Laravel's `Storage::disk('s3')->temporaryUrl()` with a short TTL. For local disk, use signed routes. |
| Add per-file authorization | High | Before serving a file, verify the requesting user has access to the parent project. Consider a dedicated `DownloadController` that checks project membership before streaming the file. |
| Migrate to S3 | Medium | Move from `public` disk to `s3` (or S3-compatible). Enforce bucket policies: no public access, IAM-based, signed URLs only. |
| Add virus scanning | Low | Integrate ClamAV or similar via a queued job after upload. |

### 3.4 API Security (Target)

| Change | Priority | Details |
|---|---|---|
| Add global rate limiting | Medium | When API routes are added, apply `throttle:api` middleware globally. Configure limits in `RouteServiceProvider` or `bootstrap/app.php`. |
| Sanctum token auth | Medium | When API is needed, use Sanctum tokens with scoped abilities per token. |
| Input validation | ✅ Current | 23 Form Request classes cover all modules. Maintain this pattern. |
| CSRF | ✅ Current | Laravel defaults are sufficient for same-domain Inertia SPA. |
| SQL injection | ✅ Current | Eloquent/Query Builder parameter binding. Maintain no raw string concatenation. |

### 3.5 Sensitive Data (Target)

| Change | Priority | Details |
|---|---|---|
| Encrypt 2FA secrets | High | `encrypted` cast on User model for `two_factor_secret` and `two_factor_recovery_codes` |
| Encrypt financial data | Medium | Consider `encrypted` cast on `cost_impact`, `cost` columns if data classification requires it |
| Enable session encryption | Medium | `SESSION_ENCRYPT=true` |
| Enforce HTTPS cookies | High | `SESSION_SECURE_COOKIE=true` in production |

### 3.6 Audit Logs (Target)

| Change | Priority | Details |
|---|---|---|
| Restrict log visibility | High | Gate-check on `ActivityLogController` — users see only their team's/project's activity |
| Add tamper evidence | Low | Append-only enforcement via DB triggers, or hash-chain each log entry (`previous_hash` + `entry_hash = sha256(data + previous_hash)`) |
| Dedicated audit channel | Low | Add `audit` channel in `config/logging.php` for security-relevant events (login failures, permission changes, data exports) |
| Retention policy | ✅ Current | 365-day cleanup is reasonable for an internal tool |

### 3.7 AI Data Handling (Target)

| Change | Priority | Details |
|---|---|---|
| Provider abstraction | Low | Design `AIProvider` interface per Phase 6. When implemented, ensure: only non-sensitive project data is sent, no PII unless necessary, data residency documented. |
| Data classification | Medium | Define what data AI providers can see: project names, stages, dates = acceptable. Financial data, user PII, 2FA secrets = never. |
| Consent/logging | Low | Log what data was sent to AI providers in the activity log for auditability. |

---

## 4. Per-Module Authorization Boundary Rules

Every feature module must declare and enforce:

1. **Who can view** — project membership (any role) or team membership
2. **Who can create** — project role `pm` or `admin` (for project-scoped modules)
3. **Who can update** — project role `pm` or `admin`
4. **Who can delete** — project role `pm` only (where applicable)
5. **Who can advance** — workflow-specific, governed by the Approval Engine and Workflow Rules

### Module Boundary Matrix (Target)

| Module | View | Create | Update | Delete | Advance |
|---|---|---|---|---|---|
| Project | membership | team admin+ | pm, admin | pm | pm (stage transitions) |
| Submittals | membership | pm, admin | pm, admin | — | approval engine |
| RFIs | membership | pm, admin | pm, admin | — | — |
| Shop Drawings | membership | pm, admin | pm, admin | — | approval engine |
| Equipment | membership | pm, admin | pm, admin | — | — |
| Quotations | membership | pm, admin | pm, admin | — | — |
| Purchase Orders | membership | pm, admin | pm, admin | — | — |
| Inspections | membership | pm, admin | pm, admin | — | — |
| Punch List | membership | pm, admin | pm, admin | — | — |
| Change Orders | membership | pm, admin | pm, admin | — | approval engine |
| Deliverables | membership | pm, admin | pm, admin | pm | — |
| Users | same team | team admin+ | hierarchy | hierarchy | — |
| Teams | membership | any authenticated | permission-based | owner only | — |
| Activity Log | team membership (target) | — | — | — | — |

---

## 5. Rules for Developers

### Authentication

1. **All routes** requiring authentication must use `auth` middleware (applied at route-group level in `web.php`)
2. **Email verification** is required for all feature routes — add `verified` middleware
3. **Password changes** must require current password confirmation (`RequirePassword` middleware or `current_password` validation rule)
4. **Never store passwords in plaintext** — always use `Hash::make()` or the `hashed` cast

### Authorization

1. **Every controller action** that accesses a resource must call `Gate::authorize()` or use a Form Request with `authorize()` returning `true`
2. **Project-child resources** must verify `$resource->project_id === $project->id` before authorization — never trust route parameters alone
3. **Never rely solely on frontend hiding** — all authorization is enforced server-side; the frontend may hide UI elements but must not be the enforcement point
4. **When adding a new module**, create a dedicated policy even if it initially delegates to `ProjectPolicy`. This creates the hook for future granular control
5. **`assigned_to` fields** must validate that the assignee is a member of the project, not just that the user exists

### File Access

1. **Never use `$media->getUrl()`** for files that require authorization — use signed/temporary URLs
2. **Validate upload MIME types** against an allowlist per collection, not just the global disallowed list
3. **Store sensitive files** on a private disk (`storage/app/private`) and serve via signed URLs with short TTLs

### Audit Logging

1. **Log every state change** — record the event, causer, subject, and attribute changes
2. **Never skip logging** for actions that modify data, change permissions, or affect financial records
3. **Activity log access** must be restricted to authorized users (team membership or admin)

### Sensitive Data

1. **Apply `encrypted` cast** to any column containing secrets (2FA, tokens, API keys)
2. **Apply `#[Hidden]`** attribute to any model property that should not be serialized to Inertia or API responses
3. **Never log secrets** — exclude sensitive fields from activity log attribute tracking

### Never

- **Do NOT** expose the global activity log without authorization checks
- **Do NOT** serve files via permanent public URLs when authorization is required
- **Do NOT** trust route parameters to establish ownership — always verify the resource belongs to the expected parent
- **Do NOT** store 2FA secrets, API keys, or tokens in plaintext
- **Do NOT** bypass rate limiting on authentication endpoints
- **Do NOT** allow `assigned_to` to reference users outside the project/team
