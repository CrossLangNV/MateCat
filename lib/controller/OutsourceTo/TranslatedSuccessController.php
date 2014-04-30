<?php
/**
 * Created by PhpStorm.
 */

/**
 * Controller that handle the success return from login page
 *
 * Used to set the next redirect page on remote provider system
 *
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 29/04/14
 * Time: 12.23
 * 
 */
class OutsourceTo_TranslatedSuccessController extends OutsourceTo_AbstractSuccessController {

    /**
     * Redirect page to review the order
     *
     * @see OutsourceTo_AbstractSuccessController::$review_order_page
     * @var string
     */
    protected $review_order_page = 'http://openid.translated.home/review.php';
//    protected $review_order_page = 'http://signin.translated.net/review.php';

    /**
     * Token key name for the authentication return
     *
     * @see OutsourceTo_AbstractSuccessController::$tokenName
     * @var string
     */
    protected $tokenName = 'tk';

} 