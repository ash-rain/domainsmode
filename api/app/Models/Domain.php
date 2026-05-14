<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Domain extends Model
{
    protected $fillable = [
        'domain',
        'nameserver_1',
        'nameserver_2',
        'nameserver_3',
        'nameserver_4',
        'mx_record',
        'a_record',
    ];

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }
}
