<?php

namespace App\Enums\Concerns;

trait HasTranslatedLabel
{
    public function getLabel(): ?string
    {
        return $this->label();
    }
}
