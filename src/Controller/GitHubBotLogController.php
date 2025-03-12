<?php

namespace App\Controller;

use App\Model\CommentsLogSource;
use App\Service\AuthService;
use App\Service\CommentLogService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PHPUnit\Framework\Error\Warning;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GitHubBotLogController extends AbstractController
{
    #[Route('/github/log/{auth}', name: 'app_git_hub_bot_log')]
    public function log(
		EntityManagerInterface $em,
		$auth
	): Response
    {
        // * Check authentication
		if (!AuthService::logger($auth))
			return new Response('Unauthorized', 401);

		// * Try to log the data
		try
		{
			// * Load request data
			$REQUESTDATA = json_decode(file_get_contents('php://input') ?? '{}', true) ?? [];

			// * Check for required attributes
			if(!isset($REQUESTDATA['url']) || empty($REQUESTDATA['url']))
				return new Response('Attribute \'url\' (STRING) must be set.', 400);

			if(!isset($REQUESTDATA['comment']) || empty($REQUESTDATA['comment']))
				return new Response('Attribute \'comment\' (STRING) must be set.', 400);

			if(!isset($REQUESTDATA['isToxic']) || !is_bool($REQUESTDATA['isToxic']))
				return new Response('Attribute \'isToxic\' (BOOL) must be set.', 400);

			if(!isset($REQUESTDATA['toxicityReasons']) || empty($REQUESTDATA['toxicityReasons']))
				return new Response('Attribute \'toxicityReasons\' (STRING) must be set.', 400);

			if(!isset($REQUESTDATA['violatedGuideline']) || empty($REQUESTDATA['violatedGuideline']))
				return new Response('Attribute \'violatedGuideline\' (STRING) must be set.', 400);

			if(!isset($REQUESTDATA['rephrasedTextOptions']) || !is_array($REQUESTDATA['rephrasedTextOptions']) ||
				empty($REQUESTDATA['rephrasedTextOptions']))
				return new Response('Attribute \'rephrasedTextOptions\' (ARRAY) must be set and cannot be empty.', 400);

			// * Log the request
			$commentLog = new CommentLogService();
			$commentLog->log(
				$em,
				$REQUESTDATA['url'] ?? '',
				'',
				$REQUESTDATA['comment'] ?? '',
				[],
				$REQUESTDATA['isToxic'] ?? false,
				$REQUESTDATA['toxicityReasons'] ?? '',
				$REQUESTDATA['violatedGuideline'] ?? '',
				$REQUESTDATA['rephrasedTextOptions'] ?? [],
				CommentsLogSource::GITHUB
			);
		}
		catch(Exception $e)
		{
			// * If something went wrong: return error message
			return new Response($e->getMessage(), 500);
		}

		// * If everything fine: empty response
		return new Response('Logged successfully.');
    }
}
