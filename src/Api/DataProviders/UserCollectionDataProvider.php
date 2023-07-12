<?php

namespace App\Api\DataProviders;


use ApiPlatform\Doctrine\Orm\Extension\QueryResultCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use App\Entity\User;
use App\Service\LoggerService;
use App\Service\RouterService;
use Doctrine\ORM\EntityManagerInterface;

class UserCollectionDataProvider
{
    private iterable $collectionExtensions;
    private EntityManagerInterface $entityManager;
    private LoggerService $logger;
    private RouterService $router;

    /**
     * Constructor
     */
    public function __construct(EntityManagerInterface $entityManager, LoggerService $logger, RouterService $router, iterable $collectionExtensions)
    {
        $this->collectionExtensions = $collectionExtensions;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->router = $router;

    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = [])
    {
        $repository = $this->entityManager->getRepository('App:User');
        $query = $repository->createQueryBuilder('c')->addOrderBy('c.id', 'ASC');
        $queryNameGenerator = new QueryNameGenerator();

        foreach ($this->collectionExtensions as $extension) {
            $extension->applyToCollection($query, $queryNameGenerator, $resourceClass, $operationName);
            if ($extension instanceof QueryResultCollectionExtensionInterface && $extension->supportsResult($resourceClass, $operationName)) {
                return $extension->getResult($query, $resourceClass, $operationName);
            }
        }
        $this->logger->debug(
            'View users',
            array(),
            array('link' => $this->router->generateUrl('showUsers'))
        );
        $this->entityManager->flush();

        return $query->getQuery()->getResult();
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return User::class === $resourceClass;
    }
}

