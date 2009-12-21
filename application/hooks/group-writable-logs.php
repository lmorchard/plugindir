<?php
function group_writable_logs_init()
{
    // HACK: Attempt to ensure log file is always group-writable
    @chmod(Kohana::log_directory().date('Y-m-d').'.log'.EXT, 0664);
}
Event::add('system.ready', 'group_writable_logs_init');
