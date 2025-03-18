<?php

namespace App\Model;

enum CommentsLogSource: string
{
	case BUGZILLA = 'BZ';
	case GITHUB = 'GH';
}