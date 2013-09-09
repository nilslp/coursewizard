<?php
/**
 * Class for printing out course entries  
 */
 
 class course_writer{

		public static function write_course($crs,$return=false){
			$arr_ret = array();
			foreach($crs as $cprop => $cval){
				$method = 'write_course_' . $cprop;
				if(method_exists(self,$method)){
					
				}
			}
		}
 } 
