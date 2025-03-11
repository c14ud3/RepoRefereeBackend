<?php

namespace App\Controller;

use App\Service\AuthService;
use App\Service\CommentLogService;
use App\Service\GoogleSheetsService;
use App\Service\RepoRefereeGPTService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use OpenAI\Exceptions\TransporterException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CommentController extends AbstractController
{
    #[Route('/comment/{auth}', name: 'comment')]
    public function comment(
		RepoRefereeGPTService $gpt,
		EntityManagerInterface $em,
		$auth
	): Response
    {
		// * Check authentication
		if (!AuthService::check($auth))
			return new Response('Unauthorized', 401);

		try
		{
			// * Check for required attributes
			if(!isset($_REQUEST['url']) || empty($_REQUEST['url']))
				return new Response('Attribute \'url\' (STRING) must be set.');

			if(!isset($_REQUEST['title']) || empty($_REQUEST['title']))
				return new Response('Attribute \'title\' (STRING) must be set.');

			if(!isset($_REQUEST['comment']) || empty($_REQUEST['comment']))
				return new Response('Attribute \'comment\' (STRING) must be set.');

			try
			{
				if(!isset($_REQUEST['contextComments'])) throw new Exception();
				$contextComments = json_decode($_REQUEST['contextComments'], true);
				if(!is_array($contextComments)) throw new Exception();
			}
			catch(Exception $e)
			{
				return new Response('Attribute \'contextComments\' (JSON-ARRAY) must be set.');
			}

			// * Request to ChatGPT
			$response = $gpt->request(
				strval($_REQUEST['title']),
				strval($_REQUEST['comment']),
				$contextComments
			);

			// * Log the request
			$commentLog = new CommentLogService();
			$commentLog->log(
				$em,
				$_REQUEST['url'] ?? '',
				$_REQUEST['title'] ?? '',
				$_REQUEST['comment'] ?? '',
				$contextComments ?? [],
				$response['TEXT_TOXICITY'] ?? false,
				$response['TOXICITY_REASONS'] ?? '',
				$response['VIOLATED_GUIDELINE'] ?? '',
				$response['REPHRASED_TEXT_OPTIONS'] ?? []
			);

			// * If toxic: add Comment & Response to Google Sheets
			if($response['TEXT_TOXICITY'] ?? false)
			{
				try
				{
					$googleSheetsService = new GoogleSheetsService();
					$googleSheetsService->newRow([
						$_REQUEST['url'] ?? '',
						$_REQUEST['comment'] ?? '',
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
