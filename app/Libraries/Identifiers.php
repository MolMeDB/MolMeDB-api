<?php
namespace App\Libraries;

use App\Models\Structure;
use App\Models\StructureLink;
use Exception;

class Identifiers
{
	CONST PREFIX = 'MM';
	CONST min_len = 7;
	CONST PATTERN = '/^[M]{2}[0-9]{5,}(?:\.[0-9]+)?$/i';
	
	/**
	 * Generates new identifier
	 * 
	 * @param int $id
	 * 
	 * @return string
	 */
	public static function generate(?Structure $structure = NULL)
	{
		if($structure?->parent?->id)
		{
			if(!$structure->parent->identifier)
			{
				throw new Exception('Parent structure has no identifier.');
			}

			if(self::isSubIdentifier($structure->parent->identifier))
			{
				throw new Exception('Parent structure has assigned subidentifier - ' . $structure->parent->identifier);
			}

			if($structure->parent->identifier == preg_replace('/\.\d+$/', '', $structure->identifier))
			{
				return $structure->identifier;
			}

			$maxSuffix = \App\Models\Structure::whereRaw("split_part(identifier, '.', 1) = ?", [$structure->parent->identifier])
				->selectRaw("
					MAX(
						CASE
							WHEN position('.' in identifier) > 0
							THEN split_part(identifier, '.', 2)::int
							ELSE 0
						END
					) as max_suffix
				")
				->value('max_suffix');

			$i = 1;
			$option = $structure->parent->identifier . '.' . ($maxSuffix + $i);
			while(StructureLink::where('identifier', $option)->exists())
			{
				$i++;
				$option = $structure->parent->identifier . '.' . ($maxSuffix + $i);
			}

			$identifier = $option;
			return $identifier;
		}
		else if($structure?->identifier &&!self::isSubIdentifier($structure->identifier))
		{
			return $structure->identifier;
		}

		if($structure?->id)
		{
			$identifier = self::get_identifier($structure->id);
			$exists = Structure::query()->where('identifier', $identifier)->exists();

			if(!$exists)
			{
				return $identifier;
			}
		}

		$max = \App\Models\Structure::where('identifier', 'like', self::PREFIX.'%')
			->selectRaw("MAX( (substring(split_part(identifier, '.', 1) from '[0-9]+$') )::int ) as max_number")
			->value('max_number');

		$i = 1;
		$option = self::get_identifier($max + $i);
		while(StructureLink::where('identifier', $option)->exists())
		{
			$i++;
			$option = self::get_identifier($max + $i);
		}

		return $option;
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

	/**
	 * Checks, if given identifier is subidentifier
	 */
	public static function isSubIdentifier($identifier)
	{
		if(!self::is_valid($identifier))
		{
			return false;
		}

		return strpos($identifier, '.') !== false;
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