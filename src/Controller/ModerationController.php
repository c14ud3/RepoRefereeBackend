<?php

namespace App\Controller;

use App\Entity\Moderation;
use App\Model\CommentsLogSource;
use App\Model\Satisfaction;
use App\Model\TimeSelector;
use App\Model\ToxicityLevel;
use App\Service\AuthService;
use App\Service\ModerationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ModerationController extends AbstractController
{
    #[Route('/moderation', name: 'app_moderation_without_auth')]
    public function indexWithoutAuth(): Response
    {
        return $this->redirectToRoute('app_moderation_comments', ['auth' => '-']);
    }

	#[Route('/moderation/{auth}', name: 'app_moderation')]
    public function index($auth): Response
    {
        return $this->redirectToRoute('app_moderation_comments', ['auth' => $auth]);
    }

	#[Route('/moderation/{auth}/comments', name: 'app_moderation_comments')]
    public function comments($auth): Response
    {
        // * Check authentication
		if (!AuthService::moderation($auth))
			return new Response('Unauthorized', 401);
		
        return $this->render('moderation/comments.html.twig', [
			'auth' => $auth,
        ]);
    }

	#[Route('/moderation/{auth}/api/comments/{params}', name: 'api_moderation_comments')]
    public function commentsApi(EntityManagerInterface $em, $auth, $params): Response
    {
		// * Check authentication
		if (!AuthService::moderation($auth))
			return new Response('Unauthorized', 401);
		
        // separate params
		list($param_filter, $param_order) = explode('-', $params);

		$criteria = [];
		if($param_filter == 'open') $criteria = ['ToxicityLevel' => null];
		else if(in_array($param_filter, [0, 1, 2]))
			$criteria = ['ToxicityLevel' => ToxicityLevel::from(intval($param_filter))];

		$moderations = $em->getRepository(Moderation::class)->findBy(
			$criteria,
			['id' => $param_order == 'newest' ? 'DESC' : 'ASC'],
		);

		$return = [];

		$sessionSource = CommentsLogSource::from(strtoupper(explode(':', $auth)[0]));

		foreach($moderations as $moderation) {
			if($moderation->getComment()->getSource() == $sessionSource) {
				$return[] = [
					'id' => $moderation->getId(),
					'toxicityLevel' => $moderation->getToxicityLevel(),
					'title' => $moderation->getComment()->getTitle(),
					'url' => $moderation->getComment()->getUrl(),
					'timestamp' => $moderation->getComment()->getTimestamp()->format('d.m.y H:i'),
				];
			}
		}

		return new Response(json_encode($return));
    }

	#[Route('/moderation/{auth}/comment/{comment_id}', name: 'app_moderation_comment')]
    public function comment(EntityManagerInterface $em, $auth, $comment_id): Response
    {
		// * Check authentication
		if (!AuthService::moderation($auth))
			return new Response('Unauthorized', 401);

        $moderation = $em->getRepository(Moderation::class)->find($comment_id);

		if($moderation->getComment()->getSource() != CommentsLogSource::from(strtoupper(explode(':', $auth)[0]))) {
			return new Response('Unauthorized', 401);
		}

		$return_moderation = [];
		$return_comment = [];

		if ($moderation != null) {
			$comment = $moderation->getComment();

			$return_moderation = [
				'id' => $moderation->getId(),
				'toxicityLevel' => $moderation->getToxicityLevel(),
				'timeUsed' => $moderation->getTimeUsed(),
				'satisfactionToxicityExplanation' => $moderation->getSatisfactionToxicityExplanation(),
				'satisfactionGuidelinesReference' => $moderation->getSatisfactionGuidelinesReference(),
				'satisfactionRephrasingOptions' => $moderation->getSatisfactionRephrasingOptions(),
				'remarks' => $moderation->getRemarks(),
				'timestamp' => $moderation->getTimestamp()->format('Y-m-d H:i:s'),
			];

			$return_comment = [
				'url' => $comment->getUrl(),
				'title' => $comment->getTitle(),
				'comment' => $comment->getComment(),
				'contextComments' => json_decode($comment->getContextComments()),
				'toxicityReasons' => $comment->getToxicityReasons(),
				'violatedGuideline' => $comment->getViolatedGuideline(),
				'rephrasedTextOptions' => json_decode($comment->getRephrasedTextOptions()),
			];

			$strings_to_reply = [];
			foreach(json_decode($comment->getRephrasedTextOptions()) as $rephrased_text_option) {
				$strings_to_reply[] = ModerationService::buildResponseText(
					$comment->getToxicityReasons(),
					$comment->getViolatedGuideline(),
					$rephrased_text_option
				);
			}
		}
		
		return $this->render('moderation/detail.html.twig', [
			'auth' => $auth,
			'comment_found' => $moderation != null,
			'moderation' => $return_moderation,
			'comment' => $return_comment,
			'strings_to_reply' => $strings_to_reply,
        ]);
    }

	#[Route('/moderation/{auth}/api/comment/{moderation_id}', name: 'api_moderation_comment')]
    public function commentApi(EntityManagerInterface $em, $auth, $moderation_id): Response
    {
		// * Check authentication
		if (!AuthService::moderation($auth))
			return new Response('Unauthorized', 401);

        $moderation = $em->getRepository(Moderation::class)->find($moderation_id);

		if($moderation->getComment()->getSource() != CommentsLogSource::from(strtoupper(explode(':', $auth)[0]))) {
			return new Response('Unauthorized', 401);
		}

		$moderation->setToxicityLevel(ToxicityLevel::from($_POST['toxicityLevel']));
		$moderation->setTimeUsed(TimeSelector::from($_POST['timeUsed']));
		$moderation->setSatisfactionToxicityExplanation(Satisfaction::from($_POST['satisfactionToxicityExplanation']));
		$moderation->setSatisfactionGuidelinesReference(Satisfaction::from($_POST['satisfactionGuidelinesReference']));
		$moderation->setSatisfactionRephrasingOptions(Satisfaction::from($_POST['satisfactionRephrasingOptions']));
		$moderation->setRemarks($_POST['remarks']);
		$moderation->setTimestamp(new \DateTime('now', new \DateTimeZone('Europe/Zurich')));
		
		$em->persist($moderation);
		$em->flush();

		return new Response();
    }
}
