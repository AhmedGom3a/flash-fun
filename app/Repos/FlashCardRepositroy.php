<?php

namespace App\Repos;

use App\Models\FlashCard;
use Illuminate\Database\Eloquent\Collection;

class FlashCardRepositroy
{
    public function getCardsByUserId(string $userId): Collection
    {
        return FlashCard::where('user_id', $userId)->select('question', 'answer')->get();
    }

    public function getNonPracticedCardsByUserId(string $userId): Collection
    {
        return FlashCard::where('user_id', $userId)
        ->whereDoesntHave('practices')
        ->select('flash_cards.id','question', 'answer')->get();
    }
}