<?php
/**
 * default theme engine
 * 
 * @category   Erdiko
 * @package    ThemeEngine
 * @module	   Theme
 * @copyright Copyright (c) 2012, Arroyo Labs, www.arroyolabs.com
 * @author	John Arroyo
 *
 * @todo add interface to this module
 */
namespace erdiko\core\theme;

use erdiko\core\ModelAbstract;
use erdiko\core\interfaces\Theme;
use Erdiko;

class ThemeEngine extends ModelAbstract implements Theme
{
	protected $_folder;
	protected $_themeName;
	protected $_namespace;
	protected $_templates;
	protected $_data;
	protected $_webroot;
	protected $_path;
	protected $_themeConfig;
	protected $_siteConfig;
	protected $_extras;
	protected $_domainName;
	protected $_numColumns;
	protected $_sidebars;
	protected $_template = null;
	protected $_layout = null;
	
	public function __construct()
	{
		$this->_templates = array(
			'header' => 'header',
		);
	}
	
	public function getWebroot()
	{
		return $this->_webroot;
	}
	
	public function getThemeFolder()
	{
		return $this->_folder;
	}

	public function setLayout($layout)
	{
		$this->_layout = $layout;
	}
	
	/**
	 * @todo get the url programmatically.
	 */
	public function getThemeUrl()
	{
		return $this->_path;
	}
	
	public function getCss()
	{
		return $this->_themeConfig['css'];
	} 
	
	public function getJs()
	{
		return $this->_themeConfig['js'];
	}
	public function getPhpToJs()
	{
		return $this->_themeConfig['phpToJs'];
	}
	
	public function getMeta()
	{		
		return $this->_themeConfig['meta'];
	}
	
	public function getHeader($name = "")
	{	
		$filename = $this->_webroot.$this->_themeConfig['templates']['header']['file'];
		$html = $this->getTemplateFile($filename, $this->getLocalConfig());
		
		return $html;
	}
	
	public function getFooter($name = "")
	{
		// return $this->_data['footer'];
		$filename = $this->_webroot.$this->_themeConfig['templates']['footer']['file'];
		$html = $this->getTemplateFile($filename, $this->getLocalConfig());
		
		return $html;
	}
		
	public function getFile($section)
	{
		
	}
	
	// @todo need to make a clean distinction between 'title' and 'page title'
	// @todo rename to siteName
	public function setTitle($title)
	{
		$this->_data['title'] = $title;
	}

	public function getTitle()
	{
		return $this->_data['title'];
	}
	
	public function getPageTitle()
	{		
		return $this->_themeConfig['title'];
	}
	
	public function getMainContent($name = "", $options = null)
	{
		return $this->_data['main_content'];
	}
	
	public function getSidebar($name, $options = null)
	{
		try {
			if( isset($this->_sidebars[$name]) )
				$html = $this->renderSidebar($this->_sidebars[$name]);
			else
				$html = "";
		} catch (\Exception $e) {
			$html = $this->getExceptionHtml( $e->getMessage() );
		}

		return $html;
	}

	public function renderSidebar($data)
	{
		// If no view specified use the default
		if(!isset($data['view']))
			$filename = $this->_webroot.$this->_themeConfig['sidebars']['default']['file'];
		else
			$filename = $this->_webroot.$this->_themeConfig['path'].'/views'.$data['view'];
		
		return $this->getTemplateFile($filename, $data['content']);
	}
	
	public function setNumCloumns($cols)
	{
		$this->_numColumns = $cols; // @todo cast to int?
	}

	public function setTemplate($name)
	{
		$this->_template = $name;
	}

	protected function getTemplate()
	{
		if($this->_template == null)
			$file = $this->_themeConfig['templates']['default']['file'];
		else
			$file = $this->_themeConfig['templates']['default']['path'].$this->_template.".php";
		
		return $file;
	}

	public function getLayout()
	{
		if($this->_layout != null)
			$filename = $this->_webroot.$this->_themeConfig['path'].'/templates'.$this->_layout;
		else
			$filename = $this->_webroot.$this->_themeConfig['layouts'][$this->_numColumns]['file'];

		echo $this->getTemplateFile($filename, $this);
	}
	
	public function mergeCss($first, $second)
	{
		foreach($second as $css)
			$first[] = array('file' => $css['file']);
		
		return $first;
	}

	/**
	 * Merge configs
	 * Entries in $second will overtake $first
	 * @param array $first
	 * @param array $second
	 * @return array $combined
	 */
	public function mergeConfig($first, $second)
	{
		foreach($second as $key => $data)
			$first[$key] = $data;
		
		return $first;
	}
	
	public function mergeJs($first, $second)
	{
		$base = 'js';
		$i = 100;

		foreach($second as $js)
		{
			error_log("js ".print_r($js, true));
			$key = "$base-$i";
			$js['order'] = $i;
			$first[$key] = $js;
			$i++;
		}
		
		return $first;
	}

	public function getTemplateFile($filename, $data)
	{			
	    if (is_file($filename))
		{
			ob_start();
			include $filename;
			return ob_get_clean();
	    }
	    return false;
	}
	
	/**
	 * @param string $themeName
	 * @param string $namespace
	 * @param string $path
	 * @param array $extras
	 */
	public function loadTheme($name, $namespace, $path, $extras)
	{	
		$this->_webroot = WEBROOT;
		$this->_themeName = $name;
		$this->_path = $path;
		$this->_namespace = $namespace;
		$this->_domainName = 'http://'.$_SERVER['SERVER_NAME'];
		$this->_extras = $extras;
		$this->_folder = $this->_webroot.$this->_path;
		$file = $this->_folder.'/theme.json';

		$this->_themeConfig = Erdiko::getConfigFile($file);		
		$this->_themeConfig['meta'] = $extras['meta']; // Add injected Meta
		$this->_themeConfig['title'] = $extras['title']; // Add injected Page title
		$this->_themeConfig['phpToJs'] = $extras['phpToJs']; // Add phpToJs variables

		// If a parent theme exists, merge the theme configs
		if( isset($this->_themeConfig['parent']) )
		{
			$parentConfig = Erdiko::getConfigFile($this->_webroot.$this->_themeConfig['parent']);

			// CSS
			$this->_themeConfig['css'] = $this->mergeCss($parentConfig['css'], $this->_themeConfig['css']);
			unset($parentConfig['css']);
			
			// JS
			$this->_themeConfig['js'] = $this->mergeConfig($parentConfig['js'], $this->_themeConfig['js']);
			unset($parentConfig['js']);
			
			// Templates
			$this->_themeConfig['templates'] = $this->_themeConfig['templates'] + $parentConfig['templates'];

			// Views
			if(!isset($this->_themeConfig['views']))
				$this->_themeConfig['views'] = array();
			$this->_themeConfig['views'] = $this->_themeConfig['views'] + $parentConfig['views'];

			// Sidebars
			if(!isset($this->_themeConfig['sidebars']))
				$this->_themeConfig['sidebars'] = array();
			$this->_themeConfig['sidebars'] = $this->_themeConfig['sidebars'] + $parentConfig['sidebars'];
		}
		
		// Add any additional javascript files needed for the page.
		if($extras['js'] != null)
			$this->_themeConfig['js'] = $this->mergeJs($this->_themeConfig['js'], $extras['js']);

		// Add any additional CSS files needed for the page.
		if($extras['css'] != null)
			$this->_themeConfig['css'] = $this->mergeCss($this->_themeConfig['css'], $extras['css']);

		// Set default number of columns
		$this->_numColumns = $this->_themeConfig['columns'];
	}
	
	/**
	 *
	 */
	public function setData($data)
	{
		$this->_data = $data;
	}
	
	/**
	 *
	 */
	public function theme($data)
	{
		$filename = $this->_webroot.$this->getTemplate();	
		$this->setData($data);
		$html = $this->getTemplateFile($filename, $this);
		
		echo $html;
	}

	/**
	 * render data given a specific 
	 */
	public function renderView($file = null, $data = null)
	{
		// if no view specified use the default
		if($file == null)
			$filename = $this->_webroot.$this->_themeConfig['views']['default']['file'];
		else
			$filename = $this->_webroot.$this->_themeConfig['path'].'/views'.$file;

		return $this->getTemplateFile($filename, $data);
	}

	/**
	 * 
	 */
	public function setSidebars($data)
	{
		$this->_sidebars = $data;
	}

	/**
	 * 
	 */
	public function getThemeConfig()
	{
		return $this->_themeConfig;
	}

	/**
	 * 
	 */
	public function setLocalConfig($config)
	{
		$this->_siteConfig = $config;
	}

	/**
	 * 
	 */
	public function getLocalConfig()
	{
		return $this->_siteConfig;
	}

	/**
	 * 
	 */
	public function getData()
	{
		return $this->_data;
	}

	public function sortByOrder($arr)
	{
		$sorted = array();
		foreach($arr as $element)
		{
			$sorted[$element['order']] = $element;
		} 
		return $sorted;
	}

}