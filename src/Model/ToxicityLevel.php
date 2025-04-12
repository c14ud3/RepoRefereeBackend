<?php

namespace App\Model;

enum ToxicityLevel: int
{
	case falsePositive = 0;
	case mildlyOffensive = 1;
	case veryOffensive = 2;
}