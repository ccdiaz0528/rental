<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity as BaseActivity;

class Activity extends BaseActivity
{
    protected $casts = [
        'properties' => 'collection',
        'attribute_changes' => 'collection',
    ];
}
