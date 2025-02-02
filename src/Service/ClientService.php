<?php

namespace App\Service;


use App\Entity\Message;
use App\Exception\PermissionException;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ClientService
{
    private AuthorizationCheckerInterface $authChecker;
    private EntityManagerInterface $em;
    private LoggerService $logger;
    private RouterService $router;
    private Security $security;

    public function __construct(EntityManagerInterface $em, AuthorizationCheckerInterface $authChecker, LoggerService $logger, Security $security, RouterService $router)
    {
        $this->em = $em;
        $this->authChecker = $authChecker;
        $this->logger = $logger;
        $this->router = $router;
        $this->security = $security;
    }

    private function assertPermission($idClient): void
    {
        $repository = $this->em->getRepository('App:Client');
        $client = $repository->find($idClient);

        if ($client->getOwner() == $this->security->getToken()->getUser() ||
            $this->authChecker->isGranted('ROLE_ADMIN')) {
            return;
        }
        throw new PermissionException("Unable to delete client: Permission denied.");
    }

    /**
     * @throws PermissionException
     * @throws Exception
     */
    public function delete($id): void
    {
        $this->assertPermission($id);
        $repository = $this->em->getRepository('App:Client');
        $client = $repository->find($id);
        $queue = $this->em->getRepository('App:Queue')->findAll();
        foreach ($queue as $item) {
            if ($item->getJob()->getClient()->getId() == $id) {
                $this->logger->err('Could not delete client %clientName%, it has jobs enqueued.', array(
                    '%clientName%' => $client->getName()
                ), array(
                    'link' => $this->router->generateClientRoute($id)
                ));
                throw new Exception("Could not delete client, it has jobs enqueued.");
            }
        }

        // try {
        $this->em->remove($client);
        $msg = new Message('DefaultController', 'TickCommand', json_encode(array(
            'command' => "elkarbackup:delete_job_backups",
            'client' => (int)$id
        )));
        $this->em->persist($msg);
        $this->em->flush();
        $this->logger->info('Client "%clientid%" deleted', array(
            '%clientid%' => $id
        ), array(
            'link' => $this->router->generateClientRoute($id)
        ));
    }

    /**
     * @throws Exception
     */
    public function save($client): void
    {
        if (isset($jobsToDelete)) {
            foreach ($jobsToDelete as $idJob => $job) {
                $client->getJobs()->removeElement($job);
                $this->em->remove($job);
                $msg = new Message('DefaultController', 'TickCommand', json_encode(array(
                    'command' => "elkarbackup:delete_job_backups",
                    'client' => (int)$client->getId(),
                    'job' => $idJob
                )));
                $this->em->persist($msg);
                $this->logger->info('Delete client %clientid%, job %jobid%', array(
                    '%clientid%' => $client->getId(),
                    '%jobid%' => $job->getId()
                ), array(
                    'link' => $this->router->generateJobRoute($job->getId(), $client->getId())
                ));
            }
        }
        $clientName = $client->getName();
        $repository = $this->em->getRepository('App:Client');
        $existingClient = $repository->findOneBy(['name' => $clientName]);
        if (null != $existingClient) {
            if ($existingClient->getId() !== $client->getId()) {
                throw new Exception("Client name " . $clientName . " already exists");
            }
        }
        if ($client->getOwner() == null) {
            $client->setOwner($this->security->getToken()
                ->getUser());
        }

        if ($client->getMaxParallelJobs() < 1) {
            throw new Exception('Max parallel jobs parameter should be positive integer');
        }
        $this->em->persist($client);
        $this->em->flush();
        $this->logger->info('Save client %clientid%', array(
            '%clientid%' => $client->getId()
        ), array(
            'link' => $this->router->generateClientRoute($client->getId())
        ));
    }
}

