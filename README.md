# Laravel CRUD Generator

Generate the boring parts of a Laravel application from one Artisan command: the **controller**, **model**, **migration**,
**views**, **language file** and **API** of a resource, or each of them on its own.

> **This is a fork** of [sohelamin/crud-generator](https://github.com/sohelamin/crud-generator), kept for Laravel 13 and for
> fixes to the generated migrations. It installs as `somisa/crud-generator` and replaces `appzcoder/crud-generator`, so the
> commands, the config file and the `Appzcoder\CrudGenerator` namespace are the same as in the original.

## Requirements

- PHP 8.0 or newer
- Laravel 8, 9, 10, 11, 12 or 13

## Installation

```bash
composer require somisa/crud-generator --dev
```

If the project already requires `appzcoder/crud-generator`, remove that line first. The fork replaces it, and the two cannot be
installed side by side.

## Quick start

Generate a whole resource (controller, model, views, migration and routes) for `Posts`:

```bash
php artisan crud:generate Posts --fields='title#string; content#text'
php artisan migrate
```

The generated views use the `<x-app-layout>` component and Tailwind classes, as in a Laravel Breeze project. Without Breeze, copy the stubs with `php artisan vendor:publish --provider="Appzcoder\CrudGenerator\CrudGeneratorServiceProvider"`
(they land in `resources/crud-generator/`), set `custom_template` to `true` in `config/crudgenerator.php`, and change the layout there.

Describe the columns with `--fields`: `name#type`, separated by semicolons. Options such as a view path, a controller namespace
or a route group are listed in the documentation.

## Commands

| Command | What it makes |
|---|---|
| `crud:generate` | the whole resource: controller, model, views, migration, routes |
| `crud:api` | the same for an API: controller, model, migration |
| `crud:controller` | a resource controller |
| `crud:api-controller` | an API controller |
| `crud:model` | a model |
| `crud:migration` | a migration |
| `crud:view` | the views |
| `crud:lang` | a language file |

Run `php artisan help crud:generate` (or any command above) to see every option.

## Documentation

- The original guide, which applies to this fork: [sohelamin/crud-generator, doc/](https://github.com/sohelamin/crud-generator/tree/master/doc#readme).
- What this fork changes, and a basic run: [`docs/README.md`](docs/README.md).

## License

MIT, see [LICENSE](LICENSE). The original work is by Sohel Amin; this fork adds changes by András Somos.
