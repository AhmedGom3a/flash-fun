<?php

namespace App\Repos;

use App\Models\Practice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PracticeRepository
{
    public function getPracticedCardsByUserId(string $userId): Collection
    {
        return Practice::leftJoin('flash_cards', 'practices.flash_card_id', '=', 'flash_cards.id')
        ->select('flash_cards.id', 'flash_cards.question', 'flash_cards.answer', DB::raw("IF(practices.correct, 'correct', 'incorrect') AS status"))
        ->where('flash_cards.user_id', $userId)
        ->get();
    }

    public function getCorrectPracticedCardsCountByUserId(string $userId): Collection
    {
        return Practice::leftJoin('flash_cards', 'practices.flash_card_id', '=', 'flash_cards.id')
        ->where('flash_cards.user_id', $userId)
        ->where('practices.correct', 1)
        ->get();
    }
}