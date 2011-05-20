<?php

# Convenience function to generate a __ object.
# Due to the different types of data, always use this function to
# create a new instance.
function __ (/*(number) or (range_lower, range_upper) or (range_lower, range_upper, step) or (anything else)*/) {
    $arguments = func_get_args();
    if (1 === count($arguments)) {
        if (is_numeric($arguments[0])) {
            return __(1, $arguments[0]);
        } else {
           return new __($arguments[0]);
        }
    } else if (2 == count($arguments)) {
        return __($arguments[0], $arguments[1], 1);
    } else if (3 === count($arguments)) {
        $array = array();
        for ($i = $arguments[0]; $i <= $arguments[1]; $i = $i + $arguments[2]) {
            $array []= $i;
        }
        return __($array);
    }

    throw new Exception('Incorrect arguments');
}

class __ implements Countable, ArrayAccess, IteratorAggregate {
    public static function __callStatic ($method, $arguments = array()) {
        $object = __(array_shift($arguments));
        return call_user_func_array(array($object, $method), $arguments);
    }

    # A holder array for all the methods that get attached to __.
    public static $methods = array();

    # Adds a method to the class. Signature for $callable is
    # function ($target, $method_args...)
    public static function add_method ($name, $returns, $callable) {
        static::$methods[$name] = array(
            'returns' => $returns,
            'callable' => $callable,
        );
    }

    # Allows a method to have different names.
    public static function alias_method($original_name, $aliased_name) {
        static::$methods[$aliased_name] =& static::$methods[$original_name];
    }

    ############# Countable
    public function count () {
        return count($this->target);
    }
    ############ /Countable

    ############ ArrayAccess
    public function offsetExists ($offset) {
        return isset($this->target[$offset]);
    }

    public function offsetGet ($offset) {
        return $this->target[$offset];
    }

    public function offsetSet ($offset, $value) {
        if (is_null($offset)) {
            $this->target []= $value;
        } else {
            $this->target[$offset] = $value;
        }
    }

    public function offsetUnset ($offset) {
        unset ($this->target[$offset]);
    }
    ############ /ArrayAccess

    ############ IteratorAggregate
    public function getIterator () {
        return new ArrayIterator($this->target);
    }
    ############ /IteratorAggregate

    public function __construct ($target) {
        $this->target = $target;
    }

    public function __get ($attribute) {
        return $this->$attribute();
    }

    public function __call ($method_name, $arguments = array()) {
        $method = static::$methods[$method_name];
        array_unshift($arguments, $this->target);

        if ($method['returns'] === 'returns nothing') {
            call_user_func_array($method['callable'], $arguments);
            return $this;
        } else if ($method['returns'] === 'returns value') {
            return call_user_func_array($method['callable'], $arguments);
        } else if ($method['returns'] === 'returns collection') {
            return __(call_user_func_array($method['callable'], $arguments));
        } else {
            throw new Exception("__#{$method_name} has an invalid 'returns' parameter: '{$method['returns']}'");
        }
    }

    public function value () {
        return $this->target;
    }
}

__::add_method('each', 'returns nothing', function ($target, $callable) {
    foreach ($target as $item) {
        call_user_func($callable, $item);
    }
});
__::alias_method('each', 'for_each');

__::add_method('each_with_key', 'returns nothing', function ($target, $callable) {
    foreach ($target as $key => $item) {
        call_user_func($callable, $key, $item);
    }
});
__::alias_method('each_with_key', 'for_each_with_key');
__::alias_method('each_with_key', 'each_with_index');
__::alias_method('each_with_key', 'for_each_with_index');

__::add_method('reverse', 'returns collection', function ($target) {
    return array_reverse($target);
});
__::add_method('reverse_with_keys', 'returns collection', function ($target) {
    return array_reverse($target, true);
});

__::add_method('map', 'returns collection', function ($target, $callable) {
    $retval = array();
    foreach ($target as $k => $v) {
        $retval[$k] = call_user_func($callable, $v);
    }
    return $retval;
});
__::alias_method('map', 'collect');

__::add_method('map_with_key', 'returns collection', function ($target, $callable) {
    $retval = array();
    foreach ($target as $k => $v) {
        $retval[$k] = call_user_func($callable, $k, $v);
    }
    return $retval;
});
__::alias_method('map_with_key', 'map_with_index');

__::add_method('times', 'returns nothing', function ($target, $callable) {
    for ($i = 0; $i < target($target); $i++) {
        call_user_func($callable);
    }
});

__::add_method('is_null', 'returns value', function ($target) {
    return is_null($target);
});

__::add_method('keys', 'returns value', function ($target) {
    return array_keys($target);
});

__::add_method('values', 'returns value', function ($target) {
    return array_values($target);
});

__::add_method('is_callable', 'returns value', function ($target) {
    return is_callable($target);
});

__::add_method('detect', 'returns value', function ($target, $callable) {
    foreach ($target as $item) {
        if (call_user_func($item)) {
            return $item;
        }
    }
});

__::add_method('select', 'returns collection', function ($target, $callable) {
    $retval = array();
    foreach ($target as $value) {
        if (call_user_func($callable, $value)) {
            $retval []= $value;
        }
    }
    return $retval;
});
__::alias_method('select', 'filter');

__::add_method('select_with_key', 'returns collection', function ($target, $callable) {
    $retval = array();
    foreach ($target as $key => $value) {
        if (call_user_func($callable, $key, $value)) {
            $retval[$key] = $value;
        }
    }
    return $retval;
});
__::alias_method('select_with_key', 'select_with_index');
__::alias_method('select_with_key', 'filter_with_key');
__::alias_method('select_with_key', 'filter_with_index');

__::add_method('reject', 'returns collection', function ($target, $callable) {
    $retval = array();
    foreach ($target as $value) {
        if ( ! call_user_func($callable, $value)) {
            $retval []= $value;
        }
    }
    return $retval;
});;

__::add_method('reject_with_key', 'returns collection', function ($target, $callable) {
    $retval = array();
    foreach ($target as $key => $value) {
        if ( ! call_user_func($callable, $key, $value)) {
            $retval[$key] = $value;
        }
    }
    return $retval;
});;
__::alias_method('reject_with_key', 'reject_with_index');
