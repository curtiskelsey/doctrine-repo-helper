<?php

namespace DoctrineRepoHelper\Command;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\FileGenerator;

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
            ->setDescription('Generate data factories for use with Codeception')
            ->setHelp('Generates data factories for use with the Codeception DataFactory module')
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

            if (file_exists($fileName)) {
                $output->writeln(
                    sprintf(
                        '%s already exists. Skipping...',
                        $fileName
                    ),
                    OutputInterface::VERBOSITY_VERBOSE
                );

                continue;
            }

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
        $fieldMappings = $this->buildFieldMappings($metaData);
        $associationMappings = $this->buildAssociationMappings($metaData);

        return array_merge(
            $fieldMappings,
            $associationMappings
        );
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

    /**
     * @param ClassMetadata $metaData
     * @return array
     */
    private function buildAssociationMappings(ClassMetadata $metaData): array
    {
        $data = array_map(
            function (array $associationMapping) {
                if ($associationMapping['type'] > 0 && $associationMapping['type'] < 4) {
                    return sprintf(
                        "        '%s' => 'entity|' . \\%s::class,\n",
                        $associationMapping['fieldName'],
                        $associationMapping['targetEntity']
                    );
                }

                return null;
            },
            $metaData->associationMappings
        );

        return array_filter($data);
    }

    /**
     * @param ClassMetadata $metaData
     * @return array
     */
    private function buildFieldMappings(ClassMetadata $metaData): array
    {
        $data = [];

        $lookupTable = [
            'smallint' => 'random_int(0, 65000)',
            'integer' => 'random_int(0, 65000)',
            'bigint' => 'random_int(0, 65000)',
            'decimal' => 'Faker::randomFloat(2)',
            'float' => 'Faker::randomFloat(2)',
            'string' => 'Faker::sentence()',
            'text' => 'Faker::paragraph()',
            'guid' => "uniqid('', true)",
            'binary' => null,
            'blob' => null,
            'boolean' => '(bool)random_int(0, 1)',
            'date' => 'Faker::dateTime()',
            'date_immutable' => 'Faker::dateTime()',
            'datetime' => 'Faker::dateTime()',
            'datetime_immutable' => 'Faker::dateTime()',
            'datetimetz' => 'Faker::dateTime()',
            'datetimetz_immutable' => 'Faker::dateTime()',
            'time' => 'Faker::dateTime()',
            'time_immutable' => 'Faker::dateTime()',
            'dateinterval' => null,
            'array' => null,
            'simple_array' => null,
            'json' => null,
            'json_array' => null,
            'object' => null,
        ];

        foreach ($metaData->fieldMappings as $fieldMapping) {
            if (!array_key_exists($fieldMapping['type'], $lookupTable)) {
                continue;
            }

            $value = $lookupTable[$fieldMapping['type']];

            if ($value) {
                $data[] = sprintf(
                    "        '%s' => %s,\n",
                    $fieldMapping['fieldName'],
                    $value
                );
            }
        }

        return $data;
    }
}