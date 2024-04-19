<?php

namespace App\Console\Commands;

use App\Models\FlashCard;
use App\Models\Practice;
use App\Repos\FlashCardRepositroy;
use App\Repos\PracticeRepository;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class ManageFlashCard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flashcard:interactive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    
    private array $actions = [
        'Create a flashcard' => 'createFlashCard',
        'List all flashcards' => 'listAllCards',
        'Practice' => 'practice',
        'Stats' => 'stats',
        'Reset' => 'resetPractices',
        'Quit' => 'quit'
    ];

    private string $userId;
    private bool $quit = false;
    private int $correctAnswers = 0;
    private int $practiced = 0;

    private $flashCards = [];
    private $allowedQuestionsAnswers = [];

    private const USER_ID_LENGTH = 6;

    public function __construct(
        private FlashCardRepositroy $flashCardRepositroy,
        private PracticeRepository $practiceRepository,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->createRandomUserId();

        while ($this->quit === false) {
            $choice = $this->showMainMenu();

            call_user_func([self::class, $this->actions[$choice]]);
        }
    }

    private function createRandomUserId(): void
    {
        $this->userId = Str::random(self::USER_ID_LENGTH);
    }

    private function createFlashCard(): void
    {
        $this->info('Let\'s create a new card!');

        $question = $this->askUntilValid('Please enter the question for this card');
        $answer = $this->askUntilValid('Please enter the answer for this question');

        FlashCard::create([
            'user_id' => $this->userId,
            'question' => $question,
            'answer' => $answer,
        ]);

        $this->info('Card created successfully!');
    }

    private function listAllCards(): void
    {
        $cards = FlashCard::select('question', 'answer')->get()->makeHidden(['status']);
        $this->table(['Question', 'Answer'], $cards);

        $this->info('---------------------');
        $this->info('Total Available Cards: '. count($cards));
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

    private function resetPractices(): void
    {
        if (false === $this->quit) {
            $this->info('Progress reset successfully!');
        }
        Practice::where('user_id', $this->userId)->delete();
    }

    private function quit(): void
    {
        $this->quit = true;
        $this->resetPractices();
    }
    
    private function showMainMenu(): string
    {
        return $this->choice('What would you like to do?', array_keys($this->actions));
    }

    private function askUntilValid(string $question)
    {
        do {
            $value = $this->ask($question);
        } while (empty($value));

        return $value;
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
}
