<?php

declare(strict_types=1);

namespace Pixel\TownHallBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Controller\Annotations\RouteResource;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Pixel\TownHallBundle\Common\DoctrineListRepresentationFactory;
use Pixel\TownHallBundle\Entity\Association;
use Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Manager\RouteManagerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Security\SecuredControllerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @RouteResource("association")
 */
class AssociationController extends AbstractRestController implements ClassResourceInterface, SecuredControllerInterface
{
    private DoctrineListRepresentationFactory $doctrineListRepresentationFactory;
    private EntityManagerInterface $entityManager;
    private MediaManagerInterface $mediaManager;
    private CategoryManagerInterface $categoryManager;
    private WebspaceManagerInterface $webspaceManager;
    private RouteManagerInterface $routeManager;
    private RouteRepositoryInterface $routeRepository;

    public function __construct(
        DoctrineListRepresentationFactory $doctrineListRepresentationFactory,
        EntityManagerInterface            $entityManager,
        MediaManagerInterface             $mediaManager,
        ViewHandlerInterface              $viewHandler,
        CategoryManagerInterface          $categoryManager,
        WebspaceManagerInterface          $webspaceManager,
        RouteManagerInterface             $routeManager,
        RouteRepositoryInterface          $routeRepository,
        ?TokenStorageInterface            $tokenStorage = null
    )
    {
        $this->doctrineListRepresentationFactory = $doctrineListRepresentationFactory;
        $this->entityManager = $entityManager;
        $this->mediaManager = $mediaManager;
        $this->categoryManager = $categoryManager;
        $this->webspaceManager = $webspaceManager;
        $this->routeManager = $routeManager;
        $this->routeRepository = $routeRepository;

        parent::__construct($viewHandler, $tokenStorage);
    }

    public function cgetAction(): Response
    {
        $listRepresentation = $this->doctrineListRepresentationFactory->createDoctrineListRepresentation(
            Association::RESOURCE_KEY
        );

        return $this->handleView($this->view($listRepresentation));
    }

    public function getAction(int $id): Response
    {
        $item = $this->entityManager->getRepository(Association::class)->find($id);
        if (!$item) {
            throw new NotFoundHttpException();
        }

        return $this->handleView($this->view($item));
    }

    public function putAction(Request $request, int $id): Response
    {
        $item = $this->entityManager->getRepository(Association::class)->find($id);
        if (!$item) {
            throw new NotFoundHttpException();
        }

        $this->mapDataToEntity($request->request->all(), $item);
        $this->updateRoutesForEntity($item);
        $this->entityManager->flush();

        return $this->handleView($this->view($item));
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function mapDataToEntity(array $data, Association $entity): void
    {
        $logoId = $data['logo']['id'] ?? null;
        $location = $data['location'] ?? null;
        $description = $data['description'] ?? null;
        $isActive = $data['isActive'] ?? null;
        $categoryId = (isset($data['category']['id'])) ? $data['category']['id'] : $data['category'];

        $entity->setName($data['name']);
        $entity->setLocation($location);
        $entity->setRoutePath($data['routePath']);
        $entity->setIsActive($isActive);
        $entity->setDescription($description);
        $entity->setCategory($this->categoryManager->findById($categoryId));
        $entity->setLogo($logoId ? $this->mediaManager->getEntityById($logoId) : null);
    }

    protected function updateRoutesForEntity(Association $entity): void
    {
        // create route for all locales of the application because event entity is not localized
        foreach ($this->webspaceManager->getAllLocales() as $locale) {
            $this->routeManager->createOrUpdateByAttributes(
                Association::class,
                (string)$entity->getId(),
                $locale,
                $entity->getRoutePath(),
            );
        }
    }

    public function postAction(Request $request): Response
    {
        $item = new Association();
        $this->mapDataToEntity($request->request->all(), $item);
        $this->entityManager->persist($item);
        $this->entityManager->flush();
        $this->updateRoutesForEntity($item);
        $this->entityManager->flush();
        return $this->handleView($this->view($item, 201));
    }

    public function deleteAction(int $id): Response
    {
        /** @var Association $item */
        $item = $this->entityManager->getReference(Association::class, $id);
        $this->entityManager->remove($item);
        $this->entityManager->flush();

        return $this->handleView($this->view(null, 204));
    }

    public function getSecurityContext(): string
    {
        return Association::SECURITY_CONTEXT;
    }

    protected function removeRoutesForEntity(Association $entity): void
    {
        // remove route for all locales of the application because event entity is not localized
        foreach ($this->webspaceManager->getAllLocales() as $locale) {
            $routes = $this->routeRepository->findAllByEntity(
                Association::class,
                (string)$entity->getId(),
                $locale
            );

            foreach ($routes as $route) {
                $this->routeRepository->remove($route);
            }
        }
    }
}
