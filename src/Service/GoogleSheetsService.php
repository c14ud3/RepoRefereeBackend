<?php

namespace App\Service;

use \Google_Client;
use \Google_Service_Sheets;

class GoogleSheetsService
{
	private Google_Client $client;
	private Google_Service_Sheets $service;

	function __construct()
	{
		// configure the Google Client
		$this->client = new Google_Client();
		$this->client->setApplicationName('Google Sheets API');
		$this->client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
		$this->client->setAccessType('offline');
		// credentials.json is the key file we downloaded while setting up our Google Sheets API
		$path = '../credentials.json';
		$this->client->setAuthConfig($path);

		// configure the Sheets Service
		$this->service = new Google_Service_Sheets($this->client);
	}

	public function newRow(array $data): void
	{
		// load env
		$env = new ENVService();
		$env->loadEnv();
		$spreadsheetId = $_ENV['GOOGLE_SPREADSHEET_ID'];

		$valueRange = new \Google_Service_Sheets_ValueRange();
		$valueRange->setValues([$data]);

		$range = 'Sheet1'; // the service will detect the last row of this sheet

		$options = ['valueInputOption' => 'USER_ENTERED'];
		
		$this->service->spreadsheets_values->append($spreadsheetId, $range, $valueRange, $options);
	}
}