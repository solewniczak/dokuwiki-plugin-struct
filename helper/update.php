<?php
use dokuwiki\plugin\struct\meta\AccessTable;
use dokuwiki\plugin\struct\meta\AccessTableLookup;
use dokuwiki\plugin\struct\meta\Schema;
use dokuwiki\plugin\struct\meta\StructException;

/**
 * Update struct date using bureaucracy forms
 *
 */

class helper_plugin_struct_update extends helper_plugin_bureaucracy_action {
    /**
     * Performs struct_lookup action
     *
     * @param helper_plugin_bureaucracy_field[] $fields  array with form fields
     * @param string $thanks  thanks message
     * @param array  $argv    array with entries: pageid/rowid
     * @return array|mixed
     *
     * @throws Exception
     */
    public function run($fields, $thanks, $argv) {
        global $ID;

        list($page_row_id) = $argv;
        $page_row_id = trim($page_row_id);
        if (!$page_row_id) {
            $page_row_id = $ID;
        } else {
            // perform replacements
            $this->prepareFieldReplacements($fields);
            $page_row_id = $this->replace($page_row_id);
        }

        // get all struct values and their associated schemas
        $tosave = [];
        foreach($fields as $field) {
            if(!is_a($field, 'helper_plugin_struct_field')) continue;
            /** @var helper_plugin_struct_field $field */
            $tbl = $field->column->getTable();
            $lbl = $field->column->getLabel();
            if(!isset($tosave[$tbl])) $tosave[$tbl] = [];
            $tosave[$tbl][$lbl] = $field->getParam('value');
        }

        /** @var \helper_plugin_struct $helper */
        $helper = plugin_load('helper', 'struct');
        $page = cleanID($page_row_id);

        try {
            if (page_exists($page)) {
                $helper->saveData($page_row_id, $tosave);
            } else {
                // we assume that we have only one lookup
                if (count($tosave) > 1) {
                    throw new Exception('Only one lookup table per struct_update action allowed.');
                }
                $table = key($tosave);
                $data = current($tosave);
                $schema = new Schema($table, 0, true);
                $access = new AccessTableLookup($schema, $page_row_id);
                $helper->saveLookupData($access, $data);
            }
        } catch(Exception $e) {
            msg($e->getMessage(), -1);
            return false;
        }

        // set thank you message
        if(!$thanks) {
            $thanks = sprintf($this->getLang('bureaucracy_action_struct_lookup_thanks'), wl($ID));
        } else {
            $thanks = hsc($thanks);
        }

        return $thanks;
    }
}
