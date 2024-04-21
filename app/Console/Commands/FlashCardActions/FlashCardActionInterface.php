<?php

namespace App\Console\Commands\FlashCardActions;

use App\Console\Commands\ManageFlashCard;

interface FlashCardActionInterface
{
    public static function getActionName(): string;
    
    public function getPriority(): int;

    public function handleAction(): void;

    public function setCommand(ManageFlashCard $command): void;
}
