<?php

namespace App\Console\Commands\FlashCardActions;

use App\Models\Practice;

class ResetPracticeAction extends AbstractAction implements FlashCardActionInterface
{
    protected int $priority = 4;

    public static function getActionName(): string
    {
        return 'Reset';
    }

    public function handleAction(): void
    {
        $this->resetPractices();
        $this->command->info('Progress reset successfully!');
    }

    protected function resetPractices(): void
    {
        Practice::where('user_id', $this->command->getUserId())->delete();
    }
}
