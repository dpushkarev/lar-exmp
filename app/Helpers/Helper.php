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



