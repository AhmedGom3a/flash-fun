<?php

namespace App\Console\Commands\FlashCardActions;

use App\Models\Practice;
use Illuminate\Support\Collection;

class PracticeAction extends AbstractAction implements FlashCardActionInterface
{
    protected int $priority = 3;
    private const BACK_TO_MAIN_MENU_INPUT = 0;
    private const FLASH_CARD_DEFAULT_STATUS = 'Not Answered';

    public static function getActionName(): string
    {
        return 'Practice';
    }

    public function handleAction(): void
    {
        $this->loadFlashCards();
        $this->displayPracticeStats();

        if (count($this->command->allowedQuestionsAnswers) === self::BACK_TO_MAIN_MENU_INPUT) {
            $this->command->info('No more questions to practice!');
            return;
        }

        $this->startPractice();
    }

    private function startPractice(): void
    {
        while (true) {
            $chosenQuestionId = $this->command->ask(sprintf(
                'Enter question number to practice or (%d) to go back',
                self::BACK_TO_MAIN_MENU_INPUT
            ));

            if ((int) $chosenQuestionId === self::BACK_TO_MAIN_MENU_INPUT) {
                return;
            }

            if (false === $this->isValidQuestionId($chosenQuestionId)) {
                continue;
            }

            if (false === in_array($chosenQuestionId, array_keys($this->command->allowedQuestionsAnswers))) {
                $this->command->info('Already answered this question!');
                continue;
            }

            $chosenQuestion = array_filter($this->command->flashCards, function($card) use ($chosenQuestionId) {
                return $card['number'] === (int) $chosenQuestionId;
            });

            $chosenQuestion = reset($chosenQuestion);
            
            $answer = $this->askUntilValid($chosenQuestion['question']);

            $correct = $this->checkValidAnswer((int) $chosenQuestionId, $answer);

            $practice = Practice::updateOrCreate(
                [
                    'flash_card_id' => $chosenQuestionId,
                    'user_id' => $this->command->getUserId()
                ],
                [
                    'correct' => $correct,
                    'user_id' => $this->command->getUserId()
                ]
            );

            $this->command->info("Your Answer is {$practice->status}!");

            $this->loadFlashCards();
            $this->displayPracticeStats();
        };
    }

    private function isValidQuestionId(string $value) {
        return false === empty($value)
        && true === is_numeric($value);
    }

    private function checkValidAnswer(int $questionId, string $answer): bool
    {
        return isset($this->command->allowedQuestionsAnswers[$questionId])
        && strtolower($this->command->allowedQuestionsAnswers[$questionId]) == strtolower($answer);
    }

    private function displayPracticeStats(): void 
    {
        usort($this->command->flashCards, function (array $a, array $b) {
            return $a['number'] <=> $b['number'];
        });

        $this->command->table(['Number','Question', 'Status'], $this->command->flashCards);
        $this->command->info('---------------------');
        $this->command->info(sprintf(
            'Correct Answered questions are %d out of %d (%d%%)',
            $this->command->correctAnswers,
            count($this->command->flashCards),
            $this->getCorrectPercentage()
        ));
    }

    protected function loadFlashCards(): void
    {
        $this->command->allowedQuestionsAnswers = [];
        $this->command->flashCards = [];
        $nonPracticed = $this->flashCardRepository->getNonPracticedCardsByUserId($this->command->getUserId());
        $this->prepareCardsCollection($nonPracticed);
        $this->prepareCardsCollection($this->practiceRepository->getUserPractices($this->command->getUserId()));
        
        $this->command->practiced = count($this->command->flashCards) - count($nonPracticed);
        $this->command->correctAnswers = count($this->command->flashCards) - count($this->command->allowedQuestionsAnswers);
    }

    private function prepareCardsCollection(Collection $cards): void
    { 
        foreach ($cards as $card) {
            $flashCardId = $card->flash_card_id ?? $card->id;
            $this->command->flashCards[$flashCardId] = [
                'number' => $flashCardId,
                'question' => $card->question,
                'status' => $card->status ?? self::FLASH_CARD_DEFAULT_STATUS
            ];

            // User can only practice not answered or Incorrect cards
            if($card->status === null || $card->status === 'Incorrect') {
                $this->command->allowedQuestionsAnswers[$flashCardId] = $card->answer;
            }
        }
    }

    protected function getCorrectPercentage(): int
    {
        return $this->getPercentage($this->command->correctAnswers, count($this->command->flashCards));
    }

    protected function getPercentage(int $value, int $total): int
    {
        return $total > 0 ? (int) (($value / $total) * 100) : 0;
    }
}
