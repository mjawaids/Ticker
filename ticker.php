<?php

/*
Plugin Name: Watts calculator
Plugin URI: http://www-ibexoft.rhcloud.com
Description: Calculates watts consumed based on watts installed.
Version: 0.1
Author: Muhammad Jawaid Shamshad
Author URI: http://www.ibexofts.tk
License: GNU Public License
*/


/*
totalwatts.txt - to store double value, and to get from previous session
wattsinstalled.txt - get value of watts installed
dataformatted.txt - matrix
*/

/**
 * A class to calculate watts
 * @author Muhammad Jawaid Shamshad
 *
 */
class Ticker
{
	//Default number of watts installed. Don't change this value
	var $DEFAULT_WATTS_INSTALLED = 20000;

	//Percent of value at 20000 watts that is added per increase of 1 watt installed
	var $PERCENT_ADDED_PER_WATT = 0.00005;

	//Finals necessary because the data sheet starts in October
	var $OCTOBER = 1;
	var $NOVEMBER = 2;
	var $DECEMBER = 3;
	var $JANUARY = 4;
	var $FEBRUARY = 5;
	var $MARCH = 6;
	var $APRIL = 7;
	var $MAY = 8;
	var $JUNE = 9;
	var $JULY = 10;
	var $AUGUST = 11;
	var $SEPTEMBER = 12;

	//Finals for time
	var $NUM_MONTHS = 12;
	var $NUM_HOURS = 24;
	var $SECONDS_IN_HOUR = 3600;

	//Number of digits in the number displayed
	var $NUM_DIGITS = 12;

	//This double array contains all the data from the excel spreadsheet
	var $arrayOfData;

	//Starting value of total watt hours
	var $totalWattHours = 0.0;

	//Number of watts installed over 20000. Used to figure out how much percent to add to the value
	var $numWattsOverDefault;

	//Number of watts installed, pulled from wattsinstalled.txt
	var $wattsInstalledEntered;

	//Amount to add based on the watts installed
	var $amountToAddToAmountAdded;

	//Time variables, used to compute how much to add to the total watt hours
	var $hour;
	var $minute;
	var $month;
	var $second;

	//Number of watt hours
	var $wattHours;

	//Amount to add at given time
	var $amountAdded;
	
	/**
	 * Constructor
	 */
	function __construct()
	{
		//Gets the total watt hours that has been saved from previous sessions
		$this->totalWattHours = $this->getTotalWattsHoursFromPreviousSession();
	}

	/**
	 * Initialize plugin
	 */
	function init()
	{
		// schedule an event
		$this->schedule();
	}
	
	/**
	 * Uninitialize plugin
	 */
	function uninit()
	{
		// clear all events
		$this->unschedule();
	}
	
	/**
	 * Schedule the plugin
	 */
	function schedule()
	{
		// check if event is not defined, then schedule one
		if( !wp_next_scheduled( 'ibx_update_ticker' )){
			wp_schedule_event( time(), 'tenminute', 'ibx_update_ticker' );
		}
	}
	
	/**
	 * Clear schedule the plugin
	 */
	function unschedule()
	{
		wp_clear_scheduled_hook('ibx_update_ticker');
	}

	/**
	 * Defines the 1 second interval
	 * @return Interval array  
	 */
	function define_interval()
	{
		$schedules['tenminute'] = array(
		      'interval'=> 1,
		      'display'=>  __('Once Every 1 Second')
		  );
		  
		return $schedules;
	}

	/**
	 * This method gets called every second. It's used to update the graphics. CALLBACK for timer
	 */
	function calculate()
	{	
		$this->getFile();

		//Sets up calendar and assigns time variables
		$this->hour 	= date('G');
		$this->minute 	= date('i');
		$this->month 	= date('n');
		$this->second 	= date('s');
		$this->setMonth();

		//Calculates how many watts installed over 20000 (default)
		$this->numWattsOverDefault = $this->getNumWattsInstalled() - $this->DEFAULT_WATTS_INSTALLED;

		//So that the ticker can automatically update at the start of a new hour
		//if($this->minute == 0 && $this->second == 0)
		{
			$this->wattHours = $this->arrayOfData[$this->hour][$this->month];
			$multiplier = $this->numWattsOverDefault / 10000;
			$this->amountAdded = ($this->wattHours + ($multiplier * $this->wattHours/2)) / $this->SECONDS_IN_HOUR;
		}

		//Adds the correct amount to the total watt hours
		$this->totalWattHours+= $this->amountAdded;

		//Writes the total watt hours to tatalwatts.txt so that all the data can be saved
		update_option("ibx_totalwatts", $this->totalWattHours);
	}

	/**
	 * Pulls the file of data and fills the data array with the data.
	 * @param arrayOfData
	 */
	function getFile()
	{
		$this->arrayOfData = array(
									//array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0), 
									//array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0), 
									//array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0), 
									//array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0), 
									//array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0), 
									//array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0), 
									//array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0), 
									//array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0), 

									array(424, 308, 251, 266, 291, 420, 487, 537, 536, 535, 516, 465), 
									array(424, 308, 251, 266, 291, 420, 487, 537, 536, 535, 516, 465), 
									array(424, 308, 251, 266, 291, 420, 487, 537, 536, 535, 516, 465), 
									array(424, 308, 251, 266, 291, 420, 487, 537, 536, 535, 516, 465), 
									array(424, 308, 251, 266, 291, 420, 487, 537, 536, 535, 516, 465), 
									array(424, 308, 251, 266, 291, 420, 487, 537, 536, 535, 516, 465), 
									array(424, 308, 251, 266, 291, 420, 487, 537, 536, 535, 516, 465), 
									array(424, 308, 251, 266, 291, 420, 487, 537, 536, 535, 516, 465), 

									array(424, 308, 251, 266, 291, 420, 487, 537, 536, 535, 516, 465), 
									array(760, 552, 450, 477, 521, 752, 873, 963, 961, 959, 925, 834), 
									array(10832, 7868, 6416, 6794, 7429, 10722, 12442, 13723, 13699, 13662, 13186, 11881), 
									array(14592, 10599, 8643, 9153, 10007, 14444, 16761, 18486, 18454, 18404, 17763, 16005), 
									array(15968, 11598, 9459, 10016, 10951, 15806, 18342, 20230, 20194, 20140, 19439, 17514), 
									array(15856, 11517, 9392, 9946, 10874, 15695, 18213, 20088, 20052, 19999, 19302, 17392), 
									array(14684, 10666, 8698, 9211, 10070, 14535, 16867, 18603, 18570, 18520, 17875, 16106), 
									array(12476, 9062, 7390, 7826, 8556, 12350, 14331, 15806, 15778, 15735, 15188, 13684), 
									array(8836, 6418, 5234, 5542, 6060, 8746, 10149, 11194, 11174, 11145, 10756, 9692), 
									array(4,000, 2905, 2369, 2509, 2743, 3959, 4595, 5068, 5059, 5045, 4869, 4387), 
									array(344, 250, 204, 216, 236, 341, 395, 436, 435, 434, 419, 377), 

									array(344, 250, 204, 216, 236, 341, 395, 436, 435, 434, 419, 377), 
									array(344, 250, 204, 216, 236, 341, 395, 436, 435, 434, 419, 377), 
									array(344, 250, 204, 216, 236, 341, 395, 436, 435, 434, 419, 377), 
									array(344, 250, 204, 216, 236, 341, 395, 436, 435, 434, 419, 377), 
									array(344, 250, 204, 216, 236, 341, 395, 436, 435, 434, 419, 377), 
									
									//array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0), 
									//array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0), 
									//array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0), 
									//array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0), 
									//array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0), 
									);
		
		//dataFile = new Scanner(new BufferedReader(new FileReader("dataformatted.txt")));

		return $this->arrayOfData;
	}

	/**
	 * This method modifies the month, so that it conforms to the data file.
	 * Because the data file starts in October, the month needs to be correctly
	 * modified.
	 */
	function setMonth()
	{
		if($this->month == 1)
			$this->month = $this->JANUARY;
		if($this->month == 2)
			$this->month = $this->FEBRUARY;
		if($this->month == 3)
			$this->month = $this->MARCH;
		if($this->month == 4)
			$this->month = $this->APRIL;
		if($this->month == 5)
			$this->month = $this->MAY;
		if($this->month == 6)
			$this->month = $this->JUNE;
		if($this->month == 7)
			$this->month = $this->JULY;
		if($this->month == 8)
			$this->month = $this->AUGUST;
		if($this->month == 9)
			$this->month = $this->SEPTEMBER;
		if($this->month == 10)
			$this->month = $this->OCTOBER;
		if($this->month == 11)
			$this->month = $this->NOVEMBER;
		if($this->month == 12)
			$this->month = $this->DECEMBER;
	}

	/**
	 * This pulls then number of watts installed from the .txt file.
	 * @return
	 */
	function getNumWattsInstalled()
	{
		$num = 153; // get_option("ibx_wattsinstalled");	// save using admin options window
//		$wattsInstalledFile = new Scanner(new BufferedReader(new FileReader("wattsinstalled.txt")));

		return $num;
	}
	
	/**
	 * Gets the total watt hours based on data from the previous session
	 * @return
	 */
	function getTotalWattsHoursFromPreviousSession()
	{
		$num = get_option("ibx_totalwatts");
		return $num;
	}

	/**
	 * Callback function to be called on AJAX call. Echoes total watts.
	*/
	function my_action_callback() 
	{
	    $this->calculate();
	    echo get_option("ibx_totalwatts");

		die(); // this is required to return a proper result
	}

	/**
	 * Shortcode for displaying total watt hours saved.
	 * @return
	 */
	function ticker_shortcode( $atts , $content = null ) 
	{
		$ajaxurl = admin_url('admin-ajax.php');

		$r = '<div id="ibx_ticker_id" class="ibx_ticker">' . get_option("ibx_totalwatts") . '</div>';

		$r .= <<<EOT
<script type="text/javascript">
jQuery(document).ready(function($) {

	setInterval( function() {
		var data = {
			'action': 'my_action' //,
		};

		var ajaxurl = '$ajaxurl';
		$.post(ajaxurl, data, function(response) {
			$( "div.ibx_ticker" ).text( response );
		});

	}, 1000);
});
</script>
EOT;
	return $r;

	}

}	// end of class


///////////////////////////////////////////////////////////////////////////////////////////////////

$t = new Ticker();

// Filters
add_filter('cron_schedules', array(&$t, 'define_interval'));		// define custom 1 sec interval

/* The activation hook is executed when the plugin is activated. */
register_activation_hook(__FILE__, array(&$t, 'init'));			// initialize plugin

/* The deactivation hook is executed when the plugin is deactivated */
//register_deactivation_hook(__FILE__, array(&$t, 'uninit'));		// uninitialize plugin

// Add AJAX action
add_action( 'wp_ajax_my_action', array(&$t, 'my_action_callback') );
add_action( 'wp_ajax_nopriv_my_action', array(&$t, 'my_action_callback') );

// Add Shortcode
add_shortcode( 'ticker', array(&$t, 'ticker_shortcode') );

// Actions
//add_action('admin_init', array(&$t, 'add_custom_box'), 1);		// to display data listing edit screen
add_action('ibx_update_ticker', array(&$t, 'calculate'));			// check for and update every 1 sec

// end of file
?>
