<?php
//connect to the user account class
include("../classes/general_class.php");

//sanitize data

// function add_user_ctr($a,$b,$c,$d,$e,$f,$g){
// 	$adduser=new customer_class();
// 	return $adduser->add_user($a,$b,$c,$d,$e,$f,$g);
// }


//--INSERT--//

/**
 * Create academic profile for a user
 * @param int $user_id User ID
 * @param int $department_id Department ID
 * @param string $major Major/degree program
 * @param int $graduation_year Graduation year (optional)
 * @return int|bool Profile ID on success, false on failure
 */
function create_academic_profile_ctr($user_id, $department_id, $major, $graduation_year = null)
{
    $general = new general_class();
    return $general->createAcademicProfile($user_id, $department_id, $major, $graduation_year);
}

//--SELECT--//

/**
 * Get university by ID
 * @param int $university_id University ID
 * @return array|bool University data on success, false on failure
 */
function get_university_by_id_ctr($university_id)
{
    $general = new general_class();
    return $general->getUniversityById($university_id);
}

//--UPDATE--//

//--DELETE--//

?>