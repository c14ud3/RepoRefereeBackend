This project is part of my Bacherlor's Thesis at the [University of Zurich](https://www.ifi.uzh.ch/en.html). The toxicity detection is based on [RepoReferee](https://gitlab.uzh.ch/szymon.kaczmarski/reporeferee-replication-package/-/tree/main/3_reporeferee_code).

# Installation
1. Clone the repository.
2. Install the dependencies using composer:
```console
$ composer install
```
3. Create a `.env` file by copying `.env.template` and fill out all requested environment variables.
4. Create a [service account for Google Sheets](https://www.nidup.io/blog/manipulate-google-sheets-in-php-with-api#create-a-google-project-and-configure-sheets-api) including a JSON-Key and upload this key to the root directory: `/credentials.json`. (Link also found [here](docs/Screenshot_Google_Sheets_API.png))
5. Add the above created service account to your Google Spreadsheet (as a contributor).
6. Add the following cell titles to the defined Google Sheets: "Link", "Comment", "Toxicity explanation", "Guidelines reference" and "Rephrasing options".
7. Create the database by calling:
```console
$ php bin/console make:migration
$ php bin/console doctrine:migrations:migrate
```
8. Adapt the toxicity definitions to your needs in `src/Service/TOXICITY_DEFINITIONS.php`.