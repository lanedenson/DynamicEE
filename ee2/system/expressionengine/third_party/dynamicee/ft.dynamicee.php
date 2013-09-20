<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(PATH_THIRD.'dynamicee/config.php');

/**
 * Dynamicee Fieldtype
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author 		Jonathan W. Kelly
 * @link 		
 * @copyright 	Copyright (c) 2013 Paramore - the digital agency
 */
class Dynamicee_ft extends EE_Fieldtype {

	var $info = array(
		'name' 		=> 'DynamicEE',
		'version' 	=> '1.0'
	);

	/**
	 * @var $cache {array}
	 */
	var $cache;

	// --------------------------------------------------------------------
	// PUBLIC METHODS
	// --------------------------------------------------------------------

	/**
	 * @return {void}
	 */
	public function __construct()
	{
		parent::__construct();

		$this->cache =& ee()->session->cache[__CLASS__];

		return;
	}

	// --------------------------------------------------------------------

	/**
	 * @return {void}
	 * @link http://ellislab.com/expressionengine/user-guide/development/fieldtypes.html#EE_Fieldtype::install
	 */
	public function install()
	{
		return;
	}

	// --------------------------------------------------------------------

	/**
	 * @return {string}
	 * @link http://ellislab.com/expressionengine/user-guide/development/fieldtypes.html#EE_Fieldtype::display_field
	 */
	public function display_field($current_value=null)
	{
		$this->_get_channels();
		$this->_get_categories();

		$current_value = $this->_clean_current_value($current_value);

		$embed_js  = '<script>DYNAMICEE = {};';
		$embed_js .= sprintf('DYNAMICEE.FIELD_ID = "%d";', $this->field_id);
		$embed_js .= sprintf('DYNAMICEE.CURRENT = %s;', $current_value);
		$embed_js .= sprintf('DYNAMICEE.CHANNELS = %s;', json_encode($this->cache['channels']));
		$embed_js .= sprintf('DYNAMICEE.CHANNEL_CAT_GROUPS = %s;', json_encode($this->cache['cat_groups_by_channel']));
		$embed_js .= sprintf('DYNAMICEE.CATEGORIES = %s;', json_encode($this->cache['catgroups']));
		$embed_js .= '</script>';

		$this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.DYNAMICEE_ASSET_PATH.'cp.css" />');
		$this->EE->cp->add_to_foot($embed_js);
		$this->EE->cp->add_to_foot('<script src="'.DYNAMICEE_ASSET_PATH.'cp.js"></script>');

		return form_hidden($this->field_name, $current_value);
	}

	// --------------------------------------------------------------------

	/**
	 * @param {string}
	 * @link http://ellislab.com/expressionengine/user-guide/development/fieldtypes.html#EE_Fieldtype::replace_tag
	 */
	public function replace_tag($data, $params=array(), $tagdata=FALSE)
	{
		$return = ''; // tag replacement string

		if(($decoded = json_decode($data)) !== FALSE)
		{
			/* channel param */
			if(!empty($decoded->channel_ids))
				ee()->db->where_in('channel_titles.channel_id', $decoded->channel_ids);

			/* category param */
			if(!empty($decoded->category_ids))
			{
				foreach($decoded->category_ids as $key =>& $val)
					if(!$decoded->category_ids[$key])
						unset($decoded->category_ids[$key]);

				if(!empty($decoded->category_ids))
				{
					ee()->db->where_in('category_posts.cat_id', $decoded->category_ids);
					ee()->db->join('category_posts', 'channel_titles.entry_id = category_posts.entry_id', 'inner');
				}
			}

			/* date range */
			ee()->db->where('channel_titles.entry_date < ', time());
			switch($decoded->date_pref)
			{
				case "6month":
					ee()->db->where('channel_titles.entry_date > ', strtotime("6 months ago"));
					break;
				case "1year":
					ee()->db->where('channel_titles.entry_date > ', strtotime("1 year ago"));
					break;
				case "2year":
					ee()->db->where('channel_titles.entry_date > ', strtotime("2 years ago"));
					break;
				case "none":
				default:
			}

			/* limit */
			$limit = (int) $decoded->limit;
			if(($limit < 0) || ($limit > 100))
				$limit = 40;
			ee()->db->limit($limit);

			/* the rest of the basic query */
			$query = ee()->db
				->select('channel_titles.entry_id')
				->where('channel_titles.status', 'open')
				->order_by('channel_titles.entry_date', 'asc')
				->where('channel_titles.site_id', ee()->config->item('site_id'))
				->group_by('channel_titles.entry_id')
				->get('channel_titles');

			$entry_ids = array();

			if($query->num_rows())

				foreach($query->result() as $row)

					if(!in_array($row->entry_id, $entry_ids))

						$entry_ids[] = $row->entry_id;

			$return = implode('|', $entry_ids);

		}

		return $return;
	}

	// --------------------------------------------------------------------
	// PRIVATE METHODS
	// --------------------------------------------------------------------

	/**
	 * @return {string} JSON object string
	 */
	private function _clean_current_value($val='')
	{
		$val = html_entity_decode($val);

		if(json_decode($val) == FALSE)
			return (string) json_encode(array(
				'channel_ids' => array(), 
				'category_ids' => array(),
				'limit' => 20,
				'date_start' => date('m/d/Y', strtotime("6 months ago")),
				'date_pref' => '6month'
			));
		else
			return (string) $val;
	}

	// --------------------------------------------------------------------

	/**
	 * @return {array}
	 */
	private function _get_channels()
	{
		if(isset($this->cache['channels']))
			return $this->cache['channels'];

		else
		{
			$this->cache['channels'] = array();
			$this->cache['cat_groups_by_channel'] = array();

			$result = ee()->db
				->select('channel_id, channel_title, cat_group')
				->order_by('channel_title', 'asc')
				->get('channels')
				->result();

			foreach($result as $row)
			{	
				$this->cache['channels'][$row->channel_id] = $row->channel_title;

				/* cache the categories associated with this channel, in reverse-lookup array */
				$this->cache['cat_groups_by_channel'][$row->channel_id] = array();
				
				foreach(explode('|', $row->cat_group) as $cat_group_id)
				{
					if($cat_group_id)
					{
						if(!in_array($cat_group_id, $this->cache['cat_groups_by_channel'][$row->channel_id]))
							$this->cache['cat_groups_by_channel'][$row->channel_id][] = $cat_group_id;
					}
				}
			}

			return $this->cache['channels'];
		}
	}

	// --------------------------------------------------------------------

	/**
	 * @return {array}
	 */
	private function _get_categories()
	{
		if(isset($this->cache['categories']))
			return $this->cache['categories'];

		if(!isset($this->cache['channels']))
			$this->_get_channels();

		$this->cache['catgroups'] = array();

		$C =& $this->cache['catgroups'];

		/* get the cat groups */
		$result = ee()->db
			->select('group_id, group_name')
			->order_by('group_name', 'asc') /* FYI - not respected by Chome when transmitted via AJAX */
			->get('category_groups')
			->result();

		foreach($result as $row)
		{
			$C[$row->group_id] = array(
				'id' => $row->group_id,
				'name' => $row->group_name,
				'categories' => array()
			);
		}

		/* 	now get the actual cats..
			NOTE: we're building a multi-dimension array below; ordering by zero 
			(parents) first should ensure we don't try to 
			add a child cat to a parent that doesn't exist 
		*/
		$result = ee()->db
			->select('cat_id, cat_name, parent_id, group_id')
			->order_by('parent_id', 'asc') 
			->order_by('cat_name', 'asc') /* FYI - not respected by Chome when transmitted via AJAX */
			->get('categories')
			->result();

		foreach($result as $row)
		{
			if(!$row->parent_id)
				$C[$row->group_id]['categories'][$row->cat_id] = array(
					'name' => $row->cat_name,
					'children' => array()
				);
			else
				$C[$row->group_id]['categories'][$row->parent_id]['children'][$row->cat_id] = array(
					'id' => $row->cat_id,
					'name' => $row->cat_name
				);
		}

		return $this->cache['catgroups'];
	}

}

// END Dynamicee_ft class

/* End of file ft.dynamicee.php */
/* Location: ./system/expressionengine/third_party/dynamicee/ft.dynamicee.php */