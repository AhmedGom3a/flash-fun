<?php

namespace App\Console\Commands\FlashCardActions;

use App\Models\Practice;
use Illuminate\Support\Collection;

class PracticeAction extends AbstractAction implements FlashCardActionInterface
{
    protected int $priority = 3;
    private const BACK_TO_MAIN_MENU_INPUT = 0;
    private const FLASH_CARD_DEFAULT_STATUS = 'Not Answered';

    protected int $correctAnswers = 0;
    protected int $practiced = 0;
    protected array $flashCards = [];
    protected array $allowedQuestionsAnswers = [];

    public static function getActionName(): string
    {
        return 'Practice';
    }

    public function handleAction(): void
    {
        $this->loadFlashCards();
        $this->displayPracticeStats();

        if (count($this->allowedQuestionsAnswers) === self::BACK_TO_MAIN_MENU_INPUT) {
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

            if (false === in_array($chosenQuestionId, array_keys($this->allowedQuestionsAnswers))) {
                $this->command->info('Already answered this question!');
                continue;
            }

            $chosenQuestion = array_filter($this->flashCards, function($card) use ($chosenQuestionId) {
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
        return isset($this->allowedQuestionsAnswers[$questionId])
        && strtolower($this->allowedQuestionsAnswers[$questionId]) == strtolower($answer);
    }

    private function displayPracticeStats(): void 
    {
        usort($this->flashCards, function (array $a, array $b) {
            return $a['number'] <=> $b['number'];
        });

        $this->command->table(['Number','Question', 'Status'], $this->flashCards);
        $this->command->info('---------------------');
        $this->command->info(sprintf(
            'Correct Answered questions are %d out of %d (%d%%)',
            $this->correctAnswers,
            count($this->flashCards),
            $this->getCorrectPercentage()
        ));
    }

    protected function loadFlashCards(): void
    {
        $this->allowedQuestionsAnswers = [];
        $this->flashCards = [];
        $nonPracticed = $this->flashCardRepository->getNonPracticedCardsByUserId($this->command->getUserId());
        $this->prepareCardsCollection($nonPracticed);
        $this->prepareCardsCollection($this->practiceRepository->getUserPractices($this->command->getUserId()));
        
        $this->practiced = count($this->flashCards) - count($nonPracticed);
        $this->correctAnswers = count($this->flashCards) - count($this->allowedQuestionsAnswers);
    }

    private function prepareCardsCollection(Collection $cards): void
    { 
        foreach ($cards as $card) {
            $flashCardId = $card->flash_card_id ?? $card->id;
            $this->flashCards[$flashCardId] = [
                'number' => $flashCardId,
                'question' => $card->question,
                'status' => $card->status ?? self::FLASH_CARD_DEFAULT_STATUS
            ];

            // User can only practice not answered or Incorrect cards
            if($card->status === null || $card->status === 'Incorrect') {
                $this->allowedQuestionsAnswers[$flashCardId] = $card->answer;
            }
        }
    }

    protected function getCorrectPercentage(): int
    {
        return $this->getPercentage($this->correctAnswers, count($this->flashCards));
    }

    protected function getPercentage(int $value, int $total): int
    {
        return $total > 0 ? (int) (($value / $total) * 100) : 0;
    }
}
