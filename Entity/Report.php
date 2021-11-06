<?php

namespace Pixel\TownHallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Persistence\Model\AuditableTrait;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;

/**
 * @ORM\Entity()
 * @ORM\Table(name="townall_report")
 * @Serializer\ExclusionPolicy("all")
 */
class Report implements AuditableInterface
{
    use AuditableTrait;

    public const RESOURCE_KEY = 'reports';
    public const FORM_KEY = 'report_details';
    public const LIST_KEY = 'reports';
    public const SECURITY_CONTEXT = 'townhall_reports.reports';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     *
     * @Serializer\Expose()
     */
    private ?int $id = null;


    /**
     * @ORM\Column(type="datetime_immutable")
     *
     * @Serializer\Expose()
     */
    private ?\DateTimeImmutable $dateReport = null;

    /**
     * @ORM\ManyToOne(targetEntity=MediaInterface::class)
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private ?MediaInterface $document = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getDateReport(): ?\DateTimeImmutable
    {
        return $this->dateReport;
    }

    /**
     * @param string|null $dateReport
     */
    public function setDateReport(?\DateTimeImmutable $dateReport): void
    {
        $this->dateReport = $dateReport;
    }

    public function getDocument(): ?MediaInterface
    {
        return $this->document;
    }

    /**
     * @return array<string, mixed>
     *
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("document")
     */
    public function getDocumentData(): ?array
    {
        if ($document = $this->getDocument()) {
            return [
                'id' => $document->getId(),
            ];
        }

        return null;
    }

    public function setDocument(?MediaInterface $document): void
    {
        $this->document = $document;
    }

}