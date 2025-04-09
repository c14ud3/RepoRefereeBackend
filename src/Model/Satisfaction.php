<?php

namespace App\Model;

enum Satisfaction: int
{
	case VERY_DISSATISFIED = 1;
	case DISSATISFIED = 2;
	case NEUTRAL = 3;
	case SATISFIED = 4;
	case VERY_SATISFIED = 5;
}