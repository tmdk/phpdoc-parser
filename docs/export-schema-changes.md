# Export Schema Changes

Breaking changes to the export data format.

## `@global` tag — structured output

**Commit:** `feat: add Global_Tag for @global docblock tag parsing`

The `@global` docblock tag is now parsed into structured fields, consistent
with `@param`, `@return`, `@var`, and other typed tags.

### Before

```json
{
    "name": "global",
    "content": "WP_Locale $wp_locale WordPress date and time locale object."
}
```

The entire tag body (type, variable, description) was stored as a single
flat string in `content`.

### After

```json
{
    "name": "global",
    "content": "WordPress date and time locale object.",
    "types": ["\\WP_Locale"],
    "variable": "$wp_locale"
}
```

| Field      | Type       | Description                                           |
|------------|------------|-------------------------------------------------------|
| `name`     | `string`   | Always `"global"`.                                    |
| `content`  | `string`   | Description text only (may be empty).                 |
| `types`    | `string[]` | Resolved type(s). Union types are split into entries. |
| `variable` | `string`   | Variable name including `$` prefix.                   |

### Type resolution

Types are resolved through phpDocumentor's `TypeResolver`:

- Unqualified class names are resolved to their FQCN (`WP_Locale` -> `\WP_Locale`).
- Union types are split into array entries (`WP_Post|null` -> `["\WP_Post", "null"]`).
- Array suffixes are preserved (`string[]` -> `["string[]"]`).
- Complex generics that fail resolution are kept as raw strings
  (`array<string, WP_Translations|NOOP_Translations>` -> `["array<string, WP_Translations|NOOP_Translations>"]`).

### Variations

All `@global` syntax found in the WordPress codebase is supported:

| Pattern                                  | types                  | variable         | content          |
|------------------------------------------|------------------------|------------------|------------------|
| `@global WP_Locale $locale Description.` | `["\WP_Locale"]`       | `"$locale"`      | `"Description."` |
| `@global wpdb $wpdb`                     | `["\wpdb"]`            | `"$wpdb"`        | `""`             |
| `@global WP_Post\|null $post The post.`  | `["\WP_Post", "null"]` | `"$post"`        | `"The post."`    |
| `@global string[] $exts Names.`          | `["string[]"]`         | `"$exts"`        | `"Names."`       |
| `@global $plugin_page`                   | *(absent)*             | `"$plugin_page"` | `""`             |

### Migration

Consumers that read `@global` tags from the export data should update from:

```php
// Before: parse type and variable out of content string
$content = $tag['content']; // "WP_Locale $wp_locale Description."
```

to:

```php
// After: use structured fields
$types    = $tag['types'];    // ["\WP_Locale"]
$variable = $tag['variable']; // "$wp_locale"
$content  = $tag['content'];  // "Description."
```
