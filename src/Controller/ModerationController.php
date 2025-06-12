<?php

namespace App\Controller;

use App\Entity\Moderation;
use App\Model\CommentsLogSource;
use App\Model\Satisfaction;
use App\Model\TimeSelector;
use App\Model\ToxicityLevel;
use App\Service\AuthService;
use App\Service\ModerationService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ModerationController extends AbstractController
{
    #[Route('/moderation', name: 'app_moderation_without_auth')]
    public function indexWithoutAuth(): Response
    {
        return $this->redirectToRoute('app_moderation_comments', ['auth' => '-', 'user' => '-']);
    }

	#[Route('/moderation/{auth}/{user}', name: 'app_moderation')]
    public function index($auth, $user): Response
    {
        return $this->redirectToRoute('app_moderation_comments', ['auth' => $auth, 'user' => $user]);
    }

	#[Route('/moderation/{auth}/{user}/comments', name: 'app_moderation_comments')]
    public function comments($auth, $user): Response
    {
        // * Check authentication
		if (!AuthService::moderation($auth))
			return new Response('Unauthorized', 401);

		$user = UserService::get($user);
		if($user === null) return new Response('Unauthorized', 401);
		
        return $this->render('moderation/comments.html.twig', [
			'auth' => $auth,
			'user' => $user->value,
        ]);
    }

	#[Route('/moderation/{auth}/{user}/api/comments/{params}', name: 'api_moderation_comments')]
    public function commentsApi(EntityManagerInterface $em, $auth, $user, $params): Response
    {
		// * Check authentication
		if (!AuthService::moderation($auth))
			return new Response('Unauthorized', 401);

		$user = UserService::get($user);
		if($user === null) return new Response('Unauthorized', 401);
		
        // separate params
		list($param_filter, $param_order, $show_all_comments) = explode('-', $params);

		$criteria = ['User' => $user];
		if($param_filter == 'open') $criteria = ['ToxicityLevel' => null];
		else if(in_array($param_filter, [0, 1, 2]))
			$criteria = ['ToxicityLevel' => ToxicityLevel::from(intval($param_filter))];

		$moderations = $em->getRepository(Moderation::class)->findBy($criteria);

		// sort the moderations according to the order parameter
		usort($moderations, function($a, $b) use ($param_order) {
			if ($param_order == 'newest') {
				return $b->getComment()->getTimestamp() <=> $a->getComment()->getTimestamp();
			} else {
				return $a->getComment()->getTimestamp() <=> $b->getComment()->getTimestamp();
			}
		});

		$return = [];

		$sessionSource = CommentsLogSource::from(strtoupper(explode(':', $auth)[0]));

		foreach($moderations as $moderation) {
			if($moderation->getComment()->getSource() == $sessionSource) {
				if (
					$show_all_comments == 'true' ||
					$moderation->getComment()->getTimestamp() > new \DateTime('2025-05-20 00:00:00', new \DateTimeZone('Europe/Zurich'))
				) {
					$return[] = [
						'id' => $moderation->getId(),
						'toxicityLevel' => $moderation->getToxicityLevel(),
						'title' => $moderation->getComment()->getTitle(),
						'url' => $moderation->getComment()->getUrl(),
						'timestamp' => $moderation->getComment()->getTimestamp()->format('d.m.y H:i'),
					];
				}
			}
		}

		return new Response(json_encode($return));
    }

	#[Route('/moderation/{auth}/{user}/comment/{comment_id}', name: 'app_moderation_comment')]
    public function comment(EntityManagerInterface $em, $auth, $user, $comment_id): Response
    {
		// * Check authentication
		if (!AuthService::moderation($auth))
			return new Response('Unauthorized', 401);

		$user = UserService::get($user);
		if($user === null) return new Response('Unauthorized', 401);

        $moderation = $em->getRepository(Moderation::class)->findBy([
			'id' => $comment_id,
			'User' => $user
		]);

		if(count($moderation) != 1) return new Response('Comment not found.', 404);
		$moderation = $moderation[0];

		if($moderation->getComment()->getSource() != CommentsLogSource::from(strtoupper(explode(':', $auth)[0])))
			return new Response('Unauthorized', 401);

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
			'user' => $user->value,
			'comment_found' => $moderation != null,
			'moderation' => $return_moderation,
			'comment' => $return_comment,
			'strings_to_reply' => $strings_to_reply,
        ]);
    }

	#[Route('/moderation/{auth}/{user}/api/comment/{moderation_id}', name: 'api_moderation_comment')]
    public function commentApi(EntityManagerInterface $em, $auth, $user, $moderation_id): Response
    {
		// * Check authentication
		if (!AuthService::moderation($auth))
			return new Response('Unauthorized', 401);

		$user = UserService::get($user);
		if($user === null) return new Response('Unauthorized', 401);

        $moderation = $em->getRepository(Moderation::class)->findBy([
			'id' => $moderation_id,
			'User' => $user
		]);

		if(count($moderation) != 1) return new Response('Comment not found.', 404);
		$moderation = $moderation[0];

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
