<?php

/**
 * 信息中心模块------ 文章工具类
 *
 * @link http://www.ibos.com.cn/
 * @copyright Copyright &copy; 2008-2013 IBOS Inc
 * @author gzwwb <gzwwb@ibos.com.cn>
 */
/**
 * 信息中心模块------  文章工具类
 * @package application.modules.article.model
 * @version $Id: Article.php 7605 2016-07-20 02:16:23Z gzhyj $
 * @author gzwwb <gzwwb@ibos.com.cn>
 */

namespace application\modules\article\utils;

use application\core\utils\StringUtil;
use application\modules\article\model\Article as ArticleModel;
use application\modules\article\model\ArticleReader;
use application\modules\department\model\Department;
use application\modules\department\model\DepartmentRelated;
use application\modules\user\model\User;
use CHtml;

class Article {

    //未读
    const TYPE_NEW = 'new';
    //已读
    const TYPE_OLD = 'old';
    //待审核
    const TYPE_NOTALLOW = 'notallow';
    //草稿
    const TYPE_DRAFT = 'draft';

	/**
	 * 列表查询条件组合
	 * @param string $type 全部、未读、已读、未审核、草稿 这几种类型
	 * @param string $catid 分类id 包括当前分类及它的子类以','号分割的字符串
	 * @param string $condition 其他的查询条件
	 * @return array $condition 组合好的查询条件
	 */
	public static function joinListCondition( $type, $uid, $catid = 0, $condition = '' ) {
		$user = User::model()->fetchByUid( $uid );
		$upDeptid = Department::model()->queryDept( $user['deptid'] );
		$typeWhere = self::joinTypeCondition( $type, $uid, $catid );
		if ( !empty( $condition ) ) {
			$condition .=" AND " . $typeWhere;
		} else {
			$condition = $typeWhere;
		}
		//加上阅读权限判断
		$allDeptId = array_filter( array_unique( explode( ',', $upDeptid . "," . $user['alldeptid'] ) ) );
		$deptCondition = '';
		$deptConditionArray = array();
		if ( count( $allDeptId ) > 0 ) {
			foreach ( $allDeptId as $deptId ) {
				$deptConditionArray[] = "FIND_IN_SET('$deptId',deptid)";
			}
			$deptCondition = implode( ' OR ', $deptConditionArray );
		} else {
			$deptCondition = "FIND_IN_SET('',deptid)";
		}
		// $scopeCondition = " ( ((deptid='alldept' OR $deptCondition OR FIND_IN_SET('{$allPosId}',positionid) OR FIND_IN_SET('{$uid}',uid)) OR (deptid='' AND positionid='' AND uid='') OR (author='{$uid}') OR (approver='{$uid}')) )";
		$scopeCondition = " ( ((deptid='alldept' OR "
				. "{$deptCondition} OR "
				. "FIND_IN_SET('{$user['allposid']}',positionid) OR "
				. "FIND_IN_SET('{$uid}',uid) OR "
				. "FIND_IN_SET('{$user['allroleid']}',roleid)) OR "
				. "(author='{$uid}') OR (approver='{$uid}')) ";

		// 如果新闻当前状态为：未审核
		// 审核人可以看到所有属于他的所有未审核新闻
		if ( self::TYPE_NOTALLOW === $type ) {
			$scopeCondition .= " OR {$typeWhere} ";
		}
		$scopeCondition .= " ) ";
		$condition.=" AND " . $scopeCondition;
		if ( !empty( $catid ) ) {
			$condition.=" AND catid IN ($catid)";
		}

		// 只有对应步骤的审核人才能看到未审核新闻
		if ( self::TYPE_NOTALLOW === $type ) {
			$condition .= " AND (approver IN (0, {$uid})) ";
		}
		return $condition;
	}

    /**
     * 获取类型条件
     * @param string $type
     * @param integer $uid
     * @param integer $catid
     * @return string
     */
    public static function joinTypeCondition( $type, $uid, $catid = 0 ) {
        $typeWhere = '';
        // 根据uid查询所有已读新闻articleid
        $articleidArr = ArticleReader::model()->fetchArticleidsByUid( $uid );
        if ( $type == self::TYPE_NEW || $type == self::TYPE_OLD ) {
            $articleidsStr = implode( ',', $articleidArr );
            $articleids = empty( $articleidsStr ) ? '-1' : $articleidsStr;
            $flag = $type == self::TYPE_NEW ? 'NOT' : '';
            $typeWhere = " articleid " . $flag . " IN($articleids) AND status=1";
        } elseif ( $type == self::TYPE_NOTALLOW ) {
            $artIds = ArticleModel::model()->fetchUnApprovalArtIds( $catid, $uid );
            $artIdStr = implode( ',', $artIds );
            $typeWhere = "FIND_IN_SET(`articleid`, '{$artIdStr}')";
        } elseif ( $type == self::TYPE_DRAFT ) {
            $typeWhere = "status='3' AND author='$uid'";
        } else {
            $typeWhere = "status ='1' AND approver!=0";
        }
        return $typeWhere;
    }

    /**
     * 组合搜索条件
     * @param array $search 查询数据
     * @param string $condition 条件
     * @return string 新的查询条件
     */
    public static function joinSearchCondition( array $search, $condition ) {
        $searchCondition = '';

        $keyword = $search['keyword'];
        // 添加对keyword的转义，防止SQL错误
        $keyword = CHtml::encode( $search['keyword'] );
        $starttime = $search['starttime'];
        $endtime = $search['endtime'];

        if ( !empty( $keyword ) ) {
            $searchCondition.=" subject LIKE '%$keyword%' AND ";
        }
        if ( !empty( $starttime ) ) {
            $starttime = strtotime( $starttime );
            $searchCondition.=" addtime>=$starttime AND";
        }
        if ( !empty( $endtime ) ) {
            $endtime = strtotime( $endtime ) + 24 * 60 * 60;
            $searchCondition.=" addtime<=$endtime AND";
        }
        $newCondition = empty( $searchCondition ) ? '' : substr( $searchCondition, 0, -4 );
        return $condition . $newCondition;
    }

	/**
	 * 判断信息中心的阅读权限
	 * @param integer $uid 用户访问uid
	 * @param array $data 文章数据
	 * @return boolean
	 */
	public static function checkReadScope( $uid, $data ) {
		if ( $data['deptid'] == 'alldept' ) {
			return true;
		}
		if ( $uid == $data['author'] ) {
			return true;
		}
		//如果都为空，返回true
		if ( empty( $data['deptid'] ) && empty( $data['positionid'] ) && empty( $data['uid'] ) ) {
			return true;
		}
		if ( empty( $data['deptid'] ) && empty( $data['positionid'] ) ) {
			return true;
		}
        //得到用户的部门id,如果该id存在于文章部门范围之内,返回true
        $user = User::model()->fetch( array( 'select' => array( 'deptid', 'positionid' ), 'condition' => 'uid=:uid', 'params' => array( ':uid' => $uid ) ) );
        $departRelated = DepartmentRelated::model()->fetchAllDeptIdByUid( $uid );
        //取得文章部门范围id以及他的子id
        $childDeptid = Department::model()->fetchChildIdByDeptids( $data['deptid'] );
        if ( StringUtil::findIn( $user['deptid'] . ',' . implode( ',', $departRelated ), $childDeptid . ',' . $data['deptid'] ) ) {
            return true;
        }
		//取得文章岗位范围Id与用户岗位相比较,话说写这个有什么用呢？因为无论审核人在不在这个文章发布的岗位发布范围内，他作为审核人肯定可以查看审核啦！因此这段代码可以说写了也没有什么用。
        if ( StringUtil::findIn( $data['positionid'], $user['positionid'] ) ) {
            return true;
		}else{
		    return true;
        }
        if ( StringUtil::findIn( $data['uid'], $uid ) ) {
            return true;
        }
        if ( StringUtil::findIn( $data['roleid'], $user['roleid'] ) ) {
            return true;
        }
        return false;
    }

    /**
     * 取得在发布范围内的uid数组
     * @param array $data
     * @return array
     */
    public static function getScopeUidArr( $data ) {
        $string = '';
        $all = false;
        if ( !empty( $data['deptid'] ) ) {
            foreach ( explode( ',', $data['deptid'] ) as $deptid ) {
                if ( $deptid == 'alldept' ) {
                    $all = true;
                    $string = 'c_0';
                } else {
                    $string .=',d_' . $deptid;
                }
            }
        }
        if ( false === $all && !empty( $data['positionid'] ) ) {
            foreach ( explode( ',', $data['positionid'] ) as $positionid ) {
                $string .= ',p_' . $positionid;
            }
        }
        if ( false === $all && !empty( $data['uid'] ) ) {
            foreach ( explode( ',', $data['uid'] ) as $uid ) {
                $string .= ',u_' . $uid;
            }
        }
        if ( false === $all && !empty( $data['roleid'] ) ) {
            foreach ( explode( ',', $data['roleid'] ) as $roleid ) {
                $string .= ',r_' . $roleid;
            }
        }
        $uidArray = StringUtil::getUidAByUDPX( trim( $string, ',' ), true, false, true );
        return $uidArray;
    }

    /**
     * 取出源数据中$field的值，用$join分割合并成字符串
     * @param string $str 逗号分割的字符串
     * @param array $data 源数据
     * @param type $field 要取出的字段
     */
    public static function joinStringByArray( $str, $data, $field, $join ) {
        if ( empty( $str ) ) {
            return '';
        }
        $result = array();
        $strArr = explode( ',', $str );
        foreach ( $strArr as $value ) {
            if ( array_key_exists( $value, $data ) ) {
                $result[] = $data[$value][$field];
            }
        }
        $resultStr = implode( $join, $result );
        return $resultStr;
    }

    /**
     * 处理请求的高亮数据，过滤无用数据
     * $highLight['highlightstyle']='bold,color,italic,underline'
     */
    public static function processHighLightRequestData( $data ) {
        $highLight = array();
        $highLight['highlightstyle'] = '';
        if ( !empty( $data['endTime'] ) ) {
            $highLight['highlightendtime'] = strtotime( $data['endTime'] ) + 24 * 60 * 60 - 1;
        }
        if ( empty( $data['bold'] ) ) {
            $data['bold'] = 0;
        }
        $highLight['highlightstyle'] = $highLight['highlightstyle'] . $data['bold'] . ',';
        if ( empty( $data['color'] ) ) {
            $data['color'] = '';
        }
        $highLight['highlightstyle'] = $highLight['highlightstyle'] . $data['color'] . ',';
        if ( empty( $data['italic'] ) ) {
            $data['italic'] = 0;
        }
        $highLight['highlightstyle'] = $highLight['highlightstyle'] . $data['italic'] . ',';
        if ( empty( $data['underline'] ) ) {
            $data['underline'] = 0;
        }
        $highLight['highlightstyle'] = $highLight['highlightstyle'] . $data['underline'] . ',';
        $highLight['highlightstyle'] = substr( $highLight['highlightstyle'], 0, strlen( $highLight['highlightstyle'] ) - 1 );
        if ( !empty( $highLight['highlightendtime'] ) || strlen( $highLight['highlightstyle'] ) > 3 ) {
            $highLight['ishighlight'] = 1;
        } else {
            $highLight['ishighlight'] = 0;
        }
        return $highLight;
    }

    /**
     * 对分类列表进行处理，增加级别处理
     * @staticvar array $result
     * @param type $list
     * @param type $pid
     * @param type $level
     * @return type
     */
    public static function getCategoryList( $list, $pid, $level ) {
        static $result = array();
        foreach ( $list as $category ) {
            if ( $category['pid'] == $pid ) {
                $category['level'] = $level;
                $result[] = $category;
                array_merge( $result, self::getCategoryList( $list, $category['catid'], $level + 1 ) );
            }
        }
        return $result;
    }

}
