<?php
/*
 * ---------------------------------------------------------------------------------------------------------------------
 * This file is part of the Morphing theme for Moodle
 *
 * The Morphing theme for Moodle software package is Copyright © 2008 onwards NetSapiensis AB and is provided
 * under the terms of the GNU GENERAL PUBLIC LICENSE Version 3 (GPL). This program is free software: you can
 * redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any later version.
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program. If not, see
 * http://www.gnu.org/licenses/
 * ---------------------------------------------------------------------------------------------------------------------
 */

/**
 * handle all admin_setting_xxx instantiations here
 * in order to keep the settings.php simple and readable
 */
class Morphing_Theme_Settings
{
    const THEME = 'theme_morphing';
    
    protected $_settings = array();
    
    protected $theme = null;
    
    public function __construct($theme = null)
    {
        $this->_init();
        
        if (!is_null($theme)) {
            $this->theme = $theme;
        }
    }
    
    /**
     * wrapper (shortcut) function for get_string
     * @param string $tag the language tag to be returned
     */
    protected function _s($settingkey, $desc = false)
    {
        if (!isset($this->_settings[$settingkey])) {
            return get_string($settingkey, self::THEME);
        }
        
        if (!isset($this->_settings[$settingkey]['title'])) {
            $this->_settings[$settingkey]['title'] = $settingkey;
        }
        $tag = $this->_settings[$settingkey]['title'];
        if ($desc) {
            $tag .= 'desc';
        }
        return get_string($tag, self::THEME);
    }
    
    /**
     * get a new instance of adminsetting by tag
     * uses $this->_settings
     * @see self::_init
     * @param string $tag
     */
    public function getAdminSetting($tag)
    {
        $s = $this->_settings[$tag];
        
        if (!empty($s['raw'])) {
            return $s['raw'];
        }
        
        if (empty($s['type'])) {
            $s['type'] = 'text';
        }
        $name = "theme_morphing/{$tag}";
        $title = $this->_s($tag);
        switch ($s['type']) {
            case 'html':
                $return = new theme_morphing_admin_setting_confightml($name, $title, '', '');
                break;
            case 'select':
                $description = $this->_s($tag, true);
                $return = new admin_setting_configselect($name, $title, $description, $s['default'], $s['extra']);
                break;
            case 'colourpicker':
                $description = $this->_s($tag, true);
                $return = new admin_setting_configcolourpicker($name, $title, $description, $s['default'], $s['extra']);
                break;
            case 'checkbox':
                $description = $this->_s($tag, true);
                $return = new admin_setting_configcheckbox($name, $title, $description, $s['default']);
                break;
            case 'text':
                $description = $this->_s($tag, true);
                $param = PARAM_RAW;
                if (isset($s['extra'])) {
                    $param = $s['extra'];
                }
                if (!isset($s['default'])) {
                    $s['default'] = '';
                }
                $return = new admin_setting_configtext($name, $title, $description, $s['default'], $param);
                break;
            case 'htmleditor':
            case 'textarea':
                $description = $this->_s($tag, true);
                if (!isset($s['default'])) {
                    $s['default'] = '';
                }
                $class = "admin_setting_config{$s['type']}";
                $return = new $class($name, $title, $description, $s['default']);
                break;
        }
        
        return $return;
    }
    
    /**
     * get a setting value
     * @param string $tag
     * @return string
     * @throws Exception
     */
    public function get($tag)
    {
        if (empty($this->theme)) {
            throw new Exception('Invalid theme specified for the morphing settings');
        }
        
        $s = $this->_settings[$tag];
        if (!isset($s['default'])) {
            $s['default'] = '';
            throw new Exception('Default not found for: ' . $tag);
        }
        
        if (isset($this->theme->settings->{$tag})) {
            return $this->theme->settings->{$tag};
        }
        
        return $s['default'];
    }
    
    /**
     * 
     * replaces the $tag in css file with the actual setting value
     * 
     * @param string $tag the tag from the css file
     * @param string $css current css being processed
     * @param callable $filter filter function to apply to the setting value
     * @param string $suffix suffix to append to the setting value, e.g 'px'
     * 
     * @return Morphing_Theme_Settings
     */
    public function apply($tag, & $css, $filter = null, $suffix = '')
    {
        $value = $this->get($tag);
        if (is_callable($filter)) {
            $value = $filter($value);
        }
        $value .= $suffix;
        
        $css = str_replace("[[setting:{$tag}]]", $value, $css);
        
        return $this;
    }
    
    /**
     * instantiate and return an array of "section" to be added to the page
     * @param string $name the section to return
     * @return array of admin_setting_xxx instances
     */
    public function getSettingsSection($name)
    {
        $return = array();
        
        foreach ($this->_settings as $k => $s) {
            if (isset($s['_section']) && $s['_section'] == $name) {
                $return []= $this->getAdminSetting($k);
            }
        }
        
        return $return;
    }
    
    /**
     * get all sections keys available
     * @return type
     */
    public function getAllTabs()
    {
        $_ = create_function('$a', 'return $a["_section"];');
        $tabs = array_map($_, $this->_settings);
        
        return array_unique($tabs);
    }
    
    /**
     * init all settings config
     */
    protected function _init()
    {   
        $sizes = array();
        for ($i = 9; $i < 21; $i++) {
            $sizes[$i] = $i . 'px';
        }
        $this->_settings = array(
            'reset_everything' => array(
                '_section' => 'reset',
                'type' => 'html',
                'title' => 'resettitle'
            ),
            // font size reference
            'fontsizereference' => array(
                '_section' => 'general',
                'type' => 'select',
                'default' => '13',
                'extra' => array(11 => '11px', 12 => '12px', 13 => '13px', 14 => '14px', 15 => '15px', 16 => '16px')
            ),
            'fontcolor' => array(
                '_section' => 'general',
                'type' => 'colourpicker',
                'default' => '#000000',
                'extra' => array('selector' => 'html,body,.form-description', 'style' => 'color')
            ),
            'linkcolor' => array(
                '_section' => 'general',
                'type' => 'colourpicker',
                'default' => '#113759',
                'extra' => array('selector' => 'html a,body a', 'style' => 'color')
            ),
            'visitedlinkcolor' => array(
                '_section' => 'general',
                'type' => 'colourpicker',
                'default' => '#113759',
                'extra' => array('selector' => 'html a,body a', 'style' => 'color')
            ),
            'maincolor' => array(
                '_section' => 'general',
                'type' => 'colourpicker',
                'default' => '#1f465e',
                'extra' => array('selector' => '#custommenu2, div#jcontrols_button,#footerwrapper,.block div.header,#dock', 'style' => 'backgroundColor')
            ),
            'loggedincolor' => array(
                '_section' => 'general',
                'type' => 'colourpicker',
                'default' => '#00aeef',
                'extra' => array('selector' => 'a.logged-in-link', 'style' => 'color')
            ),
            'alwayslangmenu' => array(
                '_section' => 'general',
                'type' => 'checkbox',
                'default' => 1
            ),
            'layouttype' => array(
                '_section' => 'general',
                'type' => 'select',
                'default' => 'fluid',
                'extra' => array('fluid' => get_string('layouttypefluid', 'theme_morphing'), 'fixed' => get_string('layouttypefixed', 'theme_morphing'))
            ),
            'layoutfluidwidth' => array(
                '_section' => 'general',
                'default' => '100',
                'extra' => '/^([0-9]{1,3})$/'
            ),
            'layoutfixedwidth' => array(
                '_section' => 'general',
                'default' => '900',
                'extra' => '/^[0-9][0-9]*$/'
            ),
            'mainbackgroundcolor' => array(
                '_section' => 'general',
                'type' => 'colourpicker',
                'default' => '#E0E0E0',
                'extra' => array('selector' => 'html, body', 'style' => 'background')
            ),
            'mainbackgroundimage' => array(
                '_section' => 'general',
                'title' => 'backgroundimage',
                'extra' => PARAM_URL
            ),
            //header
            // header background color setting
            'headerbgc' => array(
                '_section' => 'header',
                'type' => 'colourpicker',
                'default' => '#1f465e',
                'extra' => array('selector' => '#headerwrap', 'style' => 'backgroundColor')
            ),
            'headerheight' => array(
                '_section' => 'header',
                'default' => 110,
                'extra' => '/^[0-9][0-9]*$/'
            ),
            'headerlinkcolor' => array(
                '_section' => 'header',
                'type' => 'colourpicker',
                'default' => '#FFFFFF',
                'extra' => array('selector' => '#headerwrap a, #jcontrols_button a', 'style' => 'color')
            ),
            //end header
            //logo
            'logo' => array(
                '_section' => 'logo',
                'title' => 'logourl',
                'extra' => PARAM_URL
            ),
            'secondlogo' => array(
                '_section' => 'logo',
                'title' => 'headersecondimage',
                'extra' => PARAM_URL
            ),
            'logooffsetleft' => array(
                '_section' => 'logo',
                'default' => 105,
                'extra' => '/^[0-9][0-9]*$/'
            ),
            'logooffsettop' => array(
                '_section' => 'logo',
                'default' => 15,
                'extra' => '/^-?[0-9][0-9]*$/'
            ),
            'secondlogooffsetleft' => array(
                '_section' => 'logo',
                'default' => 425,
                'extra' => '/^[0-9][0-9]*$/'
            ),
            'secondlogooffsettop' => array(
                '_section' => 'logo',
                'default' => 15,
                'extra' => '/^-?[0-9][0-9]*$/'
            ),
            'breadcrumbfontsize' => array(
                '_section' => 'logo',
                'default' => 12,
                'type' => 'select',
                'extra' => $sizes
            ),
            'breadcrumbheight' => array(
                '_section' => 'logo',
                'default' => 35,
                'extra' => '/^[0-9][0-9]*$/'
            ),
            'breadcrumbleft' => array(
                '_section' => 'logo',
                'default' => 15,
                'extra' => '/^-?[0-9][0-9]*$/'
            ),
            'breadcrumbtop' => array(
                '_section' => 'logo',
                'default' => 0,
                'extra' => '/^-?[0-9][0-9]*$/'
            ),
            //end logo
            //block settings
            // block title font size
            'blocktitlefontsize' => array(
                '_section' => 'block',
                'default' => 12,
                'type' => 'select',
                'extra' => $sizes
            ),
            'regionwidth' => array(
                '_section' => 'block',
                'default' => 200,
                'extra' => array(150 => '150px', 170 => '170px', 200 => '200px', 240 => '240px', 290 => '290px', 350 => '350px', 420 => '420px'),
                'type' => 'select'
            ),
            'blocktitlealign' => array(
                '_section' => 'block',
                'default' => 'left',
                'type' => 'select',
                'extra' => array('left' => get_string('alignleft', 'theme_morphing'), 'center' => get_string('aligncenter', 'theme_morphing'), 'right' => get_string('alignright', 'theme_morphing'))
            ),
            'blocktitleleft' => array(
                '_section' => 'block',
                'default' => 5,
                'extra' => '/^-?[0-9][0-9]*$/'
            ),
            'backgroundcolor' => array(
                '_section' => 'block',
                'type' => 'colourpicker',
                'default' => '#F7F6F1',
                'extra' => array('selector' => '.block .content', 'style' => 'backgroundColor')
            ),
            'blockheadercolor' => array(
                '_section' => 'block',
                'type' => 'colourpicker',
                'default' => '#1F465E',
                'extra' => array('selector' => '.block div.header', 'style' => 'backgroundColor')
            ),
            'blockbordercolor' => array(
                '_section' => 'block',
                'type' => 'colourpicker',
                'default' => '#CCCCCC',
                'extra' => array('selector' => '.block', 'style' => 'border')
            ),
            //end block settings
            //miscellaneous settings
            'footnote' => array(
                '_section' => 'miscellaneous',
                'type' => 'htmleditor'
            ),
            'customcss' => array(
                '_section' => 'miscellaneous',
                'type' => 'textarea',
                'default' => ''
            ),
            //end miscellaneous settings
            //custom menu settings
            'custommenudisplay' => array(
                '_section' => 'custommenu',
                'type' => 'select',
                'default' => 'none',
                'extra' => array(
                    'none' => $this->_s('none'), 
                    'front' => $this->_s('frontpage'),
                    'all' => $this->_s('allpages')
                )
            ),
            'custommenuheight' => array(
                '_section' => 'custommenu',
                'default' => 35,
                'extra' => '/^[0-9][0-9]*$/'
            ),
            'custommenuitems' => array(
                '_section' => 'custommenu',
                'raw' => new admin_setting_configtextarea('theme_morphing/custommenuitems', get_string('custommenuitemsdesc', 'theme_morphing') . '<br />' . new lang_string('custommenuitems', 'admin'), new lang_string('configcustommenuitems', 'admin'), '', PARAM_TEXT, '50', '10')
            ),
            'custommenualign' => array(
                '_section' => 'custommenu',
                'default' => 'left',
                'type' => 'select',
                'extra' => array('left' => get_string('alignleft', 'theme_morphing'), 'center' => get_string('aligncenter', 'theme_morphing'))
            )
            //end custom menu settings
        );
    }
}