<?php
namespace App\Libraries;

use App\Models\Substance;

class Identifiers
{
	CONST PREFIX = 'MM';
	CONST min_len = 7;
	CONST PATTERN = '/^[M]{2}[0-9]{5,}$/i';
	
	/**
	 * Generates new identifier
	 * 
	 * @param int $id
	 * 
	 * @return string
	 */
	public static function generate($id = NULL)
	{
		// $subst_link_model = new Substance_links(); // TODO
		$identifier = self::get_identifier($id);
		
		// Check, if already exists
		$exists = Substance::query()->where('identifier', $identifier)->get_one();

		// $exists_2 = $subst_link_model->where('identifier', $identifier)->get_one();

		if(!$id || $exists->id) // || $exists_2->id)
		{
			$id = Substance::max('id') + 1;
			return self::get_identifier($id);
		}
		else
		{
			return $identifier;
		}	
	}

	/**
	 * Generates MolMeDB identifier
	 * 
	 * @param int $id
	 * 
	 * @return string
	 */
	public static function get_identifier($id)
	{
		// Get string value
		$id = strval($id);
		$id_len = strlen($id);
		
		$zero_count = self::min_len - strlen(self::PREFIX) - $id_len;
		
		for($i = 0; $i < $zero_count; $i++)
		{
			$id = '0' . $id;
		}
			
		return self::PREFIX . $id;
	}

	/**
	 * Checks, if given identifier is valid
	 * 
	 * @param string $identifier
	 * 
	 * @return boolean
	 */
	public static function is_valid($identifier)
	{
		if(!$identifier	|| trim($identifier) == '' || strlen($identifier) < self::min_len)
		{
			return false;
		}

		return preg_match(self::PATTERN, $identifier);
	}

	// /**
	//  * Checks, if given string is known identifier form
	//  * 
	//  * @param string $string
	//  * 
	//  * @return boolean
	//  */
	// public static function is_identifier($string)
	// {
	// 	$string = trim($string);

	// 	return self::is_valid($string) || 
	// 		Upload_validator::check_identifier_format($string, Upload_validator::DRUGBANK, True) ||
	// 		Upload_validator::check_identifier_format($string, Upload_validator::PUBCHEM, True) ||
	// 		Upload_validator::check_identifier_format($string, Upload_validator::CHEMBL_ID, True) || 
	// 		Upload_validator::check_identifier_format($string, Upload_validator::CHEBI_ID, True);
	// }

	// /**
    //  * Checks, if some identifier is in name 
    //  * If yes, then fill missing column
    //  * 
    //  * @param string $name
	//  * 
	//  * @return array|false - False, if not found
    //  */
    // public static function check_name_for_identifiers($name)
    // {
    //     $name = trim($name);

	// 	if(self::is_valid($name))
	// 	{
	// 		return FALSE;
	// 	}

	// 	$result = array();

	// 	// Is valid drugbank name?
	// 	if(Upload_validator::check_identifier_format($name, Upload_validator::DRUGBANK, True) && 
	// 		Url::is_valid(Config::get('drugbank_url')) && 
	// 		Url::is_reachable(Config::get('drugbank_url') . $name))
    //     {
	// 		$result['drugbank'] = $name;
    //     }
    //     elseif(Upload_validator::check_identifier_format($name, Upload_validator::CHEMBL_ID, True) && 
	// 		Url::is_valid(Config::get('chembl_url')) && 
	// 		Url::is_reachable(Config::get('chembl_url') . $name))
    //     {
    //         $result['chembl'] = $name;
    //     }

	// 	return count($result) ? $result : FALSE;
    // }
}