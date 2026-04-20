<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'ulid', 'slug', 'parent_id', 'name_en', 'name_id', 
        'route_name', 'icon', 'order', 'is_active', 
        'permission_name', 'created_by', 'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id')
                    ->where('is_active', true)
                    ->orderBy('order');
    }

    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function uniqueIds(): array 
    {
        return ['ulid']; 
    }
}
