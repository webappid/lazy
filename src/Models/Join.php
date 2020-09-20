<?php
/**
 * Created by PhpStorm.
 */

namespace WebAppId\Lazy\Models;

/**
 * @author: Dyan Galih<dyan.galih@gmail.com>
 * Date: 20/09/2020
 * Time: 11.50
 * Class Join
 * @package WebAppId\Lazy\Models
 */
class Join
{
    /**
     * @var object
     */
    public $class;

    /**
     * @var string
     */
    public $foreign;

    /**
     * @var string
     */
    public $type = 'inner';

    /**
     * @var string|null
     */
    public $primary = null;
}
