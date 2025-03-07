<?php

namespace App\Service;

use Symfony\Component\Dotenv\Dotenv;

class ENVService
{
	public function loadEnv(): void
	{
		$dotenv = new Dotenv();
		$dotenv->load(__DIR__.'/../../.env');
	}
}