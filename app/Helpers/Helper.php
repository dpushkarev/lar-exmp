<?php

if (!function_exists('cartesianArray')) {
    function cartesianArray($array)
    {
        if ($array) {
            if ($u = array_pop($array))
                foreach (cartesianArray($array) as $p)
                    foreach ($u as $v)
                        yield $p + [count($p) => $v];
        } else
            yield[];
    }
}

if (!function_exists('getClassName')) {
    function getClassName($className)
    {
        return (new \ReflectionClass($className))->getShortName();
    }
}

if (!function_exists('getXmlAttribute')) {
    function getXmlAttribute($object, $attribute)
    {
        if (isset($object[$attribute]))
            return (string)$object[$attribute];
    }
}

if (!function_exists('getUniqueCode')) {
    function getUniqueCode($length)
    {
       return bin2hex(random_bytes($length));
    }
}

if (!function_exists('convertToEngChar')) {
    function convertToEngChar($string)
    {
        return str_replace(
            ['Ć', 'Č', 'Ž', 'Đ', 'Š', 'š', 'đ', 'ž', 'č', 'ć'],
            ['C', 'C', 'Z', 'D', 'S', 's', 'd', 'z', 'c', 'c'],
            $string
        );
    }
}



