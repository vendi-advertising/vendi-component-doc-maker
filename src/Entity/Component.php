<?php

namespace App\Entity;

class Component
{
    public function __construct(
        public readonly string $name,
        public readonly string $template,
        public readonly array $cssFiles = [],
        public readonly array $jsFiles = [],
    ) {
    }

    public static function fromYaml(string $name, array $data): self
    {
        return new self(
            name: $name,
            template: $data['template'],
            cssFiles: $data['css'] ?? [],
            jsFiles: $data['js'] ?? [],
        );
    }
}