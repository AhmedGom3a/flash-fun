<?php

namespace App\Repos;

use App\Models\Practice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PracticeRepository
{
    public function getUserPractices(string $userId): Collection
    {
        return Practice::where('user_id', $userId)->get();
    }
}