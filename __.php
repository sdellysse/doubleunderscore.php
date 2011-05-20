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
    # function ($subject, $method_args...)
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
        return count($this->subject);
    }
    ############ /Countable

    ############ ArrayAccess
    public function offsetExists ($offset) {
        return isset($this->subject[$offset]);
    }

    public function offsetGet ($offset) {
        return $this->subject[$offset];
    }

    public function offsetSet ($offset, $value) {
        if (is_null($offset)) {
            $this->subject []= $value;
        } else {
            $this->subject[$offset] = $value;
        }
    }

    public function offsetUnset ($offset) {
        unset ($this->subject[$offset]);
    }
    ############ /ArrayAccess

    ############ IteratorAggregate
    public function getIterator () {
        return new ArrayIterator($this->subject);
    }
    ############ /IteratorAggregate

    public function __construct ($subject) {
        $this->subject = $subject;
    }

    public function __get ($attribute) {
        return $this->$attribute();
    }

    public function __call ($method_name, $arguments = array()) {
        $method = static::$methods[$method_name];
        array_unshift($arguments, $this->subject);

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
        return $this->subject;
    }
}

__::add_method('compact', 'returns collection', function ($subject) {
    $retval = array();
    foreach ($subject as $value) {
        if ($value) {
            $retval []= $value;
        }
    }
    return $value;
});

__::add_method('compact_with_keys', 'returns collection', function ($subject) {
    $retval = array();
    foreach ($subject as $key => $value) {
        if ($value) {
            $retval[$key] = $value;
        }
    }
    return $value;
});

__::add_method('detect', 'returns value', function ($subject, $callable) {
    foreach ($subject as $item) {
        if (call_user_func($item)) {
            return $item;
        }
    }
});

__::add_method('each', 'returns nothing', function ($subject, $callable) {
    foreach ($subject as $item) {
        call_user_func($callable, $item);
    }
});
__::alias_method('each', 'for_each');

__::add_method('each_with_key', 'returns nothing', function ($subject, $callable) {
    foreach ($subject as $key => $item) {
        call_user_func($callable, $key, $item);
    }
});
__::alias_method('each_with_key', 'each_with_index');
__::alias_method('each_with_key', 'for_each_with_index');
__::alias_method('each_with_key', 'for_each_with_key');

__::add_method('first', 'returns value', function ($subject) {
    return array_shift($subject);
});
__::alias_method('first', 'head');

__::add_method('first_with_key', 'returns value', function ($subject) {
    $keys = array_keys($subject);
    $values = array_values($subject);
    return array(
        array_shift($keys) => array_shift($values),
    );
});
__::alias_method('first_with_key', 'head_with_key');

__::add_method('is_callable', 'returns value', function ($subject) {
    return is_callable($subject);
});

__::add_method('is_null', 'returns value', function ($subject) {
    return is_null($subject);
});

__::add_method('keys', 'returns collection', function ($subject) {
    return array_keys($subject);
});


__::add_method('last', 'returns value', function ($subject) {
    return array_pop($subject);
});

__::add_method('last_with_key', 'returns value', function ($subject) {
    $keys = array_keys($subject);
    $values = array_values($subject);
    return array(
        array_pop($keys) => array_pop($values),
    );
});

__::add_method('map', 'returns collection', function ($subject, $callable) {
    $retval = array();
    foreach ($subject as $v) {
        $retval []= call_user_func($callable, $v);
    }
    return $retval;
});
__::alias_method('map', 'collect');

__::add_method('map_with_key', 'returns collection', function ($subject, $callable) {
    $retval = array();
    foreach ($subject as $k => $v) {
        $retval[$k] = call_user_func($callable, $k, $v);
    }
    return $retval;
});
__::alias_method('map_with_key', 'collect_with_index');
__::alias_method('map_with_key', 'collect_with_key');
__::alias_method('map_with_key', 'map_with_index');

__::add_method('reject', 'returns collection', function ($subject, $callable) {
    $retval = array();
    foreach ($subject as $value) {
        if ( ! call_user_func($callable, $value)) {
            $retval []= $value;
        }
    }
    return $retval;
});;

__::add_method('reject_with_key', 'returns collection', function ($subject, $callable) {
    $retval = array();
    foreach ($subject as $key => $value) {
        if ( ! call_user_func($callable, $key, $value)) {
            $retval[$key] = $value;
        }
    }
    return $retval;
});;
__::alias_method('reject_with_key', 'reject_with_index');

__::add_method('rest', 'returns collection', function ($subject) {
    array_shift($subject);
    return array_values($subject);
});
__::alias_method('rest', 'tail');

__::add_method('rest_with_keys', 'returns collection', function ($subject) {
    array_shift($subject);
    return $subject;
});
__::add_method('rest_with_keys', 'tail_with_keys');

__::add_method('reverse', 'returns collection', function ($subject) {
    return array_reverse($subject);
});
__::add_method('reverse_with_keys', 'returns collection', function ($subject) {
    return array_reverse($subject, true);
});

__::add_method('select', 'returns collection', function ($subject, $callable) {
    $retval = array();
    foreach ($subject as $value) {
        if (call_user_func($callable, $value)) {
            $retval []= $value;
        }
    }
    return $retval;
});
__::alias_method('select', 'filter');

__::add_method('select_with_key', 'returns collection', function ($subject, $callable) {
    $retval = array();
    foreach ($subject as $key => $value) {
        if (call_user_func($callable, $key, $value)) {
            $retval[$key] = $value;
        }
    }
    return $retval;
});
__::alias_method('select_with_key', 'filter_with_index');
__::alias_method('select_with_key', 'filter_with_key');
__::alias_method('select_with_key', 'select_with_index');

__::add_method('times', 'returns nothing', function ($subject, $callable) {
    for ($i = 0; $i < subject($subject); $i++) {
        call_user_func($callable);
    }
});

__::add_method('values', 'returns collection', function ($subject) {
    return array_values($subject);
});

__::add_method('without', 'returns collection', function (/* $subject, $values... */) {
    $arguments = func_get_args();
    $subject = array_shift($arguments);
    $skip_values = $arguments;

    $retval = array();
    foreach ($subject as $value) {
        $skip_this_value = false;
        foreach ($values as $skip_value) {
            if ($value === $skip_value) {
                $skip_this_value = true;
            }
        }
        if ($skip_this_value) {
            continue;
        } else {
            $retval []= $value;
        }
    }

    return $retval;
});

__::add_method('without_with_keys', 'returns collection', function (/* $subject, $values... */) {
    $arguments = func_get_args();
    $subject = array_shift($arguments);
    $skip_values = $arguments;

    $retval = array();
    foreach ($subject as $key => $value) {
        $skip_this_value = false;
        foreach ($values as $skip_value) {
            if ($value === $skip_value) {
                $skip_this_value = true;
            }
        }
        if ($skip_this_value) {
            continue;
        } else {
            $retval[$key] = $value;
        }
    }

    return $retval;
});
