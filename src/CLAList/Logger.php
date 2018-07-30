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

        	$item = array_pop($params);
        	if ($item === null) {
		        $item = "null";
	        } else {
		        if ($item instanceof \DateTime) {
			        //Fucking Christ
			        $item = $item->format("c");
		        } else {
			        try {
				        $item = (string)$item;
			        } catch (\Exception $e) {
				        $item = "";
			        }
		        }

		        $item = "'" . addslashes($item) . "'";
	        }

        	$sql = substr_replace($sql, $item, $last, 1);
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
