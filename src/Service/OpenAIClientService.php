<?php

namespace App\Service;

use ErrorException;
use OpenAI;

abstract class OpenAIClientService
{
	private string|null $api_key = null;
	private string|null $model = null;
	private float|null $temperature = null;
	private int|null $timeout = null;
	private string|null $operator = null;
	private string|null $apiDescription = null;
	protected PromptService|null $promptService = null;
	protected string|null $baseUri = null;

	function __construct() {
		$this->promptService = new PromptService();
	}

	private function loadAPIKey(): void
	{
		$env = new ENVService();
		$env->loadEnv();

		$this->api_key = $_ENV['' . $this->operator . '_API_KEY'] ?? null;

		if($this->api_key === null)
			throw new ErrorException($this->apiDescription . ' key ("' . $this->operator . '_API_KEY") not found in .env file.');
	}

	private function loadModel(): void
	{
		$env = new ENVService();
		$env->loadEnv();

		$this->model = $_ENV['' . $this->operator . '_MODEL'] ?? null;

		if($this->model === null)
			throw new ErrorException($this->apiDescription . ' model ("' . $this->operator . '_MODEL") not found in .env file.');
	}

	private function loadTemperature(): void
	{
		$env = new ENVService();
		$env->loadEnv();

		$this->temperature = $_ENV['' . $this->operator . '_TEMPERATURE'] ?? null;

		if($this->temperature === null)
			throw new ErrorException($this->apiDescription . ' temperature ("' . $this->operator . '_TEMPERATURE") not found in .env file.');
	}

	private function loadTimeout(): void
	{
		$env = new ENVService();
		$env->loadEnv();

		$this->timeout = $_ENV['' . $this->operator . '_TIMEOUT'] ?? null;

		if($this->timeout === null)
			throw new ErrorException($this->apiDescription . ' timeout ("' . $this->operator . '_TIMEOUT") not found in .env file.');
	}

	protected function chatRequest(string $message): string
	{
		$this->loadAPIKey();
		$this->loadModel();
		$this->loadTemperature();
		$this->loadTimeout();
	
		$factory = OpenAI::factory()
			->withApiKey($this->api_key)
			->withHttpClient(new \GuzzleHttp\Client(['timeout' => $this->timeout]));

		if($this->baseUri !== null)
			$factory = $factory->withBaseUri($this->baseUri);

		$client = $factory->make();

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

	protected function setModelOperator(string $opreator, string $apiDescription): void {
		$this->operator = $opreator;
		$this->apiDescription = $apiDescription;
	}

	protected function setBaseUri(string $baseUri): void {
		$this->baseUri = $baseUri;
	}
}