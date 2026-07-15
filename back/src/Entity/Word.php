<?php

namespace App\Entity;

use App\Repository\WordRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WordRepository::class)]
#[ORM\Table(name: 'word_pool')]
#[ORM\Index(name: 'idx_word_difficulty', columns: ['difficulty'])]
#[ORM\Index(name: 'idx_word_category', columns: ['category'])]
#[ORM\Index(name: 'idx_word_diff_cat', columns: ['difficulty', 'category'])]
class Word
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $text = '';

    #[ORM\Column]
    private int $difficulty = 1;

    #[ORM\Column(length: 32)]
    private string $category = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getDifficulty(): int
    {
        return $this->difficulty;
    }

    public function setDifficulty(int $difficulty): static
    {
        $this->difficulty = $difficulty;

        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }
}
