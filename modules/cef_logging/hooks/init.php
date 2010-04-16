<?php
/**
 * Initialization hook
 *
 * @package    cef_logging
 * @subpackage hooks
 * @author     l.m.orchard <lorchard@mozilla.com>
 */
Event::add('system.ready', array('cef_logging', 'init'));
