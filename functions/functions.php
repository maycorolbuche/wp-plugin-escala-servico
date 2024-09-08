<?php
// don't load directly
if (!defined('ABSPATH')) {
    die('-1');
}

function escsrv_generate_uuid()
{
    return uniqid();
}
