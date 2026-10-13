# Documentation

1. **The original documentation** (installation, configuration, usage, fields, options, templates) applies to this fork unchanged:
   [sohelamin/crud-generator, doc/](https://github.com/sohelamin/crud-generator/tree/master/doc#readme). The commands, the options
   and the config file are the same.
2. This page only says **what the fork changes** and gives **a basic run**.

## What the fork changes

| Change | Status | Why |
|---|---|---|
| Composer name `somisa/crud-generator`, with `replace` for `appzcoder/crud-generator` | done | the fork installs under its own name and takes the place of the original, never next to it |
| `illuminate/support` constraint allows Laravel 13 (`^13.0` added) | done | the original stops at Laravel 12 |
| `increments('id')` becomes `id()` in the generated migration | planned | a signed `INT` primary key cannot be referenced by a `BIGINT UNSIGNED` foreign key column (MySQL error 3780) |
| `bigInteger()->unsigned()` becomes `unsignedBigInteger()` | planned | the shorthand the framework itself uses |
| each `foreign()` on its own lines, with `->restrictOnDelete()` | planned | readable, and a delete that would orphan a row is refused |
| `timestamps()` placed before the first `foreign()` | planned | the usual column order |
| the closing `});` of the schema at the right indent | planned | the original writes it at the wrong indent |
| a constraint name longer than 64 characters gets a short explicit name | planned | MySQL's identifier limit would fail after the table was already created (error 1059) |

"Planned" means it is not in the code yet. This table is updated when a change lands.

## A basic run

```bash
composer require somisa/crud-generator --dev
php artisan crud:generate Posts --fields='title#string; content#text'
php artisan migrate
```

This writes the controller, the model, the views, the migration and the routes for `Posts`. Every other command and option is in
the original documentation (point 1).
