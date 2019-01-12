<?php

namespace DoctrineRepoHelper\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\Exception\InvalidArgumentException;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\MethodGenerator;

/**
 * Class GenerateTraitCommand
 * @package DoctrineRepoHelper\Command
 */
class GenerateTraitCommand extends Command
{
    /** @var EntityManager */
    protected $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('orm:generate-repository-trait')
            ->setDescription('Generate a repository helper trait')
            ->setHelp(
                <<<EOT
The generate repository trait command creates a trait that will allow your development environment to autocomplete
custom repository methods
EOT
            )
            ->addOption(
                'namespace',
                null,
                InputOption::VALUE_OPTIONAL,
                'Declares the namespace'
            )
            ->addOption(
                'destination',
                null,
                InputOption::VALUE_OPTIONAL,
                'Path to create the trait in',
                getcwd()
            )
            ->addOption(
                'classname',
                null,
                InputOption::VALUE_OPTIONAL,
                'Classname of the trait',
                'CustomRepositoryAwareTrait'
            )
            ->addOption(
                'em-getter',
                null,
                InputOption::VALUE_OPTIONAL,
                'Name of the property or method classes that use the trait will have to access the EntityManager',
                'getObjectManager()'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \ReflectionException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManagerGetter = $input->getOption('em-getter');
        $traitName = $input->getOption('classname');
        $traitNameSpace = $input->getOption('namespace');
        $outputFileName = sprintf(
            '%s/%s.php',
            $input->getOption('destination'),
            $traitName
        );

        $metaDatas = $this->entityManager->getMetadataFactory()->getAllMetadata();

        $trait = new \Zend\Code\Generator\TraitGenerator(
            $traitName,
            $traitNameSpace
        );

        $trait
            ->addUse(EntityManager::class)
            ->addUse(EntityRepository::class);

        $docBlock = DocBlockGenerator::fromArray(
            [
                'shortDescription' => $traitName,
                'longDescription' => 'Provides helper methods for accessing custom repositories. Provides type hints to allow for custom method auto-completion within IDEs',
                'tags' => [
                    [
                        'name' => 'package',
                        'description' => $traitNameSpace
                    ],
                    [
                        'name' => 'method',
                        'description' => sprintf(
                            'EntityManager %s',
                            $entityManagerGetter
                        )
                    ]
                ]
            ]
        );

        $trait->setDocBlock($docBlock);

        foreach ($metaDatas as $metaData) {
            $fqcn = $metaData->getName();
            $reflection = new \ReflectionClass($fqcn);
            $repoReflection = null;

            if ($metaData->customRepositoryClassName) {
                $repoReflection = new \ReflectionClass($metaData->customRepositoryClassName);
            }

            $method = new MethodGenerator(
                sprintf(
                    'get%sRepository',
                    $reflection->getShortName()
                ),
                [],
                MethodGenerator::FLAG_PUBLIC,
                sprintf(
                    'return $this->%s->getRepository(\\%s::class);',
                    $entityManagerGetter,
                    $reflection->getName()
                )
            );



            $docBlock = DocBlockGenerator::fromArray(
                [
                    'tags' => [
                        [
                            'name' => 'return',
                            'description' => sprintf(
                                '%s%s',
                                'EntityRepository',
                                $repoReflection ? '|\\' . $repoReflection->getName() : ''
                            )
                        ]
                    ]
                ]
            );

            $method->setDocBlock($docBlock);

            try {
                $trait->addMethodFromGenerator($method);

            } catch (InvalidArgumentException $e) {
                $output->writeln(
                    sprintf(
                        'Method "%s" already exists in this class',
                        $method->getName()
                    )
                );

                $method->setName(
                    sprintf(
                        'get%sRepository%s',
                        $reflection->getShortName(),
                        str_replace('.', '', uniqid('', true))
                    )
                );

                $trait->addMethodFromGenerator($method);
                $output->writeln(
                    sprintf(
                        'Refactored the method to "%s". Please refactor to a usable name you will remember',
                        $method->getName()
                    )
                );
                $output->writeln('');
            }
        }

        $file = new FileGenerator();
        $file->setClass($trait);

        file_put_contents($outputFileName, $file->generate());

        $output->writeln('');

        $output->writeln(
            sprintf(
                'Trait created in "%s"',
                $outputFileName
            )
        );

        $output->writeln('');
    }
}