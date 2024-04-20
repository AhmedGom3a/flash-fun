<?php

namespace App\Console\Commands\FlashCardActions;

use App\Models\FlashCard;

class CreateFlashCardAction extends AbstractAction implements FlashCardActionInterface
{
    protected int $priority = 1;

    public static function getActionName(): string
    {
        return 'Create a flashcard';
    }

    public function handleAction(): void
    {
        $this->command->info('Let\'s create a new card!');

        $question = $this->askUntilValid('Please enter the question for this card');
        $answer = $this->askUntilValid('Please enter the answer for this question');

        FlashCard::create([
            'user_id' => $this->command->getUserId(),
            'question' => $question,
            'answer' => $answer,
        ]);

        $this->command->info('Card created successfully!');
    }
}
