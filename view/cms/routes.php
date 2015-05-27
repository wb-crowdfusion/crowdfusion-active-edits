<?php

$routes = array (
    '/(?P<Aspect>active-edits)/total-members?' => array (
        'action' => 'activeeditheartbeat-total-members-action',
    ),

    '/(?P<Aspect>active-edits)/(?P<slug>[A-Za-z0-9\-\.]+)/?' => array (
        'action' => 'activeeditheartbeat-get-members-action',
    ),
);
