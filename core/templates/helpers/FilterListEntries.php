<?php
/**
 * This file is part of
 * Kimai-i Open Source Time Tracking // https://github.com/cloudeasy/Kimai-i
 * (c) 2015 Claude Nadon
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
 * Filters the given list entries.
 *
 * @author Kevin Papst
 */
class Zend_View_Helper_FilterListEntries extends Zend_View_Helper_Abstract
{
    /**
     * @param array $entries
     * @param bool $filterHidden
     * @return array
     */
    public function filterListEntries($entries, $filterHidden = true)
    {
        if (!is_array($entries) || count($entries) == 0) {
            return array();
        }

        $listEntries = array();
        foreach($entries as $row) {
            if ($filterHidden && isset($row['visible']) && !$row['visible']) {
                continue;
            }
            $listEntries[] = $row;
        }

        return $listEntries;
    }
} 
