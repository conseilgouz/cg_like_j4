<?php/*  * @module		CG Like * @author		ConseilGouz * @license		GNU General Public License version 2 or later * version      2.0.0 */ defined('_JEXEC') or die('Direct access denied!');use Joomla\CMS\Factory;use Joomla\CMS\Plugin\CMSPlugin;use Joomla\CMS\HTML\HTMLHelper;use Joomla\CMS\Plugin\PluginHelper;class plgContentCGLike extends CMSPlugin{	public function __construct(& $subject, $config)	{		parent::__construct($subject, $config);		if (!PluginHelper::isEnabled('ajax', 'cglike')) {				Factory::getApplication()->enqueueMessage('Activate Ajax CG Like plugin....','error');				return false;		}        $app = Factory::getApplication();        if ($app->isClient('administrator')) // 1.0.5 : 4.0 compatibility            return;				HTMLHelper::_('jquery.framework');				$doc = Factory::getDocument();		$doc->addStyleSheet('media/plg_cglike/css/cglike.css');		$doc->addStyleSheet('media/plg_cglike/css/heart.css');		$doc->addScript('media/plg_cglike/js/cglike.js');}	public function CGLikeModeling($article, $params) {		$id = $article->id;		$db	= Factory::getDBO();		$query = $db->getQuery(true);		$query->select('COUNT(id)');		$query->from('#__cg_like');		$query->where('cid = '.$db->quote($id));		$db->setQuery( (string)$query );		$res = $db->loadResult();		if(empty($res))	{			$res = 0;		}		return $res;	}	public function CGLikePrepare ($article, $params)	{		$input	= Factory::getApplication()->input;		$id = $article->id;		// View Restriction		$view = $input->get('view');		$showinart = $this->params->get('showinart', 1);		$showincat = $this->params->get('showincat', 1);		if( ($view == 'article') and (!$showinart) )			return false;		if( ($view == 'category') and (!$showincat) )			return false;			// Categories and Articles filter		if($this->params->get('encats') or $this->params->get('discats') or $this->params->get('disarts'))		{			$db	= Factory::getDBO();			if( $this->params->get('disarts') and (in_array($id, $this->params->get('disarts'))) )	{				return false;				}			if($this->params->get('discats') or $this->params->get('encats')) {				// get article category				$query = $db->getQuery(true);				$query->select('id, catid');				$query->from('#__content');				$query->where('id = '.$db->quote($id));				$db->setQuery( (string)$query );				$cnres = $db->loadObject();				$catid = $cnres->catid;				if( $this->params->get('discats') and (in_array($catid, $this->params->get('discats'))) ) {					return false;				}				if( $this->params->get('encats') and (!in_array($catid, $this->params->get('encats'))) ) {					return false;						}						}			}					// Geting data needed		$res = $this->CGLikeModeling($article, $params);		// Start of output		$output = '<div class="cg_like cgalign-'. $this->params->get('alignment', 'right') .'" id="cg_like_' . $id . '">';		$output .= '<div class="grid" id="pos_grid">';		$output .= '<div class="cglike_val cgalign-'. $this->params->get('alignment', 'right') .'" id="cglike_val_' . $id .'">';		if ((($this->params->get('regonly') == '1') && (!JFactory::getUser()->guest)) ||		($this->params->get('regonly') == '0') ) {			$output .= '<a href="javascript:void(null)" class="cg_like_btn_'.$id.';" data="'.$id.'">';			}		$result = "";		$icon = "icon-heart-empty";		if ($res > 0 ) {			$result= $res;			$icon = "icon-heart";	    }		$output .="<span class='cg-icon ".$icon."' id='cg_like_icon_".$id."'></span>";		$output .= "<span font-size:100%;' id='cg_like_val_".$id."' style='margin-left:0.5em'>".$result."</span>";		if ((($this->params->get('regonly') == '1') && (!JFactory::getUser()->guest)) ||		($this->params->get('regonly') == '0') ) {			$output .= '</a>';		}		$output .= '</div>';		$margin = "";		if ($this->params->get('alignment', 'right') != 'center') {			$margin = "margin-".$this->params->get('alignment', 'right').":1em;";		}		$output .= '<div class="cg_result cgalign-'. $this->params->get('alignment', 'right') .'" style="display:block;'.$margin.'" id="cg_result_' . $id . '">';		$output .= ' ';		$output .= '</div>';		$output .= '</div>';		$output .= '</div>';		return $output;		}	public function onContentBeforeDisplay($context, &$article, &$params, $limitstart = 1)	{        $app = Factory::getApplication();        if ($app->isClient('administrator')) return; // 1.0.5 : 4.0 compatibility 		$allowed_contexts = array('com_content.category', 'com_content.article', 'com_content.featured');		if (!in_array($context, $allowed_contexts))	return;		if($this->params->get('pos_show', 'beforec') == 'beforec') {			return $this->CGLikePrepare($article, $params);		} 	}	public function onContentAfterDisplay($context, &$article, &$params, $limitstart = 1)	{        $app = Factory::getApplication();        if ($app->isClient('administrator')) return; // 1.0.5 : 4.0 compatibility 		$allowed_contexts = array('com_content.category', 'com_content.article', 'com_content.featured');		if (!in_array($context, $allowed_contexts))	return;		if($this->params->get('pos_show', 'beforec') == 'afterc') {			return $this->CGLikePrepare($article, $params);				}	}		function countId($id) {		$db		= Factory::getDbo();		$query = $db->getQuery(true);		$query   ->select( 'COUNT(id)') 				->from($db->quoteName('#__cg_like'))				->where($db->quoteName('cid'). '='.$db->quote($id));		$db->setQuery($query);		$results = $db->loadResult();				return $results;	}}