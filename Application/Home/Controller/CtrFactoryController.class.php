<?php
/**
 * 一个简单的控制器工厂
 */
namespace Home\Controller;
use Think\Controller;
class CtrFactoryController extends Controller{

    public static $prefix = "New";

    public static function createCtr($controller_name = ''){
        $obj             = null;
        $controller_name = self::$prefix.$controller_name;
        switch ($controller_name){
            case 'NewIndex':
                if(class_exists('\Home\Controller\NewIndexController')){
                    $obj = new \Home\Controller\NewIndexController();
                }
                break;
            case 'NewGoods':
                if(class_exists('\Home\Controller\NewGoodsController')){
                    $obj = new \Home\Controller\NewGoodsController();
                }
                break;
            case 'NewPublic':
                if(class_exists('\Home\Controller\NewPublicController')){
                    $obj = new \Home\Controller\NewPublicController();
                }
                break;
            case 'NewOrder':
                if(class_exists('\Home\Controller\NewOrderController')){
                    $obj = new \Home\Controller\NewOrderController();
                }
                break;
            case 'NewSearch':
                if(class_exists('\Home\Controller\NewSearchController')){
                    $obj = new \Home\Controller\NewSearchController();
                }
                break;
            case 'NewUser':
                if(class_exists('\Home\Controller\NewUserController')){
                    $obj = new \Home\Controller\NewUserController();
                }
                break;
            case 'NewArticle':
                if(class_exists('\Home\Controller\NewArticleController')){
                    $obj = new \Home\Controller\NewArticleController();
                }
                break;
            case 'NewPay':
                if(class_exists('\Home\Controller\NewPayController')){
                    $obj = new \Home\Controller\NewPayController();
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