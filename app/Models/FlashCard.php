<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlashCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'question',
        'answer',
    ];

    public function practices(){
        return $this->hasMany(Practice::class);
    }
}
