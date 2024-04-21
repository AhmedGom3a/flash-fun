<?php

namespace App\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use App\Console\Commands\FlashCardActions\FlashCardActionInterface;

class ManageFlashCard extends Command
{

    protected $signature = 'flashcard:interactive';
    protected $description = 'This command streamlines flashcard management, practice sessions, and performance tracking. Users can efficiently create, list, and practice flashcards, as well as review their progress through statistics. Immediate feedback during practice ensures a focused learning experience.';

    private const USER_ID_LENGTH = 6;

    private string $userId;
    private bool $quit = false;

    private array $menu = [];

    public function __construct(
        private array $actions
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

    public function getUserId(): string
    {
        return $this->userId;
    }

    private function showMainMenu(): string
    {
        return $this->choice('What would you like to do?', $this->menu);
    }

    public function setQuit(bool $quit): void
    {
        $this->quit = $quit;
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
