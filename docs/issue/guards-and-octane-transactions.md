# Guard Validation & the Octane Transaction-Leak Case

This document records an incident in the role/permission module: rows that the UI showed
but database (MySQL) never kept, roles that could be "deleted" and recreated endlessly, and pages that
hung until a 30 second timeout. It explains the two root causes, the fixes, and the rules
that prevent it from coming back.

Companion documents: [`route-permissions.md`](./route-permissions.md) for how permissions are
derived from routes, [`menu-management.md`](./menu-management.md) for the menu/sync side.

## 1. Symptoms as reported

| # | Symptom |
|---|---|
| A | Creating/editing a role returned HTTP 200 with the role echoed back, the DataTable immediately showed the row, but the row was never in MySQL. |
| B | Recreating a role with the same name raised the `unique` constraint error, even though the earlier create "failed". |
| C | Deleting such a role returned 200 and the row vanished from the DataTable, yet the name could then be reused — repeatedly, forever. |
| D | Some requests spun until a `Maximum execution time of 30 seconds exceeded` error. |
| E | Any role/permission using a guard other than `web` (e.g. `api`) returned 500 with `Error: Class name must be a valid object or a string`. |

Symptoms A–C are the same bug seen from three angles. They were intermittent: the same
request could persist correctly on one attempt and vanish on the next.

## 2. Environment facts

- The application runs under **Laravel Octane with FrankenPHP**
  (`docker/Dockerfile`, `CMD php artisan octane:start --server=frankenphp --host=0.0.0.0 --port=8000`).
- Octane keeps **long-lived workers**: the PHP process, its booted application container and
  its open PDO/MySQL connection survive across many requests instead of being torn down
  after each one.
- `config/octane.php` had the Octane listener `DisconnectFromDatabases` **commented out**, so
  the PDO connection was deliberately kept alive between requests.
- `config/auth.php` registers exactly one guard: `web`.

## 3. Root cause 1 — a non-`web` guard throws `Error`, not `Exception`

Spatie's `Role` model resolves its `users()` relation through the guard:

```php
// vendor/spatie/laravel-permission/src/Guard.php
public static function getModelForGuard(string $guard): ?string   // returns null when the
                                                                  // guard has no provider
```

With only `web` configured, `getModelForGuard('api')` returns `null`, and building the
relation yields:

```
Error: Class name must be a valid object or a string
    at Illuminate\Database\Eloquent\Concerns\HasRelationships.php:1052
    at vendor/spatie/laravel-permission/src/Models/Role.php:94
    at vendor/spatie/laravel-permission/src/Traits/HasPermissions.php:46
```

Line 46 of `HasPermissions.php` is the `deleting` listener:

```php
static::deleting(function ($model) {
    ...
    if ($model instanceof Role) {
        $model->users()->detach();   // ← calls users() → Error for a provider-less guard
    }
});
```

The critical detail is the PHP error class:

- `GuardDoesNotMatch`, `PermissionDoesNotExist`, `ValidationException` … all extend
  `Exception`.
- `Error: Class name must be a valid object or a string` is a PHP **`Error`**, and
  `Error` does **not** extend `Exception`.

## 4. Root cause 2 — `catch (\Exception)` let the `Error` escape an open transaction

Every mutation in the module followed this shape:

```php
DB::beginTransaction();
try {
    $role->delete();          // ← the deleting listener throws Error above
    DB::commit();
    return response()->json([...]);
} catch (\Exception $e) {     // ← catches Exception, NOT Error
    DB::rollBack();
    ...
}
```

Because `Error` is not an `Exception`, the `catch` block never ran, so `DB::rollBack()`
never ran. The transaction stayed open and Laravel's in-memory transaction counter for that
connection stayed elevated.

### Why that produces exactly symptoms A–C

Laravel only issues a real `COMMIT` when its transaction *level* drops back to `1`. While
the level is stuck above `1`:

| Action | What the code sees | What MySQL sees |
|---|---|---|
| `create` | success, row is in the response | `INSERT` executed, **never committed** |
| DataTable reload | row is returned | the same connection reads its own uncommitted data |
| Re-creating the same name | `unique` violation | the uncommitted row still holds the name |
| `delete` | success, row disappears | `DELETE` executed on the uncommitted row |
| Any other connection | nothing | **no such row at all** |

That is why the data looked real to the browser but never reached MySQL, and why the failure
was intermittent — a request only leaked a transaction if it happened to land on a worker
whose connection was already poisoned.

The 30 second timeouts (symptom D) appear in
`storage/logs/laravel-2026-10-17.log` at
`Illuminate\Database\MySqlConnection.php:53` / `Connection.php:612`, consistent with later
work piling up behind a connection left mid-transaction.

## 5. Why `optimize:clear` appeared to fix it

`artisan optimize:clear` was run and afterwards everything persisted correctly. This is a
coincidence worth understanding, because the command itself does not repair anything:

```
$ php artisan tinker --execute="print_r(Laravel\Octane\ServiceProvider::\$optimizeClearCommands);"
Array ( )
$ php artisan tinker --execute="print_r(Laravel\Octane\ServiceProvider::\$reloadCommands);"
Array ( [octane] => octane:reload )
```

Octane registers its reload hook via `reloads()` (`OctaneServiceProvider.php:196`), which is
**not** part of `$optimizeClearCommands`. So `optimize:clear` clears bootstrap caches but
does not recycle workers. The recovery came from the workers being recycled at around the
same time — which is why it looked effective and then would have returned later.

**The correct recovery command is `php artisan octane:reload`** (or `php artisan reload`, or
restarting the container). Clearing caches alone will not release a poisoned connection.

## 6. Fixes applied

| # | Fix | File |
|---|---|---|
| 1 | Override `Role::users()` to return a safe empty relation when the guard has no model provider, so Spatie's `deleting` listener and `getRoleUsers()` no longer throw `Error`. | `app/Models/Role.php` |
| 2 | All module `catch (\Exception)` changed to `catch (\Throwable)`, so a rollback always runs and a transaction can never leak again. | `RolePermissionController.php`, `PermissionController.php` |
| 3 | `guard_name` is validated against the registered guards: `Rule::in(array_keys(config('auth.guards')))`. An unregistered guard is now a 422, never a 500. | `RolePermissionController.php`, `PermissionController.php` |
| 4 | The guard field became a hidden-input dropdown (same `.custom-filter-dropdown` pattern as the user status field) populated from `config('auth.guards')` instead of free text, so an invalid guard cannot be typed in at all. | `role-permissions/index.blade.php` |
| 5 | Client-side validation now checks membership in the registered guard list (and clears on `change`), and the assign-permissions modal load has a `.fail()` handler so it no longer spins forever on error. | `role-permissions/index.blade.php` |
| 6 | Fixed the request header in the shared AJAX helper (`headers: headers` instead of the malformed `{'X-CSRF-TOKEN': headers}`). | `public/js/iplat1_crud.js` |
| 7 | Re-enabled Octane's `DisconnectFromDatabases` listener as a safety net, so no connection (and no leaked transaction state) is carried into the next request. | `config/octane.php` |
| 8 | Added `permissions:prune-invalid-guards` (`--dry-run` supported) to list and delete roles/permissions whose guard is not registered. | `app/Console/Commands/PruneInvalidGuards.php` |
| 9 | Added regression tests for guard validation and the create/update/delete flow. | `tests/Feature/RolePermissionTest.php` |
| 10 | Overrode `Permission::users()` the same way as `Role::users()`, so direct user–permission counts stay safe for any guard. | `app/Models/Permission.php` |
| 11 | The permission list and its export now expose a user count, backed by a new `GET /ajax/permissions/{id}/users` endpoint and a clickable badge in the table. | `PermissionController.php`, `PermissionsExport.php`, `routes/web.php`, `role-permissions/index.blade.php` |
| 12 | Converted the remaining 8 `catch (\Exception)` to `catch (\Throwable)` across the whole codebase (UserController, UserPermissionController, MenuSynchronizer, MenuSyncCommand, AuthController), so no transaction can leak anywhere. See section 9. | see section 9 |

### What `Role::users()` does

```php
public function users(): BelongsToMany
{
    $guard = $this->guard_name ?? config('auth.defaults.guard');
    $model = Guard::getModelForGuard($guard);

    if ($model === null) {
        // Guard has no provider model: return an empty, safe relation.
        return $this->morphedByMany(
            User::class,
            'model',
            config('permission.table_names.model_has_roles'),
            app(PermissionRegistrar::class)->pivotRole,
            config('permission.column_names.model_morph_key')
        )->whereRaw('1 = 0');
    }

    return parent::users();
}
```

Detach/count operations therefore succeed as no-ops for a guard that cannot have users,
rather than exploding. The empty relation is built on a real class (`User`) so the query is
well-formed, and `whereRaw('1 = 0')` guarantees it never matches a row.

## 7. Cleanup performed

```bash
php artisan permissions:prune-invalid-guards --dry-run   # inspect first
php artisan permissions:prune-invalid-guards             # delete
php artisan permission:cache-reset
```

Before:

```
roles: SUPER ADMIN, DEVELOPER, MANAGER, TEAM LEADER, EMPLOYEE   (web)
       dummy, api                                               (api)  ← broken
permissions: 30 generated + 1 manual, all (web)
```

After:

```
roles: SUPER ADMIN, DEVELOPER, MANAGER, TEAM LEADER, EMPLOYEE,
       assignperm, web dari api2                                (all web)
permissions: 30 generated + development.command.test            (all web)
```

The tables `roles` and `role_has_permissions` were dumped to
`../agent-plan/backups/roles_backup.sql` (outside this repo) before the delete.

Two rows are **test data, not defects**, and are left in place for the owner to decide:
`assignperm` (role) and `development.command.test` (manual permission). Both use the valid
`web` guard, so they are harmless and can be removed from the UI.

## 8. How to verify

```bash
# syntax + style + tests
php artisan test
./vendor/bin/pint --test

# the incident's own checks
php artisan permissions:prune-invalid-guards --dry-run      # expect 0 roles, 0 permissions

# when data looks "stuck" or phantom again, recycle the workers — do not just clear caches
php artisan octane:status
php artisan octane:reload

# watch for a connection stuck mid-transaction
mysql -e "SELECT trx_id, trx_state, trx_started FROM information_schema.innodb_trx;"
```

`tests/Feature/RolePermissionTest.php` covers:

- creating a role with an unregistered guard returns **422** (was 500),
- create → update → delete of a `web` role returns **200** and the row actually persists.

## 9. Transaction policy — always `catch (\Throwable)`

**Policy (adopted after this incident).** Any code that calls `DB::beginTransaction()` must
catch `\Throwable`, never `\Exception`. All 14 transaction sites in this codebase now comply.

```php
DB::beginTransaction();
try {
    // ...
    DB::commit();
    return response()->json([...]);
} catch (\Throwable $e) {
    DB::rollBack();                                   // unconditional, first statement
    Log::error('...', ['message' => $e->getMessage()]);
    return response()->json([...], 500);
}
```

### 9.1 Why `\Throwable`

The job of that catch is not to classify the error — it is to restore transaction state.
Rollback is *cleanup*, not a decision, and cleanup has to cover every exit path. With
`catch (\Exception)` the rollback becomes optional, because PHP's `Error` (`TypeError`,
`ArgumentCountError`, `Class name must be a valid object or a string`) does not extend
`Exception`. That optionality is exactly the defect described in section 4.

Laravel does the same thing. `ManagesTransactions::transaction()` catches `Throwable` twice
— once around the callback (line 41) and again around the commit path (line 58) — and the
exception subsystem is typed on `Throwable` too (`shouldReport(Throwable $e)`).

### 9.2 Rollback first, unconditionally

The rollback is the first statement of the catch and is never gated on the error type. State
is restored whatever went wrong. Only *after* that may you branch.

### 9.3 Do not branch on `Exception` vs `Error`

That is usually the wrong axis. `Error` always means a bug and never a business condition,
while `Exception` mixes genuinely expected failures (`QueryException` on a constraint
violation, `GuardDoesNotMatch`) with unexpected ones — so `instanceof \Exception` tells you
very little.

The distinction you actually want is **expected failure vs bug**, and it is expressed with
*specific exception classes*:

```php
} catch (\Throwable $e) {
    DB::rollBack();                                   // 1. unconditional

    if ($e instanceof QueryException) {               // 2. only where behaviour differs
        Log::warning('...', ['message' => $e->getMessage()]);
        return response()->json([...], 422);          //    friendly message, no page to a dev
    }

    Log::error('...');                                // 3. fall-through
    report($e);                                       //    every Error lands here by itself
    return response()->json([...], 500);
}
```

Every `Error` reaches step 3 without any `instanceof \Error` check. If you do want an
explicit `Error` branch, the only worthwhile difference is log level and whether the error is
reported — never the rollback (always) and never the HTTP status (both are 500).

Class-based differentiation of that kind belongs in Laravel's exception handler:
`$levels`, `$dontReport`, and `reportable()` registered in `withExceptions()` in
`bootstrap/app.php` — not in the transaction catch.

### 9.4 Two rules that go with it

- **Never return success from a `catch (\Throwable)`.** A 200 coming out of a failed write is
  what makes a rollback invisible to the caller, and it is what produced the confusion that
  opened this investigation.
- **If you use `DB::transaction(fn () => ...)` instead, do not call `DB::rollBack()` again in
  your own catch.** The helper already rolled back; a second call throws or disturbs an
  enclosing transaction.

### 9.5 Better alternative: let the framework own the transaction

`DB::transaction($callback, $attempts)` catches `Throwable` internally, rolls back, and
retries deadlocks. Catch outside the closure only to shape the response:

```php
try {
    $result = DB::transaction(fn () => /* ... */);
    return response()->json([...]);
} catch (\Throwable $e) {
    return response()->json([...], 500);              // rollback already done
}
```

### 9.6 Current state

- **14** `DB::beginTransaction()` call sites — all guarded by `catch (\Throwable)`.
- **15** `catch (\Throwable)` in total; the extra one is `MenuSyncCommand`, which owns no
  transaction but must not fatal on an `Error` escaping `MenuSynchronizer::sync()`.
- **1** `DB::transaction()` call (`permissions:prune-invalid-guards`).
- **0** `catch (\Exception)` left in `app/`.

## 10. Rules for future work

1. **Never catch only `\Exception` around database writes** — see the policy in section 9. A
   PHP `Error` escaping a `try` block that called `DB::beginTransaction()` silently leaks the
   transaction for the lifetime of an Octane worker.
2. **Assume the request is not the only user of the connection.** Under Octane a variable
   that holds state (a transaction counter, a static cache, an open handle) survives into
   the next request. Anything request-scoped must be reset.
3. **Validate enum-like fields against the config, not a hardcoded list.** Guards come from
   `config('auth.guards')`; adding a guard to the config is all that is needed to support it.
4. **A "fix" that works by clearing caches is a coincidence until you can name the
   mechanism.** `optimize:clear` does not reload Octane workers; `octane:reload` does.
5. **Symptom "data exists in the UI but not in the database" almost always means an
   uncommitted transaction, not a wrong database.** Check
   `information_schema.innodb_trx` and the worker's log first.

## 11. Uncertainties

- Bugs #2/#5 (the hang and the phantom create) were never reproduced on demand. The
  diagnosis rests on the logged `Error` stack traces, the 30 second timeout traces, the code
  paths above, and the fact that symptom A–C are fully explained by the leaked-transaction
  mechanism. It was not possible to observe a stuck `innodb_trx` row at the moment of
  inspection, because the workers had already been recycled.
- The precise reason the timeouts clustered at 20:42–20:52 local time is not established.
  An open transaction on a shared connection is sufficient to explain it, but the specific
  blocking query was not captured.
