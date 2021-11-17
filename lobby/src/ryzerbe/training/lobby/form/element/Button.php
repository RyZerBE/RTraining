<?php

declare(strict_types=1);

namespace ryzerbe\training\lobby\form\element;

class Button {
    private string $text;
    private int $imageType;
    private string $imagePath;
    private ?string $label;

    public function __construct(string $text, int $imageType = -1, string $imagePath = "", ?string $label = null){
        $this->text = $text;
        $this->imageType = $imageType;
        $this->imagePath = $imagePath;
        $this->label = $label;
    }

    public function getText(): string{
        return $this->text;
    }

    public function getImageType(): int{
        return $this->imageType;
    }

    public function getImagePath(): string{
        return $this->imagePath;
    }

    public function getLabel(): ?string{
        return $this->label;
    }
}