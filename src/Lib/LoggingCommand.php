<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace App\Lib;

use Psr\Log\LoggerInterface;
use UnitEnum;

/**
 * This class can be used as base class for those commands which need
 * to log "things" in the elkarbackup application.
 */
abstract class LoggingCommand extends ContainerAwareCommand
{
    private LoggerInterface $logger;

    const ERR_CODE_PRE_FAIL = -1;
    const ERR_CODE_NO_RUN = -2;
    const ERR_CODE_OK = 0;
    const ERR_CODE_UNKNOWN = 1;
    const ERR_CODE_WARNING = 2;
    const ERR_CODE_INPUT_ARG = 3;
    const ERR_CODE_ENTITY_NOT_FOUND = 4;
    const ERR_CODE_NO_ACTIVE_RETAINS = 5;
    const ERR_CODE_PROC_EXEC_FAILURE = 6;
    const ERR_CODE_OPEN_FILE = 7;
    const ERR_CODE_WRITE_FILE = 8;
    const ERR_CODE_CREATE_FILE = 9;
    const ERR_CODE_DATA_ARGUMENTS = 10;
    const ERR_CODE_NOT_FOUND = 11;

    const TYPE_PRE = 'PRE';
    const TYPE_POST = 'POST';

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        parent::__construct();
    }

    abstract protected function getNameForLogs();

    protected function generateClientRoute($id): string
    {
        return $this->getUrlPrefix() . $this->getContainer()->get('router')->generate('editClient', array('id' => $id));
    }

    protected function generateJobRoute($idJob, $idClient): string
    {
        return $this->getUrlPrefix() . $this->getContainer()->get('router')->generate('editJob',
                array('idClient' => $idClient,
                    'idJob' => $idJob));
    }

    protected function getUrlPrefix(): UnitEnum|float|array|bool|int|string|null
    {
        return $this->getContainer()->getParameter('url_prefix');
    }

    /**
     * Flushes doctrine in order to store log messages
     * permanently. Notices that any other pending work units will be
     * flushed too.
     */
    protected function flush(): void
    {
        $this->getContainer()->get('doctrine')->getManager()->flush();
    }

    protected function err($msg, $translatorParams = array(), $context = array()): void
    {
        $logger = $this->logger;
        $translator = $this->getContainer()->get('translator');
        $context = array_merge(array('source' => $this->getNameForLogs()), $context);
        $logger->error($translator->trans($msg, $translatorParams, 'BinovoElkarBackup'), $context);
    }

    protected function info($msg, $translatorParams = array(), $context = array()): void
    {
        $logger = $this->logger;
        $translator = $this->getContainer()->get('translator');
        $context = array_merge(array('source' => $this->getNameForLogs()), $context);
        $logger->info($translator->trans($msg, $translatorParams, 'BinovoElkarBackup'), $context);
    }

    protected function warn($msg, $translatorParams = array(), $context = array()): void
    {
        $logger = $this->logger;
        $translator = $this->getContainer()->get('translator');
        $context = array_merge(array('source' => $this->getNameForLogs()), $context);
        $logger->warning($translator->trans($msg, $translatorParams, 'BinovoElkarBackup'), $context);
    }

    protected function debug($msg, $translatorParams = array(), $context = array()): void
    {
        $logger = $this->logger;
        $translator = $this->getContainer()->get('translator');
        $context = array_merge(array('source' => $this->getNameForLogs()), $context);
        $logger->debug($translator->trans($msg, $translatorParams, 'BinovoElkarBackup'), $context);
    }
}

