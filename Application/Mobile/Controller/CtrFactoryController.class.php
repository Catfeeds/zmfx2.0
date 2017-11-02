<?php
/**
 * 一个简单的控制器工厂
 */
namespace Mobile\Controller;
use Think\Controller;
class CtrFactoryController extends Controller{

    public static $prefix = "New";

    public static function createCtr($controller_name = ''){
        $obj             = null;
        $controller_name = self::$prefix.$controller_name;


        switch ($controller_name){
            case 'NewIndex':
                if(class_exists('\Mobile\Controller\NewIndexController')){
                    $obj = new \Mobile\Controller\NewIndexController();
                }
                break;
            case 'NewGoods':
                if(class_exists('\Mobile\Controller\NewGoodsController')){
                    $obj = new \Mobile\Controller\NewGoodsController();
                }
                break;
            case 'NewPublic':

                if(class_exists('\Mobile\Controller\NewPublicController')){
                    $obj = new \Mobile\Controller\NewPublicController();
                }
                break;
            case 'NewOrder':
                if(class_exists('\Mobile\Controller\NewOrderController')){
                    $obj = new \Mobile\Controller\NewOrderController();
                }
                break;
            case 'NewSearch':
                if(class_exists('\Mobile\Controller\NewSearchController')){
                    $obj = new \Mobile\Controller\NewSearchController();
                }
                break;
            case 'NewUser':
                if(class_exists('\Mobile\Controller\NewUserController')){
                    $obj = new \Mobile\Controller\NewUserController();
                }
                break;
            case 'NewArticle':
                if(class_exists('\Mobile\Controller\NewArticleController')){
                    $obj = new \Mobile\Controller\NewArticleController();
                }
                break;
            case 'NewPay':
                if(class_exists('\Mobile\Controller\NewPayController')){
                    $obj = new \Mobile\Controller\NewPayController();
                }
                break;
            case 'NewStore':
                if(class_exists('\Mobile\Controller\NewStoreController')){
                    $obj = new \Mobile\Controller\NewStoreController();
                }
                break;
            case 'NewDiamond':
                if(class_exists('\Mobile\Controller\NewDiamondController')){
                    $obj = new \Mobile\Controller\NewDiamondController();
                }
                break;
            default;
        }
        if(empty($obj)){
            return false;
        }
        return $obj;
    }
}