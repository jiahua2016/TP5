<?php

namespace app\store\model\dealer;

use app\common\model\dealer\User as UserModel;
use app\store\model\dealer\Referee as RefereeModel;
use think\Db;
/**
 * 分销商用户模型
 * Class User
 * @package app\store\model\dealer
 */
class User extends UserModel
{
    /**
     * 获取分销商用户列表
     * @param string $search
     * @return \think\Paginator 
     * @throws \think\exception\DbException
     */
    public function getList($search = '')
    {   
        // 构建查询规则
        $this->alias('dealer')
            ->field('dealer.*, user.nickName, user.avatarUrl')
            ->with(['referee','level_user'=>function($query){
                $query->alias("referee")
                ->join("user a",'a.user_id=referee.user_id')
                ->where('a.is_delete',0)
                ->field("referee.dealer_id,a.user_id,level");
            }])
            ->join('user', 'user.user_id = dealer.user_id')
            ->where('dealer.is_delete', '=', 0)
            ->order(['dealer.create_time' => 'desc']);
           
        // 查询条件
        !empty($search) && $this->where('user.nickName|dealer.real_name|dealer.mobile', 'like', "%$search%");
        // 获取列表数据
        return $this->paginate(15, false, [
            'query' => \request()->request()
        ]);
    }


    /**
     * 软删除
     * @return false|int
     */
    public function setDelete()
    {
        return $this->save(['is_delete' => 1]);
    }

    /**
     * 提现打款成功：累积提现佣金
     * @param $user_id
     * @param $money
     * @return false|int
     * @throws \think\exception\DbException
     */
    public static function totalMoney($user_id, $money)
    {
        $model = self::detail($user_id);
        return $model->save([
            'freeze_money' => $model['freeze_money'] - $money,
            'total_money' => $model['total_money'] + $money,
        ]);
    }

    /**
     * 提现驳回：解冻分销商资金
     * @param $user_id
     * @param $money
     * @return false|int
     * @throws \think\exception\DbException
     */
    public static function backFreezeMoney($user_id, $money)
    {
        $model = self::detail($user_id);
        return $model->save([
            'money' => $model['money'] + $money,
            'freeze_money' => $model['freeze_money'] - $money,
        ]);
    }


    //设定职位名称
    public function getRankAttr($value){
        $status=[1=>"代理商",2=>"销售主任",3=>"销售经理",4=>"区域总监"];
        return $status[$value];
    }

   
}