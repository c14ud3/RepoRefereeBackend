<?php

namespace App\Model;

enum CommentsLogSource: string
{
	case BUGZILLA = 'bugzilla';
	case GITHUB = 'github';
}