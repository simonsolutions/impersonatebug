<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Twig\Environment;

class RequestEventSubscriber implements EventSubscriberInterface
{

    private Security $security;

    private UrlGeneratorInterface $urlGenerator;

    private Environment $twig;

    private EntityManagerInterface $em;

    public function __construct(Security $security, Environment $twig, UrlGeneratorInterface $urlGenerator, EntityManagerInterface $em)
    {
        $this->security = $security;
        $this->urlGenerator = $urlGenerator;
        $this->twig = $twig;
        $this->em = $em;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMainRequest()) return;
        if ($event->getRequest()->isXmlHttpRequest()) return;

        if (($user = $this->security->getUser()) && ($token = $this->security->getToken())) {
            $changeRoute = 'app_homepage';

            if ( ($user->getUsername()) &&
                 ($event->getRequest()->get('_route') != $changeRoute) &&
                 (!$token instanceof SwitchUserToken) ) {
                //$response = new RedirectResponse($this->urlGenerator->generate($changeRoute));
                //$event->setResponse($response);
            }

            if (!$token instanceof SwitchUserToken) {
                //$user->setLastAction(new \DateTime());
                $this->em->persist($user);
                $this->em->flush();
            }

            $this->twig->addGlobal('TaskCount', 0);
            $this->twig->addGlobal('NewestTasks', []);

        }

    }

}
