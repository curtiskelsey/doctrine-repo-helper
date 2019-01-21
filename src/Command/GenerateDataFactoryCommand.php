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
                    'body' => sprintf(
                        'use League\\FactoryMuffin\\Faker\\Facade as Faker;

$factory->_define(
    %s::class,
    %s
);',
                        $metaData->getName(),
                        $this->buildFactoryData($metaData)->generate()
                    ),
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
     * @return ValueGenerator
     */
    private function buildFactoryData(ClassMetadata $metaData)
    {
        $data = [];

        foreach ($metaData->fieldMappings as $fieldMapping) {
            switch ($fieldMapping['type']) {
                // TODO handle all primitive types
                case 'date':
                    break;
                default:
                    $data[$fieldMapping['fieldName']] = 'Faker::word()';
                    break;
            }
        }

        foreach ($metaData->associationMappings as $associationMapping) {
            switch ($associationMapping['type']) {
                case 1:
                    // TODO entity|FQCN::class
                    break;
                case 2:
                    // TODO many to one?
                    break;
                default:
                    break;
            }
        }

        return new ValueGenerator($data);
    }
}