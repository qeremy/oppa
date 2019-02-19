<?php
/**
 * Copyright (c) 2015 Kerem Güneş
 *
 * MIT License <https://opensource.org/licenses/mit>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
declare(strict_types=1);

namespace Oppa;

/**
 * @package Oppa
 * @object  Oppa\Mapper
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final class Mapper
{
    /**
     * Type constants.
     * @const string
     */
    public const
        // int
        DATA_TYPE_INT        = 'int',
        DATA_TYPE_BIGINT     = 'bigint',
        DATA_TYPE_TINYINT    = 'tinyint',
        DATA_TYPE_SMALLINT   = 'smallint',
        DATA_TYPE_MEDIUMINT  = 'mediumint',
        DATA_TYPE_INTEGER    = 'integer',
        DATA_TYPE_SERIAL     = 'serial',
        DATA_TYPE_BIGSERIAL  = 'bigserial',
        // float
        DATA_TYPE_FLOAT      = 'float',
        DATA_TYPE_DOUBLE     = 'double',
        DATA_TYPE_DOUBLEP    = 'double precision',
        DATA_TYPE_DECIMAL    = 'decimal',
        DATA_TYPE_REAL       = 'real',
        DATA_TYPE_NUMERIC    = 'numeric',
        // boolean
        DATA_TYPE_BOOLEAN    = 'boolean',
        // bit
        DATA_TYPE_BIT        = 'bit';

    /**
     * Map.
     * @var array
     */
    protected $map = [];

    /**
     * Map options.
     * @var array
     */
    protected $mapOptions = [
        'bool' => false, // converts bits & tinyints to booleans
    ];

    /**
     * Constructor.
     * @param array $mapOptions
     */
    public function __construct(array $mapOptions = [])
    {
        $this->setMapOptions($mapOptions);
    }

    /**
     * Set map.
     * @param  array $map
     * @return void
     */
    public function setMap(array $map): void
    {
        $this->map = $map;
    }

    /**
     * Get map.
     * @return array
     */
    public function getMap(): array
    {
        return $this->map;
    }

    /**
     * Set map options.
     * @param  array $mapOptions
     * @return void
     */
    public function setMapOptions(array $mapOptions): void
    {
        $this->mapOptions = array_merge($this->mapOptions, $mapOptions);
    }

    /**
     * Get map options.
     * @return array
     */
    public function getMapOptions(): array
    {
        return $this->mapOptions;
    }

    /**
     * Map (given data by key).
     * @param  string $key Table name actually, @see Oppa\Query\Result\Mysql:process()
     * @param  array  $data
     * @return array
     */
    public function map(string $key, array $data): array
    {
        $fields = $this->map[$key] ?? null;
        if (empty($fields) || empty($data)) {
            return $data;
        }

        foreach ($fields as $fieldName => $fieldProperties) {
            foreach ($data as &$dat) {
                foreach ($dat as $name => $value) {
                    if ($name == $fieldName) { // match field?
                        if (is_array($dat)) {
                            $dat[$name] = $this->cast($value, $fieldProperties);
                        } elseif (is_object($dat)) {
                            $dat->{$name} = $this->cast($value, $fieldProperties);
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Cast (type-cast by data type).
     * @param  scalar|null $value
     * @param  array       $properties
     * @return scalar|null
     */
    public function cast($value, array $properties)
    {
        // nullable?
        if ($value === null && $properties['nullable']) {
            return $value;
        }

        $type = strtolower($properties['type']);

        // 1.000.000 iters
        // regexp-------7.442563
        // switch-------2.709796
        switch ($type) {
            case self::DATA_TYPE_INT:
            case self::DATA_TYPE_BIGINT:
            case self::DATA_TYPE_SMALLINT:
            case self::DATA_TYPE_MEDIUMINT:
            case self::DATA_TYPE_INTEGER:
            case self::DATA_TYPE_SERIAL:
            case self::DATA_TYPE_BIGSERIAL:
                return (int) $value;
            case self::DATA_TYPE_FLOAT:
            case self::DATA_TYPE_DOUBLE:
            case self::DATA_TYPE_DOUBLEP:
            case self::DATA_TYPE_DECIMAL:
            case self::DATA_TYPE_REAL:
            case self::DATA_TYPE_NUMERIC:
                return (float) $value;
            case self::DATA_TYPE_BOOLEAN:
                return ($value === 't') ? true : false;
        }

        if ($this->mapOptions['bool'] && $properties['length'] === 1) {
            if ($type == self::DATA_TYPE_TINYINT) {
                $value = (int) $value;
                if ($value === 0 || $value === 1) { // @important
                    $value = (bool) $value;
                }
            } elseif ($type == self::DATA_TYPE_BIT) {
                $value = (string) $value;
                if ($value === '0' || $value === '1') {
                    $value = (bool) $value; // @important
                }
            }
        } elseif ($type == self::DATA_TYPE_TINYINT) {
            // regular cast
            $value = (int) $value;
        }

        return $value;
    }
}
