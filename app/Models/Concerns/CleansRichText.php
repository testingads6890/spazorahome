<?php

namespace App\Models\Concerns;

use App\Support\Html;

/**
 * Models that store CKEditor text list the columns in $richText.
 * Anything saved through the model is cleaned, whichever form or import it came from.
 */
trait CleansRichText
{
    protected static function bootCleansRichText(): void
    {
        static::saving(function ($model) {
            foreach ($model->richText ?? [] as $field) {
                if ($model->isDirty($field) && is_string($model->getAttribute($field))) {
                    $model->setAttribute($field, Html::clean($model->getAttribute($field)) ?: null);
                }
            }
        });
    }
}
