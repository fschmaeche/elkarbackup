<?php

namespace App\Listener;

use App\Entity\Script;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;

class ScriptListener
{
    private string $uploadDir;

    public function __construct(string $uploadDir)
    {
        $this->uploadDir = $uploadDir;
    }

    /** @ORM\PostLoad */
    public function postLoadHandler(Script $script, LifecycleEventArgs $event): void
    {
        $script->setScriptDirectory($this->uploadDir);
    }
}

