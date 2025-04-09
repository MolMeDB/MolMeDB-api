<?php 
namespace Modules\EuropePMC\Enums;

enum Sources: string 
{
    case AGR = "AGR";
    case CBA = "CBA";
    case CTX = 'CTX';
    case ETH = "ETH";
    case HIR = 'HIR';
    case MED = 'MED';
    case NBK = 'NBK';
    case PAT = 'PAT';
    case PMC = 'PMC';
    case EUROPEPMC = 'EUROPEPMC';

    public static function exists($source) : bool {
        return in_array($source, self::cases());
    }

    public static function all() : array {
        return self::cases();
    }

    public function definition() : string {
        switch ($this) {
            case self::AGR:
                return "Agricola";
            case self::CBA:
                return "Chinese Biological Abstracts";
            case self::CTX:
                return "CiteXplore";
            case self::ETH:
                return "EthOs Theses";
            case self::HIR:
                return "NHS Evidence";
            case self::MED:
                return "PubMed/Medline NLM";
            case self::NBK:
                return "Europe PMC Book metadata";
            case self::PAT:
                return "Biological patents";
            case self::PMC:
                return "PubMed Central";
            case self::EUROPEPMC:
                return "Europe PMC";
            default:
                return "Unknown";
        }
    }
}