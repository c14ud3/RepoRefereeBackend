<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\RepoRefereeGPTService;
use OpenAI\Exceptions\TransporterException;
use Exception;

final class CommentController extends AbstractController
{
    #[Route('/comment', name: 'comment')]
    public function comment(RepoRefereeGPTService $gpt): Response
    {
		try
		{
			if(!isset($_REQUEST['comment']))
				return new Response('Attribute \'comment\' must be set.');

			$response = $gpt->request($_REQUEST['comment']);
			return new Response(json_encode($response), 200);
		}
		catch(TransporterException $e)
		{
			// check for Timeout
			if (str_contains($e->getMessage(), 'cURL error 28'))
				return new Response('The connection to ChatGPT timed out.', 408);
			return new Response($e->getMessage(), 500);
		}
		catch(Exception $e)
		{
			return new Response($e->getMessage(), 500);
		}

    }
}
