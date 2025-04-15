<?php 
namespace Modules\References\EuropePMC\Enums\Query;

enum SortBy: string 
{
    case CITED_NUMBER = 'CITED';
    case FIRST_AUTHOR = 'AUTH_FIRST';
    case PUBLISH_DATE = "P_PDATE_D";
    case SCORE = 'SCORE';
}