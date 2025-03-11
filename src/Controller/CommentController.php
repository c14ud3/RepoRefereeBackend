<?php

namespace App\Controller;

use App\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\RepoRefereeGPTService;
use OpenAI\Exceptions\TransporterException;
use Exception;
use App\Service\GoogleSheetsService;

final class CommentController extends AbstractController
{
    #[Route('/comment/{auth}', name: 'comment')]
    public function comment(RepoRefereeGPTService $gpt, $auth): Response
    {
		if (!AuthService::check($auth))
			return new Response('Unauthorized', 401);

		try
		{
			if(!isset($_POST['url']) || empty($_POST['url']))
				return new Response('Attribute \'url\' (STRING) must be set.');

			if(!isset($_POST['title']) || empty($_POST['title']))
				return new Response('Attribute \'title\' (STRING) must be set.');

			if(!isset($_POST['comment']) || empty($_POST['comment']))
				return new Response('Attribute \'comment\' (STRING) must be set.');

			try
			{
				if(!isset($_POST['contextComments'])) throw new Exception();
				$contextComments = json_decode($_POST['contextComments'], true);
				if(!is_array($contextComments)) throw new Exception();
			}
			catch(Exception $e)
			{
				return new Response('Attribute \'contextComments\' (JSON-ARRAY) must be set.');
			}

			$response = $gpt->request(
				strval($_POST['title']),
				strval($_POST['comment']),
				$contextComments
			);

			// Save to Google Sheets
			if($response['TEXT_TOXICITY'] ?? false)
			{
				try
				{
					$googleSheetsService = new GoogleSheetsService();
					$googleSheetsService->newRow([
						$_POST['url'] ?? '',
						$_POST['comment'] ?? '',
						$response['TOXICITY_REASONS'] ?? '',
						$response['VIOLATED_GUIDELINE'] ?? '',
						implode(PHP_EOL, ($response['REPHRASED_TEXT_OPTIONS'] ?? [])),
					]);
				}
				catch(Exception $e)
				{
					return new Response('Error with Google Sheets API: ' . $e->getMessage(), 500);
				}
			}

			return new Response(json_encode($response), 200);
		}
		catch(TransporterException $e)
		{
			// check for Timeout
			if (str_contains($e->getMessage(), 'cURL error 28'))
				return new Response('The request to ChatGPT took too long.', 408);
			return new Response($e->getMessage(), 500);
		}
		catch(Exception $e)
		{
			return new Response($e->getMessage(), 500);
		}

    }
}
