<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = ['name', 'dob'];

    protected $casts = [
        'dob' => 'date:Y-m-d',
    ];

    public function scopeWhereNameLike(Builder $query, $name): Builder
    {
        return $query->where('name', 'ILIKE', '%' . $name . '%');
    }

    public function scopeWhereDob(Builder $query, $dob): Builder
    {
        return $query->whereDate('dob', $dob);
    }
}
