<?php

namespace App\Console\Commands\FlashCardActions;

class ShowStatsAction extends PracticeAction implements FlashCardActionInterface
{
    protected int $priority = 4;

    public static function getActionName(): string
    {
        return 'Stats';
    }

    public function handleAction(): void
    {
        $this->loadFlashCards();

        $allCardsCount = count($this->flashCards);
        $this->command->info('Total available of questions: '. $allCardsCount);
        $this->command->info($this->getPercentage($this->practiced, $allCardsCount).' % of questions that have an answer.');
        $this->command->info($this->getCorrectPercentage().' % of questions that have a correct answer.');
    }
}
