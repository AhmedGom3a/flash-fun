<?php

namespace App\Console\Commands\FlashCardActions;

use Illuminate\Console\Command;

abstract class AbstractAction
{
    protected Command $command;

    protected int $priority = 0;

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setCommand(Command $command): void
    {
        $this->command = $command;
    }

    protected function askUntilValid(string $question)
    {
        do {
            $value = $this->command->ask($question);
        } while (empty($value));

        return $value;
    }
}
