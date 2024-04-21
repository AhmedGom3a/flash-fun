<?php

namespace App\Console\Commands\FlashCardActions;

class QuitAction extends ResetPracticeAction implements FlashCardActionInterface
{
    protected int $priority = 6;

    public static function getActionName(): string
    {
        return 'Quit';
    }

    public function handleAction(): void
    {
        $this->resetPractices();
        $this->command->setQuit(true);
    }
}
