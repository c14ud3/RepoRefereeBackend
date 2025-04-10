<?php

namespace App\Model;

enum CommentsLogSource: string
{
	case BUGZILLA = 'BZ';
	case GITHUB_TEST = 'GH-TEST';
	case GITHUB_PDFJS = 'GH-PDFJS';
	case GITHUB_MAC = 'GH-MAC';
}