<?php

namespace DoctrineRepoHelper;

use Doctrine\ORM\Tools\Console\ConsoleRunner;
use DoctrineRepoHelper\Command\GenerateTraitCommand;
use Symfony\Component\Console\Application;
use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManager;
use Zend\ModuleManager\ModuleManager;

/**
 * Class Module
 * @package DoctrineRepoHelper
 */
class Module
{
    /**
     * {@inheritDoc}
     */
    public function init(ModuleManager $e)
    {
        /** @var EventManager $events */
        $events = $e->getEventManager()->getSharedManager();

        $events->attach(
            'doctrine',
            'loadCli.post',
            function (EventInterface $e) {
                /* @var $cli Application */
                $cli = $e->getTarget();
                $em = $cli->getHelperSet()->get('em')->getEntityManager();
                ConsoleRunner::addCommands($cli);

                $cli->addCommands([new GenerateTraitCommand($em)]);
            }
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }
}