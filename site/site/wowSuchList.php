<?php

require_once dirname(dirname(__DIR__)) . '/bootstrap.php';

$em = GetEntityManager();
echo("Found " . count($em->getRepository('CLAList\Entity\Mission')->findAll()) . " missions\n");
