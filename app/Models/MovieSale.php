<?php

namespace App\Models;

use \Illuminate\Database\Eloquent\Model;
use App\Traits\FindTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieSale extends Model
{
    use FindTrait, HasFactory;

    protected $hidden = [
        'id',
        'theater_id',
        'movie_id',
    ];
    protected $fillable = [
        'sale_date',
        'price',
        'movie_id',
        'movie',
        'theater_id',
        'theater',
    ];

    public function theater(): belongsTo
    {
        return $this->belongsTo(Theater::class)->withTrashed();
    }

    public function setTheaterAttribute($value): void
    {
        $this->attributes['theater_id'] = !empty($value) ? Theater::findByUuid($value)->id : null;
    }

    public function movie(): belongsTo
    {
        return $this->belongsTo(Movie::class)->withTrashed();
    }

    public function setMovieAttribute($value): void
    {
        $this->attributes['movie_id'] = !empty($value) ? Movie::findByUuid($value)->id : null;
    }
}
