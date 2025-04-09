<?php

namespace App\Service;

abstract class ModerationService
{
	public static function buildResponseText(string $toxicityReasons, string $violatedGuideline, string $rephrasingOption): string
	{
		$return = 'Hi, your input was identified as toxic. ';
		$return .= $toxicityReasons;
		$return .= PHP_EOL . PHP_EOL;

		$return .= $violatedGuideline;
		$return .= PHP_EOL . PHP_EOL;

		$return .= 'Here is a possible rephrasing option:';
		$return .= PHP_EOL;
		$return .= $rephrasingOption;

		return $return;
	}
}