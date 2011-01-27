<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$plugin_info = array(
                 'pi_name'          => 'Get Parameters',
                 'pi_version'       => '1.2.0',
                 'pi_author'        => 'Mark Croxton',
                 'pi_author_url'    => 'http://www.hallmark-design.co.uk',
                 'pi_description'   => 'Register and sanitize POST/GET globals, with persist option for pagination',
                 'pi_usage'         => Get_parameters::usage()
               );

class Get_parameters {

	public $return_data = '';
	public $id = __CLASS__; // identifier for this group of fields
	public $persist; // persist POSTed field values? (default is true)
	public $duration = 1800; // duration of cookie, in seconds
	
	private $_fields = array();
   
	/** 
	 * Constructor
	 * @access public
	 * @return void
	 */ 
	function Get_parameters()
    {
    	$this->EE =& get_instance();

		$post = explode('|', $this->EE->TMPL->fetch_param('post'));
		$get = explode('|', $this->EE->TMPL->fetch_param('get'));
		$this->id = $this->EE->TMPL->fetch_param('id', $this->id);
		$this->persist = (strtolower($this->EE->TMPL->fetch_param('persist', 'yes')) == 'yes') ? true : false;
		$this->duration = $this->EE->TMPL->fetch_param('duration', $this->duration);
		
		// global filtering
		$this->strip_tags = (strtolower($this->EE->TMPL->fetch_param('strip_tags', 'yes')) == 'yes') ? true : false;
		$this->strip_curly_braces = (strtolower($this->EE->TMPL->fetch_param('strip_curly_braces', 'yes')) == 'yes') ? true : false;
		
		// fetch fields from POST array, or persisted in COOKIE
		if (!empty($post))
		{
			$this->_fetch_fields($post, 'post');
		}
		
		if (!empty($get))
		{
			$this->_fetch_fields($get, 'get');
		}
		
		// make a copy of the tagdata
		$tagdata = $this->EE->TMPL->tagdata;
		
		if (empty($tagdata))
		{
			// Get_parameters is being used a single tag, 
			// output var values direct in a pipe delimited string
			foreach($this->_fields as $key => $val)
			{
				$this->return_data .= $val.'|';
			}
			$this->return_data = rtrim($this->return_data, '|');
		}
		else
		{
			// Get_parameters is being used a tag pair
			// replace {variable} placeholders with field values
			foreach($this->_fields as $key => $val)
			{	
		      	$tagdata = $this->EE->TMPL->swap_var_single($key, $val, $tagdata);
			}
		
			// cleanup
		
			// restore no_results conditionals
			$no_results_id =  ($this->id !== __CLASS__) ? $this->id.'_' : '';
			$tagdata = str_replace('{'.$no_results_id.'no_results}', '{if no_results}', $tagdata);
			$tagdata = str_replace('{/'.$no_results_id.'no_results}', '{/if}', $tagdata);
			
			$this->return_data = $tagdata;
		}
    }

	/** 
	 * Either fetches form field data from the $_POST array, saves to cookie and populates $_fields array
	 * Or, if $_POST is not set retrieve values, populates $_POST and $_fields from the cookie
	 * @access private
	 * @param array $fields
	 * @return array
	 */ 
	function _fetch_fields($fields=array(), $global = 'post') 
	{	
		if ($global == 'post')
		{
			if (count($_POST)>0)
			{
				// form submitted - get field values from $_POST and set $fields array
				foreach($fields AS $field) 
				{
					if (!!$this->EE->input->post($field))
					{
						// get value from $_POST, run through xss_clean()
						$this->_fields[$field] = $this->EE->input->post($field, TRUE);
					}
				}
			
				// now save to cookie (overwrites existing cookie if it exists)
				if ($this->persist)
				{	 
					$this->EE->functions->set_cookie($this->id, serialize($this->_fields), $this->duration);
				}
			} 
			else 
			{	
				// else retrieve cookie data, restore $fields and $_POST arrays
				if (!!$this->EE->input->cookie($this->id))
				{
					$this->_fields = $_POST = unserialize($this->EE->input->cookie($this->id));
					
					// in case the cookie has been tampered with, let's run the data through xss_clean()
					$this->_fields = $this->EE->security->xss_clean($this->_fields);
				}
			}
		}
		else if ($global == 'get')
		{
			if (count($_GET)>0)
			{
				// get field values from $_GET and set $fields array
				foreach($fields AS $field) 
				{
					if (!!$this->EE->input->get($field))
					{
						// get value from $_GET, run through xss_clean()
						$this->_fields[$field] = $this->EE->input->get($field, TRUE);
					}
				}
			} 
		}
		
		
		foreach($this->_fields AS &$field) 
		{
			// strip tags?
			if ($this->strip_tags)
			{
				$field = strip_tags($field);
			}
			
			// strip curly braces?
			if ($this->strip_curly_braces)
			{
				$field = str_replace(array(LD, RD), '', $field);
			}
			
			// correct for ampersand bug in xss_clean() that leaves trailing semi-colon
			$field = rtrim($field, ';');
		}

		return $this->_fields;
	}

    function usage()
    {
		ob_start(); 
		?>
		{exp:get_parameters post="limit|orderby|sort" id="my_form_id" parse="inward"}
			{exp:channel:entries channel="my_channel" paginate="both" limit="{limit}" orderby="{orderby}" sort="{sort}" }
				... 
			  	{my_form_id_no_results}
					No results
				{/my_form_id_no_results}
			{/exp:channel:entries}
		{/exp:get_parameters}

		<?php
		$buffer = ob_get_contents();
		ob_end_clean(); 
		return $buffer;
    }
} 

/* End of file pi.get_parameters.php */ 
/* Location: ./system/expressionengine/third_party/get_parameters/pi.get_parameters.php */
 