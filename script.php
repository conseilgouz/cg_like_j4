<?php
/**
* CG Like Plugin  - Joomla 4.x/5.x plugin
* copyright 		: Copyright (C) 2025 ConseilGouz. All rights reserved.
* license    		: https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
*/
// No direct access to this file
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Version;
use Joomla\Component\Categories\Administrator\Model\CategoryModel;
use Joomla\Component\Mails\Administrator\Model\TemplateModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\File;

class plgcontentcglikeInstallerScript
{
    private $min_joomla_version      = '4.0.0';
    private $min_php_version         = '7.4';
    private $name                    = 'Plugin Content CG Like';
    private $exttype                 = 'plugin';
    private $extname                 = 'cglike';
    private $previous_version        = '';
    private $dir           = null;
    private $db;
    private $lang;
    private $installerName = 'plgcontentcglikeinstaller';
    public function __construct()
    {
        $this->dir = __DIR__;
        $this->lang = Factory::getLanguage();
        $this->lang->load($this->extname);
    }

    public function preflight($type, $parent)
    {
        if (! $this->passMinimumJoomlaVersion()) {
            $this->uninstallInstaller();
            return false;
        }

        if (! $this->passMinimumPHPVersion()) {
            $this->uninstallInstaller();
            return false;
        }
        // To prevent installer from running twice if installing multiple extensions
        if (! file_exists($this->dir . '/' . $this->installerName . '.xml')) {
            return true;
        }
    }
    public function uninstall($parent)
    {
        return true;
    }

    public function postflight($type, $parent)
    {
        if (($type == 'install') || ($type == 'update')) { // remove obsolete dir/files
            $this->postinstall_cleanup();
        }

        switch ($type) {
            case 'install': $message = Text::_('ISO_POSTFLIGHT_INSTALLED');
                break;
            case 'uninstall': $message = Text::_('ISO_POSTFLIGHT_UNINSTALLED');
                break;
            case 'update': $message = Text::_('ISO_POSTFLIGHT_UPDATED');
                break;
            case 'discover_install': $message = Text::_('ISO_POSTFLIGHT_DISC_INSTALLED');
                break;
        }
        return true;
    }
    private function postinstall_cleanup()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $conditions = array(
            $db->qn('type') . ' = ' . $db->q('plugin'),
            $db->qn('element') . ' = ' . $db->quote($this->extname)
        );
        $fields = array($db->qn('enabled') . ' = 1');

        $query = $db->getQuery(true);
        $query->update($db->quoteName('#__extensions'))->set($fields)->where($conditions);
        $db->setQuery($query);
        try {
            $db->execute();
        } catch (\RuntimeException $e) {
            Log::add('unable to enable '.$this->name, Log::ERROR, 'jerror');
        }
        $this->removeCGLikeAjax();
    }
    private function removeCGLikeAjax()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        // Remove CG LIKE AJAX folder.
        $f = JPATH_SITE . '/plugins/ajax/cglike';
        if (is_dir($f)) {
            Folder::delete($f);
        }
        // remove language files
        $langFiles = [
            sprintf("%s/language/en-GB/plg_ajax_%s.ini", JPATH_ADMINISTRATOR, 'cglike'),
            sprintf("%s/language/en-GB/plg_ajax_%s.sys.ini", JPATH_ADMINISTRATOR, 'cglike'),
            sprintf("%s/language/fr-FR/plg_ajax_%s.ini", JPATH_ADMINISTRATOR, 'cglike'),
            sprintf("%s/language/fr-FR/plg_ajax_%s.sys.ini", JPATH_ADMINISTRATOR, 'cglike'),
        ];
        foreach ($langFiles as $file) {
            if (@is_file($file)) {
                File::delete($file);
            }
        }
        // delete cglike ajax plugin
        $conditions = array(
            $db->quoteName('type').'='.$db->quote('plugin'),
            $db->quoteName('folder').'='.$db->quote('ajax'),
            $db->quoteName('element').'='.$db->quote('cglike')
        );
        $query = $db->getQuery(true);
        $query->delete($db->quoteName('#__extensions'))->where($conditions);
        $db->setQuery($query);
        try {
            $db->execute();
        } catch (\RuntimeException $e) {
            Log::add('unable to delete cg like ajax from extensions', Log::ERROR, 'jerror');
        }
        // delete #__update_sites (keep showing update even if system cg like ajax is disabled)
        $query = $db->getQuery(true);
        $query->select('site.update_site_id')
        ->from($db->quoteName('#__extensions', 'ext'))
        ->join('LEFT', $db->quoteName('#__update_sites_extensions', 'site').' ON site.extension_id = ext.extension_id')
        ->where($db->quoteName('ext.type').'='.$db->quote('plugin'))
        ->where($db->quoteName('ext.folder').'='.$db->quote('ajax'))
        ->where($db->quoteName('ext.element').'='.$db->quote('cglike'));
        $db->setQuery($query);
        $upd_id = $db->loadResult();
        if (!$upd_id) {
            return true;
        }
        $conditions = array(
            $db->qn('update_site_id') . ' = ' . $upd_id
        );

        $query = $db->getQuery(true);
        $query->delete($db->quoteName('#__update_sites'))->where($conditions);
        $db->setQuery($query);
        try {
            $db->execute();
        } catch (\RuntimeException $e) {
            Log::add('unable to delete cglike ajax from updata_sites', Log::ERROR, 'jerror');
        }

    }
    // Check if Joomla version passes minimum requirement
    private function passMinimumJoomlaVersion()
    {
        $j = new Version();
        $version = $j->getShortVersion();
        if (version_compare($version, $this->min_joomla_version, '<')) {
            Factory::getApplication()->enqueueMessage(
                'Incompatible Joomla version : found <strong>' . $version . '</strong>, Minimum : <strong>' . $this->min_joomla_version . '</strong>',
                'error'
            );

            return false;
        }

        return true;
    }

    // Check if PHP version passes minimum requirement
    private function passMinimumPHPVersion()
    {

        if (version_compare(PHP_VERSION, $this->min_php_version, '<')) {
            Factory::getApplication()->enqueueMessage(
                'Incompatible PHP version : found  <strong>' . PHP_VERSION . '</strong>, Minimum <strong>' . $this->min_php_version . '</strong>',
                'error'
            );
            return false;
        }

        return true;
    }
    private function uninstallInstaller()
    {
        if (! is_dir(JPATH_PLUGINS . '/system/' . $this->installerName)) {
            return;
        }
        $this->delete([
            JPATH_PLUGINS . '/system/' . $this->installerName . '/language',
            JPATH_PLUGINS . '/system/' . $this->installerName,
        ]);
        $db = $this->db;
        $query = $db->getQuery(true)
            ->delete('#__extensions')
            ->where($db->quoteName('element') . ' = ' . $db->quote($this->installerName))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'));
        $db->setQuery($query);
        $db->execute();
        Factory::getCache()->clean('_system');
    }
    public function delete($files = [])
    {
        foreach ($files as $file) {
            if (is_dir($file)) {
                Folder::delete($file);
            }

            if (is_file($file)) {
                File::delete($file);
            }
        }
    }
}
