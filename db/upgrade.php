<?php

function xmldb_ptogo_upgrade($oldversion=0) {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager();

        if ($oldversion < 2018100401) {


        // Define field showinlisting to be added to ptogo.
        $table = new xmldb_table('ptogo');
        $field = new xmldb_field('showinlisting', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'introformat');

        // Conditionally launch add field showinlisting.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ptogo savepoint reached.
        upgrade_mod_savepoint(true, 2018100401, 'ptogo');
    }
return true;
}

