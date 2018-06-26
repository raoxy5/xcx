<?php
namespace app\index\controller;
use think\Db;
class User extends Home
{
    private $appid;
    private $sessionKey;

    public function index()
    {	
    		$this->info(UID,'member_id,member_name');
    }
    public function address(){
    		$user = model('user');
    		$lists = $user->address();
    		succ($lists);
    }
    public function address_update(){
    		$address = model('address');
    		$data = input();
    		unset($data['token']);
    		$data['uid'] = UID;
    		if($address->_update($data)!==false){
    			succ();
    		}else{
    			err($address->getError());
    		}
    }
    public function address_info(){
				($id = input('id')) || err('id is empty');
				succ(model('address')->info($id));
    }
    //获取用户信息
    public function info($fields='*',$json=0){
    		$result = model('user')->info(UID,$fields);
    		if($json==1){
    				return $result;
    		}
    	
    		$result?succ($result->toArray()):err('获取失败');
    }
    public function updateInfo(){
    			($fields = input('fields')) || $this->err('修改失败，缺少fields');
    			$field = explode(',',$fields);
    			foreach($field as $v){
    				switch($v){
    					case 'member_tbopenid':	//保存淘宝openid
	    					if($member_tbopenid = input('member_tbopenid')){
	    							db('member')->where('member_tbopenid',$member_tbopenid)->update(['member_tbopenid'=>'','is_tb_bind'=>0]);
	    							$data['member_tbopenid'] = $member_tbopenid;
	    							$data['is_tb_bind'] = 1;
	    					}else{
	    						 $this->err('修改失败，缺少member_tbopenid值');
	    					}
    					break;
						case 'remove_tbopendid':
						$data['member_tbopenid'] = '';
						$data['is_tb_bind'] = 0;
						break;
						case 'member_passwd':
						($old_password = input('old_password'))||err('old_password is empty');
						($new_password = input('member_passwd'))||err('member_passwd is empty');
						if(db('member')->where('member_id',UID)->value('member_passwd')==md5($old_password)){
								$data['member_passwd'] = $new_password;
						}else{
								err('旧密码不对');
						}
						break;
    					case 'is_tb_bind':
    					case 'member_sex':
    					case 'member_nickname':
    					case 'member_truename':
    					case 'member_email':
    					case 'member_areaid':
    					case 'member_cityid':
    					case 'member_provinceid':
    					case 'member_avatar':
						case 'registration_id':
    					case 'zfb':
    					input($v)&&$data[$v] = input($v);
    					break;
    					case 'member_mobile':
    					$validate = 'Member.update';
    					$base = new \app\index\controller\Base();
    					$base->checkCode(input($v),input('code'),1);
    					$data['member_mobile_bind'] = 1;
    					input($v)&&$data[$v] = input($v);
    					break;
    					default:
    					$this->err('fields错误');
    					break;
    				}
    			}
    			$Member = model('Member');
				
    			$Member->validate(isset($validate)?$validate:false)->save($data,['member_id'=>UID])!==false?$this->succ([],'修改成功'):$this->err($Member->getError());
    }
    //所有收益
   	public function earning(){
   			 ($type = input('type'))||$this->err('缺少参数type');
   			 ($date = input('date'))||$this->err('缺少日期类型');
   			 $where['member_id'] = UID;
   			 $now_time = time();
   			 $lists = array();
   			 $max = 0;
   			 $i=0;
   			 for(--$type;$type>=0;$type--){
   			 		$time = strtotime("-$type day");
   			 		$where['add_date'] = date('ymd',$time);
   			 		$date1 = date($date,$time);
				    $earnings = db('member_earnings')->where($where)->value('earnings');
					$earnings||$earnings=0;
					$lists[$i]['earnings'] = $earnings;
					$lists[$i]['date'] = $date1;
					($type==1)&&$data['yertoday'] = $earnings;
					($type==0)&&$data['today'] = $earnings;
					$max>$earnings||$max=$earnings;
					$i++;
   			 }
   			 $data['max'] = $max;
   			 $data['before'] = $lists;
   			  //预测明日收益
   			 $data['tomorrow'] = $earnings;
			 $data['update_time'] = date('m-d').' 00:05';
   			 //用户信息
   			 $list = db('member')->where('member_id',UID)->field('order_price,order_nums,member_earnings,member_tx_earnings,member_all_earnings,day_rate as year_rate')->find();
			 $list['year_rate'] *= 365;
			 $list['year_rate'] = $list['year_rate']>20?20:$list['year_rate'];
   			 $data = array_merge($data,$list);
   			 $this->succ($data);
   	}
   	//历史记录
   	public function search_record(){
   			$limit = input('limit')?input('limit'):5;
			$data = db('member_search_record')->where('member_id',UID)->where('is_show',1)->limit($limit)->order('update_time desc')->column('keyword');
			$search['history'] = $data;
			$search['hot'] =  ['辣条','牛奶'];
			$data!==false?$this->succ($search):$this->err('查询失败');
   	}
   	public function delete_search_record(){
			$map['member_id'] = UID;
			$this->request->has('keyword')&&$map['keyword'] = $keyword;
			db('member_search_record')->where($map)->update(['is_show'=>0])!==false?$this->succ('删除成功'):$this->err('不存在该keyword');
   	}
   	//添加记录
   	public function add_search_record(){
			($key =input('keyword')) || $this->err('keyword不存在');
			$info = db('member_search_record')->where(['keyword'=>$key,'member_id'=>UID])->field('id,search_nums')->find();
			if(empty($info)){
					$data['member_id'] = UID;
					$data['keyword'] = $key;
					$data['search_nums'] = 1;
					$data['is_show'] = 1;
					$data['add_time'] = $data['update_time'] = time();
					$result = db('member_search_record')->insert($data);
			}else{
					$data['update_time'] = time();
					$data['search_nums'] = ++$info['search_nums'];
					$data['is_show'] = 1;
					$result = db('member_search_record')->where('id',$info['id'])->update($data);
			}
			$result?$this->succ('记录成功'):$this->err('记录失败');
   	}
   	//添加提现
   	public function add_tx(){
   			($earnings = input('earnings'))||$this->err('缺少earings');
   			//查询是最小值
   			$earnings<1&&$this->err('最小金额为1');
   			$member_info = $this->info('member_earnings,member_tx_earnings',1);
   			$earnings>$member_info['member_earnings']&&$this->err('你的余额不足');
   			//记录提现记录
   			Db::startTrans(); 
			try{
				
   					$data['member_earnings'] = $member_info['member_earnings'] - $earnings;
   					$data['member_tx_earnings'] = $member_info['member_tx_earnings'] + $earnings;
   					Db::name('member')->where('member_id',UID)->update($data);
   					//添加记录
   					$tx['member_id']=UID;
   					$tx['tx_earnings'] = $earnings; 
   					$tx['add_time'] = time();
   					$tx['state'] = 0;
   					Db::name('member_tx_record')->insert($tx);
   					Db::commit();
   					$this->succ($data['member_earnings']);
			}catch (\Exception  $e) {
					// 回滚事务 
					Db::rollback();
					$this->err('未知错误');
			}
   	}
   	//添加提现记录lv1
   	public function add_tx_1(){
   			($earnings = input('earnings'))||$this->err('缺少earings');
   			$member_info = $this->info('member_earnings,member_tx_earnings',1);
   			$Balance = model('Balance');
   			if($Balance->save_log($earnings,2,$member_info)){
   				$this->succ('添加成功');
   			}else{
   				$this->err($Balance->getError());
   			}
   	}
   	//table--表名字  
   	//limit--条数
   	//member_fields = 用户信息
   	public function table_lists($table='',$limit=10,$fields='*',$member_fields='',$json='0',$where=[]){
   			$where['member_id'] = UID;
   			switch($table){
   				case 'member_earnings':
   				break;
   				case 'member_tx_record':
   				break;
   				case 'member_balance':
   				break;
   				case 'order':
   				break;
   				default:
   				$this->err('table is Error');
   				break;
   			}
   			$table_join = isset($join)?Db::name($table)->join($join):Db::name($table);
   			$result = $table_join->field($fields)->where($where)->paginate($limit);
   			$data['lists'] = $result->toArray();
   			if($member_fields){
   					$array = $this->info($member_fields,1);	
   					$data['fields'] = is_array($array)?array_values($array):[$array];
   			}
   			if($json){
   				return $data;
   			}
   			$this->succ($data);
   	}
   	//存入
   	public function  order($type=0,$limit=10,$fields=""){
   			$where = [];
   			switch($type){
   				case 1:
   				$where['order_state'] = ['in','5,10'];
   				break;
   				case 2:
   				$where['order_state'] = 20;
   				break;
   				case 3:
   				$where['order_state'] = 0;
   				break;
   				default:
   				//$this->err('type is wrong');
   				break;
   			}
   			$json = $this->table_lists('order',$limit,'*','',1,$where);
   			succ($json['lists']);
   	}
   	public function tongyong_lists(){
   		$data = $this->table_lists(input('table'),input('limit'),input('fields'),input('member_fields'),1);
   		switch(input('table')){
   				case 'order':
   				foreach($data['lists']['data'] as &$v){
   						$info = $v;
   						$v = null;
   						$v['name'] = '购买 '.$info['order_code'];
   						$v['add_time'] =$info['into_time'];
   						$v['price'] = $info['order_price'];
   				}
   				break;
   				case 'member_balance':
   					foreach($data['lists']['data'] as &$v){
   						$info = $v;
   						$v = null;
   						$v['name'] = $info['type']==1?'超级钱包利息':'申请提现(支出)';
   						$v['add_time'] = $info['add_time'];
   						$v['price'] = $info['balance'];
   					}
   				break;
   				case 'member_tx_record':
   					foreach($data['lists']['data'] as &$v){
   						$info = $v;
   						$v = null;
   						$v['name'] = '提现成功';
   						$v['add_time'] = $info['add_time'];
   						$v['price'] = $info['tx_earnings'];
   					}
   				break;
   				case 'member_earnings':
   					foreach($data['lists']['data'] as &$v){
   						$info = $v;
   						$v = null;
   						$v['name'] = '超级钱包利息';
   						$v['add_time'] = $info['add_time'];
   						$v['price'] = $info['earnings'];
   					}
   				break;
   		}
   		$this->succ($data);
   	}
	public function year_rate(){
			$day_rate = model('Member')->info(UID,'day_rate');
			$year_on = [
				'FriendInvitation' => '5.0',
				'SpendingPower' => '17.0',
				'SharingRate' => '5.0',
				'UseRate'=>'27.25',
				'Shoppingbehavior'=>'5.0',
				'year_reate'=>$day_rate*365
			];
			$this->succ($year_on);
	}
	//用户头像 年化收益率 淘宝话费 每天收益
	public function totalfee(){
					$info = model('Member')->info(UID,'member_avatar,day_rate');
					$info['day_rate'] = $info['day_rate']*365>20?20:$info['day_rate']*365;
					$earnings = db('member_earnings')->where('member_id',UID)->order('id desc')->value('earnings');
					$info['earnings'] = $earnings?$earnings:0;
					$info['order_price'] = db('order')->where('member_id',UID)->where('order_state',20)->sum('order_price');
					succ($info);
	}

  /**
     * 获取api_ticket
     */
    public function get_ticket()
    {
        //获取openid
        $openid = db('screen_mem')->where('id',UID)->value('openid');
        // dump($openid);
        $str = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ123456789";
        $nonce_str = substr(str_shuffle($str),1,10);
        $access_token = model('wx')->get_token();
        
          $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$access_token.'&type=wx_card';
          $ticket = curl_post1($url,[]);
          $api_ticket = json_decode($ticket,true);
          // dump($api_ticket);
        if ($api_ticket['errcode']==0) {
          $api_ticket = $api_ticket['ticket'];
          $timestamp = time();
          $card_id=input('card_id');
          $appsecret = '6b6a7b6994c220b5d2484e7735c0605a';
          // $sortString = $nonce_str.$timestamp.$api_ticket.$card_id;
          // dump($sortString);
          
          $arr = array($card_id,$api_ticket,$nonce_str,$timestamp);//组装参数
          asort($arr, SORT_STRING);
          // dump($arr);
          $sortString = "";
          foreach($arr as $temp){
              $sortString = $sortString.$temp;
          }
          $signature = sha1($sortString);
          $data = array('signature'=>$signature,'nonce_str'=>$nonce_str,'timestamp'=>$timestamp,'card_id'=>$card_id,'api_ticket'=>$api_ticket);
          // dump($signature);=
              succ($data);
         
        } else{
          err('未获取到签名');
        }
        
        
    }  

}
