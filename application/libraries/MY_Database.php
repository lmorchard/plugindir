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
     * Runs a query into the driver and returns the result.
     *
     * @param   string  SQL query to execute
     * @return  Database_Result
     */
    public function query($sql = '')
    {
        if ($sql == '') return FALSE;

        if (!preg_match('#\b(?:INSERT|UPDATE|REPLACE|SET|DELETE|TRUNCATE)\b#i', $sql))
        {
            // Use shadow read database
        }

        // No link? Connect!
        $this->link or $this->connect();

        // Start the benchmark
        $start = microtime(TRUE);

        if (func_num_args() > 1) //if we have more than one argument ($sql)
        {
            $argv = func_get_args();
            $binds = (is_array(next($argv))) ? current($argv) : array_slice($argv, 1);
        }

        // Compile binds if needed
        if (isset($binds))
        {
            $sql = $this->compile_binds($sql, $binds);
        }

        // Fetch the result
        $result = $this->driver->query($this->last_query = $sql);

        // Stop the benchmark
        $stop = microtime(TRUE);

        if ($this->config['benchmark'] == TRUE)
        {
            // Benchmark the query
            Database::$benchmarks[] = array('query' => $sql, 'time' => $stop - $start, 'rows' => count($result));
        }

        return $result;
    }

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
