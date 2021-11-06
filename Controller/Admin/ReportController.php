<?php

declare(strict_types=1);

namespace Pixel\TownHallBundle\Controller\Admin;

use Pixel\TownHallBundle\Common\DoctrineListRepresentationFactory;
use Pixel\TownHallBundle\Entity\Report;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Controller\Annotations\RouteResource;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @RouteResource("report")
 */
class ReportController extends AbstractRestController implements ClassResourceInterface, SecuredControllerInterface
{
    private DoctrineListRepresentationFactory $doctrineListRepresentationFactory;
    private EntityManagerInterface $entityManager;
    private MediaManagerInterface $mediaManager;

    public function __construct(
        DoctrineListRepresentationFactory $doctrineListRepresentationFactory,
        EntityManagerInterface $entityManager,
        MediaManagerInterface $mediaManager,
        ViewHandlerInterface $viewHandler,
        ?TokenStorageInterface $tokenStorage = null
    ) {
        $this->doctrineListRepresentationFactory = $doctrineListRepresentationFactory;
        $this->entityManager = $entityManager;
        $this->mediaManager = $mediaManager;

        parent::__construct($viewHandler, $tokenStorage);
    }

    public function cgetAction(): Response
    {
        $listRepresentation = $this->doctrineListRepresentationFactory->createDoctrineListRepresentation(
            Report::RESOURCE_KEY
        );

        return $this->handleView($this->view($listRepresentation));
    }

    public function getAction(int $id): Response
    {
        $report = $this->entityManager->getRepository(Report::class)->find($id);
        if (!$report) {
            throw new NotFoundHttpException();
        }

        return $this->handleView($this->view($report));
    }

    public function putAction(Request $request, int $id): Response
    {
        $report = $this->entityManager->getRepository(Report::class)->find($id);
        if (!$report) {
            throw new NotFoundHttpException();
        }

        $this->mapDataToEntity($request->request->all(), $report);
        $this->entityManager->flush();
        return $this->handleView($this->view($report));
    }

    public function postAction(Request $request): Response
    {
        $report = new Report();
        $this->mapDataToEntity($request->request->all(), $report);
        $this->entityManager->persist($report);
        $this->entityManager->flush();

        return $this->handleView($this->view($report, 201));
    }

    public function deleteAction(int $id): Response
    {
        /** @var Report $report */
        $report = $this->entityManager->getReference(Report::class, $id);
        $this->entityManager->remove($report);
        $this->entityManager->flush();

        return $this->handleView($this->view(null, 204));
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function mapDataToEntity(array $data, Report $entity): void
    {
        $documentId = $data['document']['id'] ?? null;
        $entity->setDateReport(new \DateTimeImmutable($data['dateReport']));
        $entity->setDocument($documentId ? $this->mediaManager->getEntityById($documentId) : null);
    }

    public function getSecurityContext(): string
    {
        return Report::SECURITY_CONTEXT;
    }
}
