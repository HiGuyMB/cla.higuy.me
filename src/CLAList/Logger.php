<?php
namespace CLAList;

use Doctrine\DBAL\Logging\SQLLogger;

class Logger implements SQLLogger {

	/**
	 * Logs a SQL statement somewhere.
	 *
	 * @param string     $sql    The SQL to be executed.
	 * @param array|null $params The SQL parameters.
	 * @param array|null $types  The SQL parameter types.
	 *
	 * @return void
	 */
	public function startQuery($sql, array $params = null, array $types = null) {
		$pos = 0;
        while (count($params)) {
        	$last = strrpos($sql, "?", $pos);
        	if ($last === false)
        		break;
        	$sql = substr_replace($sql, array_pop($params), $last, 1);
        	$pos = $last - strlen($sql);
        }
		echo("Query: $sql\n");
	}

	/**
	 * Marks the last started query as stopped. This can be used for timing of queries.
	 *
	 * @return void
	 */
	public function stopQuery() {
	}
}
