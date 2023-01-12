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
class SubscribersBatchUpdateProcessor extends modProcessor {

    /** @var xPDOObject|modAccessibleObject $object The object being grabbed */
    public $object;

    /** @var string $objectType The object "type", this will be used in various lexicon error strings */
    public $objectType = 'virtunewsletter.SubscribersBatchUpdateProcessor';

    /** @var string $classKey The class key of the Object to iterate */
    public $classKey = 'vnewsSubscribers';

    /** @var string $primaryKeyField The primary key field to grab the object by */
    public $primaryKeyField = 'id';

    /** @var string $permission The Permission to use when checking against */
    public $permission = '';

    /** @var array $languageTopics An array of language topics to load */
    public $languageTopics = array('virtunewsletter:cmp');

    /**
     * {@inheritDoc}
     * @return boolean
     */
    public function initialize() {
        $props = $this->getProperties();

        if (empty($props['subscriberIds'])) {
            return $this->modx->lexicon('virtunewsletter.newsletter_err_ns_resource_id');
        }

        return parent::initialize();
    }

    function process() {
        $props = $this->getProperties();

        $subscriberIds = array_map('trim', @explode(',', $props['subscriberIds']));
        $cbCategories = $this->getProperty('categories');
        $categories = array();
        foreach($cbCategories as $k => $v) {
            if(empty($v)) {
                continue;
            }
            $categories[] = $k;
        }

        $emailProvider = $this->getProperty('email_provider', false);
        $deleteEmailProvider = $this->getProperty('delete_email_provider', false);
        $active = $this->getProperty('active', false);
        foreach ($subscriberIds as $subscriberId) {
            if(empty($subscriberId)) {
                continue;
            }
            $subscriber = $this->modx->getObject('vnewsSubscribers', $subscriberId);
            if (!$subscriber) {
                continue;
            }
            if (!empty($categories)) {
                // diff
                $this->modx->removeCollection('vnewsSubscribersHasCategories', array(
                    'subscriber_id:=' => $subscriberId,
                    'category_id:NOT IN' => $categories
                ));
                foreach ($categories as $category) {
                    $subscriber->setCategory($category);
                }
            }
            $saveObj = false;
            if (!empty($deleteEmailProvider)) {
                $subscriber->set('email_provider', null);
                $saveObj = true;
            } elseif(!empty($emailProvider)) {
                $subscriber->set('email_provider', $emailProvider);
                $saveObj = true;
            }
            if (!empty($active)) {
                if ($active === '1') {
                    $subscriber->set('is_active', 1);
                    $this->modx->virtunewsletter->addSubscriberQueues($subscriberId);
                    $saveObj = true;
                } elseif ($active === '2') {
                    $subscriber->set('is_active', 0);
                    $this->modx->virtunewsletter->removeSubscriberQueues($subscriberId);
                    $saveObj = true;
                }
            }
            if ($saveObj) {
                $subscriber->save();
            }
        }

        return $this->success();
    }

}

return 'SubscribersBatchUpdateProcessor';
