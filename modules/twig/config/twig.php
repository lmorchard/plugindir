<?php
/**
 * Configuration for the Twig view module
 *
 * @package    Twig_Module
 * @subpackage config
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
$config['extension'] = 'html';
$config['template_path'] = APPPATH.'views';
$config['cache'] = APPPATH.'cache/twig';
$config['auto_reload'] = TRUE;
$config['trim_blocks'] = TRUE;
