<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\NewsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Table(name: 'news', options: ['collate' => 'utf8mb4_unicode_ci', 'charset' => 'utf8mb4'])]
#[ORM\Entity(repositoryClass: NewsRepository::class)]
class News
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private Uuid $id;

    #[ORM\Column(length: 255, nullable: false)]
    private string $title;

    #[ORM\Column(type: Types::TEXT, length: 65535, nullable: false)]
    private string $content;

    #[ORM\Column(length: 255, nullable: false)]
    private string $categories;

    #[ORM\Column(type: Types::TEXT, length: 65535, nullable: true)]
    private ?string $url = null;

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function setCategories(string $categories): void
    {
        $this->categories = $categories;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }
}
