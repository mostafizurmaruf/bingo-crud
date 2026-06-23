# Laravel CRUD Generator (`make:crud`)

A self-contained Artisan command that scaffolds a complete CRUD stack from a
single schema string — **model, migration, form request, resource controller,
Blade views (index / create / edit / show + shared form partial + layout) and
the resource route** — with no third-party packages.

* Laravel 12 · PHP 8.3+ · PSR-12 · clean architecture · Laravel stub templates

---

## 1. Installation

Install via Composer — no copy/paste required:

```bash
composer require mostafizurmaruf/bingo-crud
```

## 3. Usage

```bash
php artisan make:crud {Model} --schema="field:type:modifier, ..." [--force]
```

| Argument / option | Description                                              |
|-------------------|----------------------------------------------------------|
| `Model`           | Singular model name, e.g. `Product`.                     |
| `--schema=`       | Comma-separated field definitions (see below).          |
| `--force`         | Overwrite files that already exist.                     |

### Example

```bash
php artisan make:crud Product --schema="name:string, sku:string(64):unique, price:decimal(8,2), description:text:nullable, stock:integer:default(0), status:enum(draft,published):default(draft), is_active:boolean:default(1), published_at:datetime:nullable, meta:json:nullable"
```

Then:

```bash
php artisan migrate
# visit /products
```

## 4. Schema syntax

```
schema   := field ("," field)*
field    := name ":" type (":" modifier)*
type     := word ( "(" args ")" )?       # e.g. decimal(8,2), string(64), enum(a,b)
modifier := nullable | unique | index | default(value)
```

### Supported types

`string`, `char`, `email`, `password`, `text`, `longText`, `integer`,
`bigInteger`, `unsignedInteger`, `unsignedBigInteger`, `boolean`, `decimal`,
`float`, `double`, `date`, `datetime`, `timestamp`, `time`, `year`, `json`,
`enum`, `uuid`.

Each type automatically maps to:

| Type      | Migration column          | Validation         | Form input        | Cast        |
|-----------|---------------------------|--------------------|-------------------|-------------|
| string    | `string`                  | `string, max:255`  | `text`            | —           |
| email     | `string`                  | `email, max:255`   | `email`           | —           |
| text      | `text`                    | `string`           | `textarea`        | —           |
| integer   | `integer`                 | `integer`          | `number`          | `integer`   |
| boolean   | `boolean`                 | `boolean`          | `checkbox`        | `boolean`   |
| decimal   | `decimal(p,s)`            | `numeric`          | `number`          | `decimal:s` |
| date      | `date`                    | `date`             | `date`            | `date`      |
| datetime  | `dateTime`                | `date`             | `datetime-local`  | `datetime`  |
| enum      | `enum([...])`             | `in:...`           | `select`          | —           |
| json      | `json`                    | `json`             | `textarea`        | `array`     |

### Modifiers

* `nullable` — column is nullable; validation becomes `nullable` instead of `required`.
* `unique`   — adds a unique index **and** a `Rule::unique(...)->ignore($id)` rule
  (correct for both store and update).
* `index`    — adds a plain index.
* `default(value)` — column default (`true`/`false` for booleans, numbers as-is,
  everything else quoted).

## 5. What gets generated

| File | Notes |
|------|-------|
| `database/migrations/<ts>_create_<table>_table.php` | Columns, modifiers, `id()` + `timestamps()`. Skipped if a `create_<table>_table` migration already exists. |
| `app/Models/<Model>.php`            | `$fillable` + typed `casts()` method. |
| `app/Http/Requests/<Model>Request.php` | `rules()` derived from the schema; `Rule` import only when needed. |
| `app/Http/Controllers/<Model>Controller.php` | Full resource controller using the form request + route-model binding. |
| `resources/views/<table>/{index,create,edit,show,_form}.blade.php` | Bootstrap 5 views; create/edit share `_form`. |
| `resources/views/layouts/app.blade.php` | Generated **only if missing** — flash + validation summary. |
| `routes/web.php` | `Route::resource(...)` appended with its controller `use` import, idempotently. |

## 6. Safety & idempotency

* Existing files are **never overwritten** without `--force`.
* The route line and controller import are added only once.
* An existing migration for the same table is left untouched.
* The application layout is only created when one does not already exist.

## 7. Notes

* Views use the Bootstrap 5 CDN (front-end only — no Composer dependency).
* Booleans render as a checkbox with a hidden `0` companion so the value is
  always submitted.
* `json` fields are serialised with `JSON_PRETTY_PRINT` for display/editing.
* To customise the generated output, edit the templates in `stubs/crud/` —
  the command reads them at runtime.
