<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlashCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'question',
        'answer',
    ];

    protected $appends = ['status'];

    public function getStatusAttribute()
    {
        return 'Not Answered';
    }

    public function practices(){
        return $this->hasMany(Practice::class);
    }
}
