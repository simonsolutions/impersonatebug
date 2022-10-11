<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class TwigEventSubscriber implements EventSubscriberInterface
{

    private UrlGeneratorInterface $urlGenerator;

    private Environment $twig;

    private KernelInterface $appKernel;

    private Security $security;

    private AuthorizationCheckerInterface $authorizationChecker;

    private string $requestPath;

    private string $routeName;

    private array $routeParameters;

    public function __construct(UrlGeneratorInterface $urlGenerator,
                                Environment $twig,
                                KernelInterface $appKernel,
                                Security $security,
                                AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->urlGenerator = $urlGenerator;
        $this->appKernel = $appKernel;
        $this->twig = $twig;
        $this->security = $security;
        $this->authorizationChecker = $authorizationChecker;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.controller' => 'onKernelController',
        ];
    }

    public function onKernelController(ControllerEvent $event)
    {
        if (!$event->isMainRequest()) return;

        $this->twig->addGlobal('userCurrent', $this->security->getUser());
        $token = $this->security->getToken();
        if ($token instanceof SwitchUserToken) {
            $this->twig->addGlobal('userImpersonator', $token->getOriginalToken()->getUser());
        }

        $this->requestPath = $event->getRequest()->getPathInfo();
        $this->routeName = $event->getRequest()->attributes->get('_route');
        $this->routeParameters = $event->getRequest()->attributes->get('_route_params');
    }

    private function checkActiveState(array &$menuItems, array &$breadcrumbs): bool
    {
        $hadActive = false;
        foreach ($menuItems as &$menuItem) {
            if ($menuItem['RouteName'] == $this->routeName) {
                $hadActive = true;
                $menuItem['IsActive'] = true;
                $menuItem['IsVisible'] = true;
                $menuItem['RouteParams'] = $this->routeParameters;
                if (isset($menuItem['Submenu']) and is_array($menuItem['Submenu']) and count($menuItem['Submenu']) == 0) {
                    $breadcrumbs[] = $menuItem;
                }
            }
            if (isset($menuItem['Submenu']) and is_array($menuItem['Submenu']) and count($menuItem['Submenu']) > 0) {
                $tmpHadActive = $this->checkActiveState($menuItem['Submenu'], $breadcrumbs);
                if ($tmpHadActive) {
                    $menuItem['IsActive'] = true;
                    $menuItem['IsVisible'] = true;
                    $breadcrumbs[] = $menuItem;
                }
                if (!$hadActive) {
                    $hadActive = $tmpHadActive;
                }
            }
        }
        return $hadActive;
    }

    private function setArrayDefaults(array $menuItems): array
    {
        $finalItems = array();
        foreach ($menuItems as &$menuItem) {

            if (!isset($menuItem['Caption'])) $menuItem['Caption'] = '';
            if (!isset($menuItem['IconClass'])) $menuItem['IconClass'] = '';
            if (!isset($menuItem['IsActive'])) $menuItem['IsActive'] = false;
            if (!isset($menuItem['IsVisible'])) $menuItem['IsVisible'] = true;
            if (!isset($menuItem['IsServiceMenu'])) $menuItem['IsServiceMenu'] = false;
            if (!isset($menuItem['IsHeader'])) $menuItem['IsHeader'] = false;
            if (!isset($menuItem['HasBadge'])) $menuItem['HasBadge'] = false;
            if (!isset($menuItem['BadgeText'])) $menuItem['BadgeText'] = '';
            if (!isset($menuItem['BadgeClass'])) $menuItem['BadgeClass'] = '';
            if (!isset($menuItem['RouteName'])) $menuItem['RouteName'] = '';
            if (!isset($menuItem['RouteParams'])) $menuItem['RouteParams'] = array();
            if (!isset($menuItem['Submenu'])) $menuItem['Submenu'] = array();

            if (count($menuItem['Submenu']) > 0) {
                $menuItem['Submenu'] = $this->setArrayDefaults($menuItem['Submenu']);
            }
            $isRoutePermitted = false;
            if ($menuItem['RouteName'] !== '') {
                $isRoutePermitted = $this->authorizationChecker->isGranted($menuItem['RouteName']);
            }
            if (($isRoutePermitted) or (($menuItem['IsHeader']) and (count($menuItem['Submenu']) > 0)) or (count($menuItem['Submenu']) > 0)) {
                $finalItems[] = $menuItem;
            }

        }
        return $finalItems;
    }

}
