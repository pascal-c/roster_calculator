<?php

namespace App\EventSubscriber;

use App\Controller\RatingController;
use App\Controller\RosterController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AuthenticationSubscriber implements EventSubscriberInterface
{
    public function __construct(private ParameterBagInterface $params)
    {
    }

    public function onKernelController(ControllerEvent $event)
    {
        $controller = $event->getController();

        // when a controller class defines multiple action methods, the controller
        // is returned as [$controllerInstance, 'methodName']
        if (is_array($controller)) {
            $controller = $controller[0];
        }

        if ($controller instanceof RatingController || $controller instanceof RosterController) {
            $authorizationHeader = $event->getRequest()->headers->get('Authorization');
            if (null === $authorizationHeader || false === str_starts_with($authorizationHeader, 'Bearer ')) {
                $event->setController(fn () => new JsonResponse('Unauthorized - Bearer Authentication required', Response::HTTP_UNAUTHORIZED));

                return;
            }

            $bearerToken = str_replace('Bearer ', '', $authorizationHeader);
            $validToken = $this->params->get('app.api_token');

            if ($bearerToken !== $validToken) {
                $event->setController(fn () => new JsonResponse('Unauthorized - invalid token', Response::HTTP_UNAUTHORIZED));
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}
