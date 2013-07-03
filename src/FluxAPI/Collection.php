<?php
namespace FluxAPI;

use Traversable;

abstract class Collection implements \IteratorAggregate, \ArrayAccess, \Countable
{
    const ORDER_ASCENDING = 'asc';
    const ORDER_DESCENDING = 'desc';

    protected $_items = array();

    /**
     * Prepares an item for injection into the collection
     *
     * @param mixed $item
     * @return $this
     */
    protected function _prepareItem($item)
    {
        return $this;
    }

    /**
     * Checks if a given variable is a collection of the same class or subclass
     *
     * @param mixed $var
     * @return bool
     */
    public static function isInstance($var)
    {
        $self_class = get_called_class();
        return (is_object($var) && (get_class($var) == $self_class || is_subclass_of($var, $self_class)));
    }

    public function __construct(array $items = array())
    {
        $this->_items = $items;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->_items);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return isset($this->_items[$offset]);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->_items[$offset]);
    }

    /**
     * Return item at specific index
     *
     * @param int $index
     * @return mixed
     */
    public function get($index)
    {
        return $this->_items[$index];
    }

    /**
     * Set item at specific index
     *
     * @param int $index
     * @param mixed $item
     * @return $this
     */
    public function set($index, $item)
    {
        if (is_object($item)) {
            $this->_prepareItem($item);
        }

        $this->_items[$index] = $item;

        return $this;
    }

    /**
     * Returns the first item in the collection that has a given property with a given value
     *
     * @param string $property
     * @param mixed $value
     * @return null|object
     */
    public function findFirstBy($property, $value)
    {
        foreach($this->_items as $item) {
            if (is_object($item) && isset($item->$property)) {
                if (is_object($value) && $item->property === $value) {
                    return $item;
                }
                elseif ($item->$property == $value) {
                    return $item;
                }
            }
        }

        return null;
    }

    /**
     * Return a new collection with all items that have a given property with a given value
     *
     * @param string $property
     * @param mixed $value
     * @return Collection
     */
    public function findBy($property, $value)
    {
        return $this->filterBy($property, $value);
    }

    /**
     * Returns a subset of items in a new collection filtered by a callback
     *
     * See: http://de1.php.net/manual/en/function.array-filter.php
     *
     * @param $callback
     * @return Collection
     */
    public function filter($callback)
    {
        return new $this(array_filter($this->_items, $callback));
    }

    /**
     * Returns a subset of items in a new collection filtered by the value of a given property
     *
     * @param string $property
     * @param mixed $value
     * @return Collection
     */
    public function filterBy($property, $value)
    {
        return $this->filter(function($item) use ($property, $value) {
            if (is_string($property) && is_object($item) && isset($item->$property)) {
                if (is_object($value)) {
                    return $item->$property === $value;
                }
                else {
                    return $item->$property == $value;
                }
            }

            return false;
        });
    }

    /**
     * Counts the number of items in the collection
     *
     * @return int
     */
    public function count()
    {
        return count($this->_items);
    }

    /**
     * Appends an item to the collection
     *
     * @param mixed $item
     * @return $this
     */
    public function push($item)
    {
        if (is_object($item)) {
            $this->_prepareItem($item);
        }

        array_push($this->_items, $item);

        return $this;
    }

    public function pop()
    {
        return array_pop($this->_items);
    }

    /**
     * Merges another collection
     *
     * See: http://php.net/manual/en/function.array-merge.php
     *
     * @param Collection $collection
     * @return $this
     */
    public function merge(Collection $collection)
    {
        $this->_items = array_merge($this->_items, $collection->toArray());

        return $this;
    }

    /**
     * Removes item at specific index
     *
     * @param int $index
     * @return $this
     */
    public function removeAt($index)
    {
        array_splice($this->_items, $index, 1);

        return $this;
    }

    /**
     * Returns the index of an item
     *
     * @param mixed $item
     * @return int|string
     */
    public function indexOf($item)
    {
        if (is_object($item)) {
            $strict = true;

        }
        else {
            $strict = false;
        }

        $index = array_search($item, $this->_items, $strict);

        if ($index !== false)
        {
            return $index;
        }
        else {
            return -1;
        }
    }

    /**
     * Removes an item from the collection
     *
     * @param mixed $item
     * @return $this
     */
    public function remove($item)
    {
        $index = $this->indexOf($item);

        if ($index != -1) {
            unset($this->_items[$index]);
        }

        return $this;
    }

    /**
     * Inset an item in the collection at a specific index
     *
     * @param int $index
     * @param mixed $item
     * @return $this
     */
    public function insertAt($index, $item)
    {
        $index = (int) $index;

        if (is_object($item)) {
            $this->_prepareItem($item);
        }

        array_splice($this->_items, $index, 1, $item);

        return $this;
    }

    /**
     * Sort Collection with a callback
     *
     * See: http://www.php.net/manual/en/function.usort.php
     *
     * @param callable $callback
     * @return $this
     */
    public function sort($callback)
    {
        usort($this->_items, $callback);

        return $this;
    }

    /**
     * Sort Collection by a given property
     *
     * @param string $property
     * @param mixed $value
     * @param string $order Collection::ORDER_ASCENDING or Collection::ORDER_DESCENDING
     * @return $this
     */
    public function sortBy($property, $value, $order = Collection::ORDER_ASCENDING)
    {
        return $this->sort(function($a, $b) use ($property, $value, $order) {
            if (is_object($a) && is_object($b) && isset($a->$property) && isset($b->$property)) {

                switch($order) {
                    case Collection::ORDER_ASCENDING:
                        return ($a->$property < $b->$property) ? -1 : 1;
                        break;

                    case Collection::ORDER_DESCENDING:
                        return ($a->$property > $b->$property) ? -1 : 1;
                        break;
                }
            }

            return 0;
        });
    }

    /**
     * Removes all items from the collection
     */
    public function clear()
    {
        $this->_items = array();
    }

    /**
     * Returns an array represantation of this collection
     *
     * @param bool $recursive if true the items will be converted to an array to (as long as they have a toArray() method)
     * @return array
     */
    public function toArray($recursive = false)
    {
        if ($recursive) {
            $_items = array();

            foreach($this->_items as $key => $item) {
                if (is_object($item) && method_exists($item, 'toArray')) {
                    $_items[$key] = $item->toArray();
                }
                else {
                    $_items[$key] = $item;
                }
            }

            return $_items;
        }
        else {
            return $this->_items;
        }
    }

    /**
     * @return string
     */
    public function toString()
    {
        $str = '';
        foreach($this->_items as $key => $item) {
            $str .= $key . ' => ' . $item . "\n";
        }

        return $str;
    }

    function __toString()
    {
        return $this->toString();
    }
}