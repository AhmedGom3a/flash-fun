<?php

namespace App\Console\Commands\FlashCardActions;

use Illuminate\Console\Command;

interface FlashCardActionInterface
{
    public static function getActionName(): string;
    
    public function getPriority(): int;

    public function handleAction(): void;

    public function setCommand(Command $command): void;
}
