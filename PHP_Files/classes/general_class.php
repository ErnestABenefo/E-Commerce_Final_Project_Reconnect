<?php
//connect to database class
require_once __DIR__ . "/../settings/db_class.php";

/**
*General class to handle all functions 
*/
/**
 *@author Ernest Ampomah-Benefo
 *
 */

//  public function add_brand($a,$b)
// 	{
// 		$ndb = new db_connection();	
// 		$name =  mysqli_real_escape_string($ndb->db_conn(), $a);
// 		$desc =  mysqli_real_escape_string($ndb->db_conn(), $b);
// 		$sql="INSERT INTO `brands`(`brand_name`, `brand_description`) VALUES ('$name','$desc')";
// 		return $this->db_query($sql);
// 	}

if (!class_exists('general_class')) {
class general_class extends db_connection
{
	//--INSERT--//
	
	/**
	 * Create academic profile for a user
	 * @param int $user_id User ID
	 * @param int $department_id Department ID
	 * @param string $major Major/degree program
	 * @param int $graduation_year Graduation year (optional)
	 * @return int|bool Profile ID on success, false on failure
	 */
	public function createAcademicProfile($user_id, $department_id, $major, $graduation_year = null)
	{
		$ndb = new db_connection();
		$ndb->db_connect();
		
		$stmt = $ndb->db->prepare(
			"INSERT INTO UserAcademicProfile (user_id, department_id, major, graduation_year) 
			 VALUES (?, ?, ?, ?)"
		);
		
		$stmt->bind_param("iisi", $user_id, $department_id, $major, $graduation_year);
		
		if ($stmt->execute()) {
			return $ndb->db->insert_id;
		}
		return false;
	}

	//--SELECT--//

	/**
	 * Get university by ID
	 * @param int $university_id University ID
	 * @return array|bool University data on success, false on failure
	 */
	public function getUniversityById($university_id)
	{
		$ndb = new db_connection();
		$ndb->db_connect();
		
		$stmt = $ndb->db->prepare("SELECT * FROM University WHERE university_id = ?");
		$stmt->bind_param("i", $university_id);
		$stmt->execute();
		$result = $stmt->get_result();
		return $result ? $result->fetch_assoc() : false;
	}

	//--UPDATE--//



	//--DELETE--//
	

}
} // End of class_exists check

?>