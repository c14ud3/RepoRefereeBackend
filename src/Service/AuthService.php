<?php

namespace App\Service;

class AuthService
{
	public static function check(string $key): bool
	{
		$env = new ENVService();
		$env->loadEnv();

		$keys = explode(';', $_ENV['CHECKER_AUTH_KEYS'] ?? '');

		return in_array($key, $keys);
	}
}