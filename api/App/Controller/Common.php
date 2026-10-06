<?php
/*
*@author 牛头
*@function 通用业务基类（重构版）
*@date 2026/9/28
*@version v2.0.0
*/
namespace Hst;

use Controller\Controller;

class Common extends Controller{
    /* ==================== 成员属性 ==================== */

    protected $uid = 0;
    public $adminurl;
    public $adminname;
    public $website;
    public $agentid;

    public $baseurl;
    public $domain;

    // 微信相关
    public $wx_appid = '';
    public $wx_appsecret = '';
    public $wx_mchid = '';
    public $wx_paykeys = '';

    protected $detail = false;       // 关联表数组，如 ['MemberDetail', 'MemberExt']
    protected $tb = '';              // 覆盖默认表名
    protected $tbon = false;         // 关联条件数组，与 $detail 一一对应
    protected $autofill = 0;
    protected $where = '';           // 默认查询条件
    protected $add_update_log = false;

    protected $field = '';           // 查询字段
    protected $order = '';           // 排序
    protected $perpage = 20;         // 每页数量
    protected $showsql = false;      // 调试：返回 SQL
    protected $subtabnums = 0;       // 分表数量（0 表示不分表）

    protected $allowindex = false;
    protected $allowedit = false;
    protected $allowupdate = false;
    protected $allowdelete = false;
    protected $allowsort = false;
    protected $physical = false;     // 物理删除

    protected $checkfieldarr;        // 字段校验规则
    protected $seararr;              // 搜索字段配置
    protected $batcharr;             // 批量操作配置
    protected $datesearch;           // 日期搜索字段

    protected $tihuanarr = null;     // 图片路径替换
    protected $timearr = null;       // 时间字段（提交时转时间戳）
    protected $pricearr = null;      // 价格字段（提交时转分）
    protected $iscid = 0;
    protected $ismanager = false;
    protected $roleid = 0;
    protected $total = 0;

    public $nowtime;

    protected $verkey = 'louhaifeng101920221755abcd';
    protected $detailtbid = '1';
    public $pre;
    protected $userinfo;

    /* ==================== 初始化 ==================== */

    public function _init()
    {
        // 1. 基础环境
        $this->nowtime = time();
        $this->pre = C('DB_PREFIX');

        // 2. 站点配置
        $website = loadcache(2);
        $this->website = $website ?: array();

        $this->wx_appid     = isset($this->website['wx_appid'])     ? $this->website['wx_appid']     : '';
        $this->wx_appsecret = isset($this->website['wx_appsecret']) ? $this->website['wx_appsecret'] : '';
        $this->wx_mchid     = isset($this->website['wx_mchid'])     ? $this->website['wx_mchid']     : '';
        $this->wx_paykeys   = isset($this->website['wx_paykeys'])   ? $this->website['wx_paykeys']   : '';

        // 3. URL 相关
        $this->adminurl  = '/index.php?s=' . MODULE_NAME . '&a=';
        $this->adminname = '/';
        $this->domain    = isset($_SERVER['HTTP_HOST']) ? strtolower($_SERVER['HTTP_HOST']) : '';
        $this->baseurl   = 'http://' . $this->domain . '/';

        // 4. 登录校验
        $this->checkLoginOrExit();

        // 5. 加载用户信息
        $this->loadUserInfo();
    }

    /**
     * 登录校验：未登录且非白名单模块时直接返回 JSON
     */
    protected function checkLoginOrExit()
    {
        $uid = getuid();
        $whiteList = array('Update', 'Login', 'Services', 'Crontab', 'Api', 'WxApi');

        if ($uid < 1 && !in_array(MODULE_NAME, $whiteList)) {
            // 记录来源
            $ref = getref();
            if ($ref == '') {
                $ref = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
            }
            $ref = base64url_encode($ref);

            $js = array(
                'status'  => 0,
                'nologin' => 1,
                'msg'     => '您还没有登录呦',
                'ref'     => $ref,
            );
            $this->json($js);
        }

        $this->uid = $uid;
    }

    /**
     * 加载当前登录用户信息及权限
     */
    protected function loadUserInfo()
    {
        if ($this->uid < 1) {
            return;
        }

        $userinfo = D('Member')->getValue($this->uid);
        $this->userinfo = $userinfo ?: array();
        $this->roleid   = isset($this->userinfo['roleid']) ? $this->userinfo['roleid'] : 0;

        // 缓存 roleid 到 cookie
        cookie('member_roleid', $this->roleid, 31536000);

        // 管理员判断
        if ($this->roleid == 9) {
            $this->ismanager = true;
        }

        // 管理员专属模块
        $adminOnly = array('Authorized', 'Department', 'Job', 'Log', 'Website', 'Crontab');
        if (in_array(MODULE_NAME, $adminOnly) && $this->roleid != 9) {
            $this->json(array('status' => 0, 'msg' => '您无权限访问呦'));
        }
    }

    /* ==================== 登录检查（供子类调用） ==================== */

    public function checklogin()
    {
        $uid = getuid();
        $js = array('status' => 0, 'msg' => '', 'nologin' => 0);
        if ($uid < 1) {
            $js['nologin'] = 1;
            $js['msg'] = '您还没有登录呦';
            $this->json($js);
        }
    }

    /* ==================== 列表页 ==================== */

    public function index()
    {
        $js = array('status' => 0, 'msg' => '');

        if ($this->allowindex === false) {
            $js['msg'] = '主页功能未启用';
            $this->json($js);
        }

        $uid = getuid();
        $name = MODULE_NAME;
        $privilegeid = isset($this->userinfo['privilegeid']) ? $this->userinfo['privilegeid'] : '';

        // 部门/岗位特殊权限
        if (in_array(MODULE_NAME, array('Job', 'Department', 'Staff'))
            && strpos(',' . $privilegeid . ',', ',1,') === false
            && $this->ismanager === false
        ) {
            $js['msg'] = '您无权限查看此页';
            $this->json($js);
        }

        // 钩子
        if (method_exists($this, '_before_index')) {
            $this->_before_index();
        }

        if ($this->tb != '') {
            $name = $this->tb;
        }
        $this->tb = $name;

        // 查询条件构造
        $where = $this->buildIndexWhere();

        // 排序默认值
        if ($this->order == '') {
            $this->order = $this->detail ? '`A1`.`id` DESC' : '`id` DESC';
        }

        // 分页
        $this->perpage = max(1, intval($this->perpage));
        $nowpage = intval(I('p', 1, ''));
        if ($nowpage < 1) {
            $nowpage = 1;
        }
        if ($nowpage > 100) {
            $nowpage = 100;
        }
		$js['perpage'] = $this->perpage;
        $limit = (($nowpage - 1) * $this->perpage) . ',' . $this->perpage;

        // 查询
        $list = array();
        $count = 0;

        if ($this->detail) {
            list($list, $count, $sqlDebug) = $this->queryWithDetail($name, $where, $limit);
            if ($this->showsql === true && $sqlDebug) {
                $js['sql1'] = $sqlDebug['count'];
                $js['sql']  = $sqlDebug['list'];
            }
        } else {
            $m = M($name);
            $count = $m->alias('AS `A1`')->where($where)->count();

            if ($this->showsql === true) {
                $js['sql1'] = $m->getLastSql() . '<br />';
            }

            $list = $m->alias('AS `A1`')
                      ->field($this->field)
                      ->where($where)
                      ->limit($limit)
                      ->order($this->order)
                      ->select();

            if ($this->showsql === true) {
                $js['sql'] = $m->getLastSql() . '<br />';
            }
        }

        $this->total = $count;

        // 后置钩子
        if ($list && method_exists($this, '_after_index_list')) {
            $list = $this->_after_index_list($list);
        }

        if (method_exists($this, '_after_index_js')) {
            $jsoninfo = $this->_after_index_js($js);
            if ($jsoninfo) {
                $js = $jsoninfo;
            }
        }

        // 返回
        if ($list) {
            $js['nextpage'] = $nowpage + 1;
            $js['data']     = $list;
            $js['status']   = 1;
            $js['total']    = $count;
            $js['msg']      = '获取成功';
            $js['page']     = '';
        } else {
            $js['msg'] = ($nowpage == 1) ? '没有符合条件的记录' : '没有更多了';
        }
        $this->json($js);
    }

    /**
     * 构造 index 的 where 条件
     */
    protected function buildIndexWhere()
    {
        $where = '1 ';

        // 基础 where
        if ($this->where != '') {
            $where .= ' AND ' . $this->where;
        }

        // 日期搜索
        if ($this->datesearch) {
            $btime = I('btime', 0, '');
            $etime = I('etime', 0, '');
            if ($etime > $btime) {
                $btimeTs = strtotime($btime);
                $etimeTs = strtotime($etime);
                if ($btimeTs && $etimeTs && $etimeTs > $btimeTs) {
                    $where .= ' AND ' . $this->datesearch . ' between ' . $btimeTs . ' AND ' . $etimeTs;
                }
            }
        }

        // 搜索字段
        if ($this->seararr) {
            foreach ($this->seararr as $vv) {
                $outfield = !empty($vv['outfield']) ? $vv['outfield'] : $vv['field'];
                $vv1 = I($outfield, 0, '');

                // 跳过省市区
                if (in_array($vv['field'], array('countyid', 'provinceid', 'cityid'))) {
                    continue;
                }
                if ($vv1 === '' || $vv1 === null) {
                    continue;
                }

                $where .= ' AND ';
                if (!empty($vv['afield'])) {
                    $where .= $vv['afield'];
                }
                $where .= '`' . $vv['field'] . '`';

                $type = isset($vv['type']) ? $vv['type'] : '';
                $isnumeric = isset($vv['isnumeric']) ? intval($vv['isnumeric']) : 0;

                if ($type == 'like') {
                    if ($isnumeric == 1) {
                        $where .= ' like ' . $vv1;
                    } else {
                        $val = isset($vv['base64']) ? base64_encode($vv1) : $vv1;
                        $where .= " like '%" . addslashes($val) . "%' ";
                    }
                } else {
                    if ($isnumeric == 1) {
                        $where .= ' = ' . $vv1;
                    } else {
                        $where .= " = '" . addslashes($vv1) . "'";
                    }
                }
            }
        }

        return $where;
    }

    /**
     * 带关联表的查询（返回 [list, count, debug]）
     */
    protected function queryWithDetail($name, $where, $limit)
    {
        $pre = C('DB_PREFIX');
        $mm = M('');

        // 字段
        if ($this->field == '') {
            $fieldstr = '`A1`.*';
        } else {
            if (strpos($this->field, 'A1`') === false) {
                $fieldstr = '`A1`.*,' . $this->field;
            } else {
                $fieldstr = $this->field;
            }
        }

        // 主表 + JOIN
        $sql = '`' . $pre . cc_table($name) . '` AS `A1`';
        foreach ($this->detail as $key => $v) {
            $asfield = 'A' . ($key + 2);
            $sql .= ' LEFT JOIN `' . $pre . cc_table($v) . '` AS `' . $asfield . '` ON ';
            if ($this->tbon && isset($this->tbon[$key])) {
                $sql .= ' ' . $this->tbon[$key];
            } else {
                $sql .= ' `' . $asfield . '`.`id`=`A1`.`id`';
            }
        }

        if ($where != '') {
            $sql .= ' WHERE ' . $where;
        }

        // count
        $countarr = $mm->query('SELECT count(*) AS `tpcount` FROM ' . $sql);
        $count = 0;
        if ($countarr && isset($countarr[0]['tpcount'])) {
            $count = intval($countarr[0]['tpcount']);
        }

        $sqlDebug = array(
            'count' => $mm->getLastSql(),
            'list'  => '',
        );

        // list
        $sqlcmd = 'SELECT ' . $fieldstr . ' FROM ' . $sql;
        if ($this->order != '') {
            $sqlcmd .= ' ORDER BY ' . $this->order;
        }
        $sqlcmd .= ' limit ' . $limit;

        $list = $mm->query($sqlcmd);
        $sqlDebug['list'] = $mm->getLastSql();

        return array($list ?: array(), $count, $sqlDebug);
    }

    /* ==================== 编辑页 ==================== */

    public function edit()
    {
        $js = array('status' => 0, 'msg' => '');

        if ($this->allowedit === false) {
            $js['msg'] = '编辑功能禁用';
            $this->json($js);
        }

        $uid = getuid();
        if (method_exists($this, '_before_edit')) {
            $this->_before_edit();
        }

        $headid       = isset($this->userinfo['headid'])       ? $this->userinfo['headid']       : 0;
        $departmentid = isset($this->userinfo['departmentid']) ? $this->userinfo['departmentid'] : 0;

        $name = MODULE_NAME;
        if ($this->tb) {
            $name = $this->tb;
        }
        $this->tb = $name;

        $id = intval(I('id', 0, ''));
        $iscopy = I('iscopy', 0, '');

        $m = M($name);
        $mm = M('');
        $vo = null;

        if ($this->detail) {
            // 关联表查询
            $where = '`A1`.`id`=' . $id;

            // 部门主管权限
            if ($headid > 0 && $headid == $uid && $this->ismanager === false) {
                $uidlist = M('Member')->where('departmentid=' . $departmentid)->getField('id', true);
                if ($uidlist) {
                    $uidstr = implode(',', $uidlist);
                    $where .= ' AND `A1`.`uid` in(' . $uid . ',' . $uidstr . ')';
                }
            }

            $fieldstr = $this->get_field_str($this->field, $this->detail);
            $pre = C('DB_PREFIX');
            $sql = '`' . $pre . cc_table($name) . '` AS `A1`';
            foreach ($this->detail as $key => $v) {
                $myfield = 'A' . ($key + 2);
                $sql .= ' LEFT JOIN `' . $pre . cc_table($v) . '` AS `' . $myfield . '` ON ';
                if ($this->tbon && isset($this->tbon[$key])) {
                    $sql .= $this->tbon[$key];
                } else {
                    $sql .= '`' . $myfield . '`.`id`=`A1`.`id`';
                }
            }
            $sql = 'SELECT ' . $fieldstr . ' FROM ' . $sql . ' WHERE ' . $where;
            $lists = $mm->query($sql);
            if ($lists) {
                $vo = $lists[0];
            }
        } else {
            // 单表查询
            $where = 'id=' . $id;
            if ($headid > 0 && $headid == $uid && $this->ismanager === false) {
                $uidlist = M('Member')->where('departmentid=' . $departmentid)->getField('id', true);
                if ($uidlist) {
                    $uidstr = implode(',', $uidlist);
                    $where = 'id=' . $id . ' AND uid in(' . $uid . ',' . $uidstr . ')';
                }
            }
            $vo = $m->field($this->field)->where($where)->find();
        }

        // 新增或不存在时，取默认字段
        if ($id == 0 || !$vo) {
            $info = $this->getfield_json($name);
            if (!empty($info['status']) && $info['status'] === true) {
                $vo = $info['data'];
            }
        }

        if (method_exists($this, '_after_edit')) {
            $vo = $this->_after_edit($vo);
        }
        if (method_exists($this, '_after_edit_json')) {
            $js = $this->_after_edit_json($id, $js);
        }

        $js['data'] = $vo;
        if ($vo) {
            $js['status'] = 1;
        }
        $this->json($js);
    }

    /* ==================== 获取单条更新信息 ==================== */

    public function get_update_info($name = '', $id = 0)
    {
        $vo = null;

        if (method_exists($this, '_after_update_vo')) {
            $this->_after_update_vo($id);
        }

        $pre = C('DB_PREFIX');

        if ($this->detail) {
            $fieldstr = $this->get_field_str($this->field, $this->detail);
            $sql = '`' . $pre . cc_table($name) . '` AS `A1`';
            foreach ($this->detail as $key => $v) {
                $myfield = 'A' . ($key + 2);
                $sql .= ' LEFT JOIN `' . $pre . cc_table($v) . '` AS `' . $myfield . '` ON ';
                if ($this->tbon && isset($this->tbon[$key])) {
                    $sql .= $this->tbon[$key];
                } else {
                    $sql .= '`' . $myfield . '`.`id`=`A1`.`id`';
                }
            }
            $sql = 'SELECT ' . $fieldstr . ' FROM ' . $sql . ' WHERE `A1`.`id`=' . intval($id);
            $lists = M('')->query($sql);
            if ($lists) {
                $vo = $lists[0];
            }
        } else {
            $vo = M($name)->where('id=' . intval($id))->find();
        }

        if (method_exists($this, '_after_update_rt')) {
            $vo = $this->_after_update_rt($vo);
        }

        return $vo;
    }

    /* ==================== 排序 ==================== */

    public function sort_json()
    {
        $js = array('status' => 0, 'msg' => '');

        if ($this->allowsort === false) {
            $js['msg'] = '排序功能禁用';
            $this->json($js);
        }

        $name = MODULE_NAME;
        if ($this->tb != '') {
            $name = $this->tb;
        }
        $this->tb = $name;

        $sortarr = isset($_POST['sort']) ? (array)$_POST['sort'] : array();
        $idarr   = isset($_POST['id'])   ? (array)$_POST['id']   : array();

        if (empty($idarr)) {
            $js['msg'] = 'ID选择错误';
            $this->json($js);
        }
        if (empty($sortarr)) {
            $js['msg'] = '排序值错误';
            $this->json($js);
        }
        if (count($idarr) != count($sortarr)) {
            $js['msg'] = '数据不对应';
            $this->json($js);
        }

        $data = array();
        foreach ($idarr as $k => $v) {
            $data[] = array(
                'id'   => intval($v),
                'sort' => isset($sortarr[$k]) ? intval($sortarr[$k]) : 0,
            );
        }

        $m = M($name);
        $rtcmd = $m->saveAll($data, $name);
        $tit = '排序';
        if ($rtcmd !== false) {
            $js['status'] = 1;
            $js['msg']    = $tit . '成功';
        } else {
            $js['msg'] = $tit . '失败';
        }
        $this->json($js);
    }

    /* ==================== 新增/编辑提交 ==================== */

    public function update_json()
    {
        $js = array('status' => 0, 'msg' => '', 'isadd' => 0);

        $tb = MODULE_NAME;
        if ($this->tb != '') {
            $tb = $this->tb;
        }

        if ($this->allowupdate === false) {
            $js['msg'] = '更新功能未启用';
            $this->json($js);
        }

        $privilegeid = isset($this->userinfo['privilegeid']) ? $this->userinfo['privilegeid'] : '';
        $id = intval(I('id', 0, 'post'));

        // 权限校验
        if (strpos(',' . $privilegeid . ',', ',3,') === false && $id == 0 && $this->ismanager === false) {
            $js['msg'] = '您无新增权限';
            $this->json($js);
        }
        if (strpos(',' . $privilegeid . ',', ',5,') === false && $id > 0 && $this->ismanager === false) {
            $js['msg'] = '您无编辑权限';
            $this->json($js);
        }

        $headid       = isset($this->userinfo['headid'])       ? $this->userinfo['headid']       : 0;
        $departmentid = isset($this->userinfo['departmentid']) ? $this->userinfo['departmentid'] : 0;
        $uid = getuid();

        // 编辑时校验数据归属
        if ($id > 0) {
            $where = 'id=' . $id;
            if ($this->ismanager === false) {
                $where .= ' AND uid=' . $uid;
            }
            if ($headid > 0 && $headid == $uid && $this->ismanager === false) {
                $uidlist = M('Member')->where('departmentid=' . $departmentid)->getField('id', true);
                if ($uidlist) {
                    $uidstr = implode(',', $uidlist);
                    $where = 'id=' . $id . ' AND uid in(' . $uid . ',' . $uidstr . ')';
                }
            }
            $rts = M($tb)->where($where)->find();
            if (!$rts) {
                $js['msg'] = '只能修改自己的数据';
                $this->json($js);
            }
        }

        // 前置钩子
        if (method_exists($this, '_before_update')) {
            $istrue = $this->_before_update();
            if ($istrue !== true) {
                $js['msg'] = $istrue;
                $this->json($js);
            }
        }

        // 图片路径替换
        if ($this->tihuanarr) {
            tihuan_content($this->tihuanarr);
        }

        $name = MODULE_NAME;
        if ($this->tb != '') {
            $name = $this->tb;
        }

        // 处理 POST 数据
        $data = $_POST;
        unset($data['issj'], $data['reurl'], $data['ischeck'], $data['ispd'], $data['nowstock']);

        if ($id == 0) {
            $data['addtime'] = time();
        }

        // 时间字段
        if ($this->timearr) {
            foreach ($this->timearr as $vv) {
                if (isset($data[$vv]) && !is_numeric($data[$vv])) {
                    $data[$vv] = strtotime($data[$vv]);
                }
            }
        }

        // 价格字段
        if ($this->pricearr) {
            foreach ($this->pricearr as $vv) {
                if (isset($data[$vv])) {
                    $data[$vv] = intval(floatval($data[$vv]) * 100);
                }
            }
        }

        // 字段校验
        if ($this->checkfieldarr) {
            foreach ($this->checkfieldarr as $vv) {
                $myvalue = isset($data[$vv['field']]) ? $data[$vv['field']] : '';
                if (isset($vv['tid']) && $vv['tid'] == 0) {
                    $len = utf8_strlen($myvalue);
                    if (isset($vv['minlen']) && $len < $vv['minlen']) {
                        $js['msg'] = $vv['title'] . '最小' . $vv['minlen'] . '个字符';
                        $this->json($js);
                    }
                    if (isset($vv['maxlen']) && $len > $vv['maxlen']) {
                        $js['msg'] = $vv['title'] . '最大' . $vv['maxlen'] . '个字符';
                        $this->json($js);
                    }
                }
            }
        }

        // 只校验不提交
        $ischeck = I('ischeck', 1);
        if ($ischeck == 0) {
            $js['status'] = 1;
            $js['msg'] = '检测成功!';
            $this->json($js);
        }

        $ispd = I('ispd', 1);

        // 标题文案
        $tit = '新增';
        if (MODULE_NAME == 'Pro') {
            $tit = '入库';
            if ($ispd == 1) {
                $tit = '盘点';
            }
        }

        $m = M($name);

        /* ---------- 新增 ---------- */
        if ($id == 0) {
            $data['uid'] = $uid;
            $data['addtime'] = $this->nowtime;

            $list = $m->add($data);
            if ($list !== false) {
                if ($this->detail) {
                    foreach ($this->detail as $v) {
                        $modeldd = M($name . $v);
                        $modeldd->add($data);
                    }
                }
                $_POST['id'] = $list;

                if (method_exists($this, '_after_update')) {
                    $this->_after_update($list);
                }

                $js['status'] = 1;
                $js['isadd']  = 1;
                $js['data']   = $this->get_update_info($name, $list);
                $js['msg']    = $tit . '新增成功!';
            } else {
                $js['msg'] = $tit . '新增失败!';
            }
            $this->json($js);
        }

        /* ---------- 编辑 ---------- */
        $before_content = json_encode(M($name)->where('id=' . $id)->find());
        unset($data['id']); // 避免更新主键

        $tit = '编辑';
        $nowstock = I('nowstock', 1, 'post');
        if (MODULE_NAME == 'Pro') {
            $tit = '入库';
            if ($ispd == 1) {
                $tit = '盘点';
            } else {
                $tit = ($nowstock > 0) ? '入库' : '编辑商品';
            }
        }

        $list = $m->where('id=' . $id)->save($data);

        if ($list !== false) {
            // 关联表更新
            if ($this->detail) {
                foreach ($this->detail as $v) {
                    if ($this->subtabnums > 0) {
                        $tb_nums = get_table_nums($id, $this->subtabnums, 1);
                        $modeldd = M($v . C('DB_SUB_AFT') . $tb_nums);
                    } else {
                        $modeldd = M($v);
                    }
                    $rtcmd = $modeldd->where('id=' . $id)->count();
                    if ($rtcmd > 0) {
                        $modeldd->where('id=' . $id)->save($data);
                    } else {
                        $data['id'] = $id;
                        $modeldd->add($data);
                    }
                }
            }

            if (method_exists($this, '_after_update')) {
                $this->_after_update($id);
            }

            $_POST['id'] = $id;

            // 操作日志
            if ($this->add_update_log === true) {
                $after_content = json_encode(M($name)->where('id=' . $id)->find());
                $adddata = array(
                    array(
                        'aid'            => $id,
                        'tid'            => 1,
                        'before_content' => $before_content,
                        'after_content'  => $after_content,
                    ),
                );
                $this->update_log($adddata);
            }

            $js['data']   = $this->get_update_info($name, $id);
            $js['status'] = 1;
            $js['msg']    = $tit . '成功!';
        } else {
            $js['msg'] = $tit . '失败';
        }
        $this->json($js);
    }

    /* ==================== 删除 ==================== */

    public function delete_json()
    {
        $js = array('status' => 0, 'msg' => '');

        if ($this->allowdelete === false) {
            $js['msg'] = '删除功能未启用';
            $this->json($js);
        }

        $privilegeid = isset($this->userinfo['privilegeid']) ? $this->userinfo['privilegeid'] : '';
        if (strpos(',' . $privilegeid . ',', ',7,') === false && $this->ismanager === false) {
            $js['msg'] = '您无删除权限';
            $this->json($js);
        }

        if (method_exists($this, '_before_delete')) {
            $istrue = $this->_before_delete();
            if ($istrue !== true) {
                $js['msg'] = $istrue;
                $this->json($js);
            }
        }

        $name = MODULE_NAME;
        if ($this->tb != '') {
            $name = $this->tb;
        }
        $this->tb = $name;

        $id = intval(I('id', 0, 'post'));
        if ($id == 0) {
            $js['msg'] = '数据不存在';
            $this->json($js);
        }

        $m = M($name);
        $rt = $m->where('id=' . $id)->find();
        if (!$rt) {
            $js['msg'] = '数据不存在';
            $this->json($js);
        }

        if ($this->physical === true) {
            $rtcmd = $m->where('id=' . $id)->delete();
        } else {
            $rtcmd = $m->where('id=' . $id)->setField('deleted', 1);
            if ($name == 'Orders') {
                M('OrdersDetails')->where('ordid=' . $id)->setField('deleted', 1);
            }
        }

        $tit = '删除数据';
        if ($rtcmd !== false) {
            if (method_exists($this, '_after_delete')) {
                $this->_after_delete($id);
            }

            if ($this->add_update_log === true) {
                $adddata = array(
                    array(
                        'aid'            => $id,
                        'tid'            => 3,
                        'before_content' => '',
                        'after_content'  => '',
                    ),
                );
                $this->update_log($adddata);
            }

            $js['status'] = 1;
            $js['data']   = $id;
            $js['msg']    = $tit . '成功';
        } else {
            $js['msg'] = $tit . '失败';
        }
        $this->json($js);
    }

    /* ==================== 操作日志 ==================== */

    protected function update_log($data = null)
    {
        if (!$data) {
            return;
        }
        $uid  = getuid();
        $nowt = time();
        $ip   = getipint();

        foreach ($data as $k => $v) {
            $data[$k]['uid']         = $uid;
            $data[$k]['addtime']     = $nowt;
            $data[$k]['ip']          = $ip;
            $data[$k]['module_name'] = MODULE_NAME;
        }
        M('Log')->addAll($data);
    }

    /* ==================== 字段 JSON ==================== */

    /**
     * 获取表字段 JSON，支持字段别名（AS）
     * @param string $tb 主表名
     * @return array ['status'=>bool, 'msg'=>'', 'data'=>[]]
     */
    public function getfield_json($tb = '')
    {
        $result = array('status' => false, 'msg' => '');

        // 1. 主表字段
        $mainFields = $this->get_tb_field($tb);
        $originalFields = null;

        if ($this->field && strpos($this->field, '.') !== false && strpos($this->field, '`A1`') === false) {
            $originalFields = $mainFields;
        }

        // 2. 合并关联表字段
        if (!empty($this->detail) && is_array($this->detail)) {
            foreach ($this->detail as $detailTable) {
                $detailFields = $this->get_tb_field($detailTable);
                if (!empty($detailFields)) {
                    $mainFields = array_merge($mainFields, $detailFields);
                }
            }
        }

        // 3. 未指定 field 时返回全部
        if ($this->field === '' || $this->field === null) {
            if (!empty($mainFields)) {
                $result['status'] = true;
                $result['data']   = $mainFields;
            }
            return $result;
        }

        // 4. 解析字段列表
        $allowedFields = array();
        $aliasMap      = array();

        if (strpos($this->field, '.') === false) {
            // 简单逗号分隔
            $allowedFields = array_map('trim', explode(',', $this->field));
        } else {
            // 复杂 SQL 片段，解析 AS
            $fieldItems = array_map('trim', explode(',', $this->field));
            foreach ($fieldItems as $item) {
                if ($item === '') {
                    continue;
                }
                if (preg_match('/\bAS\s+`?(\w+)`?\s*$/i', $item, $m)) {
                    $alias = $m[1];
                    $realField = preg_replace('/\s+AS\s+`?\w+`?\s*$/i', '', $item);
                    $realField = preg_replace('/^`?\w+`?\./', '', $realField);
                    $realField = trim($realField, '`');
                    $aliasMap[$alias] = $realField;
                    $allowedFields[]  = $realField;
                } else {
                    $realField = preg_replace('/^`?\w+`?\./', '', $item);
                    $realField = trim($realField, '`');
                    $allowedFields[] = $realField;
                }
            }
            $allowedFields = array_unique($allowedFields);
        }

        // 5. 筛选
        $filtered = array();
        if (!empty($allowedFields) && !empty($mainFields)) {
            foreach ($mainFields as $fieldName => $fieldInfo) {
                if (in_array($fieldName, $allowedFields)) {
                    $key = array_search($fieldName, $aliasMap);
                    if ($key !== false) {
                        $filtered[$key] = $fieldInfo;
                    } else {
                        $filtered[$fieldName] = $fieldInfo;
                    }
                }
            }
        }

        // 6. 合并原始主表字段
        if (!empty($originalFields)) {
            $filtered = array_merge($originalFields, $filtered);
        }

        if (!empty($filtered)) {
            $result['status'] = true;
            $result['data']   = $filtered;
        } else {
            $result['msg'] = 'No matching fields found';
        }

        return $result;
    }

    /**
     * 获取表字段（数值型返回 0，其他返回 ''）
     */
    protected function get_tb_field($tb = '')
    {
        if ($tb === '' || $tb === null) {
            return array();
        }
        $pre = C('DB_PREFIX');
        $table = $pre . cc_table($tb);

        // 白名单校验表名
        if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            return array();
        }

        $sql = 'SHOW FULL COLUMNS FROM `' . $table . '`';
        $rt = M('')->query($sql);
        $vo = array();
        if (!empty($rt)) {
            foreach ($rt as $v) {
                $type = $v['Type'];
                $isNumeric = preg_match('/(int|float|double|decimal|numeric|real)/i', $type);
                $vo[$v['Field']] = $isNumeric ? 0 : '';
            }
        }
        return $vo;
    }

    /* ==================== 关联表字段拼接 ==================== */

    /**
     * 获取除了 id 之外的关联表字段
     */
    protected function get_field_str($fieldstr = '', $arrlist = null)
    {
        if (!$arrlist) {
            return '';
        }

        if ($fieldstr != '') {
            if (strpos($fieldstr, 'A1`') === false) {
                $str = '`A1`.*,' . $fieldstr;
            } else {
                $str = $fieldstr;
                $str1 = $this->getJoinFields($arrlist, $fieldstr);
                if ($str1 != '') {
                    $str .= ',' . $str1;
                }
            }
        } else {
            $str = '`A1`.*';
            $str1 = $this->getJoinFields($arrlist);
            if ($str1 != '') {
                $str .= ',' . $str1;
            }
        }
        return $str;
    }

    /**
     * 生成关联表字段列表（原 getField，避免与 Model 冲突改名）
     */
    public function getJoinFields($arr, $fieldstr = '')
    {
        $pre = C('DB_PREFIX');
        $fieldarr = array();
        $m = M('');

        foreach ($arr as $key => $v) {
            if ($fieldstr && strpos($fieldstr, 'A' . ($key + 2) . '`') !== false) {
                continue;
            }
            $table = $pre . cc_table($v);
            if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
                continue;
            }
            $sql = 'SHOW FULL COLUMNS FROM `' . $table . '`';
            $arrs = $m->query($sql);
            if ($arrs) {
                foreach ($arrs as $vvv) {
                    if (strtolower($vvv['Field']) != 'id') {
                        $fieldarr[] = '`A' . ($key + 2) . '`.`' . $vvv['Field'] . '`';
                    }
                }
            }
        }
        if (empty($fieldarr)) {
            return '';
        }
        return implode(',', $fieldarr);
    }

    /* ==================== 库存日志 ==================== */

    public function stock_add($list = null)
    {
        if (empty($list)) {
            return false;
        }
        return M('ProStockLog')->addAll($list);
    }
}