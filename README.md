## Moorsp Plagiarism Plugin for Moodle

Moorsp - A really simple Plagiarism tool for Moodle.
Author: Dan Marsden <dan@danmarsden.com> & Ramindu Deshapriya <ramindu@rdeshapriya.com>
Copyright 2015 Dan Marsden http://danmarsden.com & Ramindu Deshapriya http://rdeshapriya.com

### Quick Install

1. Place these files in a new folder in your Moodle install under /plagiarism/moorsp
2. Visit the Notifications page in Moodle to trigger the upgrade scripts
3. Enable the Plagiarism API under admin > Advanced Features
4. Configure the Moorsp plugin under admin > plugins > Plagiarism > Moorsp

### Running Tests

#### PHPUnit Tests

1. Follow the instructions to set up PHPUnit tests for Moodle at https://docs.moodle.org/dev/PHPUnit
2. Run Moorsp PHPUnit Tests using `vendor/bin/phpunit -c plagiarism/moorsp`

#### Behat Tests

1. Follow the instructions to set up Behat tests for Moodle at https://docs.moodle.org/dev/Acceptance_testing
2. Run Moorsp Behat tests with the `--tags="@plagiarism_moorsp"` tag enabled.
