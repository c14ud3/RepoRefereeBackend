<?php

namespace App\Model;

enum TimeSelector: string
{
	case lessThan1Minute = '<1m';
	case from1MinuteTo5Minutes = '1m-5m';
	case from5MinutesTo30Minutes = '5m-30m';
	case from30MinutesTo1Hour = '30m-1h';
	case from1HourTo4Hours = '1h-4h';
	case moreThan4Hours = '>4h';
}