<?php

namespace Binovo\Tknika\BackupsBundle\Entity;

use Binovo\Tknika\BackupsBundle\Lib\Globals;
use Doctrine\ORM\Mapping as ORM;
use Monolog\Logger;
use \RuntimeException;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Job
{
    const NOTIFY_TO_ADMIN = 'admin';
    const NOTIFY_TO_OWNER = 'owner';
    const NOTIFY_TO_EMAIL = 'email';

    const NOTIFICATION_LEVEL_ALL     = 0;
    const NOTIFICATION_LEVEL_INFO    = Logger::INFO;
    const NOTIFICATION_LEVEL_WARNING = Logger::WARNING;
    const NOTIFICATION_LEVEL_ERROR   = Logger::ERROR;
    const NOTIFICATION_LEVEL_NONE    = 1000;


    /**
     * @ORM\ManyToOne(targetEntity="Client", inversedBy="jobs")
     */
    protected $client;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isActive = true;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $notificationsEmail;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $notificationsTo = '["owner"]';

    /**
     * @ORM\Column(type="integer")
     */
    protected $minNotificationLevel = self::NOTIFICATION_LEVEL_ERROR;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     */
    protected $owner;

    /**
     * Include expressions
     * @ORM\Column(type="text", nullable=true)
     */
    protected $include;

    /**
     * Exclude expressions
     * @ORM\Column(type="text", nullable=true)
     */
    protected $exclude;

    /**
     * @ORM\ManyToOne(targetEntity="Policy")
     */
    protected $policy;

    /**
     * @ORM\ManyToOne(targetEntity="Script")
     */
    protected $postScript;

    /**
     * @ORM\ManyToOne(targetEntity="Script")
     */
    protected $preScript;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $path;

    /**
     * Disk usage in KB.
     *
     * @ORM\Column(type="integer")
     */
    protected $diskUsage = 0;

    /**
     * Priority. Lower numbered jobs run first. Set to 2**31-1 for newly
     * created jobs so that they will run last.
     *
     * @ORM\Column(type="integer")
     */
    protected $priority = 2147483647;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $useLocalPermissions = true;

    /**
     * Helper variable to store the LogEntry to show on screen,
     * typically the last log LogRecord related to this client.
     */
    protected $logEntry = null;

    /**
     * Returns the full path of the snapshot directory
     */
    public function getSnapshotRoot()
    {
        return Globals::getSnapshotRoot($this->getClient()->getId(), $this->getId());
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Job
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Job
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set postScript
     *
     * @param string $postScript
     * @return Job
     */
    public function setPostScript($postScript)
    {
        $this->postScript = $postScript;

        return $this;
    }

    /**
     * Get postScript
     *
     * @return string
     */
    public function getPostScript()
    {
        return $this->postScript;
    }

    /**
     * Set preScript
     *
     * @param string $preScript
     * @return Job
     */
    public function setPreScript($preScript)
    {
        $this->preScript = $preScript;

        return $this;
    }

    /**
     * Get preScript
     *
     * @return string
     */
    public function getPreScript()
    {
        return $this->preScript;
    }

    /**
     * Set path
     *
     * @param string $path
     * @return Job
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        $clientUrl = $this->client->getUrl();
        if (empty($clientUrl)) {

            return $this->path;
        } else {

            return sprintf("%s:%s", $this->client->getUrl(), $this->path);
        }
    }

    /**
     * Set client
     *
     * @param Binovo\Tknika\BackupsBundle\Entity\Client $client
     * @return Job
     */
    public function setClient(\Binovo\Tknika\BackupsBundle\Entity\Client $client = null)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get client
     *
     * @return Binovo\Tknika\BackupsBundle\Entity\Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Set include
     *
     * @param string $include
     * @return Policy
     */
    public function setInclude($include)
    {
        $this->include = $include;

        return $this;
    }

    /**
     * Get include
     *
     * If the include list of the job is empty fetches the exclude list of the policy.
     *
     * @return string
     */
    public function getInclude()
    {
        $include = '';
        if (!empty($this->include)) {
            $include = $this->include;
        } else if ($this->policy) {
            $include = $this->policy->getInclude();
        }

        return $include;
    }

    /**
     * Set exclude
     *
     * @param string $exclude
     * @return Policy
     */
    public function setExclude($exclude)
    {
        $this->exclude = $exclude;

        return $this;
    }

    /**
     * Get exclude.
     *
     * If the exclude list of the job is empty fetches the exclude list of the policy.
     *
     * @return string
     */
    public function getExclude()
    {
        $exclude = '';
        if (!empty($this->exclude)) {
            $exclude = $this->exclude;
        } else if ($this->policy) {
            $exclude = $this->policy->getExclude();
        }

        return $exclude;
    }

    /**
     * Set policy
     *
     * @param Binovo\Tknika\BackupsBundle\Entity\Policy $policy
     * @return Job
     */
    public function setPolicy(\Binovo\Tknika\BackupsBundle\Entity\Policy $policy = null)
    {
        $this->policy = $policy;

        return $this;
    }

    /**
     * Get policy
     *
     * @return Binovo\Tknika\BackupsBundle\Entity\Policy
     */
    public function getPolicy()
    {
        return $this->policy;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return Job
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set owner
     *
     * @param Binovo\Tknika\BackupsBundle\Entity\User $owner
     * @return Job
     */
    public function setOwner(User $owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner
     *
     * @return Binovo\Tknika\BackupsBundle\Entity\User
     */
    public function getOwner()
    {
        return $this->owner;
    }


    /**
     * Set notificationsTo
     *
     * @param string $notificationsTo
     * @return Job
     */
    public function setNotificationsTo($notificationsTo)
    {
        $this->notificationsTo = json_encode(array_values($notificationsTo));

        return $this;
    }

    /**
     * Get notificationsTo
     *
     * @return string
     */
    public function getNotificationsTo()
    {
        return json_decode($this->notificationsTo, true);
    }

    /**
     * Set notificationsEmail
     *
     * @param string $notificationsEmail
     * @return Job
     */
    public function setNotificationsEmail($notificationsEmail)
    {
        $this->notificationsEmail = $notificationsEmail;

        return $this;
    }

    /**
     * Get notificationsEmail
     *
     * @return string
     */
    public function getNotificationsEmail()
    {
        return $this->notificationsEmail;
    }

    /**
     * Set minNotificationLevel
     *
     * @param integer $minNotificationLevel
     * @return Job
     */
    public function setMinNotificationLevel($minNotificationLevel)
    {
        $this->minNotificationLevel = $minNotificationLevel;

        return $this;
    }

    /**
     * Get minNotificationLevel
     *
     * @return integer
     */
    public function getMinNotificationLevel()
    {
        return $this->minNotificationLevel;
    }

    /**
     * Set LogEntry
     *
     * @param LogRecord $LogEntry
     * @return Client
     */
    public function setLogEntry(LogRecord $logEntry = null)
    {
        $this->logEntry = $logEntry;

        return $this;
    }

    /**
     * Get LogEntry
     *
     * @return LogRecord
     */
    public function getLogEntry()
    {
        return $this->logEntry;
    }

    /**
     * Set diskUsage
     *
     * @param integer $diskUsage
     * @return Job
     */
    public function setDiskUsage($diskUsage)
    {
        $this->diskUsage = $diskUsage;

        return $this;
    }

    /**
     * Get diskUsage
     *
     * @return integer
     */
    public function getDiskUsage()
    {
        return $this->diskUsage;
    }

    /**
     * Set Priority
     *
     * @param integer $Priority
     * @return Job
     */
    public function setPriority($Priority)
    {
        $this->priority = $Priority;

        return $this;
    }

    /**
     * Get Priority
     *
     * @return integer
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set useLocalPermissions
     *
     * @param boolean $useLocalPermissions
     * @return Job
     */
    public function setUseLocalPermissions($useLocalPermissions)
    {
        $this->useLocalPermissions = $useLocalPermissions;
        return $this;
    }

    /**
     * Get useLocalPermissions
     *
     * @return boolean
     */
    public function getUseLocalPermissions()
    {
        return $this->useLocalPermissions;
    }
}