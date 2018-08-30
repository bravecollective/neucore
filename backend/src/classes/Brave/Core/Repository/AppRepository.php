<?php declare(strict_types=1);

namespace Brave\Core\Repository;

use Brave\Core\Entity\App;

/**
 * AppRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @method App|null find($id, $lockMode = null, $lockVersion = null)
 * @method App|null findOneBy(array $criteria, array $orderBy = null)
 * @method App[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AppRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Constructor that makes this class autowireable.
     */
    public function __construct(\Doctrine\ORM\EntityManagerInterface $em)
    {
        parent::__construct($em, $em->getClassMetadata(App::class));
    }
}