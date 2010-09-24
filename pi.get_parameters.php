<?php
$plugin_info = array(
                 'pi_name'          => 'Get Parameters',
                 'pi_version'       => '',
                 'pi_author'        => 'Mark Croxton',
                 'pi_author_url'    => 'http://www.hallmark-design.co.uk',
                 'pi_description'   => 'Register and persist POSTed form values',
                 'pi_usage'         => My_plugin::usage()
               );

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Get_parameters {

	$return_data = '';
	$id = '';
	$fields = array();
   
	function Get_parameters()
    {
    	$this->EE =& get_instance();

		$post = explode('|', $this->EE->TMPL->fetch_param('post'));
		$this->id = isset($this->EE->TMPL->fetch_param('id')) ? $this->EE->TMPL->fetch_param('id') : get_class($this);
		
		// fetch fields from POST array, or persisted in COOKIE
		$this->_fetch_fields($post);
		
		// make a copy of the tagdata
		$tagdata = $this->EE->TMPL->tagdata;

		// replace {variable} placeholders with field values
		foreach($this->fields as $key => $val)
		{	
	      	$tagdata = $this->EE->TMPL->swap_var_single($key, $val, $tagdata);
		}	
		$this->return_data = $tagdata;
    }

	/** 
	 * Either fetches form field data from the $_POST array, saves to cookie and populates $fields array
	 * Or, if $_POST is not set retrieve values, populates $_POST and $fields from the cookie
	 * @access private
	 * @param array $fields
	 * @return array
	 */ 
	function _fetch_fields($fields=array()) 
	{	
		$cookie_length = .5; // hours
		
		if (count($_POST)>0)
		{
			// form submitted - get field values from $_POST and set $fields array
			foreach($fields AS $field) 
			{
				// form just submitted - get cleaned-up POSTed values from $IN
				if (!!$this->EE->input->post($field))
				{
					$this->fields[$field] = $this->EE->input->post($field);
				}
			}
			
			// now save to cookie (overwrites existing cookie if it exists)	 
			$this->EE->functions->set_cookie($this->id, serialize($this->fields), 60*60*$cookie_length);
		} 
		else 
		{	
			// else retrieve cookie data, restore $fields and $_POST arrays
			if (!!$this->EE->input->cookie($this->id))
			{
				$this->fields = $_POST = unserialize($this->EE->input->cookie($this->id));
			}
		}
		return $this->fields;
	}

    function usage()
    {
		ob_start(); 
		?>
		{exp:get_parameters post="limit|orderby|sort" id="a_unique_identifier" parse="inward"}
			{exp:channel:entries channel="my_channel" paginate="both" limit="{limit}" orderby="{orderby}" sort="{sort}" }
			  ... 
			{/exp:channel:entries}
		{/exp:get_parameters}

		<?php
		$buffer = ob_get_contents();
		ob_end_clean(); 
		return $buffer;
    }
} 

/* End of file pi.register_globals.php */ 
/* Location: ./system/expressionengine/third_party/plugin_name/pi.register_globals.php */
 