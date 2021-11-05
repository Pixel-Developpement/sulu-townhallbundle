<?php

declare(strict_types=1);

namespace Pixel\TownHallBundle\Controller\Admin;

use Pixel\TownHallBundle\Entity\Settings;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Controller\Annotations\RouteResource;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @RouteResource("townhall-settings")
 */
class SettingsController extends AbstractRestController implements ClassResourceInterface, SecuredControllerInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        ViewHandlerInterface $viewHandler,
        ?TokenStorageInterface $tokenStorage = null
    ) {
        $this->entityManager = $entityManager;
        parent::__construct($viewHandler, $tokenStorage);
    }

    public function getAction(): Response
    {
        $applicationSettings = $this->entityManager->getRepository(Settings::class)->findOneBy([]);

        return $this->handleView($this->view($applicationSettings ?: new Settings()));
    }

    public function putAction(Request $request): Response
    {
        $applicationSettings = $this->entityManager->getRepository(Settings::class)->findOneBy([]);
        if (!$applicationSettings) {
            $applicationSettings = new Settings();
            $this->entityManager->persist($applicationSettings);
        }

        $this->mapDataToEntity($request->request->all(), $applicationSettings);
        $this->entityManager->flush();

        return $this->handleView($this->view($applicationSettings));
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function mapDataToEntity(array $data, Settings $entity): void
    {
        $entity->setTownhallName($data['townhallName']);
    }

    public function getSecurityContext(): string
    {
        return Settings::SECURITY_CONTEXT;
    }
}