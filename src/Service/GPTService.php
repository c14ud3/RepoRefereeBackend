<?php

namespace App\Service;

use ErrorException;
use OpenAI;

abstract class GPTService
{
	private string|null $api_key = null;
	private string|null $model = null;
	private float|null $temperature = null;
	private int|null $timeout = null;

	private function loadAPIKey(): void
	{
		$env = new ENVService();
		$env->loadEnv();

		$this->api_key = $_ENV['OPENAI_API_KEY'] ?? null;

		if($this->api_key === null)
			throw new ErrorException('GPT API key ("OPENAI_API_KEY") not found in .env file.');
	}

	private function loadModel(): void
	{
		$env = new ENVService();
		$env->loadEnv();

		$this->model = $_ENV['OPENAI_MODEL'] ?? null;

		if($this->model === null)
			throw new ErrorException('GPT API key ("OPENAI_MODEL") not found in .env file.');
	}

	private function loadTemperature(): void
	{
		$env = new ENVService();
		$env->loadEnv();

		$this->temperature = $_ENV['OPENAI_TEMPERATURE'] ?? null;

		if($this->temperature === null)
			throw new ErrorException('GPT API key ("OPENAI_TEMPERATURE") not found in .env file.');
	}

	private function loadTimeout(): void
	{
		$env = new ENVService();
		$env->loadEnv();

		$this->timeout = $_ENV['OPENAI_TIMEOUT'] ?? null;

		if($this->timeout === null)
			throw new ErrorException('GPT API key ("OPENAI_TIMEOUT") not found in .env file.');
	}

	protected function chatRequest(string $message): string
	{
		$this->loadAPIKey();
		$this->loadModel();
		$this->loadTemperature();
		$this->loadTimeout();
	
		$client = OpenAI::factory()
			->withApiKey($this->api_key)
			->withHttpClient(new \GuzzleHttp\Client(['timeout' => $this->timeout]))
			->make();

		$result = $client->chat()->create([
			'model' => $this->model,
			'temperature' => $this->temperature,
			'messages' => [
				[
					'role' => 'user',
					'content' => $message,
				],
			],
		]);

		return $result->choices[0]->message->content;
	}

	abstract protected function generatePrompt(string $comment): string;
	abstract protected function handleResponse(string $response): array;
}