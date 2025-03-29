<?php

namespace App\Entity;

use App\Repository\MusicRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MusicRepository::class)]
#[ORM\HasLifecycleCallbacks]

class Music
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $url = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $auteur = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'music')]
    private ?self $Category = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'Category')]
    private Collection $music;

    public function __construct()
    {
        $this->music = new ArrayCollection();
    }
    
    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if($this->createdAt == null){
            $this->createdAt = new \DateTimeImmutable();
            }
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getAuteur(): ?string
    {
        return $this->auteur;
    }

    public function setAuteur(?string $auteur): static
    {
        $this->auteur = $auteur;

        return $this;
    }

    public function getCategory(): ?self
    {
        return $this->Category;
    }

    public function setCategory(?self $Category): static
    {
        $this->Category = $Category;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getMusic(): Collection
    {
        return $this->music;
    }

    public function addMusic(self $music): static
    {
        if (!$this->music->contains($music)) {
            $this->music->add($music);
            $music->setCategory($this);
        }

        return $this;
    }

    public function removeMusic(self $music): static
    {
        if ($this->music->removeElement($music)) {
            // set the owning side to null (unless already changed)
            if ($music->getCategory() === $this) {
                $music->setCategory(null);
            }
        }

        return $this;
    }

}