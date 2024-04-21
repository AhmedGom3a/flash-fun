<?php

namespace App\Console\Commands\FlashCardActions;

use App\Repos\PracticeRepository;
use App\Repos\FlashCardRepository;
use App\Console\Commands\ManageFlashCard;

abstract class AbstractAction
{
    public function __construct(
        protected FlashCardRepository $flashCardRepository,
        protected PracticeRepository $practiceRepository,
    ) {
    }

    protected ManageFlashCard $command;

    protected int $priority = 0;

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setCommand(ManageFlashCard $command): void
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
