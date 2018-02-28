<?php

namespace EricNewbury\DanceVT\Models\Repository;


use Doctrine\ORM\EntityRepository;

class PermissionRequestRepository extends EntityRepository
{
    public function getIncomplete(){
        $qb = $this->createQueryBuilder('r');
         $qb->where($qb->expr()->isNull('r.completed'));
            return $qb->getQuery()->getResult();
    }
}