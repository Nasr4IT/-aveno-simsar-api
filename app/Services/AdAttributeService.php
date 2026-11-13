<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\Category;

// Per-category dynamic specs (category_attributes) on an ad — validation
// of a submitted attributes[] payload and persisting it. Shared by the
// JSON API (Api\AdController) and the website (Site\*) so an ad posted
// from either surface is checked against exactly the same rules.
class AdAttributeService
{
    // Returns a list of Arabic error strings (empty = valid). Checks
    // required-ness, and value types/options, against the category's specs.
    // enforceRequired=false is used on update, where the request is a
    // partial edit and not every spec has to be resent.
    public function validate(Category $category, array $attributes, bool $enforceRequired): array
    {
        $errors = [];

        foreach ($category->attributes_ as $attr) {
            $present = array_key_exists($attr->key, $attributes)
                && $attributes[$attr->key] !== null
                && $attributes[$attr->key] !== '';

            if (! $present) {
                if ($enforceRequired && $attr->is_required) {
                    $errors[] = __('الحقل ":label" مطلوب.', ['label' => $attr->localized('label')]);
                }

                continue;
            }

            $value = $attributes[$attr->key];

            match ($attr->type) {
                'number' => is_numeric($value) ? null : $errors[] = __('قيمة ":label" يجب أن تكون رقمًا.', ['label' => $attr->localized('label')]),
                'select' => in_array($value, $attr->options ?? [], true) ? null : $errors[] = __('قيمة ":label" غير صالحة.', ['label' => $attr->localized('label')]),
                'multiselect' => array_diff(is_array($value) ? $value : [$value], $attr->options ?? []) === []
                    ? null : $errors[] = __('قيمة ":label" غير صالحة.', ['label' => $attr->localized('label')]),
                default => null,
            };
        }

        return $errors;
    }

    // Creates/updates/clears ad_attribute_values rows for exactly the keys
    // present in $attributes (unknown keys not defined on the category are
    // ignored; an empty value clears an existing row).
    public function sync(Ad $ad, Category $category, array $attributes): void
    {
        foreach ($category->attributes_ as $attr) {
            if (! array_key_exists($attr->key, $attributes)) {
                continue;
            }

            $value = $attributes[$attr->key];

            if ($value === null || $value === '') {
                $ad->attributeValues()->where('category_attribute_id', $attr->id)->delete();

                continue;
            }

            $ad->attributeValues()->updateOrCreate(
                ['category_attribute_id' => $attr->id],
                ['value' => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value]
            );
        }
    }
}
