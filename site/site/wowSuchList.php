<?php

require_once dirname(dirname(__DIR__)) . '/bootstrap.php';

$em = GetEntityManager();
echo("Found " . count($em->getRepository('CLAList\Model\Entity\Mission')->findAll()) . " missions\n");
