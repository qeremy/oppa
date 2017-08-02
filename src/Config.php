<?php
/**
 * Copyright (c) 2015 Kerem Güneş
 *    <k-gun@mail.com>
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
declare(strict_types=1);

namespace Oppa;

/**
 * @package Oppa
 * @object  Oppa\Config
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final class Config implements \ArrayAccess
{
    /**
     * Options stack.
     * @var array
     */
    private $options = [];

    /**
     * Constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        if (!empty($options)) {
            foreach ($options as $key => $value) {
                $this->options[$key] = $value;
            }
        }
    }

    /**
     * Set an option.
     * @param  string $key
     * @param  any    $value
     * @return self
     */
    public function __set(string $key, $value): self
    {
        return $this->set($key, $value);
    }

    /**
     * Get an option.
     * @param  string $key
     * @return any
     */
    public function __get(string $key)
    {
        return $this->get($key);
    }


    /**
     * Check an option.
     * @param  string $key
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return isset($this->options[$key]);
    }

    /**
     * Remove an option.
     * @param  string $key
     * @return void
     */
    public function __unset(string $key): void
    {
        unset($this->options[$key]);
    }


    /**
     * Set an option.
     * @param  string $key
     * @param  any    $value
     * @return self
     */
    public function set(string $key, $value): self
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * Get an option.
     * @param  string $key
     * @param  any    $value
     * @return any
     */
    public function get(string $key, $value = null)
    {
        if ($this->__isset($key)) {
            $value = $this->options[$key];
        }

        return $value;
    }

    /**
     * Set an option.
     * @param  int|string $key
     * @param  any $value
     * @return self
     */
    public function offsetSet($key, $value): self
    {
        return $this->set($key, $value);
    }

    /**
     * Get an option.
     * @param  int|string $key
     * @return any
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Check an option.
     * @param  int|string $key
     * @return bool
     */
    public function offsetExists($key): bool
    {
        return $this->__isset($key);
    }

    /**
     * Remove an option.
     * @param  any $key
     * @return void
     */
    public function offsetUnset($key): void
    {
        $this->__unset($key);
    }

    /**
     * Get all options as array.
     * @return array
     */
    public function toArray(): array
    {
        return $this->options;
    }
}
