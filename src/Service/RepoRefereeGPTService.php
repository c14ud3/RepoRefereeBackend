<?php

namespace App\Service;
use ErrorException;

class RepoRefereeGPTService extends GPTService
{
	
	public function request(string $title, string $message, array $context): array
	{
		if (empty($message))
			throw new ErrorException('Message contents cannot be empty.');
		
		$messageWithContext = $this->generateContext($title, $message, $context);
		$prompt = $this->generatePrompt($messageWithContext);
		$response = $this->chatRequest($prompt);
		$handledResponse = $this->handleResponse($response);
		$returnArray = $this->buildReturnArray($handledResponse);
		return $returnArray;
	}

	protected function chatRequest(string $message): string
	{
		$resultContent = parent::chatRequest($message);
		$resultContent = str_replace([
			'``` json\n\n\n\n', '```json\n\n\n\n', '``` json\n\n\n', '```json\n\n\n', '``` json\n\n', '```json\n\n'
			, '``` json\n', '```json\n', '``` json', '```json', '```',
		], '', $resultContent);
		return $resultContent;
	}

	private function generateContext(string $title, string $message, array $context): string
	{
		$messageWithContext = '';

		// Title
		if(!empty($title))
			$messageWithContext .= 'Title: \'\'\'' . $title . '\'\'\'\n';

		// Context
		if(count($context) > 0)
		{
			$messageWithContext .= 'Comments before the TARGET comment:\n';
			foreach(array_values($context) as $key => $contextComment)
				$messageWithContext .= '- Comment nr. ' . $key . ': \'\'\'' . $contextComment . '\'\'\'\n\n';
		}

		// Target comment
		$messageWithContext .= 'TARGET comment: \'\'\'' . $this->deleteReplies($message) . '\'\'\'\n';

		return $messageWithContext;
	}

	protected function generatePrompt(string $messageWithContext): string
	{
		// Toxicity definition
		$prompt = TOXICITY_DEFINITIONS::TOXICITY_DEFINITION . '\n' .
			'Sub-concepts of toxicity are defined below:\n';

		// Sub toxicity definition
		foreach (TOXICITY_DEFINITIONS::TOXICITY_TYPES as $toxicityType => $toxicityDescription) {
			$prompt .= ' - ' . $toxicityDescription . ': ' . TOXICITY_DEFINITIONS::PROMPT_COMMENTS[$toxicityType][0] . '. ' .
				'Examples of ' . $toxicityDescription . ': ';
			$prompt .= '"' . TOXICITY_DEFINITIONS::PROMPT_COMMENTS[$toxicityType][1] . '", ';
			$prompt .= '"' . TOXICITY_DEFINITIONS::PROMPT_COMMENTS[$toxicityType][2] . '", ';
			$prompt .= '"' . TOXICITY_DEFINITIONS::PROMPT_COMMENTS[$toxicityType][3] . '"\n';
		}
		$prompt .= '\n';

		// Comment
		$prompt .= 'Based on the provided toxicity definition and context analyze the text and decide whether this TARGET text is toxic:\n' .
			$messageWithContext . '\n\n';

		# GUIDELINES
		$prompt .= 'Additionally, these are Community Participation Guidelines:\n' .
			'\'\'\'' . TOXICITY_DEFINITIONS::GUIDELINES . '\'\'\'\n\n';
		$prompt .= 'If the comment is toxic, explain why the text is considered toxic, referencing the specific sub-concept definition, indicate which specific guideline from the Community Participation Guidelines was violated and provide three rephrased versions of the text that maintain the original intent but without toxicity or any negative tone. Try to make the rephrased versions as nice and firendly as possible.\n';

		# ANSWER FORMAT
		$prompt .= 'Structure your answer in the following JSON format:\n';
		$prompt .= '{"TEXT_TOXICITY": [true/false], ';
		$prompt .= '"TOXICITY_REASONS": "[Short explanation based on the definitions provided, citing specific sub-concepts]", ';
		$prompt .= '"VIOLATED_GUIDELINE": "[Short explanation of the specific guideline broken]", ';
		$prompt .= '"REPHRASED_TEXT_OPTIONS": ["Option 1", "Option 2", "Option 3"]}';

		return $prompt;
	}

	private function deleteReplies(string $comment): string
	{
		$lines = explode("\n", $comment);
		$filteredLines = array_filter($lines, function ($line): bool {
			return !str_starts_with($line, '>');
		});
		return implode("\n", $filteredLines);
	}

	protected function handleResponse(string $response): array
	{
		try {
			$arrayResponse = json_decode($response, true);
		} catch (\JsonException $e) {
			throw new ErrorException('Received invalid response from OpenAI.');
		}

		$responseKeys = array_keys($arrayResponse);
		if (
			!in_array('TEXT_TOXICITY', $responseKeys) ||
			!in_array('TOXICITY_REASONS', $responseKeys) ||
			!in_array('VIOLATED_GUIDELINE', $responseKeys) ||
			!in_array('REPHRASED_TEXT_OPTIONS', $responseKeys)
		) {
			throw new ErrorException('Received invalid response from OpenAI.');
		}

		if(!is_bool($arrayResponse['TEXT_TOXICITY']))
			throw new ErrorException('TEXT_TOXICITY must be a boolean.');

		return $arrayResponse;
	}

	private function buildReturnArray(array $response)
	{
		return [
			'TEXT_TOXICITY' => $response['TEXT_TOXICITY'],
			'TOXICITY_REASONS' => $response['TOXICITY_REASONS'],
			'VIOLATED_GUIDELINE' => $response['VIOLATED_GUIDELINE'],
			'REPHRASED_TEXT_OPTIONS' => $response['REPHRASED_TEXT_OPTIONS'],
		];
	}
}