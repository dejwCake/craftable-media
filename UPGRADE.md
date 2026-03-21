# Upgrade Guide

## From v1 to v2

### Requirements

- PHP `^8.5` (was `^8.2`)
- Laravel `^13.0` (was `^12.0`)
- `spatie/laravel-medialibrary` `^11.21` (was `^11.12.7`)

### Breaking changes

#### `AutoProcessMediaTrait` boot method renamed

The boot method was renamed from `bootHasMediaCollectionsTrait` to `bootAutoProcessMediaTrait` to match Laravel's trait boot convention (`boot<TraitName>`). The v1 name was incorrect and the auto-processing only worked because it was called explicitly elsewhere.

If you overrode or referenced `bootHasMediaCollectionsTrait`, rename it:

```diff
- public static function bootHasMediaCollectionsTrait(): void
+ public static function bootAutoProcessMediaTrait(): void
```

#### `MediaCollection::disk()` method removed

The deprecated `disk()` method has been removed. Use `private()` for the private disk, or rely on the default public disk:

```diff
- $this->addMediaCollection('documents')->disk('media_private');
+ $this->addMediaCollection('documents')->private();
```

#### `MediaCollection` is now `final`

The class can no longer be extended. If you have subclasses of `MediaCollection`, move the logic into your model or a separate service class.

#### `MediaCollection` properties changed from `protected` to `private`

All internal properties (`$isImage`, `$maxNumberOfFiles`, `$maxFileSize`, `$acceptedFileTypes`, `$viewPermission`, `$uploadPermission`) are now `private`. Use the public getters instead:

- `isImage()`
- `getMaxNumberOfFiles()`
- `getMaxFileSize()`
- `getAcceptedFileTypes()`
- `getViewPermission()`
- `getUploadPermission()`

#### `MediaCollection` getter return types tightened

| Method | v1 | v2 |
|---|---|---|
| `getName()` | `?string` | `string` |
| `getDisk()` | `?string` | `string` |
| `getMaxFileSize()` | `?int` | `int` |

Remove any `null` checks on these return values.

#### `FileIsTooBig` exception message fixed

The exception previously used the wrong translation key (`brackets/media::media.exceptions.thumbs_does_not_exists`). It now correctly uses `brackets/media::media.exceptions.file_is_too_big`. Update any code that matched on the old (incorrect) message.

#### All concrete classes are now `final`

The following classes can no longer be extended:

- `MediaServiceProvider`
- `MediaCollection`
- `LocalUrlGenerator`
- `FileUploadController`
- `FileViewController`
- `MediaCollectionAlreadyDefined`
- `ThumbsDoesNotExists`
- `FileIsTooBig`
- `TooManyFiles`

### Config and language files

#### Config publish path changed

The config source moved from `install-stubs/config/` to `config/`. Re-publish if needed:

```shell
php artisan vendor:publish --tag=config --provider="Brackets\Media\MediaServiceProvider"
```

#### Language files moved

Language files moved from `resources/lang/` to `lang/`. Translations are now auto-loaded by the service provider (no publish required). To customize, publish with the new tag:

```shell
php artisan vendor:publish --tag=lang --provider="Brackets\Media\MediaServiceProvider"
```

If you previously published translations, update or remove the old files at `lang/vendor/brackets/media/`.

### Internal changes (no action needed)

- `#[\Override]` attributes added to overridden methods
- `app()` helper replaced with DI / static calls in traits and service provider
- `LocalUrlGenerator::makeCompatibleForNonUnixHosts()` changed from `protected` to `private`
- Config path helpers use single-argument form: `public_path('/media')`, `storage_path('/app/media')`
