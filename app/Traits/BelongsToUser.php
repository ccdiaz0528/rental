<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait BelongsToUser
{
    protected static function bootBelongsToUser(): void
    {
        static::creating(function (Model $model) {
            if (auth()->check() && ! $model->user_id) {
                $model->user_id = auth()->id();
            }
        });
    }
}
