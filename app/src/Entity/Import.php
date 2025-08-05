<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enumeration\ImportStatusEnumeration;
use App\Repository\ImportRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Table(name: 'import', options: ['collate' => 'utf8mb4_unicode_ci', 'charset' => 'utf8mb4'])]
#[ORM\Index(name: 'ix_status', columns: ['status'])]
#[ORM\Entity(repositoryClass: ImportRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Import
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private Uuid $id;

    #[ORM\Column(type: 'string', nullable: false)]
    private string $importFile;

    #[ORM\Column(type: 'string', enumType: ImportStatusEnumeration::class, options: ['default' => ImportStatusEnumeration::RUNNING])]
    private ImportStatusEnumeration $status = ImportStatusEnumeration::RUNNING;

    #[ORM\Column(name: 'import_start_at', type: 'datetime', nullable: true)]
    private ?DateTime $importStartAt = null;

    #[ORM\Column(name: 'import_end_at', type: 'datetime', nullable: true)]
    private ?DateTime $importEndAt = null;

    #[ORM\Column(name: 'row_count', type: 'integer', nullable: true)]
    private ?int $rowCount = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $errorFile = null;

    #[ORM\PrePersist]
    public function setImportStartAt(): void
    {
        if ($this->importStartAt === null) {
            $this->importStartAt = new DateTime();
        }
    }

    public function setImportFile(string $importFile): void
    {
        $this->importFile = $importFile;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function setImportEndAt(?DateTime $importEndAt): void
    {
        $this->importEndAt = $importEndAt;
    }

    public function setStatus(ImportStatusEnumeration $status): void
    {
        $this->status = $status;
    }
}
