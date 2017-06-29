<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Default settings maker.
 *
 * @package    block_xp
 * @copyright  2017 Branch Up Pty Ltd
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_xp\local\setting;
defined('MOODLE_INTERNAL') || die();

use admin_category;
use admin_settingpage;
use admin_externalpage;
use admin_setting_heading;
use admin_setting_configcheckbox;
use admin_setting_configselect;
use admin_setting_configtext;
use block_xp\local\config\config;
use block_xp\local\config\course_world_config;
use block_xp\local\routing\url_resolver;

/**
 * Default settings maker.
 *
 * @package    block_xp
 * @copyright  2017 Branch Up Pty Ltd
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class default_settings_maker implements settings_maker {

    /** @var config The config holding the defaults. */
    protected $defaults;
    /** @var url_resolver The URL resolver. */
    protected $urlresolver;

    /**
     * Constructor.
     *
     * @param config $defaults The config object to get the defaults from.
     * @param url_resolver $urlresolver The URL resolver.
     */
    public function __construct(config $defaults, url_resolver $urlresolver) {
        $this->defaults = $defaults;
        $this->urlresolver = $urlresolver;
    }

    /**
     * Get the settings.
     *
     * @param environment $env The environment for creating the settings.
     * @return part_of_admin_tree|null
     */
    public function get_settings(environment $env) {
        $catname = 'block_xp_category';
        $plugininfo = $env->get_plugininfo();

        // Create a category to hold different pages.
        $settings = new admin_category($catname, $plugininfo->displayname);

        // Block are given a generic settings page.
        // We rename it, add it to the category, and populate it.
        $settingspage = $env->get_settings_page();
        $settingspage->visiblename = get_string('generalsettings', 'admin');
        $settings->add($catname, $settingspage);
        if ($env->is_full_tree()) {
            array_map(function($setting) use ($settingspage) {
                $settingspage->add($setting);
            }, $this->get_general_settings());
        }

        // Default settings page.
        $settingspage = new admin_settingpage('block_xp_default_settings', get_string('defaultsettings', 'block_xp'));
        if ($env->is_full_tree()) {
            array_map(function($setting) use ($settingspage) {
                $settingspage->add($setting);
            }, $this->get_default_settings());
        }
        $settings->add($catname, $settingspage);

        // Add the external rules page.
        $settingspage = new admin_externalpage('block_xp_default_rules',
            get_string('defaultrules', 'block_xp'),
            $this->urlresolver->reverse('admin/rules'));
        $settings->add($catname, $settingspage);

        return $settings;
    }

    /**
     * Get the general settings.
     *
     * @return admin_setting[]
     */
    protected function get_general_settings() {
        $settings = [];

        // Context in which the block is enabled.
        $settings[] = (new admin_setting_configselect(
            'block_xp_context',
            get_string('wherearexpused', 'block_xp'),
            get_string('wherearexpused_desc', 'block_xp'),
            $this->defaults->get('context'),
            [
                CONTEXT_COURSE => get_string('incourses', 'block_xp'),
                CONTEXT_SYSTEM => get_string('forthewholesite', 'block_xp')
            ]
        ));

        return $settings;
    }

    /**
     * Get the default settings.
     *
     * @return admin_setting[]
     */
    protected function get_default_settings() {
        $defaults = $this->defaults->get_all();
        $settings = [];

        // Intro.
        $settings[] = (new admin_setting_heading('block_xp/hdrintro', '', get_string('admindefaultsettingsintro', 'block_xp')));

        // General settings.
        $settings[] = (new admin_setting_heading('block_xp/hdrgeneral', get_string('general'), ''));

        // Enable the information page?
        $settings[] = (new admin_setting_configcheckbox('block_xp/enableinfos',
            get_string('enableinfos', 'block_xp'), get_string('enableinfos_help', 'block_xp'),
            $defaults['enableinfos']));

        // Enable the level-up notification?
        $settings[] = (new admin_setting_configcheckbox('block_xp/enablelevelupnotif',
            get_string('enablelevelupnotif', 'block_xp'), get_string('enablelevelupnotif_help', 'block_xp'),
            $defaults['enablelevelupnotif']));

        // Ladder settings.
        $settings[] = (new admin_setting_heading('block_xp/hdrladder', get_string('ladder', 'block_xp'), ''));

        // Enable the ladder?
        $settings[] = (new admin_setting_configcheckbox('block_xp/enableladder',
            get_string('enableladder', 'block_xp'), get_string('enableladder_help', 'block_xp'),
            $defaults['enableladder']));

        // Anonymity.
        $settings[] = (new admin_setting_configselect('block_xp/identitymode',
            get_string('anonymity', 'block_xp'), get_string('anonymity_help', 'block_xp'),
            $defaults['identitymode'], [
                course_world_config::IDENTITY_OFF => get_string('hideparticipantsidentity', 'block_xp'),
                course_world_config::IDENTITY_ON => get_string('displayparticipantsidentity', 'block_xp'),
            ]
        ));

        // Neighbours.
        $settings[] = (new admin_setting_configselect('block_xp/neighbours',
            get_string('limitparticipants', 'block_xp'), get_string('limitparticipants_help', 'block_xp'),
            $defaults['neighbours'], [
                0 => get_string('displayeveryone', 'block_xp'),
                1 => get_string('displayoneneigbour', 'block_xp'),
                2 => get_string('displaynneighbours', 'block_xp', 'two'),
                3 => get_string('displaynneighbours', 'block_xp', 'three'),
                4 => get_string('displaynneighbours', 'block_xp', 'four'),
                5 => get_string('displaynneighbours', 'block_xp', 'five'),
            ]
        ));

        // Ranking mode.
        $settings[] = (new admin_setting_configselect('block_xp/rankmode',
            get_string('ranking', 'block_xp'), get_string('ranking_help', 'block_xp'),
            $defaults['rankmode'], [
                course_world_config::RANK_OFF => get_string('hiderank', 'block_xp'),
                course_world_config::RANK_ON => get_string('displayrank', 'block_xp'),
                course_world_config::RANK_REL => get_string('displayrelativerank', 'block_xp'),
            ]
        ));

        // Cheat guard settings.
        $settings[] = (new admin_setting_heading('block_xp/hdrcheatguard', get_string('cheatguard', 'block_xp'), ''));

        // Enable the cheat guard?
        $settings[] = (new admin_setting_configcheckbox('block_xp/enablecheatguard',
            get_string('enablecheatguard', 'block_xp'), '',
            $defaults['enablecheatguard']));

        // Max actions per time.
        $settings[] = (new admin_setting_configtext('block_xp/maxactionspertime',
            get_string('maxactionspertime', 'block_xp'), get_string('maxactionspertime_help', 'block_xp'),
            $defaults['maxactionspertime'], PARAM_INT));

        // Time for max actions.
        $settings[] = (new admin_setting_configtext('block_xp/timeformaxactions',
            get_string('timeformaxactions', 'block_xp'), get_string('timeformaxactions_help', 'block_xp'),
            $defaults['timeformaxactions'], PARAM_INT));

        // Time between identical actions.
        $settings[] = (new admin_setting_configtext('block_xp/timebetweensameactions',
            get_string('timebetweensameactions', 'block_xp'), get_string('timebetweensameactions_help', 'block_xp'),
            $defaults['timebetweensameactions'], PARAM_INT));

        // Logging settings.
        $settings[] = (new admin_setting_heading('block_xp/hdrlogging', get_string('logging', 'block_xp'), ''));

        // Enable logs?
        $settings[] = (new admin_setting_configcheckbox('block_xp/enablelog',
            get_string('enablelogging', 'block_xp'), '',
            $defaults['enablelog']));

        // Keeps logs for.
        $settings[] = (new admin_setting_configselect('block_xp/keeplogs',
            get_string('keeplogs', 'block_xp'), '',
            $defaults['keeplogs'], [
                '0' => get_string('forever', 'block_xp'),
                '1' => get_string('for1day', 'block_xp'),
                '3' => get_string('for3days', 'block_xp'),
                '7' => get_string('for1week', 'block_xp'),
                '30' => get_string('for1month', 'block_xp'),
            ]
        ));

        return $settings;
    }

}
