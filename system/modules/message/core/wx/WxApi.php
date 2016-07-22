<?php

/**
 * WxAPI class file.
 *
 * @author banyanCheung <banyan@ibos.com.cn>
 * @link http://www.ibos.cn/
 * @copyright 2008-2014 IBOS Inc
 */
/**
 * 微信API处理类
 *
 * @author banyanCheung <banyan@ibos.com.cn>
 * @package application.modules.message.core.wx
 * @version $Id$
 */

namespace application\modules\message\core\wx;

use application\core\model\Log;
use application\core\utils\Api;
use application\core\utils\Env;
use application\core\utils\File;
use application\core\utils\IBOS;
use application\core\utils\WebSite;
use application\modules\department\model\DepartmentBinding;
use application\modules\main\model\Setting;
use application\modules\user\model\UserBinding;
use CJSON;

class WxApi extends Api {

	// 刷新ACCESS_TOKEN
	const REFRESH_TOKEN_URL = 'https://qyapi.weixin.qq.com/cgi-bin/gettoken';
	// 上传媒体文件地址
	const MEDIA_UPLOAD_URL = 'https://qyapi.weixin.qq.com/cgi-bin/media/upload';
	// 授权验证URL
	const OAUTH_URL = 'https://open.weixin.qq.com/connect/oauth2/authorize';
	// 创建菜单URL
	const CREATE_MENU_URL = 'https://qyapi.weixin.qq.com/cgi-bin/menu/create';
	// 删除菜单URL
	const DEL_MENU_URL = 'https://qyapi.weixin.qq.com/cgi-bin/menu/delete';

	public static function getInstance( $className = __CLASS__ ) {
		return parent::getInstance( $className );
	}

	public function resetCorp() {
		Setting::model()->updateSettingValueByKey( 'corpid', '0' );
		Setting::model()->updateSettingValueByKey( 'qrcode', '0' );
		UserBinding::model()->deleteAll( " `app` = 'wxqy'" );
		DepartmentBinding::model()->deleteAll( " `app` = 'wxqy' " );
	}

	public function getAeskey() {
		return Setting::model()->fetchSettingValueByKey( 'aeskey' );
	}

	/**
	 *
	 * @param type $mediaId
	 * @return type
	 */
	public function getMediaContent( $mediaId ) {
		$url = WebSite::SITE_URL . "Api/Wximg";
		$param = array(
			'corpid' => Setting::model()->fetchSettingValueByKey( 'corpid' ),
			'mediaid' => $mediaId,
		);
		$url = $this->buildUrl( $url, $param );
		return File::readFile( $url );
	}

	/**
	 * 发请求给官网让微信推送消息
	 * @param mixed $param
	 * @return boolean
	 */
	public function push( $data ) {
		$route = 'Api/WxPush/pushToWx';
		$corpid = Setting::model()->fetchSettingValueByKey( 'corpid' );
		$param = array(
			'data' => $data,
			'corpid' => $corpid,
		);
		$res = WebSite::getInstance()->fetch( $route, json_encode( $param ), 'post' );
		Log::write( array(
			'param' => $param,
			'route' => $route,
			'res' => $res,
				), 'wxqy_push', 'message.core.wx.WxApi' );
		return $this->getSendIsSuccess( $res );
	}

	/**
	 * 创建部门
	 * @param string $deptName
	 * @param integer $pid
	 * @return integer 创建后的部门ID
	 */
	public function createDept( $deptName, $pid, $order, $url ) {
		$post = <<<EOT
{
   "name": "{$deptName}",
   "parentid": "{$pid}",
   "order": "{$order}"
}
EOT;

		$return = $this->fetchResult( $url, $post, 'post' );
		$isSuccess = $this->getSendIsSuccess( $return );
		$res = CJSON::decode( $return, true );
		if ( $res['errcode'] == '60008' || //部门已经存在的话，也继续同步部门操作
				$res['errcode'] == '60004' || //父部门不存在的话，也继续同步部门操作
				$res['errcode'] == '60011' ) {//如果遇到权限不够，也继续同步
			$isSuccess = true;
		}
		return array(
			'isSuccess' => $isSuccess,
			'data' => $res,
		);
	}

	public function createUser( $user, $url ) {
		$depstr = $user['deptid'];
		if ( empty( $depstr ) ) {
			$depstr = 1;
		}
		$telephone = isset( $user['telephone'] ) ? $user['telephone'] : '';
		$post = <<<EOT
{
   "userid": "{$user['userid']}",
   "name": "{$user['realname']}",
   "department": {$depstr},
   "position": "{$user['posname']}",
   "mobile": "{$user['mobile']}",
   "gender": {$user['gender']},
   "tel": "{$telephone}",
   "email": "{$user['email']}",
   "weixinid": "{$user['weixin']}"
}
EOT;
		$res = $this->fetchResult( $url, $post, 'post' );
		if ( !is_array( $res ) ) {
			$res = CJSON::decode( $res, true );
			if ( $res['errcode'] == '0' ) {
				return '';
			} else {
				switch ( $res['errcode'] ) {
					case '60104'://手机号存在
						$msg = '';
						$this->setBind( $user['uid'], $res['errmsg'] );
						break;
					case '60106'://邮箱已存在
						$msg = '';
						$this->setBind( $user['uid'], $res['errmsg'] );
						break;
					case '60108'://微信已存在
						$msg = '';
						$this->setBind( $user['uid'], $res['errmsg'] );
						break;
					case '60102'://userid存在
						$msg = '';
						$this->setBind( $user['uid'], '9527:' . $user['userid'] );
						break;
					default :
						$msg = Code::getErrmsg( $res['errcode'] );
				}
				return $msg;
			}
		} else {
			return Code::SYNC_ERROR_MSG;
		}
	}

	/**
	 * 如果是手机邮箱微信存在，微信返回的errmsg里带有userid
	 * 如果，我是说如果，这个规则被微信改了……怪我么？
	 * 这么做是为了减少对微信的请求次数，不然你行你上
	 * @param type $uid
	 * @param type $errmsg
	 */
	private function setBind( $uid, $errmsg ) {
		list($msg, $userid) = explode( ':', $errmsg );
		UserBinding::model()->deleteAll( sprintf( "`uid` = '%s' AND `app` = 'wxqy' ", $uid ) );
		UserBinding::model()->add( array( 'uid' => $uid, 'bindvalue' => $userid, 'app' => 'wxqy' ) );
	}

	/**
	 *
	 * @param string $url 官网那边接收请求地址
	 * @param array $userid 需要发送邀请的用户id（姓名转成拼音）列表
	 * @return type
	 */
	public function sendInvition( $url, $userid ) {
		$res = $this->fetchResult( $url, json_encode( $userid ), 'post' );
		return $res;
	}

	/**
	 * 获取已同步的微信员工
	 * @param integer $departmentId 部门id
	 * @param integer $status 0获取全部员工，1获取已关注成员列表，2获取禁用成员列表，4获取未关注成员列表。status可叠加
	 * @return array
	 */
	public function getDeptUser( $url, $departmentId = 1, $status = 0 ) {
		$res = $this->fetchResult( $url, array(
			'department_id' => $departmentId,
			'fetch_child' => 1,
			'status' => $status
				) );
		if ( !is_array( $res ) ) {
			$result = CJSON::decode( $res, true );
			if ( isset( $result['userlist'] ) ) {
				return $result['userlist'];
			}
		}
		return array();
	}

	/**
	 * 创建微信用户验证URL
	 * @param string $route
	 * @param integer $appId微信端appId
	 * @return string
	 */
	public function createOauthUrl( $route, $appId ) {
		$corpid = Setting::model()->fetchSettingValueByKey( 'corpid' );
		$param = array(
			'appid' => $corpid,
			'redirect_uri' => WebSite::SITE_URL . '/Api/Wxoauth/?redirect=' . base64_encode( $route ) . '&corpid=' . $corpid,
			'response_type' => 'code',
			'scope' => 'snsapi_base',
			'state' => $appId
		);
		return $this->buildUrl( self::OAUTH_URL, $param ) . '#wechat_redirect';
	}

	/**
	 * 解析网页端的URL为微信端的URL
	 * @param string $mobileTag 微信端的URL
	 * @param string $url 待解析的URL
	 * @param string $field ID字段
	 * @return sring
	 */
	public function parseMobileUrl( $mobileTag, $url, $field ) {
		$query = parse_url( $url, PHP_URL_QUERY );
		!empty( $query ) && parse_str( $query );
		if ( isset( $$field ) ) {
			$route = 'http://app.ibos.cn?host=' . urlencode( $this->getHostInfo() ) . "#{$mobileTag}/" . $$field;
		} else {
			$route = $url;
		}
		return $route;
	}

	/**
	 * 解析网页端的URL为微信端的URL
	 * @param string $mobileTag 微信端的URL
	 * @param string $url 待解析的URL
	 * @param string $field ID字段
	 * @return sring
	 */
	public function parseNewMobileUrl( $mobileTag, $url, $field ) {
		$query = parse_url( $url, PHP_URL_QUERY );
		!empty( $query ) && parse_str( $query );
		if ( isset( $$field ) ) {
			$route = 'http://app.ibos.cn?host=' . urlencode( $this->getHostInfo() ) . "/#/{$mobileTag}/" . $$field;
		} else {
			$route = $url;
		}
		return $route;
	}

	/**
	 * 获取主动发送信息是否成功
	 * @param string $res 上游返回的反馈信息
	 * @return boolean
	 */
	protected function getSendIsSuccess( $res ) {
		if ( !is_array( $res ) ) {
			$res = CJSON::decode( $res, true );
			return isset( $res['errcode'] ) && $res['errcode'] == 0;
		}
		return false;
	}

	/**
	 * 获取项目地址eg:http://abc.com
	 * @return type
	 */
	public function getHostInfo() {
		static $hostInfo = false;
		if ( !$hostInfo ) {
			if ( defined( 'CALLBACK' ) ) {
				$hostInfo = rtrim( IBOS::app()->request->getHostInfo(), '/' );
			} else {
				$hostInfo = rtrim( Env::getSiteUrl( Env::isHttps() ), '/' );
			}
		}
		return $hostInfo;
	}

}
