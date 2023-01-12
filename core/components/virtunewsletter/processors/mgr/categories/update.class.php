<?php

/**
 * virtuNewsletter
 *
 * Copyright 2013-2023 by goldsky <goldsky@virtudraft.com>
 *
 * This file is part of virtuNewsletter, a newsletter system for MODX
 * Revolution.
 *
 * virtuNewsletter is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation version 3,
 *
 * virtuNewsletter is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * virtuNewsletter; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 */

/**
 * @package virtunewsletter
 * @subpackage processor
 */
class CategoriesUpdateProcessor extends modObjectUpdateProcessor {

    public $classKey = 'vnewsCategories';
    public $languageTopics = array('virtunewsletter:cmp');
    public $objectType = 'virtunewsletter.CategoriesUpdate';

    /**
     * {@inheritDoc}
     * @return boolean
     */
    public function initialize() {
        $name = $this->getProperty('name');
        if (empty($name)) {
            $this->addFieldError('name', $this->modx->lexicon('virtunewsletter.category_err_ns_name'));
            return FALSE;
        }

        return parent::initialize();
    }

    /**
     * Override in your derivative class to do functionality after save() is run
     * @return boolean
     */
    public function afterSave() {
        $catId = $this->getProperty('id');

        $usergroups = array();
        $cbUsergroups = $this->getProperty('usergroups');
        if (!empty($cbUsergroups)) {
            foreach($cbUsergroups as $k => $v) {
                if ($v > 0) {
                    $usergroups[] = $k;
                }
            }
        }

        if (!empty($usergroups)) {
            // remove diff first
            $diffs = $this->modx->getCollection('vnewsCategoriesHasUsergroups', array(
                'category_id:=' => $catId,
                'usergroup_id:NOT IN' => $usergroups,
            ));
            if ($diffs) {
                foreach ($diffs as $diff) {
                    $diff->remove();
                }
            }

            $addUsergroups = array();
            foreach ($usergroups as $usergroup) {
                if (empty($usergroup)) {
                    continue;
                }
                $catHasUg = $this->modx->getObject('vnewsCategoriesHasUsergroups', array(
                    'category_id' => $catId,
                    'usergroup_id' => $usergroup,
                ));
                if (!$catHasUg) {
                    $catHasUg = $this->modx->newObject('vnewsCategoriesHasUsergroups');
                    $catHasUg->fromArray(array(
                        'category_id' => $catId,
                        'usergroup_id' => $usergroup,
                            ));
                    $addUsergroups[] = $catHasUg;
                }
            }
            if (!empty($addUsergroups)) {
                $this->object->addMany($addUsergroups);
                $this->object->save();
            }
        } else {
            $olds = $this->modx->getCollection('vnewsCategoriesHasUsergroups', array(
                'category_id' => $catId,
            ));
            if ($olds) {
                foreach ($olds as $old) {
                    $old->remove();
                }
            }
        }

        return true;
    }

}

return 'CategoriesUpdateProcessor';
