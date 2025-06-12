This project is part of my Bacherlor's Thesis at the [University of Zurich](https://www.ifi.uzh.ch/en.html). The toxicity detection is based on [RepoReferee](https://gitlab.uzh.ch/szymon.kaczmarski/reporeferee-replication-package/-/tree/main/3_reporeferee_code).

# Installation
1. Clone the repository.
2. Install the dependencies using composer:
```console
$ composer install
```
3. Create a `.env` file by copying `.env.template` and fill out all requested environment variables.
4. Create the database by calling:
```console
$ php bin/console make:migration
$ php bin/console doctrine:migrations:migrate
```
5. Adapt the toxicity definitions to your needs in `src/Service/TOXICITY_DEFINITIONS.php`.
6. If you would like to only show comments starting from a specific timestamp in the moderation UI, please add it in `src/Controller/ModerationController.php` on line 85. If not, please comment the corresponding if-statement out, just like this:
```php
foreach($moderations as $moderation) {
    if($moderation->getComment()->getSource() == $sessionSource) {
        // if (
        //     $show_all_comments == 'true' ||
        //     $moderation->getComment()->getTimestamp() > new \DateTime('2025-05-20 00:00:00', new \DateTimeZone('Europe/Zurich'))
        // ) {
            $return[] = [
                'id' => $moderation->getId(),
                'toxicityLevel' => $moderation->getToxicityLevel(),
                'title' => $moderation->getComment()->getTitle(),
                'url' => $moderation->getComment()->getUrl(),
                'timestamp' => $moderation->getComment()->getTimestamp()->format('d.m.y H:i'),
            ];
        // }
    }
}
```