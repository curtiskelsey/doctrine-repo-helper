<?php

namespace DoctrineRepoHelper\Command;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateTypeScriptInterfaceCommand extends Command
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    /**
     * GenerateTypeScriptInterfaceCommand constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('orm:generate-ts-interfaces')
            ->setDescription('Generate typescript interfaces')
            ->setHelp('Generates typescript interfaces for use in frontend development')
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_OPTIONAL,
                'Output path',
                getcwd() . '/data/interfaces'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Overwrite existing interfaces',
                true
            )
            ->addOption(
                'filter',
                null,
                InputOption::VALUE_OPTIONAL,
                'filter the list of entities interfaces are created for'
            )
            ->addOption(
                'namespace',
                null,
                InputOption::VALUE_OPTIONAL,
                'Declares the namespace'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $destination = $input->getOption('output');
        $namespace = $input->getOption('namespace');
        $overwrite = $input->hasOption('force');
        $metaDataEntries = $this->entityManager->getMetadataFactory()->getAllMetadata();

        if (!is_dir($destination)) {
            mkdir($destination, 0777, true);
        }

        /** @var ClassMetadata $metaData */
        foreach ($metaDataEntries as $metaData) {
            if ($filter = $input->getOption('filter')) {
                if (strpos($metaData->getName(), $filter) === false) {
                    $output->writeln(
                        sprintf(
                            'Filtering out %s...',
                            $metaData->getName()
                        ),
                        OutputInterface::VERBOSITY_VERY_VERBOSE
                    );
                    continue;
                }
            }

            $fileName = $destination . '/' . $metaData->reflClass->getShortName() . 'Interface.ts';
            // TODO handle duplicate names

            $indent = '';
            $fileContent = '';

            if ($namespace) {
                $fileContent = sprintf("%snamespace %s {", $indent, $namespace) . PHP_EOL;
                $indent = '    ';
            }

            $fileContent .= $indent . sprintf(
                "%sinterface %s {" . PHP_EOL,
                $namespace ? 'export ': '',
                $metaData->reflClass->getShortName()
            );
            $indent .= '    ';

            foreach ($metaData->fieldMappings as $fieldMapping) {
                $fileContent .= sprintf(
                    '%s%s%s: %s;' . PHP_EOL,
                    $indent,
                    $fieldMapping['fieldName'],
                    $fieldMapping['nullable'] ? '?' : '',
                    $this->toTypeScript($fieldMapping['type'])
                );
            }

            $indent = substr($indent, 0, -4);
            $fileContent .= $indent . '}' . PHP_EOL;

            if ($namespace) {
                $indent = '';
                $fileContent .= sprintf("%s}", $indent) . PHP_EOL;
            }

            if (file_exists($fileName) && !$overwrite) {
                $output->writeln(
                    sprintf(
                        '%s already exists. Skipping...',
                        $fileName
                    ),
                    OutputInterface::VERBOSITY_VERBOSE
                );

                continue;
            }

            file_put_contents($fileName, $fileContent);
        }

        $output->writeln(
            sprintf(
                'TypeScript interfaces written to "%s"',
                $destination
            )
        );
    }

    /**
     * @param string $type
     * @return string
     */
    private function toTypeScript(string $type): string
    {
        switch ($type) {
            case 'smallint':
            case 'integer':
            case 'decimal':
            case 'float':
            case 'bigint':
                return 'number';
                break;
            case 'text':
            case 'guid':
            case 'binary':
            case 'blob':
            case 'string':
                return 'string';
                break;
            case 'boolean':
                return 'boolean';
                break;
            case 'date':
            case 'date_immutable':
            case 'datetime':
            case 'datetime_immutable':
            case 'datetimetz':
            case 'datetimetz_immutable':
            case 'time':
            case 'time_immutable':
                return 'Date';
                break;
            case 'dateinterval':
            case 'array':
            case 'simple_array':
            case 'json':
            case 'json_array':
            case 'object':
            default:
                return 'any';
                break;
        }
    }

}