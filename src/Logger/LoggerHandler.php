<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace App\Logger;

use App\Entity\LogRecord;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This handler writes the log to the Database using the LogRecord
 * entity. It is important to call flush after any of the log
 * generating calls, otherwise the log entries will NOT be saved.
 */
class LoggerHandler extends AbstractProcessingHandler implements ContainerAwareInterface
{
    private ContainerInterface $container;
    private EntityManagerInterface $em;
    private array $messages;
    private bool $isRecordingMessages;

    public function __construct(EntityManagerInterface $em, int $level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->em = $em;
        $this->messages = array();
        $this->isRecordingMessages = false;
    }

    /**
     * {@inheritdoc}
     */
    protected function write($record): void
    {
        $logRecord = new LogRecord($record['channel'],
            $record['datetime'],
            $record['level'],
            $record['level_name'],
            $record['message'],
            $record['context']['link'] ?? null,
            $record['context']['source'] ?? null,
            !empty($record['extra']['user_id']) ? $record['extra']['user_id'] : null,
            $record['extra']['user_name'] ?? null,
            $record['context']['logfile'] ?? null);
        $this->em->persist($logRecord);
        if ($this->isRecordingMessages) {
            $this->messages[] = $logRecord;
        }
        $this->em->flush();
    }

    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    public function getMessages(string $link = null): array
    {
        if (!is_null($link)) {
            $messages = [];
            foreach ($this->messages as $m) {
                if ($m->getLink() == $link) {
                    $messages[] = $m;
                }
            }
            return $messages;
        } else {
            return $this->messages;
        }
    }

    public function clearMessages(): void
    {
        $this->messages = array();
    }

    public function startRecordingMessages(): void
    {
        $this->isRecordingMessages = true;
    }

    public function stopRecordingMessages(): void
    {
        $this->isRecordingMessages = false;
    }
}
