# Flashcard Manager

## Overview

The Flashcard Manager is a Laravel command-line tool designed to streamline flashcard management, practice sessions, and performance tracking.

## Features

- **Menu Navigation**: Easily navigate through various options using a numbered menu interface.
- **Flashcard Creation**: Create new flashcards for efficient learning.
- **Listing Cards**: View a list of all existing flashcards.
- **Practice Mode**: Engage in practice sessions to test knowledge.
- **Immediate Feedback**: Receive instant feedback on correctness during practice.
- **Performance Tracking**: Track performance and progress through statistics.
- **Reset Option**: Reset progress to start anew.
- **Quit Option**: Exit the command when done.

## Usage

1. Clone the repository to your local machine.
2. Run the Laravel command `php artisan your-command` to launch the Flashcard Manager.
3. Follow the on-screen instructions to navigate the menu, create flashcards, practice, view stats, reset progress, or quit.

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