<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Identifier extends Model
{
    /** @use HasFactory<\Database\Factories\SubstanceIdentifierFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** IDENTIFIERS CONSTANTS */
    const TYPE_NAME = 1;
    // const TYPE_SMILES = 2;
    // const TYPE_INCHIKEY = 3;
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
    const STATE_NEW = 1;
    const STATE_VALIDATED = 2;
    const STATE_INVALID = 3;
    // const STATE_NEW = IdentifierValidation::STATE_NEW;
    // const STATE_VALIDATED = IdentifierValidation::STATE_VALIDATED;
    // const STATE_INVALID = IdentifierValidation::STATE_INVALID;

    private static $enum_states = array
    (
        self::STATE_NEW => 'New',
        self::STATE_VALIDATED => 'Validated',
        self::STATE_INVALID => 'Invalid',  
    );

    /**
     * FLAGS
     */
    // const SMILES_FLAG_CANONIZED = 1;
    // const SMILES_FLAG_CANONIZATION_ERROR = 2;

    /**
     * Enum types of identifiers
     */
    private static $enum_types = array
    (
        self::TYPE_NAME => 'Name',
        // self::TYPE_SMILES => 'SMILES',
        // self::TYPE_INCHIKEY => 'InChIKey',
        self::TYPE_PUBCHEM => 'Pubchem',
        self::TYPE_DRUGBANK => 'DrugBank',
        self::TYPE_CHEBI  => 'ChEBI',
        self::TYPE_PDB    => 'PDB',
        self::TYPE_CHEMBL => 'ChEMBL',
    );

    /**
     * Enum types of servers
     */
    private static $enum_servers = array
    (
        self::SERVER_PUBCHEM => 'Pubchem',
        self::SERVER_DRUGBANK => 'Drugbank',
        self::SERVER_CHEBI  => 'ChEBI',
        self::SERVER_PDB    => 'PDB',
        self::SERVER_CHEMBL => 'ChEMBL',
        self::SERVER_UNICHEM => 'Unichem',
        self::SERVER_RDKIT => 'RDkit',
        self::SERVER_MOLMEDB => 'MolMeDB',
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
        // self::TYPE_SMILES => array
        // (
        //     self::SERVER_CHEMBL, // 8
        //     self::SERVER_PUBCHEM, // 4
        //     self::SERVER_PDB, // 7
        // ),
        // self::TYPE_INCHIKEY => array
        // (
        //     self::SERVER_RDKIT
        // ),
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

    public static function enumType($type) : string 
    {
        return isset(self::$enum_types[$type]) ? self::$enum_types[$type] : 'N/A';
    }

    public static function enumServers($server) : string 
    {
        return isset(self::$enum_servers[$server]) ? self::$enum_servers[$server] : 'N/A';
    }

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
    public function structure() : BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    /**
     * Return parent identifier
     */
    // public function parent() : BelongsTo
    // {
    //     return $this->belongsTo(Identifier::class, 'source_id');
    // }

    public function source() : MorphTo
    {
        return $this->morphTo();
    }

    public function children() : MorphMany
    {
        return $this->morphMany(Identifier::class, 'source');
    }

    public function childIdentifiers() : MorphMany
    {
        return $this->morphMany(self::class, 'source');
    }

    public function sourceIdentifier() : BelongsTo
    {
        return $this->belongsTo(self::class, 'source_id')
            ->wherePivot('source_model_type', self::class);
    }

    public function sourceUser() : BelongsTo 
    {
        return $this->belongsTo(User::class, 'source_id')
            ->where('source_model_type', User::class);
    }

    public function name() : ?string 
    {
        return self::enumType($this->type) . ': ' . $this->value;
    }

    /**
     * Returns datasets, from which the record was imported
     */
    // public function datasets() : BelongsToMany
    // {
    //     return $this->belongsToMany(Dataset::class, 'substance_identifier_dataset');
    // }

    /**
     * Returns identifier, which replaced this identifier
     */
    // public function replacedBy() : BelongsToMany
    // {
    //     return $this->belongsToMany(Identifier::class, 'substance_identifier_changes', 'old_id', 'new_id')
    //         ->withPivot('message', 'datetime', 'user');
    // }

    /**
     * Returns identifiers, which were replaced by this identifier
     */
    // public function replacing() : BelongsToMany
    // {
    //     return $this->belongsToMany(Identifier::class, 'substance_identifier_changes', 'new_id', 'old_id')
    //         ->withPivot('message', 'datetime', 'user');
    // }
}
