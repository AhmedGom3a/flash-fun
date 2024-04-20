<?php

namespace App\Providers;

use App\Repos\PracticeRepository;
use App\Repos\FlashCardRepositroy;
use Illuminate\Support\ServiceProvider;
use App\Console\Commands\ManageFlashCard;

class FlashCardActionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('FlashCard\Action\CardActions', function ($app) {

            $actions = [];
            foreach (glob(app_path('Console/Commands/FlashCardActions/*.php')) as $actionFile) {
                
                if (
                    basename($actionFile) === 'FlashCardActionInterface.php'
                    || basename($actionFile) === 'AbstractAction.php'
                ) {
                    continue;
                }
                
                $className = 'App\\Console\\Commands\\FlashCardActions\\' . basename($actionFile, '.php');
                $actions[$className::getActionName()] = $app->make($className);
            }
            
            return $actions;
        });

        $this->app->bind('App\Console\Commands\ManageFlashCard', function ($app) {
            return new ManageFlashCard(
                $app->make('FlashCard\Action\CardActions'),
                $app->make(FlashCardRepositroy::class),
                $app->make(PracticeRepository::class)
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
