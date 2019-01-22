<?php

namespace DoctrineRepoHelper\Command;


use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\ValueGenerator;

/**
 * Class GenerateDataFactoryCommand
 * @package DoctrineRepoHelper\Command
 */
class GenerateDataFactoryCommand extends Command
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    /**
     * GenerateTraitCommand constructor.
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
            ->setName('orm:generate-data-factories')
            ->setDescription('')
            ->setHelp('')
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_OPTIONAL,
                'Output path',
                getcwd() . '/data/factories'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Overwrite existing data factories'
            )
            ->addOption(
                'filter',
                null,
                InputOption::VALUE_OPTIONAL,
                'filter the list of entities data factories are created for'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $destination = $input->getOption('output');
        $metaDataEntries = $this->entityManager->getMetadataFactory()->getAllMetadata();

        if (!is_dir($destination)) {
            mkdir($destination, 0777, true);
        }

        /** @var ClassMetadata $metaData */
        foreach ($metaDataEntries as $metaData) {
            if ($filter = $input->getOption('filter')) {
                if (strpos($metaData->getName(), $filter) === false) {
                    continue;
                }
            }

            $fileName = $destination . '/' . $metaData->reflClass->getShortName() . 'DataFactory.php';
            // TODO handle duplicate names

            $file = FileGenerator::fromArray(
                [
                    'docblock' => DocBlockGenerator::fromArray(
                        [
                            'shortDescription' => '',
                            'longDescription' => '',
                            'tags' => [
                                [
                                    'name' => 'var',
                                    'description' => '\\Codeception\\Module\\DataFactory $factory'
                                ],
                                [
                                    'name' => 'var',
                                    'description' => '\\Doctrine\\ORM\\EntityManager $em'
                                ]
                            ]
                        ]
                    ),
                    'body' => $this->buildBody($metaData),
                ]
            );

            file_put_contents($fileName, $file->generate());
        }

        $output->writeln(
            sprintf(
                'Data factories written to "%s"',
                $destination
            )
        );
    }

    /**
     * @param ClassMetadata $metaData
     * @return string[]
     */
    private function buildFactoryData(ClassMetadata $metaData): array
    {
        $data = [];

        foreach ($metaData->fieldMappings as $fieldMapping) {
            switch ($fieldMapping['type']) {
                case 'smallint':
                case 'integer':
                case 'bigint':
                    $data[] = sprintf(
                        "        '%s' => random_int(0, 65000),\n",
                        $fieldMapping['fieldName']
                    );
                    break;
                case 'decimal':
                case 'float':
                    $data[] = sprintf(
                        "        '%s' => Faker::randomFloat(2),\n",
                        $fieldMapping['fieldName']
                    );
                    break;
                case 'string':
                    $data[] = sprintf(
                        "        '%s' => Faker::sentence(),\n",
                        $fieldMapping['fieldName']
                    );
                    break;
                case 'text':
                    $data[] = sprintf(
                        "        '%s' => Faker::paragraph(),\n",
                        $fieldMapping['fieldName']
                    );
                    break;
                case 'guid':
                    // TODO
                case 'binary':
                case 'blob':
                    break;
                case 'boolean':
                    $data[] = sprintf(
                        "        '%s' => (bool)random_int(0, 1),\n",
                        $fieldMapping['fieldName']
                    );
                    break;
                case 'date':
                case 'date_immutable':
                case 'datetime':
                case 'datetime_immutable':
                case 'datetimetz':
                case 'datetimetz_immutable':
                case 'time':
                case 'time_immutable':
                    $data[] = sprintf(
                        "        '%s' => Faker::dateTime(),\n",
                        $fieldMapping['fieldName']
                    );
                    break;
                case 'dateinterval':
                case 'array':
                case 'simple_array':
                case 'json':
                case 'json_array':
                case 'object':
                    break;
                default:
                    $data[] = sprintf(
                        "        '%s' => Faker::word(),\n",
                        $fieldMapping['fieldName']
                    );
                    break;
            }
        }

        foreach ($metaData->associationMappings as $associationMapping) {
            switch ($associationMapping['type']) {
                case 1:
                    $data[] = sprintf(
                        "        '%s' => 'entity|' . \\%s::class",
                        $associationMapping['fieldName'],
                        $associationMapping['targetEntity']
                    );
                    break;
                case 2:
                    // TODO many to one?
                    break;
                default:
                    break;
            }
        }

        return $data;
    }

    /**
     * @param ClassMetadata $metaData
     * @return string
     */
    private function buildBody(ClassMetadata $metaData): string
    {
        $fields = $this->buildFactoryData($metaData);

        $body = sprintf(
            "use League\\FactoryMuffin\\Faker\\Facade as Faker;

\$factory->_define(
    %s::class,
    [\n",
            $metaData->getName()
        );

        foreach ($fields as $field) {
            $body .= $field;
        }

        $body .= '
    ]
);';
        return $body;
    }
}