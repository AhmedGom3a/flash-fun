<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Practice extends Model
{
    use HasFactory;

    protected $fillable = [
        'flash_card_id',
        'correct'
    ];

    public function flashCard(){
        return $this->belongsTo(FlashCard::class);
    }
}
