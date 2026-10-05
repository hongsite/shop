<?php
/* 获取提成 */
namespace Models\Agent;

use Model\Model;

class Agent extends Model
{
    private $ffield = '`A1`.`id`,`A1`.`allowagent`,`A1`.`truename`,`A1`.`allowdis`,`A1`.`level`,`A1`.`parentid`,`A1`.`openid`';

    /* ============================================================
     * 计算购物车/订单列表中可分佣的金额
     * 原方法名 getValue 名不副实，这里保留名字但修正返回值
     * ============================================================ */
    public function getValue($list)
    {
        $allprice = 0;
        foreach ($list as $k => $v) {
            $price    = isset($v['price'])    ? $v['price']    : 0;
            $quantity = isset($v['quantity']) ? $v['quantity'] : 0;

            if ($v['allowdis'] == 1) {
                if ($v['rebate'] == 1) {
                    $price = 0;
                }
                $agentprice = $price * $quantity;
            } else {
                $agentprice = 0;
            }
            $allprice += $agentprice;
        }
        return $allprice;   // 修正：原代码算了却 return $list
    }

    /* ============================================================
     * 绑定上级（cookie 中的 parentid）
     * ============================================================ */
    public function setagent($uid = 0)
    {
        $uid = intval($uid);
        if ($uid <= 0) {
            return false;
        }

        $website = loadcache(2);
        $cominfo = [
            $website['agentper01'] ?? 5,
            $website['agentper02'] ?? 3,
            $website['agentper03'] ?? 2,
            $website['isagent'],
            $website['isagentclass'],
        ];

        $userinfo = D('Agent')->getagent($uid, $cominfo);
        if (!$userinfo) {
            return false;
        }

        $parentid = intval(I('parentid', 0, 'cookies'));

        if ($userinfo['parentid'] == 0 && $parentid > 0) {
            if ($parentid != $userinfo['id'] && $userinfo['allowagent'] == 1) {
                M('Member')->where('id=' . $uid)->setField('parentid', $parentid);
            }
        }
        return true;
    }

    /* ============================================================
     * 取「自己」这条分销记录（list 中的第一条）
     * 原代码里 $list[1]['level'] 是死代码，已删除
     * ============================================================ */
    public function getagent($uid = 0, $arr = null)
    {
        $list = $this->getagentlist($uid, $arr);
        if ($list && isset($list[0])) {
            return $list[0];
        }
        return false;
    }

    /* ============================================================
     * 核心：从 $uid 出发，往上找最多 3 级上级
     * 返回数组，list[0] = 自己，list[1] = 一级上级，list[2] = 二级，list[3] = 三级
     * ============================================================ */
    public function getagentlist($uid = 0, $arr = null)
    {
        $uid = intval($uid);
        if ($uid <= 0) {
            return false;
        }

        // 读取自己
        $rt = D('Member')->getValue($uid, $this->ffield);
        if (!$rt) {
            return false;
        }

        $agentper01 = intval(isset($arr[0]) ? $arr[0] : 0);
        $agentper02 = intval(isset($arr[1]) ? $arr[1] : 0);
        $agentper03 = intval(isset($arr[2]) ? $arr[2] : 0);
        $isagent    = intval(isset($arr[3]) ? $arr[3] : 0);

        // 全局关闭分销
        if ($isagent == 0) {
            $agentper01 = $agentper02 = $agentper03 = 0;
        }

        $list = [];

        /* ---------- 1. 自己 ---------- */
        $self = $this->buildPerson($rt, 1);   // 自己是第 1 层视角
        $allowagent = empty($rt['allowagent']) ? 0 : 1;

        if ($isagent == 0) {
            $allowagent = 0;
        }

        $self['allowagent'] = $allowagent;
        $self['allowdis']   = $allowagent;
        $self['agentper']   = $allowagent == 1
            ? ($agentper01 + $agentper02 + $agentper03)
            : 0;

        // 没有上级，直接返回自己
        $parentId1 = intval($rt['parentid']);
        if ($parentId1 == 0) {
            $list[] = $self;
            return $list;
        }

        /* ---------- 2. 一级上级 ---------- */
        $rt1 = D('Member')->getValue($parentId1, $this->ffield);
        if (!$rt1) {
            $list[] = $self;
            return $list;
        }

        $p1 = $this->buildPerson($rt1, 2);   // 站在"自己"视角，一级上级是第 2 层
        $p1['agentper']  = $agentper01;
        $p1['allowdis']  = $isagent == 1 ? 1 : 0;
        $p1['allowagent'] = 1;

        // 如果只有一级，二级比例为 0 或二级不存在
        $parentId2 = intval($rt1['parentid']);
        if ($parentId2 == 0 || $agentper02 == 0) {

            if ($agentper02 == 0) {
                // 二级不参与，比例上浮给一级
                $self['allowdis'] = 1;
                $p1['allowdis']   = 1;
                $self['lever']    = 1;
                $p1['lever']      = 2;
            }

            if ($agentper03 > 0 && ($agentper01 + $agentper02) > 0) {
                // 原逻辑：把三级的比例按一级/二级比例二次分配
                $allagent       = $agentper01 + $agentper02;
                $agentper01_01  = intval(($agentper01 / $allagent) * $agentper03);
                $p1['agentper']  = $agentper01 + $agentper01_01;
                $self['agentper'] = $agentper02 + ($agentper03 - $agentper01_01);
            }

            if ($isagent == 0) {
                $self['allowdis']  = 0;
                $p1['allowdis']    = 0;
                $self['allowagent'] = 0;
                $p1['allowagent']  = 0;
            }

            $list[] = $self;
            $list[] = $p1;
            return $list;
        }

        /* ---------- 3. 二级上级 ---------- */
        $rt2 = D('Member')->getValue($parentId2, $this->ffield);
        if (!$rt2) {
            $list[] = $self;
            $list[] = $p1;
            return $list;
        }

        $p2 = $this->buildPerson($rt2, 3);   // 站在"自己"视角，二级上级是第 3 层
        $p2['agentper']  = $agentper02;
        $p2['allowdis']  = $isagent == 1 ? 1 : 0;
        $p2['allowagent'] = 1;

        $parentId3 = intval($rt2['parentid']);
        if ($parentId3 == 0 || $agentper03 == 0) {

            if ($agentper03 == 0) {
                $self['lever'] = 1;
                $p1['lever']   = 2;
                $p2['lever']   = 3;
            }

            if ($isagent == 0) {
                $self['allowdis'] = 0;
                $p1['allowdis']   = 0;
                $p2['allowdis']   = 0;
                $self['allowagent'] = 0;
                $p1['allowagent']   = 0;
                $p2['allowagent']   = 0;
            }

            $list[] = $self;
            $list[] = $p1;
            $list[] = $p2;
            return $list;
        }

        /* ---------- 4. 三级上级 ---------- */
        $rt3 = D('Member')->getValue($parentId3, $this->ffield);
        if (!$rt3) {
            $list[] = $self;
            $list[] = $p1;
            $list[] = $p2;
            return $list;
        }

        $p3 = $this->buildPerson($rt3, 4);   // 站在"自己"视角，三级上级是第 4 层
        $p3['agentper']  = $agentper03;
        $p3['allowdis']  = $isagent == 1 ? 1 : 0;
        $p3['allowagent'] = 1;

        // 自己拿不到上级的三级佣金，所以自己 allowagent=0
        $self['allowagent'] = 0;
        $self['agentper']   = $agentper03;
        $p1['agentper']     = $agentper02;
        $p2['agentper']     = $agentper01;
        $self['lever']      = 3;
        $p1['lever']        = 2;
        $p2['lever']        = 1;
        $p3['lever']        = 3;

        if ($isagent == 0) {
            $self['allowdis'] = 0;
            $p1['allowdis']   = 0;
            $p2['allowdis']   = 0;
            $p3['allowdis']   = 0;
            $self['allowagent'] = 0;
            $p1['allowagent']   = 0;
            $p2['allowagent']   = 0;
            $p3['allowagent']   = 0;
        }

        $list[] = $self;
        $list[] = $p1;
        $list[] = $p2;
        $list[] = $p3;
        return $list;
    }

    /* ============================================================
     * 构造一条分销记录（统一字段，避免每处手写漏字段）
     * ============================================================ */
    private function buildPerson($row, $lever)
    {
        return [
            'id'            => $row['id'],
            'parentid'       => $row['parentid'],
            'nickname'      => $row['nickname'] ?? '',
            'truename'      => $row['truename'],
            'wxnickname'    => isset($row['wxnickname']) ? $row['wxnickname'] : '',
            'wxface'        => isset($row['wxface'])     ? $row['wxface']     : '',
            'openid'        => $row['openid'],
            'level'       => $row['level'],
            'lever'         => $lever,
            'allowagent'    => 1,
            'allowdis'      => 1,
            'agentper'      => 0,
            'relation_type' => 'DISTRIBUTOR',
        ];
    }
}