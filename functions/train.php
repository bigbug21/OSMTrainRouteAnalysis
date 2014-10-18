<?php 
    /**
    
    OSMTrainRouteAnalysis Copyright © 2014 sb12 osm.mapper999@gmail.com
    
    This file is part of OSMTrainRouteAnalysis.
    
    OSMTrainRouteAnalysis is free software: you can redistribute it 
    and/or modify it under the terms of the GNU General Public License 
    as published by the Free Software Foundation, either version 3 of 
    the License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
    
    */
?>
<?php
/**
 * describes the train that is used for the route
 * @author sb12
 *
 */
Class Train
{
	/**
	 * ref for train
	 * @var String
	 */
	public $ref;
	
	/**
	 * maxspeed of train
	 * @var Array[ref]
	 */
	public $maxspeed;
	
	/**
	 * max acceleration of train (not used at the moment)
	 * @var Array[ref]
	 */
	public $acceleration;
	
	/**
	 * max brake acceleration of train
	 * @var Array[ref]
	 */
	public $brake;
	
	/**
	 * masss of empty train
	 * @var Array[ref]
	 */
	public $mass_empty;
	
	/**
	 * max torque of engine
	 * @var Array[ref]
	 */
	public $torque;
	
	/**
	 * max power of engine
	 * @var Array[ref]
	 */
	public $power;
	
	/**
	 * type of train (regional, tram, highspeed etc)
	 * @var Array[ref]
	 */
	public $type;
	
	/**
	 * name of train
	 * @var Array[ref]
	 */
	public $name;
	
	/**
	 * available seats in train (not used at the moment)
	 * @var Array[ref]
	 */
	public $seats;
	
	/**
	 * lenght of train
	 * @var Array[ref]
	 */
	public $length;
	
	/**
	 * filename of image of train
	 * @var Array[ref]
	 */
	public $image;
	
	/**
	 * initializes train
	 * @param string $train ref of train
	 */
	public function __construct($train="")
	{
		//including train details
		include "train_details.php";
		
		//check if a train was loaded
		if ( isset($_GET["train"] ) )
		{
			$train = $_GET["train"];
		}
		
		// set default train if not specified
		// FIXME set default train depending on route type
		if ( !$train )
		{
			$train = "BR425";	
		}
		
		//check if train exists
		if ( !isset($tr_maxspeed[$train]) )
		{
			$train = "BR425"; //set default train
		}
		
		//get details for set train
		$this->ref = $train;
		$this->maxspeed = $tr_maxspeed[$train];
		$this->acceleration = $tr_acc[$train] * 12960;
		$this->brake = max($tr_brake[$train],0.7) * 12960; // max 0.7 m/s² for comfort reasons
		$this->mass_empty = $tr_mass_empty[$train] * 12960;
		$this->torque = $tr_torque[$train] * 12960;
		$this->power = $tr_power[$train] * 12960;
		$this->type = $tr_type[$train];
		$this->name = $tr_name[$train];
		$this->seats = $tr_seats[$train];
		$this->length = $tr_length[$train];
		if ( isset($tr_image[$train]) )
		{
			$this->image = $tr_image[$train];
		}
	}
	
	/**
	 * calculates acceleration of train
	 * @param number $massin mass of train
	 * @param number $torque maximum torque
	 * @param number $power maximum power
	 * @param number $passenger passengers in train
	 * @param number $v1in start speed
	 * @param number $v2in end speed
	 * @return number acceleration
	 */
	function acceleration($massin,$torque,$power,$passenger,$v1in,$v2in)
	{
		//no acceleration
		if ( $v1in == $v2in )
		{
			return 0;
		}
		
		//calculate weight
		$mass = $massin + $passenger * 0.080; // average: 80kg/passenger
	
		// a_max = F / m;
		$a_max = $torque / $mass;
		
		$v1 = $v1in / 3.6; // convert to m/s
		$v2 = $v2in / 3.6; // convert to m/s
		
		//get point where acceleration depends on available power
		$v_krit = $power / $torque;
	
		//Case 1: v1 and v2 lower than v_krit
		if ( $v1 < $v_krit && $v2 < $v_krit )
		{
			return $a_max * 12960;
		}	
		
		//Case 2: v1 and v2 higher than v_krit
		if ( $v1 > $v_krit && $v2 > $v_krit )
		{
			// a(v) = P / ( v * m )
			// a_m = ( ( P / m ) * ( log( v1 / v2 ) ) ) / ( v2 - v1 )
			$a_m = ( ( $power / $mass ) * ( log( $v1 / $v2  ) ) ) / ( $v1 - $v2 );
			if ( $a_m < 0 )
			{
				//This is an error!! FIXME
			}
			return $a_m * 12960;
		}
		
		//Case 3: v2 higher than v_krit, v1 lower than v_krit	
		if ( $v1 < $v_krit && $v2 > $v_krit )
		{
			// a(v) = P / ( v * m )
			// a_m = ( ( P / m ) * ( log( v1 / v2 ) ) ) / ( v2 - v1 )
			$a_m2 = ( ( $power / $mass ) * ( log( $v2 / $v_krit ) ) ) / ( $v2 - $v_krit );
	
			$a_m= ( $a_m2 * ( $v2 - $v_krit ) + $a_max * ( $v_krit - $v1 ) ) / ( $v2 - $v1 );
			if ( $a_m < 0 )
			{
				//This is an error!! FIXME
			}
			return $a_m * 12960;
		}

		//Case 4: v1 higher than v_krit, v2 lower than v_krit
		if ( $v1 > $v_krit && $v2 < $v_krit )
		{
			// a(v) = P / ( v * m )
			// a_m = ( ( P / m ) * ( log( v1 / v2 ) ) ) / ( v2 - v1 )
			$a_m2 = ( ( $power / $mass ) * ( log( $v1 / $v_krit ) ) ) / ( $v1 - $v_krit );
	
			$a_m = ( $a_m2 * ( $v1 - $v_krit ) + $a_max * ( $v_krit - $v2 ) ) / ( $v1 - $v2 );
			if ( $a_m < 0 )
			{
				//This is an error!! FIXME
			}
			return $a_m * 12960;
		}
	}
	
	/**
	 * generates form to change the train
	 */
	function changeTrain()
	{
		include "train_details.php";
		?>
		<select name="train">
		<?php 
		foreach ( $tr_name as $ref => $name )
		{
			?>
			<option <?php if ( $ref == $this->ref ){ echo 'selected="selected"'; } ?>value="<?php echo $ref; ?>"><?php echo $name; ?></option>
			<?php 
		}
		?>
		</select>
		<?php 
		
	}
}
?>