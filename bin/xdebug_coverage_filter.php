<?php

xdebug_set_filter(
    XDEBUG_FILTER_CODE_COVERAGE,
    XDEBUG_PATH_INCLUDE,
    [ str_replace('bin', 'src', __DIR__ . DIRECTORY_SEPARATOR) ]
);
