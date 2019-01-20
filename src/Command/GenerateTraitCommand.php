<?php
/**
 * @noinspection PhpUndefinedFieldInspection
 */

namespace DoctrineRepoHelper\Command;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\Exception\InvalidArgumentException;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\TraitGenerator;

/**
 * Class GenerateTraitCommand
 * @package DoctrineRepoHelper\Command
 */
class GenerateTraitCommand extends Command
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
            ->setName('orm:generate-repository-trait')
            ->setDescription('Generate a repository helper trait')
            ->setHelp(
                'The generate repository trait command creates a trait that will allow your development 
                environment to autocomplete custom repository methods'
            )
            ->addOption(
                'namespace',
                null,
                InputOption::VALUE_OPTIONAL,
                'Declares the namespace'
            )
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_OPTIONAL,
                'Output path',
                getcwd()
            )
            ->addOption(
                'className',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Classname of the trait',
                'CustomRepositoryAwareTrait'
            )
            ->addOption(
                'em-getter',
                null,
                InputOption::VALUE_OPTIONAL,
                'Property or method name to access the EntityManager',
                'getObjectManager()'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Overwrite existing trait'
            )
            ->addOption(
                'filter',
                null,
                InputOption::VALUE_OPTIONAL,
                'Filter the list of entities getters are created for'
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
        /** @var string $traitName */
        $traitName = (string)$input->getOption('className');

        /** @var string $destination */
        $destination = (string)$input->getOption('output');

        $outputFileName = sprintf(
            '%s/%s.php',
            $destination,
            $traitName
        );

        $metaDataEntries = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $trait = $this->generateTrait($input);

        foreach ($metaDataEntries as $metaData) {
            if ($filter = $input->getOption('filter')) {
                if (strpos($metaData->getName(), $filter) === false) {
                    continue;
                }
            }

            $method = $this->generateMethod(
                $input,
                $metaData
            );

            try {
                $trait->addMethodFromGenerator($method);
            } catch (InvalidArgumentException $e) {
                $output->writeln(
                    sprintf(
                        'Method "%s" already exists in this class',
                        $method->getName()
                    )
                );

                $reflection = new \ReflectionClass($metaData->getName());

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

        if (!is_dir($destination)) {
            mkdir($destination, 0777, true);
        }

        if (!$input->getOption('force')) {
            if (file_exists($outputFileName)) {
                $output->writeln('File already exists. Use "-f" to force overwriting the existing file');
                return;
            }
        }

        file_put_contents(
            $outputFileName,
            $file->generate()
        );

        $output->writeln('');

        $output->writeln(
            sprintf(
                'Trait created in "%s"',
                $outputFileName
            )
        );

        $output->writeln('');
    }

    /**
     * @param InputInterface $input
     * @return TraitGenerator
     */
    private function generateTrait(InputInterface $input): TraitGenerator
    {
        /** @var string $entityManagerGetter */
        $entityManagerGetter = (string)$input->getOption('em-getter');
        /** @var string $traitName */
        $traitName = (string)$input->getOption('className');
        /** @var string $traitNameSpace */
        $traitNameSpace = (string)$input->getOption('namespace');

        $trait = new TraitGenerator(
            $traitName,
            $traitNameSpace
        );

        $trait
            ->addUse(EntityManager::class)
            ->addUse(EntityRepository::class);

        $docBlock = DocBlockGenerator::fromArray(
            [
                'shortDescription' => $traitName,
                'longDescription' => 'Provides helper methods for accessing custom repositories. In addition, provides 
                    type hints to allow for custom method auto-completion within IDEs',
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
        return $trait;
    }

    /**
     * @param ClassMetadata $metaData
     * @param InputInterface $input
     * @return MethodGenerator
     * @throws \ReflectionException
     */
    private function generateMethod(InputInterface $input, ClassMetadata $metaData): MethodGenerator
    {
        $entityManagerGetter = $input->getOption('em-getter');

        $reflection = new \ReflectionClass($metaData->getName());
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
        return $method;
    }
}
