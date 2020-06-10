<?php

if (!function_exists('human_file_size')) {
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
