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