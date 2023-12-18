<?php

namespace App\Command;

use App\Entity\Component;
use App\Entity\ComponentTest;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(name: 'app:generate', description: 'Add a short description for your command')]
class GenerateCommand extends Command
{
    public function __construct()
    {
        parent::__construct();

        require_once __DIR__.'/../../inc/functions.php';
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $componentFolder = '/mnt/f/dev/smph-med-v8/wp-content/themes/smph/page-parts/component';

        $yamlText = <<<YAML
css:
    shared:
        - 000-reset.css
        - 010-vars.css
        - 050-fonts.css
        - 100-main.css
    buttons:
        - 150-buttons.css
        - 151-buttons-styles.css
components:
    basic_copy:
        css:
            - "@shared"
            - 300-comp-basic-copy.css
        template: basic_copy.php
        tests:
            test:
                name: First
                file: basic-copy/basic-copy-001.html
                sub_fields:
                    full_width: null
                    copy: "@lorem/3/paragraphs"
    figure:
        css:
            - "@shared"
            - 101-figure.css
            - 300-comp-figure.css
        template: figure.php
        tests:
            test:
                name: First
                file: figure/figure-001.html
                sub_fields:
                    css_class: 'legacy'
                    float: 'none'
                    caption: 'I am the caption'
                    photo_credit: 'I am the photo credit'
                    image: null
                global_functions:
                    vendi_maybe_get_row_id_attribute:
                        returns: 1
            test2:
                name: Second
                file: figure/figure-002.html
                sub_fields:
                    css_class: 'legacy'
                    float: 'none'
                    caption: 'I am the caption'
                    photo_credit: 'I am the photo credit'
                    image:
                        url: 'https://via.placeholder.com/150'
                        alt: 'Placeholder'
                global_functions:
                    vendi_maybe_get_row_id_attribute:
                        returns: 2
            test3:
                name: Third
                file: figure/figure-003.html
                sub_fields:
                    css_class: null
                    float: 'left'
                    caption: 'I am the caption'
                    photo_credit: 'I am the photo credit'
                    photo_size: 'small'
                    image:
                        url: 'https://via.placeholder.com/150'
                        alt: 'Placeholder'
                global_functions:
                    vendi_maybe_get_row_id_attribute:
                        returns: 2
YAML;

        $config = Yaml::parse($yamlText);

        global $globalCss;

        foreach ($config['css'] as $globalCssName => $globalCssFiles) {
            $globalCss[$globalCssName] = $globalCssFiles;
        }

        foreach ($config['components'] as $name => $componentArray) {
            $template = Path::join($componentFolder, $componentArray['template']);
            if (!is_readable($template)) {
                throw new RuntimeException('Template not found: '.$template);
            }

            $component = Component::fromYaml($name, $componentArray);

            foreach ($componentArray['tests'] as $testArray) {
                $test = ComponentTest::fromYaml($name, $testArray);

                $test->render($component, $name, $template);
            }
        }

        $io = new SymfonyStyle($input, $output);
        $io->success('Done');

        return Command::SUCCESS;
    }
}
