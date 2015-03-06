<?php
/**
 * Copyright (c) 2015 Kerem Gunes
 *    <http://qeremy.com>
 *
 * GNU General Public License v3.0
 *    <http://www.gnu.org/licenses/gpl-3.0.txt>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Oppa\Database\Query;

use \Oppa\Helper;
use \Oppa\Database\Connector\Connection;
use \Oppa\Exception\Database as Exception;

/**
 * @package    Oppa
 * @subpackage Oppa\Database\Query
 * @object     Oppa\Database\Query\Builder
 * @uses       Oppa\Helper, Oppa\Exception\Database, Oppa\Database\Connector\Connection
 * @version    v1.0
 * @author     Kerem Gunes <qeremy@gmail>
 */
final class Builder
{
    /**
     * And/or operators.
     * @const string
     */
    const OP_OR = 'OR', OP_AND = 'AND';

    /**
     * Asc/desc operators.
     * @const string
     */
    const OP_ASC = 'ASC', OP_DESC = 'DESC';

    /**
     * Query stack.
     * @var array
     */
    private $query = [];

    /**
     * Query string.
     * @var string
     */
    private $queryString = '';

    /**
     * Target table for query.
     * @var string
     */
    private $table;

    /**
     * Database connection.
     * @var Oppa\Database\Connector\Connection
     */
    private $connection;

    /**
     * Create a fresh Query object.
     *
     * @param Oppa\Database\Connector\Connection $connection
     */
    final public function __construct(Connection $connection = null) {
        if ($connection) {
            $this->setConnection($connection);
        }
    }

    /**
     * Shortcut for debugging.
     *
     * @return string
     */
    final public function __toString() {
        return $this->toString();
    }

    /**
     * Set connection.
     *
     * @param  Oppa\Database\Connector\Connection $connection
     * @return void
     */
    final public function setConnection(Connection $connection) {
        $this->connection = $connection;
    }

    /**
     * Get connection.
     * @return Oppa\Database\Connector\Connection
     */
    final public function getConnection() {
        return $this->connection;
    }

    /**
     * Set target table for query.
     *
     * @param string $table
     * @return void
     */
    final public function setTable($table) {
        $this->table = $table;
    }

    /**
     * Get target table.
     *
     * @return string
     */
    final public function getTable() {
        return $this->table;
    }

    /**
     * Reset self vars.
     *
     * @return void
     */
    final public function reset() {
        $this->query = [];
        $this->queryString = '';
        return $this;
    }

    /**
     * Add select statement.
     *
     * @param  string $field
     * @return self
     */
    final public function select($field = null) {
        $this->reset();
        // pass for aggregate method, e.g select().aggregate('count', 'id')
        if (empty($field)) {
            $field = 1;
        }

        if (is_array($field)) {
            $field = join(', ', $field);
        }

        return $this->push('select', $field);
    }

    /**
     * Add insert statement.
     *
     * @param  array $data
     * @return self
     */
    final public function insert(array $data) {
        $this->reset();
        // simply check is not assoc to prepare multi-insert
        if (!isset($data[0])) {
            $data = [$data];
        }

        return $this->push('insert', $data, false);
    }

    /**
     * Add update statement.
     *
     * @param  array $data
     * @return self
     */
    final public function update(array $data) {
        $this->reset();

        return $this->push('update', $data, false);
    }

    /**
     * Add deletet statement.
     *
     * @return self
     */
    final public function delete() {
        $this->reset();

        return $this->push('delete', true, false);
    }

    /**
     * Add "JOIN" statement with "ON" keyword.
     *
     * @param  string $table To join.
     * @return self
     */
    final public function join($table, $on, array $params = null) {
        // Prepare params safely
        if (!empty($params)) {
            $on = $this->connection->getAgent()->prepare($on, $params);
        }

        return $this->push('join', sprintf('JOIN %s ON %s', $table, $on));
    }

    /**
     * Add "JOIN" statement with "USING" keyword.
     *
     * @param  string     $table  To join.
     * @param  string     $using
     * @param  array|null $params
     * @return self
     */
    final public function joinUsing($table, $using, array $params = null) {
        // Prepare params safely
        if (!empty($params)) {
            $using = $this->connection->getAgent()->prepare($using, $params);
        }

        return $this->push('join', sprintf('JOIN %s USING (%s)', $table, $using));
    }

    /**
     * Add "LEFT JOIN" statement with "ON" keyword.
     *
     * @param  string $table To join.
     * @return self
     */
    final public function joinLeft($table, $on, array $params = null) {
        // Prepare params safely
        if (!empty($params)) {
            $on = $this->connection->getAgent()->prepare($on, $params);
        }

        return $this->push('join', sprintf('LEFT JOIN %s ON %s', $table, $on));
    }

    /**
     * Add "LEFT JOIN" statement with "USING" keyword.
     *
     * @param  string     $table  To join.
     * @param  string     $using
     * @param  array|null $params
     * @return self
     */
    final public function joinLeftUsing($table, $using, array $params = null) {
        // Prepare params safely
        if (!empty($params)) {
            $using = $this->connection->getAgent()->prepare($using, $params);
        }

        return $this->push('join', sprintf('LEFT JOIN %s USING (%s)', $table, $using));
    }

    /**
     * Add "WHERE" statement.
     *
     * @param  string     $query
     * @param  array|null $params
     * @param  string     $op
     * @return self
     */
    final public function where($query, array $params = null, $op = self::OP_AND) {
        // prepare if params provided
        if (!empty($params)) {
            $query = $this->connection->getAgent()->prepare($query, $params);
        }

        // add and/or operator
        if (isset($this->query['where']) && !empty($this->query['where'])) {
            $query = sprintf('%s %s', $op, $query);
        }

        return $this->push('where', $query);
    }

    /**
     * Add "WHERE" statement for "LIKE" queries.
     *
     * @param  string     $query
     * @param  array|null $params
     * @param  string     $op
     * @return self
     */
    final public function whereLike($query, array $params = null, $op = self::OP_AND) {
        if (!empty($params)) {
            foreach ($params as &$param) {
                $charFirst = strval($param[0]);
                $charLast  = substr($param, -1);
                // both appended
                if ($charFirst == '%' && $charLast == '%') {
                    $param = $charFirst . addcslashes(substr($param, 1, -1), '%_') . $charLast;
                }
                // left appended
                elseif ($charFirst == '%') {
                    $param = $charFirst . addcslashes(substr($param, 1), '%_');
                }
                // right appended
                elseif ($charLast == '%') {
                    $param = addcslashes(substr($param, 0, -1), '%_') . $charLast;
                }
            }
        }

        return $this->where($query, $params, $op);
    }

    /**
     * Add "WHERE" statement for "IS NULL" queries.
     *
     * @param  string $field
     * @return self
     */
    final public function whereNull($field) {
        return $this->push('where', sprintf('%s IS NULL', $field));
    }

    /**
     * Add "WHERE" statement for "IS NOT NULL" queries.
     *
     * @param  string $field
     * @return self
     */
    final public function whereNotNull($field) {
        return $this->push('where', sprintf('%s IS NOT NULL', $field));
    }

    /**
     * Add "HAVING" statement.
     *
     * @param  string     $query
     * @param  array|null $params
     * @return self
     */
    final public function having($query, array $params = null) {
        // prepare if params provided
        if (!empty($params)) {
            $query = $this->connection->getAgent()->prepare($query, $params);
        }

        return $this->push('having', $query);
    }

    /**
     * Add "GROUP BY" statement.
     *
     * @param  string $field
     * @return self
     */
    final public function groupBy($field) {
        return $this->push('groupBy', $field);
    }

    /**
     * Add "ORDER BY" statement.
     *
     * @param  string $field
     * @param  string $op
     * @return self
     */
    final public function orderBy($field, $op = null) {
        // check operator is valid
        if ($op == self::OP_ASC || $op == self::OP_DESC) {
            return $this->push('orderBy', $field .' '. $op);
        }
        return $this->push('orderBy', $field);
    }

    /**
     * Add "LIMIT" statement.
     *
     * @param  integer $start
     * @param  integer $stop
     * @return self
     */
    final public function limit($start, $stop = null) {
        return ($stop === null)
            ? $this->push('limit', $start)
            : $this->push('limit', $start)->push('limit', $stop);
    }

    /**
     * Add a aggregate statement like "COUNT(*)" etc.
     *
     * @param  string      $aggr
     * @param  string      $field
     * @param  string|null $fieldAlias Used for "AS" keyword
     * @return self
     */
    final public function aggregate($aggr, $field = '*', $fieldAlias = null) {
        // if alias not provided
        if (empty($fieldAlias)) {
            $fieldAlias = ($field && $field != '*')
                // aggregate('count', 'id') count_id
                // aggregate('count', 'u.id') count_uid
                ? preg_replace('~[^\w]~', '', $aggr .'_'. $field) : $aggr;
        }

        return $this->push('aggregate', sprintf('%s(%s) %s',
            $aggr, $field, $fieldAlias
        ));
    }

    /**
     * Execute builded query.
     *
     * @param  callable $callback
     * @return mixed
     */
    final public function execute(callable $callback = null) {
        $result = $this->connection->getAgent()->query($this->toString());
        // Render result if callback provided
        if (is_callable($callback)) {
            $result = $callback($result);
        }

        return $result;
    }

    /**
     * Shortcut for select one operations.
     *
     * @param  callable|null $callback
     * @return mixed
     */
    final public function get(callable $callback = null) {
        $result = $this->connection->getAgent()->get($this->toString());
        if (is_callable($callback)) {
            $result = $callback($result);
        }

        return $result;
    }

    /**
     * Shortcut for select all operations.
     *
     * @param  callable|null $callback
     * @return mixed
     */
    final public function getAll(callable $callback = null) {
        $result = $this->connection->getAgent()->getAll($this->toString());
        if (is_callable($callback)) {
            $result = $callback($result);
        }

        return $result;
    }

    /**
     * Stringify query stack.
     *
     * @throws Oppa\Exception\Database\ErrorException
     * @return string
     */
    final public function toString() {
        // if any query
        if (!empty($this->query)) {
            if (empty($this->table)) {
                throw new Exception\ErrorException(
                    'Table is not defined yet! Call before setTable() to set target table.');
            }
            // reset query
            $this->queryString = '';

            // prapere for "SELECT" statement
            if (isset($this->query['select'])) {
                // add aggregate statements
                $aggregate = isset($this->query['aggregate'])
                    ? ', '. join(', ', $this->query['aggregate'])
                    : '';
                $this->queryString .= sprintf('SELECT %s%s FROM %s',
                    join(', ', $this->query['select']), $aggregate, $this->table);

                // add join statements
                if (isset($this->query['join'])) {
                    foreach ($this->query['join'] as $value) {
                        $this->queryString .= sprintf(' %s', $value);
                    }
                }

                // add where statement
                if (isset($this->query['where'])) {
                    $this->queryString .= sprintf(' WHERE %s', join(' ', $this->query['where']));
                }

                // add group by statement
                if (isset($this->query['groupBy'])) {
                    $this->queryString .= sprintf(' GROUP BY %s', join(', ', $this->query['groupBy']));
                }

                // add having statement
                if (isset($this->query['having'])) {
                    // use only first element of having for now..
                    $this->queryString .= sprintf(' HAVING %s', $this->query['having'][0]);
                }

                // add order by statement
                if (isset($this->query['orderBy'])) {
                    $this->queryString .= sprintf(' ORDER BY %s', join(', ', $this->query['orderBy']));
                }

                // add limit statement
                if (isset($this->query['limit'])) {
                    $this->queryString .= !isset($this->query['limit'][1])
                        ? sprintf(' LIMIT %d', $this->query['limit'][0])
                        : sprintf(' LIMIT %d,%d', $this->query['limit'][0], $this->query['limit'][1]);
                }
            }
            // prapere for "INSERT" statement
            elseif (isset($this->query['insert'])) {
                $agent = $this->connection->getAgent();
                if ($data = Helper::getArrayValue('insert', $this->query)) {
                    $keys = $agent->escapeIdentifier(array_keys(current($data)));
                    $values = [];
                    foreach ($data as $d) {
                        $values[] = '('. $agent->escape(array_values($d)) .')';
                    }

                    $this->queryString = sprintf(
                        "INSERT INTO {$this->table} ({$keys}) VALUES %s", join(', ', $values));
                }
            }
            // prapere for "UPDATE" statement
            elseif (isset($this->query['update'])) {
                $agent = $this->connection->getAgent();
                if ($data = Helper::getArrayValue('update', $this->query)) {
                    // prepare "SET" data
                    $set = [];
                    foreach ($data as $key => $value) {
                        $set[] = sprintf('%s = %s',
                            $agent->escapeIdentifier($key), $agent->escape($value));
                    }
                    // check again
                    if (!empty($set)) {
                        $this->queryString = sprintf(
                            "UPDATE {$this->table} SET %s", join(', ', $set));

                        // add criterias
                        if (isset($this->query['where'])) {
                            $this->queryString .= sprintf(' WHERE %s', join(' ', $this->query['where']));
                        }
                        if (isset($this->query['orderBy'])) {
                            $this->queryString .= sprintf(' ORDER BY %s', join(', ', $this->query['orderBy']));
                        }
                        if (isset($this->query['limit'])) {
                            $this->queryString .= sprintf(' LIMIT %d', $this->query['limit'][0]);
                        }
                    }
                }
            }
            // prapere for "DELETE" statement
            elseif (isset($this->query['delete'])) {
                $agent = $this->connection->getAgent();

                $this->queryString = "DELETE FROM {$this->table}";

                // add criterias
                if (isset($this->query['where'])) {
                    $this->queryString .= sprintf(' WHERE %s', join(' ', $this->query['where']));
                }
                if (isset($this->query['orderBy'])) {
                    $this->queryString .= sprintf(' ORDER BY %s', join(', ', $this->query['orderBy']));
                }
                if (isset($this->query['limit'])) {
                    $this->queryString .= sprintf(' LIMIT %d', $this->query['limit'][0]);
                }
            }
        }

        return trim($this->queryString);
    }

    /**
     * Push a statement and query into query stack.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  boolean $multi
     * @return self
     */
    final protected function push($key, $value, $multi = true) {
        if ($multi) {
            // Set query as sub array
            $this->query[$key][] = $value;
        } else {
            $this->query[$key] = $value;
        }

        return $this;
    }
}
