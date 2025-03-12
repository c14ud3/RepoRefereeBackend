<?php

namespace App\Service;

class AuthService
{
	public static function checker(string $key): bool
	{
		$env = new ENVService();
		$env->loadEnv();

		$keys = explode(';', $_ENV['CHECKER_AUTH_KEYS'] ?? '');

		return in_array($key, $keys);
	}

	public static function logger(string $key): bool
	{
		$env = new ENVService();
		$env->loadEnv();

		$keys = explode(';', $_ENV['LOGGER_AUTH_KEYS'] ?? '');

		return in_array($key, $keys);
	}
}