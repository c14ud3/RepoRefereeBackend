<?php

namespace App\Service;

class RepoRefereeGroqService extends OpenAIClientService
{
	
	public function request(string $title, string $message, array $context): array
	{
		$this->setModelOperator('GROQ', 'Groq');
		$this->setBaseUri('https://api.groq.com/openai/v1');

		$messageWithContext = $this->promptService->generateContext($title, $message, $context);
		$prompt = $this->promptService->generatePrompt($messageWithContext);
		$response = $this->chatRequest($prompt);
		$parsedResponse = $this->promptService->parseResponse($response);
		$handledResponse = $this->promptService->handleResponse($parsedResponse);
		$returnArray = $this->promptService->buildReturnArray($handledResponse);
		return $returnArray;
	}
}