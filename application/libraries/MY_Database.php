<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Database customizations for this project
 *
 * @package    PluginDir
 * @subpackage libraries
 * @author     l.m.orchard <lorchard@mozilla.com>
 */
class Database extends Database_Core {

	/**
	 * Selects the or where(s) for a database query.
     *
     * Tweaked to wrap the set of OR clauses in parentheses, AND'ed with the 
     * rest of the where clauses.  Only tested with MySQL so far.
	 *
	 * @param   string|array  key name or array of key => value pairs
	 * @param   string        value to match with key
	 * @param   boolean       disable quoting of WHERE clause
	 * @return  Database_Core        This Database object.
	 */
	public function orwhere($key, $value = NULL, $quote = TRUE)
	{
		$quote = (func_num_args() < 2 AND ! is_array($key)) ? -1 : $quote;
		if (is_object($key))
		{
			$keys = array((string) $key => '');
		}
		elseif ( ! is_array($key))
		{
			$keys = array($key => $value);
		}
		else
		{
			$keys = $key;
		}

        $sub_where = array();
		foreach ($keys as $key => $value)
		{
			$key         = (strpos($key, '.') !== FALSE) ? $this->config['table_prefix'].$key : $key;
			$sub_where[] = $this->driver->where($key, $value, 'OR ', count($sub_where), $quote);
		}
        $this->where[] =
            ( count($this->where) ? 'AND ' : '' ) . 
            '( ' .  implode(' ', $sub_where) .  ' )';

		return $this;
    }

}
