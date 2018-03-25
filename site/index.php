<?php

require_once dirname(__DIR__) . "/bootstrap.php";

$em = GetEntityManager();
$builder = $em->createQueryBuilder();
$query = $builder
	->select('m.id')
	->from('CLAList\Entity\Mission', 'm')
	->orderBy('RAND()', 'ASC')
	->getQuery()
    ->setMaxResults(1)
;
$random = $query->getSingleScalarResult();

?>

<h1>Pretend there's a frontend here.</h1><br>
So I may have nuked the old backend to this.
Fancy new frontend can be seen <a href="missionList.php">here</a> if you really care though.<br>
If you're feeling lucky, try a <a href="api/getMissionZip.php?id=<?= $random ?>">random mission</a>.
Who knows, it may actually be a good one.
