<?php

namespace App\Console\Commands\FlashCardActions;

use App\Models\FlashCard;

class ListAllCardsAction extends AbstractAction implements FlashCardActionInterface
{
    protected int $priority = 2;

    public static function getActionName(): string
    {
        return 'List all cards';
    }

    public function handleAction(): void
    {
        $cards = FlashCard::select('question', 'answer')->get()->makeHidden(['status']);
        $this->command->table(['Question', 'Answer'], $cards);

        $this->command->info('---------------------');
        $this->command->info('Total Available Cards: '. count($cards));
    }
}
