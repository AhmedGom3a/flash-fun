<?php

namespace App\Console\Commands;

use App\Models\FlashCard;
use App\Models\Practice;
use App\Repos\FlashCardRepositroy;
use App\Repos\PracticeRepository;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;

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
        'Quit' => 'quit'
    ];

    private string $userId;
    private bool $quit = false;

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
        $this->userId = Str::random(6);
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
        $cards = $this->flashCardRepositroy->getCardsByUserId($this->userId)->makeHidden(['status']);
        $this->table(['Question', 'Answer'], $cards);

        $this->info('---------------------');
        $this->info('Total Available Cards: '. count($cards));
        $this->info('---------------------');
    }

    private function practice(): void
    {
        $nonPracticedCards = $this->flashCardRepositroy->getNonPracticedCardsByUserId($this->userId)->toArray();
        $correctAnswers = $this->practiceRepository->getCorrectPracticedCardsCountByUserId($this->userId)->toArray();
        $PracticedCards = $this->practiceRepository->getPracticedCardsByUserId($this->userId)->toArray();

        $totalCards = count($nonPracticedCards) + count($PracticedCards);
        $percentage = $totalCards > 0 ? (int) ((count($correctAnswers) / $totalCards) * 100) : 0;

        $allCards = array_merge($nonPracticedCards, $PracticedCards);
        $cardIds = array_column($allCards, 'id');
        $practiceAllowedIds = array_diff($cardIds, array_column($correctAnswers, 'id'));
        array_multisort($cardIds, SORT_ASC, $allCards);

        $this->table(['Id','Question', 'Answer', 'Status'], $allCards);

        $this->info('---------------------');
        $this->info(sprintf(
            'Correct Answered questions are %d out of %d (%d%%)',
            $correctAnswers,
            $totalCards,
            $percentage
        ));
        $this->info('---------------------');

        if (count($practiceAllowedIds) === 0) {
            return;
        }

        $questionId = $this->chooseQuestion(
            'Choose enter question Id to practice or (0) to go back',
            $practiceAllowedIds
        );

        if($questionId == 0) {
            return;
        }

        $answer = $this->askUntilValid('Please enter the answer for this question');

        Practice::updateOrCreate(
            ['flash_card_id' => $questionId],
            ['correct' => $this->checkValidAnswer((int) $questionId, $answer)]
        );
    }

    private function quit(): void
    {
        // clear questions
        // clear practices
        $this->quit = true;
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

    private function chooseQuestion(string $question, array $practiceAllowedIds)
    {
        do {
            $value = $this->ask($question);
        } while (
            $value !== '0'
            && (
                empty($value)
                || false === is_numeric($value)
                || false === in_array($value, $practiceAllowedIds)
            )
        );

        return $value;
    }

    private function checkValidAnswer(int $questionId, string $answer): bool
    {
        return strtolower(FlashCard::find($questionId)->answer) == strtolower($answer);
    }
}
