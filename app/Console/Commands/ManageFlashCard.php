<?php

namespace App\Console\Commands;

use App\Models\Practice;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use App\Repos\PracticeRepository;
use App\Repos\FlashCardRepositroy;
use Illuminate\Database\Eloquent\Collection;
use App\Console\Commands\FlashCardActions\FlashCardActionInterface;

class ManageFlashCard extends Command
{

    protected $signature = 'flashcard:interactive';
    protected $description = 'Command description';

    private const USER_ID_LENGTH = 6;

    private string $userId;
    private bool $quit = false;

    private int $correctAnswers = 0;
    private int $practiced = 0;

    private array $menu = [];
    private array $flashCards = [];
    private array $allowedQuestionsAnswers = [];


    public function __construct(
        private array $actions,
        private FlashCardRepositroy $flashCardRepositroy,
        private PracticeRepository $practiceRepository,
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $this->createRandomUserId();
        $this->prepareActions();

        while ($this->quit === false) {
            $choice = $this->showMainMenu();

            call_user_func([$this->actions[$choice], 'handleAction']);
        }
    }

    private function createRandomUserId(): void
    {
        $this->userId = Str::random(self::USER_ID_LENGTH);
    }

    private function showMainMenu(): string
    {
        return $this->choice('What would you like to do?', $this->menu);
    }

    private function practice(): void
    {
        $this->loadFlashCards();
        $this->displayPracticeStats();

        if (count($this->allowedQuestionsAnswers) === 0) {
            $this->info('No more questions to practice!');
            return;
        }

        $this->startPractice();
    }

    private function stats(): void
    {
        $this->loadFlashCards();
        $allCardsCount = count($this->flashCards);
        $this->info('Total available of questions: '. $allCardsCount);
        $this->info($this->getPercentage($this->practiced, $allCardsCount).' % of questions that have an answer.');

        $this->info($this->getCorrectPercentage().' % of questions that have a correct answer.');
    }

    public function setQuit(bool $quit): void
    {
        $this->quit = $quit;
    }

    private function startPractice(): void
    {
        while (true) {
            $chosenQuestionId = $this->ask('Enter question number to practice or (0) to go back');

            if ($chosenQuestionId == 0) {
                return;
            }

            if (false === $this->isValidQuestionId($chosenQuestionId)) {
                continue;
            }

            if (false === in_array($chosenQuestionId, array_keys($this->allowedQuestionsAnswers))) {
                $this->info('Already answered this question!');
                continue;
            }
            
            $answer = $this->askUntilValid($this->flashCards[$chosenQuestionId]['question']);

            $correct = $this->checkValidAnswer((int) $chosenQuestionId, $answer);

            $practice = Practice::updateOrCreate(
                [
                    'flash_card_id' => $chosenQuestionId,
                    'user_id' => $this->userId
                ],
                [
                    'correct' => $correct,
                    'user_id' => $this->userId
                ]
            );

            $this->info("Your Answer is {$practice->status}!");

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
        $this->table(['Number','Question', 'Status'], $this->flashCards);

        $this->info('---------------------');
        $this->info(sprintf(
            'Correct Answered questions are %d out of %d (%d%%)',
            $this->correctAnswers,
            count($this->flashCards),
            $this->getCorrectPercentage()
        ));
    }
   
    private function loadFlashCards(): void
    {
        $this->allowedQuestionsAnswers = [];
        $this->flashCards = [];
        $nonPracticed = $this->flashCardRepositroy->getNonPracticedCardsByUserId($this->userId);
        $this->prepareCardsCollection($nonPracticed);
        $this->prepareCardsCollection($this->practiceRepository->getUserPractices($this->userId));
        
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
                'status' => $card->status ?? 'Not Answered'
            ];

            if($card->status === null || $card->status === 'Incorrect') {
                $this->allowedQuestionsAnswers[$flashCardId] = $card->answer;
            }
        }
    }

    private function getCorrectPercentage(): int
    {
        return count($this->flashCards) > 0 ? (int) (($this->correctAnswers / count($this->flashCards)) * 100) : 0;
        return $this->getPercentage($this->correctAnswers, count($this->flashCards));
    }

    private function getPercentage(int $value, int $total): int
    {
        return $total > 0 ? (int) (($value / $total) * 100) : 0;
    }

    private function prepareActions(): void
    {
        $sortedActions = $this->sortActionsByPriority();
        
        $this->actions = [];
        $this->menu = [];

        /** @var FlashCardActionInterface $actionInstance */
        foreach ($sortedActions as $actionInstance) {
            $actionInstance->setCommand($this);

            $this->menu[$actionInstance->getPriority()] = $actionInstance->getActionName();
            $this->actions[$actionInstance->getActionName()] = $actionInstance;
        }
    }
    
    private function sortActionsByPriority(): array
    {
        $sortedActions = array_values($this->actions);

        usort($sortedActions, function (FlashCardActionInterface $a, FlashCardActionInterface $b) {
            return $a->getPriority() <=> $b->getPriority();
        });

        return $sortedActions;
    }
}
