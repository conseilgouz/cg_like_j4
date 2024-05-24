<?php
/*
 * @module		CG Like for Joomla 4.x / 5.x
 * @author		ConseilGouz
 * @license		GNU General Public License version 3 or later
 */

namespace ConseilGouz\Plugin\Content\Cglike\Extension;

defined('_JEXEC') or die('Direct access denied!');
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\SubscriberInterface;

class Cglike extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;
    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return [
            'onAjaxCglike'   => 'goAjax',
            'onContentAfterDisplay' => 'afterDisplay',
            'onContentBeforeDisplay' => 'beforeDisplay'
        ];
    }

    public function __construct(& $subject, $config)
    {
        parent::__construct($subject, $config);

        $app = Factory::getApplication();
        if ($app->isClient('administrator')) { // 4.0 compatibility
            return;
        }

        $plg	= 'media/plg_cglike/';
        /** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('cglike', $plg.'css/cglike.css');
        $wa->registerAndUseStyle('heart', $plg.'css/heart.css');
        $wa->registerAndUseScript('cglike', $plg.'js/cglike.js');
    }
    public function CGLikeModeling($article)
    {
        $id = $article->id;
        $db	= Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select('COUNT(id)');
        $query->from('#__cg_like');
        $query->where('cid = '.$db->quote($id));
        $db->setQuery((string)$query);
        $res = $db->loadResult();
        if(empty($res)) {
            $res = 0;
        }
        return $res;
    }
    public function CGLikePrepare($article)
    {
        $input	= Factory::getApplication()->input;
        $id = $article->id;
        // View Restriction
        $view = $input->get('view');
        $showinart = $this->params->get('showinart', 1);
        $showincat = $this->params->get('showincat', 1);
        if(($view == 'article') and (!$showinart)) {
            return "";
        }
        if(($view == 'category') and (!$showincat)) {
            return "";
        }
        // Categories and Articles filter
        if($this->params->get('encats') or $this->params->get('discats') or $this->params->get('disarts')) {
            $db	= Factory::getContainer()->get(DatabaseInterface::class);
            if($this->params->get('disarts') and (in_array($id, $this->params->get('disarts')))) {
                return "";
            }
            if($this->params->get('discats') or $this->params->get('encats')) {
                // get article category
                $query = $db->getQuery(true);
                $query->select('id, catid');
                $query->from('#__content');
                $query->where('id = '.$db->quote($id));
                $db->setQuery((string)$query);
                $cnres = $db->loadObject();
                $catid = $cnres->catid;
                if($this->params->get('discats') and (in_array($catid, $this->params->get('discats')))) {
                    return "";
                }
                if($this->params->get('encats') and (!in_array($catid, $this->params->get('encats')))) {
                    return "";
                }
            }
        }
        // Geting data needed
        $res = $this->CGLikeModeling($article);
        $clearfix = "";
        if ($this->params->get('clearfix', '0')) {
            $clearfix = " clearfix";
        }
        // Start of output
        $output = '<div class="cg_like '.$clearfix.'" id="cg_like_' . $id . '">';
        $output .= '<div class="grid" id="pos_grid">';
        $output .= '<div class="cglike_val cgalign-'. $this->params->get('alignment', 'right') .'" id="cglike_val_' . $id .'">';
        if ((($this->params->get('regonly') == '1') && (!Factory::getApplication()->getIdentity()->guest)) ||
        ($this->params->get('regonly') == '0')) {
            $output .= '<a href="javascript:void(null)" class="cg_like_btn_'.$id.';" data="'.$id.'">';
        }
        $result = "";
        $icon = "icon-heart-empty";
        if ($res > 0) {
            $result = $res;
            $icon = "icon-heart";
        }
        $output .= "<span class='cg-icon ".$icon."' id='cg_like_icon_".$id."'></span>";
        $output .= "<span font-size:100%;' id='cg_like_val_".$id."' style='margin-left:0.5em'>".$result."</span>";
        if ((($this->params->get('regonly') == '1') && (!Factory::getApplication()->getIdentity()->guest)) ||
        ($this->params->get('regonly') == '0')) {
            $output .= '</a>';
        }
        $output .= '</div>';
        $margin = "";
        if ($this->params->get('alignment', 'right') != 'center') {
            $margin = "margin-".$this->params->get('alignment', 'right').":1em;";
        }
        $output .= '<div class="cg_result cgalign-'. $this->params->get('alignment', 'right') .'" style="display:block;'.$margin.'" id="cg_result_' . $id . '">';
        $output .= ' ';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';
        return $output;
    }
    public function beforeDisplay($event)
    {
        $app = Factory::getApplication();
        if ($app->isClient('administrator')) {
            return;
        }
        $context    = $event->getContext();
        $article    = $event->getItem();

        $allowed_contexts = array('com_content.category', 'com_content.article', 'com_content.featured');
        if (!in_array($context, $allowed_contexts)) {
            return;
        }
        if($this->params->get('pos_show', 'beforec') == 'beforec') {
            $event->addResult($this->CGLikePrepare($article));
            return true;
        }
    }
    public function afterDisplay($event)
    {
        $app = Factory::getApplication();
        if ($app->isClient('administrator')) {
            return;
        }

        $context    = $event->getContext();
        $article    = $event->getItem();

        $allowed_contexts = array('com_content.category', 'com_content.article', 'com_content.featured');
        if (!in_array($context, $allowed_contexts)) {
            return;
        }
        if($this->params->get('pos_show', 'beforec') == 'afterc') {
            $event->addResult($this->CGLikePrepare($article));
        }
        return true;
    }
    public function countId($id)
    {
        $db	= Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query   ->select('COUNT(id)')
                ->from($db->quoteName('#__cg_like'))
                ->where($db->quoteName('cid'). '='.$db->quote($id));
        $db->setQuery($query);
        $results = $db->loadResult();
        return $results;
    }
    public function goAjax($event)
    {
        $input	= Factory::getApplication()->input;
        $id  = $input->get('id', '', 'integer');
        $out = "";
        if (!self::cookie($id)) {// cookie exist => exit
            $out .= '{"ret":"9","msg":"'.Text::_("CG_AJAX_ALREADY").'"}';
            return  $event->addResult($out);
        }
        $plugin = PluginHelper::getPlugin('content', 'cglike');
        $params = new Registry($plugin->params);
        self::setcookie($id, $params);
        if (!self::addOne($id)) {
            $out .= '{"ret":"9","msg":"'.Text::_("CG_AJAX_SQL_ERROR").'"}';
            return  $event->addResult($out);
        }
        $count = self::countId($id);
        $out .= '{"ret":"0","msg":"'.Text::_("CG_AJAX_THANKS").'","cnt":"'.$count.'"}';
        return  $event->addResult($out);
    }
    public function cookie($id)
    {
        $jinput = Factory::getApplication()->input;
        $cookieName = 'cg_like_'.$id;
        $value = $jinput->cookie->get($cookieName);
        if ($value) { // cookie exist
            return false;
        }
        return true;
    }
    public function setcookie($id, $params)
    {
        $duration = $params->get('voteagain', '0'); // duree de vie du cookie (0 => pas de cookie, pour debug/demo)
        $name = "cg_like_".$id;
        $value = date("Y-m-d");
        $expire =  time() + 3600 * 24 * $duration;
        $path = "/";
        $domain = "";
        $secure = true; // assume https
        if (array_key_exists("HTTPS", $_SERVER)) {
            $secure = $_SERVER["HTTPS"] ? true : false;
        }
        $httponly = false;
        if (PHP_VERSION_ID < 70300) {
            setcookie($name, $value, $expire, "$path; samesite=Lax", $domain, $secure, $httponly);
        } else {
            $res = setcookie($name, $value, [
            'expires' => $expire,
            'path' => $path,
            'domain' => $domain,
            'samesite' => 'Lax',
            'secure' => $secure,
            'httponly' => $httponly,
            ]);
        }
    }
    public function addOne($id)
    {
        $db	= Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->insert('#__cg_like');
        $query->set('cid = '.$db->quote($id));
        $query->set('lastdate = NOW()');
        $db->setQuery((string)$query);
        if (!$db->execute()) {
            return false;
        }
        return true;
    }

}
