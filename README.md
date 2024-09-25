# Flash Fun

## Overview

Flash Fun is a console tool designed to streamline flashcard management, practice sessions, and performance tracking.

## Features

- **Menu Navigation**: Easily navigate through various options using a numbered menu interface.
- **Flashcard Creation**: Create new flashcards for efficient learning.
- **Listing Cards**: View a list of all existing flashcards.
- **Practice Mode**: Engage in practice sessions to test knowledge.
- **Immediate Feedback**: Receive instant feedback on correctness during practice.
- **Performance Tracking**: Track performance and progress through statistics.
- **Reset Option**: Reset progress to start anew.

## Usage

1. Clone the repository to your local machine.
2. Follow [installation](#installation) steps.
3. Run the Laravel command `php artisan flashcard:interactive` to launch the Flashcard Manager.
4. Follow the on-screen instructions to navigate the menu, create flashcards, practice, view stats, reset progress, or quit.

## Requirements

- PHP >= 8.3
- Laravel >= 11

## Installation

1. Install all required packages:
    ```bash
        composer install
    ```

2. Migrate the database:
    ```bash
        php artisan migrate
    ```
