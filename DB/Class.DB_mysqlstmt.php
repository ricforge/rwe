<?php

/*

RWE

Copyright (C) 2004, 2005 Riku Nurminen

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

require_once(RWE_DIR . 'DB' . DIRECTORY_SEPARATOR . 'Class.DB_sqlstmt.php');

/**
 * A class holding a reusable query prepared with DB_mysql::prepare.
 *
 * @remarks See the
 * <a href="http://www.rakkis.net/web/rwe/manual/">RWE User manual</a>
 * chapter
 * <a href="http://www.rakkis.net/web/rwe/manual/chap4/">4. Database managers</a>
 * for details on this class and the database managers in general.
 *
 * @author This class is originally based on the DB_mysqlstmt class
 * found in "Advanced PHP Programming" by George Schlossnagle.
 *
 * @sa DB_mysql
 */
class DB_mysqlstmt extends DB_sqlstmt
{
    /*! @publicsection */
    /* ====================================================================== */

    /** Constructor.
     *
     * @remarks Never called directly by the user; use DB_mysql::prepare
     * to prepare a query and return an instance of this class.
     * 
     * @param dbmgr The database manager object that constructed this
     * statement.
     * @param query The query string.
     *
     * @sa DB_mysql::prepare
     */
    function DB_mysqlstmt(&$dbmgr, $query)
    {
        $this->DB_sqlstmt($dbmgr, $query);
    }

    /** 
     * Executes this statement.
     *
     * @remarks Accepts variable amount of arguments; the arguments passed
     * in should correspond to the "placeholder" variables in the query.
     * 
     * @return $this is returned on success, false on failure.
     *
     * @sa DB_mysql
     */
    function execute()
    {
        $args  = func_get_args();

        // Expand placeholders in query
        $query = parent::getExpandedQuery($args);

        // Capture time now
        $mtime  = explode(' ', microtime());
        $tstart = (float)$mtime[1] + (float)$mtime[0];

        // Execute query
        $this->mResult = mysql_query($query, $this->mDBH);
        if(!$this->mResult) {
            return false;
        }

        // Calc time the query took to execute
        $mtime    = explode(' ', microtime());
        $tend     = (float)$mtime[1] + (float)$mtime[0];
        $exectime = (float)$tend - (float)$tstart;

        $this->mDbMgr->_incrementSQLTime($exectime);
        $this->mDbMgr->_incrementExecutedQueries();

        return $this;
    }

    /** 
     * Fetch the next row from this statement (DB_mysqlstmt::execute must
     * be called first!).
     * 
     * @remarks Actually just a wrapper around PHP's
     * <a href="http://www.php.net/mysql_fetch_row">mysql_fetch_row()</a>.
     *
     * @return The next row from the query, or false if there are no more rows
     * or if this statement doesn't have a valid result resource (ie. it hasn't
     * been executed yet).
     */
    function fetchRow() { return mysql_fetch_row($this->mResult); }

    /** 
     * Fetch the next row from this statement as an associative array
     * (DB_mysqlstmt::execute must be called first!).
     * 
     * @remarks Actually just a wrapper around PHP's
     * <a href="http://www.php.net/mysql_fetch_assoc">mysql_fetch_assoc()</a>.
     *
     * @return The next row from the query, or false if there are no more rows
     * or if this statement doesn't have a valid result resource (ie. it hasn't
     * been executed yet).
     */
    function fetchAssoc() { return mysql_fetch_assoc($this->mResult); }

    /** 
     * Fetch all rows from this statement as an associative array
     * (DB_mysqlstmt::execute must be called first!).
     * 
     * @remarks Calls PHP's
     * <a href="http://www.php.net/mysql_fetch_assoc">mysql_fetch_assoc()</a>,
     * collects each resulting row into an array and returns that array.
     *
     * @return The rows from the query, or an empty array if there are no more
     * rows (ie. if DB_mysqlstmt::fetchRow or
     * DB_mysqlstmt::fetchAssoc has already been called to fetch all rows)
     * or if this statement doesn't have a valid result resource (ie. it hasn't
     * been executed yet).
     */
    function fetchAllAssoc()
    {
        $retval = array();
        while($row = $this->fetchAssoc()) {
            $retval[] = $row;
        }
        return $retval;
    }

    /** 
     * Get the number of rows returned by this statement.
     *
     * @note The statement must be an executed SELECT statement!
     */
    function numRows() { return mysql_num_rows($this->mResult); }

    /** 
     * Get the number of rows affected by the last INSERT, UPDATE or DELETE
     * query.
     * 
     * @note If you are using transactions, you need to call this after your
     * INSERT, UPDATE, or DELETE query, <b>not</b> after the commit.
     * @note If the last query was a DELETE query with no WHERE clause, all of the
     * records will have been deleted from the table but this function will return
     * zero.
     */
    function numAffectedRows() { return mysql_affected_rows($this->mDBH); }
}

?>