<?php
/**
 * This file is part of
 * Kimai-i Open Source Time Tracking // https://github.com/claude-ws01/Kimai-i
 * (c) 2015 Claude Nadon  https://github.com/claude-ws01
 * (c) 2006-2009 Kimai-Development-Team // http://www.kimai.org
 *
 * Kimai-i is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; Version 3, 29 June 2007
 *
 * Kimai-i is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Kimai; If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Provides functions for printing the permission hierarchy in HTML.
 *
 */
class Zend_View_Helper_EchoHierarchy extends Zend_View_Helper_Abstract
{

    /**
     * @brief Print nested fieldsets for the permissions hierarchy.
     *
     * @param array   $kga          the Kimaii global Array, necessary for translations
     * @param array   $keyHierarchy the key hierarchy, see parseHierarchy
     * @param array   $parentKeys   all keys of the parents, the closest one at the end
     * @param integer $level        the level in the hierarchy
     */
    public function echoHierarchy($keyHierarchy, array $parentKeys = array(), $level = 0)
    {
        global $kga;

        $originalLevel     = $level;
        $noLegendOnLevel[] = 0;


        // If the hierarchy only contains one key that key is "jumped" to simplify the displayed hierarchy.
        while ($this->isJumpable($keyHierarchy)) {
            $keys         = array_keys($keyHierarchy);
            $parentKeys[] = $keys[0];
            $level++;
            $keyHierarchy = $keyHierarchy[$keys[0]];
        }


        // fieldset name (customer, project, activity...)
        $fieldset_id = null;
        if ($level > 0) {

            // find if were in last level. Set as level 9 for css.
            $cssLevel = null;
            reset($keyHierarchy);
            if (is_array(current($keyHierarchy)) || count($keyHierarchy) === 1) {
                $cssLevel = $level;
            }
            else {
                foreach ($keyHierarchy as $val) {
                    if (is_array($val)) {
                        $cssLevel = $level;
                    }
                }
            }
            $cssLevel = $cssLevel ?: 9;

            $fieldset_id = implode('__', $parentKeys);
            echo "<fieldset id=\"{$fieldset_id}\" class=\"hierarchyLevel{$cssLevel}\">";


            $names = array();
            for ($i = max(0, $originalLevel - 1), $n = count($parentKeys); $i < $n; $i++) {
                if (in_array($i, $noLegendOnLevel, false) !== false) {
                    continue;
                }

                $name = $parentKeys[$i];
                if (isset($kga['dict']['permissions'][$name])) {
                    $name = $kga['dict']['permissions'][$name];
                }
                if (isset($kga['dict'][$name])) {
                    $name = $kga['dict'][$name];
                }
                $names[] = $name;
            }

            echo '<legend> ' . implode(', ', $names) . ' </legend>';

        }

        $nb_keys    = 0;
        $nb_checked = 0;

        // each permission in 1 fieldset (add, edit, delete...)
        foreach ($keyHierarchy as $key => $subKeys) {
            if (is_array($subKeys)) {
                continue;
            }

            $permissionKey = empty($parentKeys) ? $key : implode('__', $parentKeys) . '__' . $key;

            $name = $key;
            if (isset($kga['dict']['permissions'][$name])) {
                $name = $kga['dict']['permissions'][$name];
            }
            elseif (isset($kga['dict'][$name])) {
                $name = $kga['dict'][$name];
            }

            $checkedAttribute = '';
            if ((int)$subKeys === 1) {
                $checkedAttribute = 'checked = "checked"';
                $nb_checked++;
            }

            //CN..a little help to developpers!
            if (IN_DEV) {
                echo "<span class=\"permission\"><input type=\"checkbox\" value=\"1\" name=\"{$permissionKey}\"
                    title=\"{$permissionKey}\" {$checkedAttribute}/>{$name}</span>";
            }
            else {
                echo "<span class=\"permission\"><input type=\"checkbox\" value=\"1\" name=\"{$permissionKey}\" {$checkedAttribute}/>{$name}</span>";
            }
            $nb_keys++;
        }


        // 'all' make all ON or OFF
        if ($level > 0 && ($nb_keys > 1 || ($nb_keys === 0 && count($keyHierarchy) > 1))) {

            $checkedAttribute = '';
            if ($nb_keys > 1 && $nb_keys === $nb_checked) {
                $checkedAttribute = 'checked = "checked"';
            }

            $name          = 'all';

            echo "<span class=\"permission_all\"><input onchange=\"adm_ext_permissionChangeAll(this)\" type=\"checkbox\" value=\"1\" name=\"{$fieldset_id}\" {$checkedAttribute}/>{$name}</span>";
        }


        foreach ($keyHierarchy as $key => $subKeys) {
            if (!is_array($subKeys)) {
                continue;
            }

            $newParentKeys   = $parentKeys;
            $newParentKeys[] = $key;

            $this->echoHierarchy($subKeys, $newParentKeys, $level + 1);
        }

        if ($level > 0) {
            echo '</fieldset>';
        }
    }


    /**
     * @brief Decide if a hierarchy step can be jumped.
     *
     * A hierarchy step can be jumped if there is only one item on the current level and
     * at least one item on the next level in the hierarchy. This effectivly combines several
     * levels of hierarchy if they are only used for structure and not to provide several permissions
     * on the same level.
     *
     * @param array $keyHierarchy the hierarchy of keys, see parseHierarchy
     *
     * @return boolean true if this level can be jumped, false otherwise
     */
    private function isJumpable($keyHierarchy)
    {
        if (count($keyHierarchy) !== 1) {
            return false;
        }

        $keys   = array_keys($keyHierarchy);
        $values = $keyHierarchy[$keys[0]];
        if (!is_array($values)) {
            return false;
        }

        return true;
    }
} 
