<?php

require_once dirname(__DIR__) . '/bootstrrap.php';

$em = GetEntityManager();
echo("Found " . count($em->getRepository('CLAList\Entity\Mission')->findAll()) . " missions\n");
