<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitedPlace extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'place_id',
        'name',
        'lat',
        'lng',
        'menu_image_url',
        'menu_table',
        'visited_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lat' => 'decimal:8',
            'lng' => 'decimal:8',
            'menu_table' => 'array',
            'visited_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the visited place.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
