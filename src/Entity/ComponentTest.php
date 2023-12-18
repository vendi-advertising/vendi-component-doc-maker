<?php

namespace App\Entity;

use RuntimeException;
use Symfony\Component\Filesystem\Path;

class ComponentTest
{
    public function __construct(
        public readonly string $name,
        public readonly string $file,
        public readonly array $subFields = [],
        public readonly array $globalFunctions = [],
    ) {
    }

    public static function fromYaml(string $name, array $data): self
    {
        return new self(
            name: $name,
            file: $data['file'],
            subFields: $data['sub_fields'] ?? [],
            globalFunctions: $data['global_functions'] ?? [],
        );
    }

    public function render(Component $component, string $componentName, string $template): void
    {
        $cssFolder = '/mnt/f/dev/smph-med-v8/wp-content/themes/smph/css/';
        $outputFolder = '/mnt/f/dev/vendi-component-doc-maker/.output/';

        $buffer = $this->getHtmlStart($componentName, $component, $cssFolder);

        global $sub_fields;
        $sub_fields = $this->subFields;

        ob_start();
        include $template;

        $buffer .= ob_get_flush();

        $buffer .= $this->getHtmlEnd();

        $outputFile = Path::join($outputFolder, $this->file);

        $outputDirectory = dirname($outputFile);
        if (!is_dir($outputDirectory)) {
            mkdir($outputDirectory, 0777, true);
        }

        file_put_contents($outputFile, $buffer);
    }

    private function getHtmlStart(string $name, Component $component, string $cssFolder): string
    {
        $buffer = '';

        $buffer .= <<<HTML
<doctype html>
<html>
<head>
    <title>Component: $name</title>
HTML;

        global $globalCss;

        foreach ($component->cssFiles as $css) {
            if (str_starts_with($css, '@')) {
                $name = substr($css, 1);
                if (!array_key_exists($name, $globalCss)) {
                    throw new RuntimeException('Global CSS not found: '.$name);
                }

                foreach ($globalCss[$name] as $globalCssFile) {
                    $buffer .= $this->getCss($cssFolder, $globalCssFile);
                }

            } else {
                $buffer .= $this->getCss($cssFolder, $css);
            }
        }

        $buffer .= <<<HTML
</head>
<body>
   <main>
HTML;

        return $buffer;
    }

    private function getCss($cssFolder, $css)
    {
        $cssFile = Path::join($cssFolder, $css);
        if (!is_readable($cssFile)) {
            throw new RuntimeException('CSS file not found: '.$cssFile);
        }

        $buffer = '<style>';
        $buffer .= file_get_contents($cssFile);
        $buffer .= '</style>';

        return $buffer;
    }

    private function getHtmlEnd(): string
    {
        return <<<HTML
    </main>
</body>
</html>
HTML;
    }
}