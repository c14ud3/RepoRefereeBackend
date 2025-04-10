<?php

namespace App\Service;

use App\Model\CommentsLogSource;

class AuthService
{
	public static function checker(string $key): bool
	{
		$env = new ENVService();
		$env->loadEnv();

		$keys = explode(';', $_ENV['CHECKER_AUTH_KEYS'] ?? '');

		return in_array($key, $keys);
	}

	public static function moderation(string $key): bool
	{
		$env = new ENVService();
		$env->loadEnv();

		$keys = explode(';', $_ENV['MODERATION_AUTH_KEYS'] ?? '');

		$commentsLogSource = CommentsLogSource::tryFrom(strtoupper(explode(':', $key)[0]));

		return in_array($key, $keys) && !is_null($commentsLogSource);
	}
}