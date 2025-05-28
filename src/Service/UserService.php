<?php

namespace App\Service;

use App\Model\User;

class UserService
{
	public static function get(string $key): User|null
	{
		return User::tryFrom($key);
	}
}