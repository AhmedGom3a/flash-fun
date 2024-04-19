<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Practice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'flash_card_id',
        'correct'
    ];

    public function flashCard(){
        return $this->belongsTo(FlashCard::class);
    }

    public function getStatusAttribute()
    {
        return $this->correct ? 'Correct': 'Incorrect';
    }

    public function getQuestionAttribute()
    {
        return $this->flashCard->question;
    }

    public function getAnswerAttribute()
    {
        return $this->flashCard->answer;
    }
}
