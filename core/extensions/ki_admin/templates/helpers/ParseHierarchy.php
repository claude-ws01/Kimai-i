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
 * Provides functions for parsing the hierarchy of permissions and printing them in HTML.
 *
 */
class Zend_View_Helper_ParseHierarchy extends Zend_View_Helper_Abstract
{

    /**
     * @brief Parse the hierarchy of permissions.
     *
     *  All permissions are split at dashes. From those parts two hierearchies are built.
     *
     * @param array $permissions  list of permission names
     * @param array $extensions   will contain all extensions for which an access permission key exists
     * @param array $keyHierarchy will contain all other permissions in a hierarchy split by the dash (-)
     */
    public function parseHierarchy($permissions, &$extensions, &$keyHierarchy)
    {

        foreach ($permissions as $key => $value) {

            $keyParts = explode('__', $key);

            if (count($keyParts) === 2 && $keyParts[1] === 'access') {
                $extensions [$keyParts[0]] = $value;
                continue;
            }
            $currentHierarchyLevel = &$keyHierarchy;

            foreach ($keyParts as $keyPart) {
                if (!array_key_exists($keyPart, $currentHierarchyLevel)) {
                    $currentHierarchyLevel[$keyPart] = array();
                }
                $currentHierarchyLevel =      &$currentHierarchyLevel[$keyPart];
            }

            $currentHierarchyLevel = $value;

        }
    }
} 
