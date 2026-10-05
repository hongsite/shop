<?php
/*
* @author 牛头
* @company 鸿思特科技
* @function 发票管理
* @date 2022/11/18
* @version hst-v1.0.0
ww189f5ad7a3b4b944
1106482623
tvlhxwcrqixubebnsfegisklimnkguee
*/
namespace Hst\Index;
use Hst\Common;
class Index extends Common {	
    public function _init(){		
        parent::_init();
		$searinfo[] = array('field'=>'xfname');
		$searinfo[] = array('field'=>'xfswh');
		$searinfo[] = array('field'=>'fphm');
		$this->seararr=$searinfo;
		$this->allowindex = true;			
    }
	private function getnums($tid='',$uid=0){
		$where = 'uid='.$uid.' AND deleted=0';
		if($tid!='all') $where .= ' AND addtime {'.$tid.'}';
		$nums = M('Business')->where($where)->count();
		return $nums;
	}
	public function index_json(){	
		$uid = getuid();

		$nowt = time();
		
		$js['status'] = 1;
	    $js['msg'] = '数据刷新成功';
		$js['roleid'] = 0;
		$js['roleid'] = $this->roleid;
		
		$js['android_isupdate'] = 1;
		$js['android_version'] = 1005;
		$js['android_subversion'] = '01';
		$js['android_apkUrl'] = 'http://oapic.hongsite.com/down/oamember.apk';
		$js['android_title'] = '升级说明';
		$js['android_content'] = '01、主要功能的升级'.chr(13).chr(10).'02、已知问题的更新';		
		
		$js['userinfo'] = $this->userinfo;

		$orderlist = $this->getorderlist();

		$js['orderlist'] = $orderlist;

		$sql = 'SELECT
		/* 今日 */
		SUM(IF(addtime >= UNIX_TIMESTAMP(CURDATE())
			   AND addtime <  UNIX_TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL 1 DAY)),
			   allprice, 0)) AS today,

		/* 昨日 */
		SUM(IF(addtime >= UNIX_TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 1 DAY))
			   AND addtime <  UNIX_TIMESTAMP(CURDATE()),
			   allprice, 0)) AS yesterday,

		/* 本月 */
		SUM(IF(addtime >= UNIX_TIMESTAMP(DATE_FORMAT(CURDATE(), \'%Y-%m-01\'))
			   AND addtime <  UNIX_TIMESTAMP(DATE_ADD(DATE_FORMAT(CURDATE(), \'%Y-%m-01\'), INTERVAL 1 MONTH)),
			   allprice, 0)) AS `month`,

		/* 上月 */
		SUM(IF(addtime >= UNIX_TIMESTAMP(DATE_SUB(DATE_FORMAT(CURDATE(), \'%Y-%m-01\'), INTERVAL 1 MONTH))
			   AND addtime <  UNIX_TIMESTAMP(DATE_FORMAT(CURDATE(), \'%Y-%m-01\')),
			   allprice, 0)) AS `lastmonth`,

		/* 本年：今年 1 月 1 号 ~ 明年 1 月 1 号 */
		SUM(IF(addtime >= UNIX_TIMESTAMP(DATE_FORMAT(CURDATE(), \'%Y-01-01\'))
			   AND addtime <  UNIX_TIMESTAMP(DATE_ADD(DATE_FORMAT(CURDATE(), \'%Y-01-01\'), INTERVAL 1 YEAR)),
			   allprice, 0)) AS `year`,

		/* 去年：去年 1 月 1 号 ~ 今年 1 月 1 号 */
		SUM(IF(addtime >= UNIX_TIMESTAMP(DATE_SUB(DATE_FORMAT(CURDATE(), \'%Y-01-01\'), INTERVAL 1 YEAR))
			   AND addtime <  UNIX_TIMESTAMP(DATE_FORMAT(CURDATE(), \'%Y-01-01\')),
			   allprice, 0)) AS `lastyear`,

		/* 总销售（全部，不限时间） */
		SUM(allprice) AS `all`

		FROM hst_orders
		WHERE deleted = 0 AND stat in(1,2,3);';

		$tjlist = M('')->query($sql);
		$row = $tjlist[0];

		$memberCount = M('Member')->where('deleted=0')->count();		

		$data = [
			['title' => '今日销售', 'value' => intval($row['today'])],
			['title' => '昨日销售', 'value' => intval($row['yesterday'])],
			['title' => '本月销售', 'value' => intval($row['month'])],
			['title' => '上月销售', 'value' => intval($row['lastmonth'])],
			['title' => '本年销售', 'value' => intval($row['year'])],
			['title' => '去年销售', 'value' => intval($row['lastyear'])],
			['title' => '总销售', 'value' => intval($row['all'])],
			['title' => '注册会员', 'value' => intval($memberCount)*100],
		];

		$js['tjlist'] = $data;	
		$js['paihanglist'] = $this->getpaihang();

		$js['daylist'] = $this->getdaylist();
		$js['catezb'] = $this->getcatezb();

		$this->json($js);
	}

	private function getcatezb(){

		$sql = 'SELECT
    IF(rn <= 10, class_title, \'其他\') AS label,
    SUM(order_count) AS value
FROM (
    SELECT
        t.class_title,
        t.order_count,
        @rn := @rn + 1 AS rn
    FROM (
        SELECT
            pc.title AS class_title,
            COUNT(*) AS order_count
        FROM hst_orders_details AS od
        INNER JOIN hst_orders AS o
            ON o.id = od.ordid
        INNER JOIN hst_pro_class AS pc
            ON pc.id = od.proid
        WHERE o.stat IN (0,1, 2, 3)
        GROUP BY pc.id, pc.title
        ORDER BY order_count DESC
    ) AS t
    CROSS JOIN (SELECT @rn := 0) AS init
) AS ranked
GROUP BY IF(rn <= 10, class_title, \'其他\')
ORDER BY value DESC;';

		$data = M('')->query($sql);

		return $data;

	}

	

	private function getdaylist(){

		$sql = 'SELECT
			DATE(FROM_UNIXTIME(addtime)) AS `day`,
			IFNULL(SUM(payprice),0)/100 AS `nums`
		FROM hst_orders
		WHERE deleted = 0
		GROUP BY `day`
		ORDER BY `day` DESC
		LIMIT 10;';

		$data = M('')->query($sql);

		return $data;

	}

	private function getorderlist(){
		$where = '`A1`.`deleted`=0';			
		$field = '`A1`.`id`,`A1`.`ordid`,`A1`.`title`,`A1`.`payprice`,`A1`.`addtime`,`A1`.`stat`,`A2`.`truename`,`A2`.`mobile`,`A2`.`address`';
		$field .= ',`A3`.`title` AS `county`,`A3`.`id` AS `countyid`,`A4`.`title` AS `city`,`A4`.`id` AS `cityid`,`A5`.`title` AS `province`';
		$field .= ',`A5`.`id` AS `provinceid`';

		$arr['name'] = array('Orders','OrdersAddress','Area','Area','Area');
		$arr['field'] = $field;
		$arr['on'] = array('`A1`.`id`=`A2`.`id`','`A2`.`countyid`=`A3`.`id`','`A3`.`pid`=`A4`.`id`','`A4`.`pid`=`A5`.`id`');
		
		
		$arr['limit'] = 8;
		$arr['order'] = '`A1`.`id` DESC';
		$arr['where'] = $where;
		$obj = D('SubTab',1);
		$list = $obj->mysql_list($arr);
		return $list;
	}
	private function getpaihang(){
		$sql = 'SELECT
			d.id,
			p.title,
			SUM(d.quantity)*100 AS `nums`,
			SUM(d.price*d.quantity) AS `price` 
		FROM hst_orders_details d
		LEFT JOIN hst_pro p ON p.id = d.proid
		LEFT JOIN hst_orders o ON o.id = d.ordid
		WHERE o.deleted = 0
		  AND o.stat in(1,2,3) 
		  AND p.deleted = 0
		GROUP BY d.proid
		ORDER BY `nums` DESC LIMIT 8;';

		$list = M('')->query($sql);
		return $list;
	}
	public function islogin_json(){	
		
		
		$js['status'] = 0;
	    $js['msg'] = '您还未登录';

		$uid = getuid();
		if($uid>0){
			$js['status'] = 1;
			$js['msg'] = '登录成功';
		}
		
		$this->json($js);
	}
}
