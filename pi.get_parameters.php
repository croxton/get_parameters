<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$plugin_info = array(
                 'pi_name'          => 'Get Parameters',
                 'pi_version'       => '1.1',
                 'pi_author'        => 'Mark Croxton',
                 'pi_author_url'    => 'http://www.hallmark-design.co.uk',
                 'pi_description'   => 'Register and persist POSTed form values',
                 'pi_usage'         => Get_parameters::usage()
               );

class Get_parameters {

	public $return_data = '';
	public $id = __CLASS__; // identifier for this group of fields
	public $persist = true; // persist field values?
	public $duration = 1800; // seconds
	
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
		$this->id = $this->EE->TMPL->fetch_param('id', $this->id);
		$this->persist = $this->EE->TMPL->fetch_param('persist', $this->persist);
		$this->duration = $this->EE->TMPL->fetch_param('duration', $this->duration);
		
		// fetch fields from POST array, or persisted in COOKIE
		$this->_fetch_fields($post);
		
		// make a copy of the tagdata
		$tagdata = $this->EE->TMPL->tagdata;

		// replace {variable} placeholders with field values
		foreach($this->_fields as $key => $val)
		{	
	      	$tagdata = $this->EE->TMPL->swap_var_single($key, $val, $tagdata);
		}
		
		// restore no_results conditionals
		$no_results_id =  ($this->id !== __CLASS__) ? $this->id.'_' : '';
		$tagdata = str_replace('{'.$no_results_id.'no_results}', '{if no_results}', $tagdata);
		$tagdata = str_replace('{/'.$no_results_id.'no_results}', '{/if}', $tagdata);
			
		$this->return_data = $tagdata;
    }

	/** 
	 * Either fetches form field data from the $_POST array, saves to cookie and populates $_fields array
	 * Or, if $_POST is not set retrieve values, populates $_POST and $_fields from the cookie
	 * @access private
	 * @param array $fields
	 * @return array
	 */ 
	function _fetch_fields($fields=array()) 
	{		
		if (count($_POST)>0)
		{
			// form submitted - get field values from $_POST and set $fields array
			foreach($fields AS $field) 
			{
				if (!!$this->EE->input->post($field))
				{
					$this->_fields[$field] = $this->EE->input->post($field);
				}
			}
			
			// now save to cookie (overwrites existing cookie if it exists)	 
			$this->EE->functions->set_cookie($this->id, serialize($this->_fields), $this->duration);
		} 
		else 
		{	
			// else retrieve cookie data, restore $fields and $_POST arrays
			if (!!$this->EE->input->cookie($this->id))
			{
				$this->_fields = $_POST = unserialize($this->EE->input->cookie($this->id));
			}
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