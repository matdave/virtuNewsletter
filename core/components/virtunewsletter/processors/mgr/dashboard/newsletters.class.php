<?php
/**
 * virtuNewsletter
 *
 * Copyright 2013-2019 by goldsky <goldsky@virtudraft.com>
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
class VirtuNewsletterDashboardNewslettersProcessor extends modProcessor
{

    public function process()
    {
        $output = array(
            'emails_queueing'    => $this->modx->getCount('vnewsReports', array('status' => 'queue')),
            'upcoming_schedules' => $this->modx->getCount('vnewsNewsletters', array('scheduled_for:>=' => time(), 'is_active:=' => 1)),
        );

        return $this->success('', $output);
    }
}

return "VirtuNewsletterDashboardNewslettersProcessor";
