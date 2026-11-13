<?php

namespace App\Models\Concerns;

/**
 * For models that store a value in both languages as `<field>_ar` and
 * `<field>_en` columns (categories, governorates, cities, category
 * attributes). `$category->localized('name')` returns the value for the
 * current app locale, falling back to Arabic when the English one is blank.
 */
trait HasLocalizedFields
{
    public function localized(string $field): ?string
    {
        $value = app()->getLocale() === 'en' ? $this->{$field.'_en'} : null;

        return filled($value) ? $value : $this->{$field.'_ar'};
    }
}
