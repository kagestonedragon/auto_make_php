<?php

function sortDirectories($a, $b) {
    if (is_string($a) && is_int($b)) {
        return (1);
    } else if (is_string($b) && is_int($a)) {
        return (-1);
    } else {
        return (0);
    }
}