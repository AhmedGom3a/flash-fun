<?php

namespace App\Repos;

use App\Models\FlashCard;
use Illuminate\Database\Eloquent\Collection;

class FlashCardRepositroy
{
    public function getNonPracticedCardsByUserId(string $userId): Collection
    {
        return FlashCard::whereDoesntHave('practices', function($query) use($userId) {
            return $query->where('user_id', $userId);
        })->select('id','question', 'answer')->get();
    }
}