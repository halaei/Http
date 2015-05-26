<?php
namespace Poirot\Http\Message\Request;

use Poirot\Core\AbstractOptions;
use Poirot\Core\Interfaces\iOptionImplement;
use Poirot\Core\Traits\OpenOptionsTrait;

/**
 * Provide Some Data Can Be Set On Http Message Object
 * with from() or on __construct() as Option
 *
 * the getter methods must deal with iHttpRequest interface
 *
 */
abstract class AbstractReqBuilder implements iOptionImplement
{
    use OpenOptionsTrait;
}
