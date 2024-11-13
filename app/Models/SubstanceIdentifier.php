<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubstanceIdentifier extends Model
{
    /** @use HasFactory<\Database\Factories\SubstanceIdentifierFactory> */
    use HasFactory;

    /** IDENTIFIERS CONSTANTS */
    const TYPE_NAME = 1;
    const TYPE_SMILES = 2;
    const TYPE_INCHIKEY = 3;
    const TYPE_PUBCHEM = 4;
    const TYPE_DRUGBANK = 5;
    const TYPE_CHEBI = 6;
    const TYPE_PDB = 7;
    const TYPE_CHEMBL = 8;

    /** Servers constants */
    const SERVER_PUBCHEM = self::TYPE_PUBCHEM;
    const SERVER_DRUGBANK = self::TYPE_DRUGBANK;
    const SERVER_CHEBI = self::TYPE_CHEBI;
    const SERVER_PDB = self::TYPE_PDB;
    const SERVER_CHEMBL = self::TYPE_CHEMBL;
    const SERVER_UNICHEM = 9;
    const SERVER_RDKIT = 10;
    const SERVER_MOLMEDB = 99;

    /** ACTIVE STATES */
    const INACTIVE = 0;
    const ACTIVE = 1;

    /** STATES */
    const STATE_NEW = SubstanceIdentifierValidation::STATE_NEW;
    const STATE_VALIDATED = SubstanceIdentifierValidation::STATE_VALIDATED;
    const STATE_INVALID = SubstanceIdentifierValidation::STATE_INVALID;

    private static $enum_states = array
    (
        self::STATE_NEW => 'New',
        self::STATE_VALIDATED => 'Validated',
        self::STATE_INVALID => 'Invalid',  
    );

    /**
     * FLAGS
     */
    const SMILES_FLAG_CANONIZED = 1;
    const SMILES_FLAG_CANONIZATION_ERROR = 2;

    /**
     * Enum types of identifiers
     */
    private static $enum_types = array
    (
        self::TYPE_NAME => 'name',
        self::TYPE_SMILES => 'smiles',
        self::TYPE_INCHIKEY => 'inchikey',
        self::TYPE_PUBCHEM => 'pubchem',
        self::TYPE_DRUGBANK => 'drugbank',
        self::TYPE_CHEBI  => 'chebi',
        self::TYPE_PDB    => 'pdb',
        self::TYPE_CHEMBL => 'chembl',
    );

    /**
     * Enum types of servers
     */
    private static $enum_servers = array
    (
        self::SERVER_PUBCHEM => 'Pubchem server',
        self::SERVER_DRUGBANK => 'Drugbank server',
        self::SERVER_CHEBI  => 'ChEBI server',
        self::SERVER_PDB    => 'PDB server',
        self::SERVER_CHEMBL => 'ChEMBL server',
        self::SERVER_UNICHEM => 'Unichem server',
        self::SERVER_RDKIT => 'RDkit software',
        self::SERVER_MOLMEDB => 'MolMeDB server',
    );

    /** 
     * Holds info about preffered servers for getting given identifiers
     * 
     * PERSIST PRIORITY!
     */
    public static $type_servers = array
    (
        self::TYPE_NAME => array
        (
            self::SERVER_PDB,
            self::SERVER_PUBCHEM,
        ),
        self::TYPE_SMILES => array
        (
            self::SERVER_CHEMBL, // 8
            self::SERVER_PUBCHEM, // 4
            self::SERVER_PDB, // 7
        ),
        self::TYPE_INCHIKEY => array
        (
            self::SERVER_RDKIT
        ),
        self::TYPE_PUBCHEM => array
        (
            self::SERVER_UNICHEM
        ),
        self::TYPE_DRUGBANK => array
        (
            self::SERVER_PDB,
            self::SERVER_UNICHEM
        ),
        self::TYPE_CHEBI  => array
        (
            self::SERVER_UNICHEM
        ),
        self::TYPE_PDB    => array
        (
            self::SERVER_UNICHEM
        ),
        self::TYPE_CHEMBL => array
        (
            self::SERVER_UNICHEM
        ),
    );

    /**
     * Holds info about what servers are idependent on MolMeDB
     */
    public static $independent_servers = array
    (
        self::SERVER_PUBCHEM,
        self::SERVER_PDB,
        self::SERVER_CHEMBL,
        self::SERVER_CHEBI,
        self::SERVER_DRUGBANK 
    );

    /**
     * Credible sources
     */
    public static $credible_sources = array
    (
        self::SERVER_PUBCHEM,
        self::SERVER_PDB,
        self::SERVER_CHEMBL,
        self::SERVER_CHEBI,
        self::SERVER_DRUGBANK,
        self::SERVER_RDKIT,
        self::SERVER_MOLMEDB
    );

    
    /**
     * Enum active states
     */
    private static $enum_active_states = array
    (
        self::INACTIVE => "inactive",
        self::ACTIVE => 'active'
    );

    /**
     * Checks, if source is credible
     * 
     * @param int $server_id
     * 
     * @return boolean
     */
    // public function is_credible($server_id)
    // {
    //     if(!$this || !$this->id)
    //     {
    //         return false;
    //     }

    //     return in_array($server_id, self::$credible_sources); // && $this->state === self::STATE_VALIDATED;
    // }

    /**
     * Returns all available servers
     */
    public static function servers() : array
    {
        return self::$enum_servers;
    }

    /**
     * Returns all available identifier types
     */
    public static function types() : array
    {
        return self::$enum_types;
    }

    /**
     * Returns all available states
     */
    public static function states() : array
    {
        return self::$enum_states;
    }

    /**
     * Returns assigned substance
     */
    public function substance() : BelongsTo
    {
        return $this->belongsTo(Substance::class);
    }

    /**
     * Return parent identifier
     */
    public function parent() : BelongsTo
    {
        return $this->belongsTo(SubstanceIdentifier::class, 'parent_id');
    }

    /**
     * Returns record author
     */
    public function user() : BelongsTo 
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Returns datasets, from which the record was imported
     */
    public function datasets() : BelongsToMany
    {
        return $this->belongsToMany(Dataset::class, 'substance_identifier_dataset');
    }

    /**
     * Returns identifier, which replaced this identifier
     */
    public function replacedBy() : BelongsToMany
    {
        return $this->belongsToMany(SubstanceIdentifier::class, 'substance_identifier_changes', 'old_id', 'new_id')
            ->withPivot('message', 'datetime', 'user');
    }

    /**
     * Returns identifiers, which were replaced by this identifier
     */
    public function replacing() : BelongsToMany
    {
        return $this->belongsToMany(SubstanceIdentifier::class, 'substance_identifier_changes', 'new_id', 'old_id')
            ->withPivot('message', 'datetime', 'user');
    }
}
