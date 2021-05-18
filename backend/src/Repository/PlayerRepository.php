<?php

declare(strict_types=1);

namespace Neucore\Repository;

use Doctrine\ORM\EntityRepository;
use Neucore\Entity\Player;

/**
 * PlayerRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @method Player|null find($id, $lockMode = null, $lockVersion = null)
 * @method Player|null findOneBy(array $criteria, array $orderBy = null)
 * @method Player[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PlayerRepository extends EntityRepository
{
    /**
     * @return Player[]
     */
    public function findWithoutCharacters(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.characters', 'c')
            ->andWhere('c.id IS NULL')
            ->orderBy('p.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Player[]
     */
    public function findWithCharacters(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.characters', 'c')
            ->andWhere('c.id IS NOT NULL')
            ->orderBy('p.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Player[]
     */
    public function findWithCharactersAndStatus(string $status): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.characters', 'c')
            ->andWhere('c.id IS NOT NULL')
            ->andWhere('p.status = :status')
            ->setParameter('status', $status)
            ->orderBy('p.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Player[]
     */
    public function findWithInvalidToken(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.characters', 'c')
            ->andWhere('c.id IS NOT NULL')
            ->andWhere('c.validToken = :valid_token')
            ->setParameter('valid_token', false)
            ->orderBy('p.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Player[]
     */
    public function findWithNoToken(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.characters', 'c')
            ->andWhere('c.id IS NOT NULL')
            ->andWhere('c.validToken IS NULL')
            ->orderBy('p.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Player[]
     */
    public function findInCorporation(int $corporationId): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.characters', 'c')
            ->andWhere('c.id IS NOT NULL')
            ->andWhere('c.corporation = :corp')
            ->setParameter('corp', $corporationId)
            ->orderBy('p.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int[] $groupIds
     * @return Player[]
     */
    public function findWithGroups(array $groupIds): array
    {
        $qb = $this->createQueryBuilder('p');
        return $qb
            ->leftJoin('p.groups', 'g')
            ->where($qb->expr()->in('g.id', ':ids'))
            ->orderBy('p.id')
            ->setParameter('ids', $groupIds)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Player[]
     */
    public function findWithRole(int $roleId): array
    {
        $qb = $this->createQueryBuilder('p');
        return $qb
            ->leftJoin('p.roles', 'r')
            ->where($qb->expr()->eq('r.id', ':id'))
            ->orderBy('p.id')
            ->setParameter('id', $roleId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Return all players who have characters in one of the provided corporation
     * and do not belong to one of the provided players.
     *
     * @param array $corporationIds Player accounts with characters in these corporations
     * @param Player[] $players Exclude these players
     * @return Player[]
     */
    public function findInCorporationsWithExcludes(array $corporationIds, array $players): array
    {
        if (empty($corporationIds)) {
            return [];
        }

        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.characters', 'c');

        $qb->andWhere($qb->expr()->in('c.corporation', ':corporationIds'))
            ->setParameter('corporationIds', $corporationIds);

        if (! empty($players)) {
            $qb->andWhere($qb->expr()->notIn('p.id', ':playerIds'))
                ->setParameter('playerIds', $players);
        }

        $qb->orderBy('p.name');

        return $qb->getQuery()->getResult();
    }

    /**
     * Return all players who have characters that are not in NPC corporations,
     * not in a corporation from the provided list
     * and are not one of the provided players.
     *
     * @param array $corporationIds Exclude these corporations
     * @param Player[] $players Exclude these players
     * @return Player[]
     */
    public function findNotInNpcCorporationsWithExcludes(array $corporationIds, array $players): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.characters', 'c');

        $qb->andWhere($qb->expr()->gt('c.corporation', ':npc'))
            ->setParameter('npc', 2000000);

        if (! empty($corporationIds)) {
            $qb->andWhere($qb->expr()->notIn('c.corporation', ':corporationIds'))
                ->setParameter('corporationIds', $corporationIds);
        }

        if (! empty($players)) {
            $qb->andWhere($qb->expr()->notIn('p.id', ':playerIds'))
                ->setParameter('playerIds', $players);
        }

        $qb->orderBy('p.name');

        return $qb->getQuery()->getResult();
    }

    public function findCharacters(string $nameOrId, bool $currentOnly): array
    {
        // current characters
        $query1 = $this
            ->createQueryBuilder('p')
            ->select(
                'c.id AS character_id',
                'c.name AS character_name',
                'p.id AS player_id',
                'p.name AS player_name',
            )
            ->leftJoin('p.characters', 'c')
            ->where('c.name LIKE :name')
            ->orWhere('c.id = :id')
            ->orderBy('c.name', 'ASC')
            ->setParameter('name', "%$nameOrId%")
            ->setParameter('id', $nameOrId);

        if (!$currentOnly) {
            // removed characters
            $query2 = $this
                ->createQueryBuilder('p')
                ->select(
                    'rc.characterId AS character_id',
                    'rc.characterName AS character_name',
                    'p.id AS player_id',
                    'p.name AS player_name',
                )
                ->distinct()
                ->leftJoin('p.removedCharacters', 'rc')
                ->where('rc.characterName LIKE :name')
                ->orderBy('rc.characterName', 'ASC')
                ->setParameter('name', "%$nameOrId%");

            // character name changes
            $query3 = $this
                ->createQueryBuilder('p')
                ->select(
                    'c.id AS character_id',
                    #'IDENTITY(ccn.character) AS character_id',
                    'ccn.oldName AS character_name',
                    'p.id AS player_id',
                    'p.name AS player_name',
                )
                ->leftJoin('p.characters', 'c')
                ->leftJoin('c.characterNameChanges', 'ccn')
                ->where('ccn.oldName LIKE :name')
                ->orderBy('ccn.oldName', 'ASC')
                ->setParameter('name', "%$nameOrId%");
        }

        $result = $query1->getQuery()->getResult();
        if (!$currentOnly) {
            /* @phan-suppress-next-line PhanPossiblyUndeclaredVariable */
            $result = array_merge($result, $query2->getQuery()->getResult(), $query3->getQuery()->getResult());
        }

        if (!$currentOnly) {
            uasort($result, function ($a, $b) {
                $nameA = mb_strtolower($a['character_name']);
                $nameB = mb_strtolower($b['character_name']);
                $playerNameA = mb_strtolower($a['player_name']);
                $playerNameB = mb_strtolower($b['player_name']);
                if ($nameA < $nameB) {
                    return -1;
                } elseif ($nameA > $nameB) {
                    return 1;
                } elseif ($playerNameA < $playerNameB) {
                    return -1;
                } elseif ($playerNameA > $playerNameB) {
                    return 1;
                }
                return 0;
            });
        }

        return array_map(function ($row) {
            return [
                'character_id' => (int) $row['character_id'],
                'character_name' => $row['character_name'],
                'player_id' => $row['player_id'],
                'player_name' => $row['player_name'],
            ];
        }, array_values($result));
    }

    public function findPlayersOfCharacters(array $characterIds): array
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('p.id')->distinct()
            ->leftJoin('p.characters', 'c')
            ->where($qb->expr()->in('c.id', ':ids'))
            ->setParameter('ids', $characterIds);

        return array_map(function (array $char) {
            return (int) $char['id'];
        }, $qb->getQuery()->getResult());
    }
}
