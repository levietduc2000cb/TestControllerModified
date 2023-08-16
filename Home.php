<?php

// KTK: Shops Home Controller
//      2016-09-30
//      Created by Khuc Trung Kien

defined('BASEPATH') or exit('No direct script access allowed');

class Home extends CI_Controller
{

    private $user;

    public function __construct()
    {

        parent::__construct();

        $this->load->helper('url');
        $this->load->helper('security');
        $this->load->library('shop_user');
    }

    function create_sub_shop($type, $sub_shop_name)
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;


        $params = array();
        $params['name'] = $sub_shop_name;
        $now = strtotime(date("Y-m-d"));
        $expired = date("Y-m-d", strtotime("+45 days", $now));
        $params['expired'] = $expired;
        $params['parent'] = $shop_id;
        $params['type'] = $type;
        $this->load->model('shop_model');
        $sub_shop_id = $this->shop_model->add_shop($params);
        $this->shop_user_model->init_table($sub_shop_id);
        if ($type == 11) {
            $this->shop_user_model->init_table_data($sub_shop_id, $type);
        }


        $params = array();
        $params['children'] = intval($this->user->shop['children']) + 1;

        $subs = '' . $this->user->shop['subs'];
        $subs = json_decode($subs, true);
        if (!$subs) {
            $subs = array();
        }
        $subs[] = array('id' => $sub_shop_id, 'name' => $sub_shop_name);
        $this->user->shop['subs'] = json_encode($subs);
        $this->user->save_session();
        $params['subs'] = json_encode($subs);
        $this->shop_model->update_shop($shop_id, $params);
        return $sub_shop_id;
    }

    function new_sub_shop()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $data = array();
        if (!empty($_POST)) {
            $type = intval($this->input->post('type'));
            if ($type == 100) {
                $type = 0;
            }
            $shop_name = std($this->input->post('shop_name'));
            $this->create_sub_shop($type, $shop_name);
            redirect('/home?message=Đã+tạo+cửa+hàng+con');
        }


        $shop_id = $this->user->shop_id;
        $data['title'] = tb_word('shops.purchase.orders');
        $data["user"] = $this->user;
        // $this->load->view('headers/html_header', $data);


        $this->load->model('shop_type_model');
        $shop_types = $this->shop_type_model->get_all_shop_types();

        $this->load->model('vietnam_model');
        $provinces = $this->vietnam_model->get_all_province();
        $data['provinces'] = $provinces;
        $data['title'] = 'create shop';
        $type = intval($this->input->get('type'));
        $data['type'] = $type;
        $data['shop_types'] = $shop_types;
        $user = new shop_user();
        $data['user'] = $user;
        $data["url"] = $this->config->base_url();

        //toadnk2 sửa
        if (check_user_agent('mobile')) {
            $data['title_header'] = 'Tạo cửa hàng con';
            $this->load->view('mobile_views/html_header_app', $data);
            $this->load->view('mobile_views/new_sub_shop', $data);
        } else {
            $this->load->view('headers/html_header', $data);
            $this->load->view('new_sub_shop', $data);
            $this->load->view('headers/html_footer');
        }

        // $this->load->view('new_sub_shop', $data);
        // $this->load->view('headers/html_footer');
    }
    function sub_shop_login()
    {

        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $sub_shop_id = intval($this->input->get('shop_id'));

        $this->load->model('shop_model');
        $sub_shop = $this->shop_model->get_sub_shop($shop_id, $sub_shop_id);
        if (!$sub_shop) {
            //redirect('/home');
            return;
        }

        $this->user->shop_id = $sub_shop_id;
        $this->user->shop = $sub_shop;
        $this->user->save_session();
        //echo(json_encode($this->user) . "<br>");
        redirect('/home');
    }

    function return_to_parent_shop()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->row['shop_id'];
        $this->load->model('shop_model');
        $shop = $this->shop_model->get_shop($shop_id);
        if (!$shop) {
            //redirect('/home');
            return;
        }

        $this->user->shop_id = $shop_id;
        $this->user->shop = $shop;
        $this->user->save_session();
        //echo(json_encode($this->user) . "<br>");
        redirect('/home');
    }

    function pharm_reg()
    {
        $shop_name = std($this->input->post('shop_name'));
        $name = std($this->input->post('pharm_representative'));
        $phone = std($this->input->post('phone'));
        $province = std($this->input->post('province'));
        $this->load->model('shop_model');
        $this->load->model('shop_user_model');

        if ($this->shop_user_model->check_phone_existence($phone) == 1) {
            $result = array();
            $result['result'] = 0;
            echo (json_encode($result));
            return;
        }

        $password = $this->input->post('password');
        $pharm_representative = std($this->input->post('pharm_representative'));

        $params = array();
        $params['pharm_representative'] = $pharm_representative;
        $params['name'] = $shop_name;
        $params['phone'] = $phone;
        $params['state'] = $province;
        $params['type'] = 11;
        $now = strtotime(date("Y-m-01"));
        $expired = date("Y-m-d", strtotime("+90 days", $now));
        $params['expired'] = $expired;

        $shop_id = $this->shop_model->add_shop($params);


        $user = new shop_user();
        //$data['user_pass'] = $password;
        $data['user_group'] = "admin";
        $data['user_role'] = "shops.lists.user-roles.manager";
        $data['full_name'] = $name;
        $data['phone'] = $phone;
        $data['shop_id'] = intval($shop_id);

        $data['user_group'] = 'admin';
        $data['user_pass'] = dinhdq_encode($phone, $password);
        $id = $this->shop_user_model->add($data);

        $this->shop_user_model->init_table($shop_id);
        $this->shop_user_model->init_table_data($shop_id, 11);

        $result = array();
        $result['result'] = $shop_id;
        echo (json_encode($result));
    }

    public function shop_create()
    {
        redirect('https://hokinhdoanh.online/m/reg');
        return;

        //$this->output->enable_profiler(TRUE);
        $this->load->model('vietnam_model');
        if (!empty($_POST)) {
            //$this->output->enable_profiler(TRUE);
            //return;
            $this->load->model('shop_model');
            $count_shop_by_ip = $this->shop_model->count_shop_by_ip($this->input->ip_address());
            if ($count_shop_by_ip > 100) {
                redirect('/shop_create');
            }
            session_start();
            $count_shop_by_session = $this->shop_model->count_shop_by_session(session_id());
            if ($count_shop_by_session > 100) {
                redirect('/shop_create');
            }

            $shop_name = std($this->input->post('shop_name'));
            $name = std($this->input->post('full_name'));
            $phone = std($this->input->post('phone'));
            $this->load->model('shop_user_model');
            if ($this->shop_user_model->check_phone_existence($phone) == 1) {
                redirect('/shop_create');
            }

            $password = std($this->input->post('password'));
            $code = std($this->input->post('code'));
            $code1 = std($this->input->post('code1'));
            $email = std($this->input->post('email'));
            $address = std($this->input->post('address'));
            $province = std($this->input->post('province1'));
            $district = std($this->input->post('district1'));
            $ward = intval($this->input->post('ward'));
            $location = $this->vietnam_model->get_vietnam($ward);
            $ward_name = $location['name'];
            $location_id = $location['aux_id'];
            $pharm_type = std($this->input->post('pharm_type'));
            $pharm_representative = std($this->input->post('pharm_representative'));
            $pharm_representative_id = std($this->input->post('pharm_representative_id'));

            $pharm_responsible = std($this->input->post('pharm_responsible'));
            $pharm_responsible_no = std($this->input->post('pharm_responsible_no'));
            $pharm_responsible_id = std($this->input->post('pharm_responsible_id'));
            $pharm_responsible_level = std($this->input->post('pharm_responsible_level'));
            $pharm_responsible_phone = std($this->input->post('pharm_responsible_phone'));
            $pharm_responsible_email = std($this->input->post('pharm_responsible_email'));


            $micro = intval($this->input->post('micro'));

            $type = intval($this->input->post('type'));
            if ($type == 100) {
                $type = 0;
            }


            $promotion_code = std($this->input->post('promotion_code'));

            $params = array();
            $params['name'] = $shop_name;
            $params['phone'] = $phone;
            $params['email'] = $email;
            $params['state'] = $province;
            $params['district'] = $district;
            $params['ward'] = $ward_name;
            $params['address'] = $address;
            $params['location_id'] = $location_id;
            $params['type'] = $type;
            $params['promotion_code'] = $promotion_code;
            $params['ip'] = $this->input->ip_address();
            $params['session'] = session_id();
            $params['code'] = $code;
            $params['code1'] = $code1;
            $params['pharm_type'] = $pharm_type;
            $params['pharm_representative'] = $pharm_representative;
            $params['pharm_representative_id'] = $pharm_representative_id;

            $params['pharm_responsible'] = $pharm_responsible;
            $params['pharm_responsible_no'] = $pharm_responsible_no;

            $params['pharm_responsible_id'] = $pharm_responsible_id;
            $params['pharm_responsible_level'] = $pharm_responsible_level;
            $params['pharm_responsible_phone'] = $pharm_responsible_phone;
            $params['pharm_responsible_email'] = $pharm_responsible_email;

            $params['micro'] = $micro;

            $now = strtotime(date("Y-m-d"));
            $expired = date("Y-m-d", strtotime("+14 days", $now));
            $params['expired'] = $expired;


            $shop_id = $this->shop_model->add_shop($params);

            $user = new shop_user();
            //$data['user_pass'] = $password;
            $data['user_group'] = "admin";
            $data['user_role'] = "shops.lists.user-roles.manager";
            $data['full_name'] = $name;

            $data['phone'] = $phone;
            $data['email'] = $email;
            $data['shop_id'] = intval($shop_id);

            $data['user_group'] = 'admin';
            $token = ktk_get_token();
            $data['user_pass'] = dinhdq_encode($phone, $password);


            $id = $this->shop_user_model->add($data);

            $this->shop_user_model->init_table($shop_id);

            if ($type == 11) {
                $this->shop_user_model->init_table_data($shop_id, $type);
            }

            $this->load->helper('cookie');

            $cookie = array(
                'name'   => 'phone',
                'value'  => $phone,
                'expire' => time() + 6 * 30 * 24 * 3600,
                'domain' => $_SERVER['HTTP_HOST'],
                'path'   => '/',
                'prefix' => '',
            );
            set_cookie($cookie);
            //

            //$shop_name = $name;
            $shops = get_cookie('shops');
            if ($shops == null) {
                $shops = array();
            } else {
                $shops = json_decode($shops, true);
            }
            $shops[$shop_id] = $phone;
            $shops = json_encode($shops);
            $cookie = array(
                'name'   => 'shops',
                'value'  => $shops,
                'expire' => time() + 6 * 30 * 24 * 3600,
                'domain' => $_SERVER['HTTP_HOST'],
                'path'   => '/',
                'prefix' => '',
            );
            set_cookie($cookie);

            $message = "Bạn đã tạo cửa hàng thành công, xin mời đăng nhập";
            //redirect("login?phone=".$phone . "&message=" . urlencode($message));
            redirect("/welcome");
            return;
        }

        $data = array();

        $phone = std($this->input->get('phone'));
        $email = std($this->input->get('email'));

        $syt = array();
        $syt['location_id'] = 0;
        $syt['district_id'] = 0;
        $syt['ward'] = 0;
        $syt['email'] = '';
        $syt['phone'] = '';
        $syt['no'] = '';
        $syt['address'] = '';
        $syt['name'] = '';
        $syt['pharm_responsible'] = '';


        $data['syt'] = $syt;

        $this->load->model('shop_type_model');
        $shop_types = $this->shop_type_model->get_all_shop_types();

        $provinces = $this->vietnam_model->get_all_province();
        $data['provinces'] = $provinces;
        $data['title'] = 'create shop';
        $type = intval($this->input->get('type'));
        $data['type'] = $type;
        $data['shop_types'] = $shop_types;
        $user = new shop_user();
        $data['user'] = $user;
        $data["url"] = $this->config->base_url();

        $this->load->model('shop_model');
        $shops = $this->shop_model->get_shops_by_type(11);
        $data["shops"] = $shops;

        $this->load->view('create_shop', $data);
        if ($type != 11) {
            $this->load->view('headers/html_footer');
        } else {
            $this->load->view('headers/pharm_footer');
        }
    }


    public function shop_online_create()
    {
        //$this->output->enable_profiler(TRUE);
        $this->load->model('vietnam_model');
        if (!empty($_POST)) {
            //$this->output->enable_profiler(TRUE);
            //return;
            $this->load->model('shop_model');
            $count_shop_by_ip = $this->shop_model->count_shop_by_ip($this->input->ip_address());
            if ($count_shop_by_ip > 100) {
                redirect('/shop_create');
            }
            session_start();
            $count_shop_by_session = $this->shop_model->count_shop_by_session(session_id());
            if ($count_shop_by_session > 100) {
                redirect('/shop_create');
            }

            $shop_name = std($this->input->post('shop_name'));
            $name = std($this->input->post('full_name'));
            $phone = std($this->input->post('phone'));
            $this->load->model('shop_user_model');
            if ($this->shop_user_model->check_phone_existence($phone) == 1) {
                redirect('/shop_create');
            }

            $password = std($this->input->post('password'));
            $code = std($this->input->post('code'));
            $code1 = std($this->input->post('code1'));
            $email = std($this->input->post('email'));
            $address = std($this->input->post('address'));
            $province = std($this->input->post('province1'));
            $district = std($this->input->post('district1'));
            $ward = std($this->input->post('ward2'));
            $location = $this->vietnam_model->get_vietnam($ward);
            $ward_name = $location['name'];
            $location_id = $location['aux_id'];
            $pharm_type = std($this->input->post('pharm_type'));
            $pharm_representative = std($this->input->post('pharm_representative'));
            $pharm_representative_id = std($this->input->post('pharm_representative_id'));

            $pharm_responsible = std($this->input->post('pharm_responsible'));
            $pharm_responsible_no = std($this->input->post('pharm_responsible_no'));
            $pharm_responsible_id = std($this->input->post('pharm_responsible_id'));
            $pharm_responsible_level = std($this->input->post('pharm_responsible_level'));
            $pharm_responsible_phone = std($this->input->post('pharm_responsible_phone'));
            $pharm_responsible_email = std($this->input->post('pharm_responsible_email'));


            $micro = intval($this->input->post('micro'));

            $type = intval($this->input->post('type'));
            if ($type == 100) {
                $type = 0;
            }


            $promotion_code = std($this->input->post('promotion_code'));

            $params = array();
            $params['name'] = $shop_name;
            $params['phone'] = $phone;
            $params['email'] = $email;
            $params['state'] = $province;
            $params['district'] = $district;
            $params['ward'] = $ward;
            $params['address'] = $address;
            $params['location_id'] = $location_id;
            $params['type'] = $type;
            $params['promotion_code'] = $promotion_code;
            $params['ip'] = $this->input->ip_address();
            $params['session'] = session_id();
            $params['code'] = $code;
            $params['code1'] = $code1;
            $params['pharm_type'] = $pharm_type;
            $params['pharm_representative'] = $pharm_representative;
            $params['pharm_representative_id'] = $pharm_representative_id;

            $params['pharm_responsible'] = $pharm_responsible;
            $params['pharm_responsible_no'] = $pharm_responsible_no;

            $params['pharm_responsible_id'] = $pharm_responsible_id;
            $params['pharm_responsible_level'] = $pharm_responsible_level;
            $params['pharm_responsible_phone'] = $pharm_responsible_phone;
            $params['pharm_responsible_email'] = $pharm_responsible_email;

            $params['micro'] = $micro;
            $params['type1'] = 1;

            $now = strtotime(date("Y-m-d"));
            $expired = date("Y-m-d", strtotime("+14 days", $now));
            $params['expired'] = $expired;


            $shop_id = $this->shop_model->add_shop($params);

            $user = new shop_user();
            //$data['user_pass'] = $password;
            $data['user_group'] = "admin";
            $data['user_role'] = "shops.lists.user-roles.manager";
            $data['full_name'] = $name;

            $data['phone'] = $phone;
            $data['email'] = $email;
            $data['shop_id'] = intval($shop_id);

            $data['user_group'] = 'admin';
            $token = ktk_get_token();
            $data['user_pass'] = dinhdq_encode($phone, $password);


            $id = $this->shop_user_model->add($data);

            $this->shop_user_model->init_table($shop_id);

            if ($type == 11) {
                $this->shop_user_model->init_table_data($shop_id, $type);
            }

            $this->load->helper('cookie');

            $cookie = array(
                'name'   => 'phone',
                'value'  => $phone,
                'expire' => time() + 6 * 30 * 24 * 3600,
                'domain' => $_SERVER['HTTP_HOST'],
                'path'   => '/',
                'prefix' => '',
            );
            set_cookie($cookie);
            //

            //$shop_name = $name;
            $shops = get_cookie('shops');
            if ($shops == null) {
                $shops = array();
            } else {
                $shops = json_decode($shops, true);
            }
            $shops[$shop_id] = $phone;
            $shops = json_encode($shops);
            $cookie = array(
                'name'   => 'shops',
                'value'  => $shops,
                'expire' => time() + 6 * 30 * 24 * 3600,
                'domain' => $_SERVER['HTTP_HOST'],
                'path'   => '/',
                'prefix' => '',
            );
            set_cookie($cookie);

            $message = "Bạn đã tạo cửa hàng thành công, xin mời đăng nhập";
            //redirect("login?phone=".$phone . "&message=" . urlencode($message));
            redirect("/welcome");
            return;
        }

        $data = array();
        $lat = std($this->input->get('lat'));
        if ($lat == '') {
            $data['fresh'] = 1;
            $data['province1'] = '';
            $data['district1'] = '';
            $data['ward1'] = '';
        } else {
            $data['fresh'] = 0;
            //https://muongi.vn/index.php/api/thx_by_location?key=51027f8PP55ghP5&lat=21.003340&lon=105.846103
            $lon = std($this->input->get('lon'));
            $url = 'https://muongi.vn/api/thx_by_location?key=51027f8PP55ghP5&lat=' . $lat . '&lon=' . $lon;
            $st = file_get_contents($url);
            $location = json_decode($st, true);
            $location = $location['xa'];
            if (!empty($location)) {
                $ward = $location['xa'];
                $district = $location['huyen'];
                $province = $location['tinh'];

                $data['province1'] = $province;
                $data['district1'] = $district;
                $data['ward1'] = $ward;
            }
        }


        $phone = std($this->input->get('phone'));
        $email = std($this->input->get('email'));

        $syt = array();
        $syt['location_id'] = 0;
        $syt['district_id'] = 0;
        $syt['ward'] = 0;
        $syt['email'] = '';
        $syt['phone'] = '';
        $syt['no'] = '';
        $syt['address'] = '';
        $syt['name'] = '';
        $syt['pharm_responsible'] = '';


        $data['syt'] = $syt;

        $this->load->model('shop_type_model');
        $shop_types = $this->shop_type_model->get_all_shop_types();

        $provinces = $this->vietnam_model->get_all_province();
        $data['provinces'] = $provinces;
        $data['title'] = 'create shop';
        $type = intval($this->input->get('type'));
        $data['type'] = $type;
        $data['type1'] = 1;
        $data['shop_types'] = $shop_types;
        $user = new shop_user();
        $data['user'] = $user;
        $data["url"] = $this->config->base_url();

        $this->load->model('shop_model');
        $shops = $this->shop_model->get_shops_by_type(11);
        $data["shops"] = $shops;

        $this->load->view('create_online_shop', $data);
        if ($type != 11) {
            $this->load->view('headers/html_footer');
        } else {
            $this->load->view('headers/pharm_footer');
        }
    }


    public function welcome()
    {
        $data = array();
        $data['title'] = 'create shop';
        $user = new shop_user();
        $data['user'] = $user;
        $data["url"] = $this->config->base_url();
        $this->load->view('welcome', $data);
        $this->load->view('headers/html_footer');
    }

    public function shop_create1()
    {
        $this->load->model('vietnam_model');
        if ($this->input->post('save') !== null) {
            //$this->output->enable_profiler(TRUE);
            //return;

            $shop_name = std($this->input->post('shop_name'));
            $name = std($this->input->post('full_name'));
            $phone = std($this->input->post('phone'));
            $password = std($this->input->post('password'));
            $email = std($this->input->post('email'));
            $address = std($this->input->post('address'));
            $province = std($this->input->post('province1'));
            $district = std($this->input->post('district1'));
            $ward = intval($this->input->post('ward'));
            $location = $this->vietnam_model->get_vietnam($ward);
            $ward_name = $location['name'];
            $location_id = $location['aux_id'];

            $type = intval($this->input->post('type'));
            $promotion_code = std($this->input->post('promotion_code'));

            $params = array();
            $params['name'] = $shop_name;
            $params['phone'] = $phone;
            $params['email'] = $email;
            $params['state'] = $province;
            $params['district'] = $district;
            $params['ward'] = $ward_name;
            $params['address'] = $address;
            $params['location_id'] = $location_id;
            $params['type'] = $type;
            $params['promotion_code'] = $promotion_code;

            $now = strtotime(date("Y-m-d"));
            $expired = date("Y-m-d", strtotime("+90 days", $now));
            $params['expired'] = $expired;

            $this->load->model('shop_model');
            $shop_id = $this->shop_model->add_shop($params);

            /*
            $token = ktk_get_token();
            $url = $this->config->item('myadmin_url');
            $url = $url . "members/add_shop?&shop_name=" . urlencode($shop_name) . "&token=". $token . "&type=" . $type . "&address=" . urlencode($address) . "&phone=" . urlencode($phone) . "&email=" . urlencode($email) . "&province=" . urlencode($province) . "&district=" . urlencode($district) . "&ward=" . urlencode($ward) . "&promotion_code=" . urlencode($promotion_code);
            */
            //echo($url);
            //return;

            //$shop_id = trim(get_content($url));

            //echo($shop_id . "<br>");
            //return;

            $user = new shop_user();
            //$data['user_pass'] = $password;
            $data['user_group'] = "admin";
            $data['user_role'] = "shops.lists.user-roles.manager";
            $data['full_name'] = $name;

            $data['phone'] = $phone;
            $data['email'] = $email;
            $data['shop_id'] = intval(trim($shop_id));

            //$id = $user->_addOwner($token, $shop_id, $data);
            //$pass = $data['user_pass'];
            $data['user_group'] = 'admin';
            $token = ktk_get_token();
            $data['user_pass'] = dinhdq_encode($email, $password);
            //echo($data['user_pass']);
            //return;

            //$id = $this->model->_add($token, $shop_id, $data);

            $this->load->model('shop_user_model');
            $id = $this->shop_user_model->add($data);

            $this->shop_user_model->init_table($shop_id);

            $this->load->helper('cookie');

            $cookie = array(
                'name'   => 'shop_id',
                'value'  => $shop_id,
                'expire' => time() + 6 * 30 * 24 * 3600,
                'domain' => $_SERVER['HTTP_HOST'],
                'path'   => '/',
                'prefix' => '',
            );
            set_cookie($cookie);
            //

            //$shop_name = $name;
            $shops = get_cookie('shops');
            if ($shops == null) {
                $shops = array();
            } else {
                $shops = json_decode($shops, true);
            }
            $shops[$shop_id] = $shop_name . '|' . $email;
            $shops = json_encode($shops);
            $cookie = array(
                'name'   => 'shops',
                'value'  => $shops,
                'expire' => time() + 6 * 30 * 24 * 3600,
                'domain' => $_SERVER['HTTP_HOST'],
                'path'   => '/',
                'prefix' => '',
            );
            set_cookie($cookie);

            $message = "Bạn đã tạo cửa hàng thành công, xin mời đăng nhập";
            redirect("login?shop_id=" . $shop_id . "&message=" . urlencode($message));
            return;
        }

        $data = array();
        $this->load->model('shop_type_model');
        $shop_types = $this->shop_type_model->get_all_shop_types();

        $provinces = $this->vietnam_model->get_all_province();
        $data['provinces'] = $provinces;
        $data['title'] = 'create shop';
        $type = intval($this->input->get('type'));
        $data['type'] = $type;
        $data['shop_types'] = $shop_types;
        $user = new shop_user();
        $data['user'] = $user;
        $data["url"] = $this->config->base_url();
        $this->load->view('shop_create', $data);
        $this->load->view('headers/html_footer');
    }

    public function index()
    {
        //$this->output->enable_profiler(TRUE);

        if ($this->check_user() === false) {
            $this->login();
            return;
        }

        //echo(json_encode($this->user));
        if ($this->user->row['user_role'] === 'shops.lists.user-roles.kitchen') {
            $this->kitchen();
            return;
        }

        $this->home();
    }

    public function menu()
    {

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $tk    = $this->uri->segment(3);
        if ($tk != NULL && ktk_check_token($tk)) {
            echo 'access denied.';
            return;
        }

        $id    = tb_decode_value($this->uri->segment(4));
        $title = 'system.under-construction';

        $data = array();
        $data['menu']  = $id;
        $data['title'] = $title;
        $data['user']  = $this->user;

        $hpage = 'headers/html_header';
        $fpage = 'headers/html_footer';

        $cat = new my_category();
        $cat->load_me($id);
        $action = $cat->property('action');
        $action = trim(strtolower($action));
        $view = $cat->property('view');
        $view = trim(strtolower($view));

        if ($action != '' || $action != 'menu') {
            switch ($action) {
                case 'user_roles':
                    $this->user_roles($cat);
                    break;
                case 'shop_users';
                    $this->shop_users($cat);
                    break;
            }
            return;
        }

        // menu off
        $code  = trim($cat->property('code'));
        $arr   = array();
        $arr[] = 'shops.menu.configs.users';
        $arr[] = 'shops.menu.configs.roles';
        if (in_array($code, $arr)) {
            $data['menu_off'] = true;
        }

        if (strlen($view) == 0) {
            $hpage = 'headers/simple_header';
            $fpage = 'headers/simple_footer';
            $view  = 'system_message';
            $data['code'] = 'under-construction';
        }

        $this->load->view($hpage, $data);
        $this->load->view($view, $data);
        $this->load->view($fpage);
    }

    public function product_types()
    {

        if ($this->check_user() === false) {
            $this->login();
            return;
        }

        $tk = $this->uri->segment(3);
        $id = $this->uri->segment(4);
        $id = tb_decode_value($id);
        $id = intval($id);

        if ($tk != NULL && ktk_check_token($tk) != 0) {
            $this->show_message('session.session-exprired');
            return;
        }

        if (isset($_POST['btnSave'])) {
            foreach ($_POST as $key => $value) {
                //echo '<br>field: ' . $key;
            }

            $arr = array();
            //$arr['code'] = 
            if (isset($_POST['checks'])) {
                $a = $_POST['checks'];
                foreach ($a as $item) {
                    //echo '<br>' . $item;
                }
            }
        }


        $maker = new html_maker('my-box');
        $maker->left_width  = 40;
        $maker->left_bg     = 'transparent';

        $data['title']   = 'shops.home';
        $data['user']    = $this->user;
        $data['maker']   = $maker;
        $data['type_id'] = $id;
        $data['menu_off'] = true;

        $this->load->view('headers/html_header', $data);
        $this->load->view('product/product_types', $data);
        $this->load->view('headers/html_footer');
    }

    function super_sale()
    {
        $this->order2(0);
    }

    function super_purchase()
    {
        $this->order2(1);
    }

    public function sales_order()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }

        $large = intval($this->user->shop['large']);
        if ($large == 0) {
            $this->order1(0);
        } else {
            $this->order2(0);
        }
    }

    public function purchase_order()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }


        $large = intval($this->user->shop['large']);
        if ($large == 0) {
            $this->order1(1);
        } else {
            $this->order2(1);
        }
    }
    /*
    private function order($type){
        
        if ($this->check_user()===false) {
            $this->login();
            return;
        }

        $data = array();
        //var_dump($data);
        //return;
        $this->load->model('customer_model');
        
        $this->load->model('user_model');
        
        
        $this->load->library('product_lib');
        $shop_id = $this->user->shop_id;
        $abc = $this->product_lib->get_shop_product_prefix($shop_id);
        $products = $this->product_lib->get_shop_product($shop_id);
        $img_path = "/img/$shop_id/";

        $customers = $this->customer_model->get_all_customers($shop_id, $type);
        $data['customers'] = $customers;
        $cashiers = $this->user_model->get_all_users($shop_id);
        $data['abc'] = $abc;
        
        $data['cashiers'] = $cashiers;
        $data['email'] = $this->user->email;
        $data['type'] = $type;
        $data['product_array']=$products;
        $data['img_path']=$img_path;
        if ($type==0){
            $data['title'] = tb_word('shops.sale.order');

        }
        else{
            $data['title'] = tb_word('shops.purchase.order');
        }
        $data["user"] = $this->user;
        $data["url"] = $this->config->base_url();
        
        $this->load->view('headers/html_header', $data);
        $this->load->view('bill_item', $data);
        $this->load->view('bill_product', $data);
        //$this->load->view('layouts/sale', $data);
        $this->load->view('headers/html_footer');
        
    }
    */
    function expired()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }

        $data = array();
        $data["user"] = $this->user;
        $data["url"] = $this->config->base_url();
        $data["title"] = "Hết hạn sử dụng";
        $this->load->view('headers/html_header', $data);
        $this->load->view('expired', $data);
        $this->load->view('headers/html_footer');
    }

    public function sales_order1()
    {
        $this->order1(0);
    }


    private function order1($type)
    {
        //$this->output->enable_profiler(TRUE);
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $shop_type = $this->user->shop['type'];
        $period_closed_date = $this->user->shop['period_closed_date'];
        $period_closed_date1 = vn_date($period_closed_date);

        $whole_sale = intval($this->input->get('whole_sale'));

        $internal = intval($this->input->get('internal'));
        if ($shop_type == 11 && $shop_id != 1011) {
            if ($type == 1) {
                redirect('/pharm/purchase_order');
            } else {
                //redirect('/pharm/sale_order');
                redirect('/m/p_sale');
            }
        }

        $child = intval($this->input->get('child'));
        $datetime1 = date_create(date('Y-m-d'));
        $datetime2 = date_create($this->user->shop['expired']);
        if ($datetime2 < $datetime1) {
            redirect("/expired");
        }


        $data = array();
        //var_dump($data);
        //return;
        $this->load->model('customer_model');

        $this->load->model('user_model');

        $this->load->model('product_model');

        $this->load->model('nationality_model');

        //$this->load->model('currency_model');

        $data["user"] = $this->user;
        $data["url"] = $this->config->base_url();
        //echo("sale");
        //toandk2 sửa
        if (check_user_agent('mobile')) {
            $data["title_header"] = 'Xuất bán/Thu tiền';
            $this->load->view('mobile_views/html_header_app', $data);
        } else {
            $this->load->view('headers/html_header', $data);
        }
        // $this->load->view('headers/html_header', $data);

        $order_ids = $this->input->post('order_ids');
        $order_ids = json_decode($order_ids[0], true);

        //echo(json_encode($order_ids));

        if ($order_ids) {
            $materials = $this->product_model->get_material_items($shop_id, $order_ids);
            //echo(json_encode($order_ids));
            //echo(json_encode($materials));
            $data['init_purchase_products'] = $materials;
        }
        //return;

        $groups = $this->product_model->get_product_groups_by_type($shop_id, $type);

        $products = $this->product_model->get_shop_product_by_type1($shop_id, $type);

        $nationalities = $this->nationality_model->get_all_nationality();

        $img_path = "/img/$shop_id/";

        $this->load->model('shop_detail_model');
        $shop_detail = $this->shop_detail_model->get_shop_detail($shop_id);
        if (!$shop_detail) {
            $shop_detail = array();
            $shop_detail['name'] = $this->user->shop['name'];
            $shop_detail['unit'] = $this->user->shop['name'];
            $shop_detail['address'] = $this->user->shop['address'];
            $shop_detail['bill_book'] = '';
            $shop_detail['director'] = '';
            $shop_detail['chief_accountant'] = '';
            $shop_detail['cashier'] = '';
            $shop_detail['storekeeper'] = '';
        }

        $data['shop_detail'] = $shop_detail;
        $data['period_closed_date'] = $period_closed_date;
        $data['period_closed_date1'] = $period_closed_date1;

        $customers = $this->customer_model->get_all_customers($shop_id, $type);
        $cashiers = $this->user_model->get_all_users($shop_id);
        //$currencies = $this->currency_model->get_all_currencies();
        $data['groups'] = $groups;
        $data['customers'] = $customers;
        $data['cashiers'] = $cashiers;
        $data['email'] = $this->user->email;
        $data['type'] = $type;
        $data['products'] = $products;
        $data['nationalities'] = $nationalities;
        $data['img_path'] = $img_path;
        $data['child'] = $child;

        $subs = null;
        if ($this->user->shop['subs'] != '') {
            $subs = json_decode($this->user->shop['subs'], true);
        } else {
            if ($this->user->shop['parent'] != 0) {
                $this->load->model('shop_model');
                $shop = $this->shop_model->get_shop($this->user->shop['parent']);
                if ($shop) {
                    $subs = array();
                    $sub = array();
                    $sub['id'] = $shop['id'];
                    $sub['name'] = 'Cửa hàng mẹ';
                    $subs[] = $sub;

                    $subs = array_merge($subs, json_decode($shop['subs'], true));
                }
            } else {
                $internal = 0;
            }
        }
        $data['subs'] = $subs;
        $data['internal'] = $internal;

        if ($type == 0) {
            $data['title'] = tb_word('shops.sale.order');
            $data['customer_title'] = "Khách hàng";

            $product_ids = $this->input->post('sale_products');
            if (!empty($product_ids)) {
                //xxxyyyzzz
                $this->load->model('product_model');
                $products = $this->product_model->get_selected_products($shop_id, $product_ids);
                $data['init_products'] = $products;
                $s_date = $this->input->post('s_date');
                $e_date = $this->input->post('e_date');
                $data['s_date'] = $s_date;
                $data['e_date'] = $e_date;

                $start_date = $this->input->post('start_date');
                $end_date = $this->input->post('end_date');
                $data['start_date'] = $s_date;
                $data['end_date'] = $e_date;

                $earlier = new DateTime($s_date);
                $later = new DateTime($e_date);

                $days = $later->diff($earlier)->format("%a");

                $data['days'] = intval($days);
            }
            $url_redirect = "/sales_order";
        } else {
            $data['title'] = tb_word('shops.purchase.order');
            $data['customer_title'] = "Nhà cung cấp";
            $product_ids = $this->input->post('purchase_products');
            if (is_array($product_ids)) {
                //xxxyyyzzz
                $this->load->model('product_model');
                $products = $this->product_model->get_selected_products($shop_id, $product_ids);
                //echo(json_encode($products));
                $data['init_purchase_products'] = $products;
            }
            $url_redirect = "/purchase_order";
        }
        $order_id = intval($this->input->get('order_id'));
        $data['url_redirect'] = $url_redirect;

        $tentative = intval($this->input->get('tentative'));
        $data['tentative'] = $tentative;

        $copy_from = intval($this->input->get('copy_from'));
        if ($copy_from != 0) {
            $this->load->model('bill_item_model');
            $bill_items = $this->bill_item_model->get_order_bill_items($shop_id, $copy_from);
            $data['copy_items'] = $bill_items;
        }


        if ($order_id == 0) {
            if ($tentative != 2) {
                if (!check_user_agent('mobile')) {
                    $this->load->view('bill_item', $data);
                }
            } else {
                $order_id = intval($this->input->get('order'));
                $data['order_id'] = $order_id;

                $this->load->model('muongi_order_detail_model');
                $order_items = $this->muongi_order_detail_model->get_muongi_order_details($shop_id, $order_id);
                $data['order_items'] = $order_items;

                $this->load->view('muongi_item_edit', $data);
            }
        } else {
            $item_id = intval($this->input->get('item_id'));

            $data['order_id'] = $order_id;
            $data['bill_item_id'] = $item_id;

            $this->load->model('order_model');
            $this->load->model('bill_item_model');
            $order = $this->order_model->get_order($order_id, $shop_id);
            $bill_items = $this->bill_item_model->get_order_bill_items($shop_id, $order_id);
            $data['order'] = $order;
            $data['bill_items'] = $bill_items;
            if (!check_user_agent('mobile')) {
                $this->load->view('bill_item_edit', $data);
            }
        }
        $product_json = array();
        foreach ($products as $product) {
            $item = array();
            $item['id'] = $product['id'];
            $item['code'] = $product['product_code'];
            $item['name'] = $product['product_name'];
            if ($type == 0) {
                $item['price'] = $product['list_price'];
            } else {
                $item['price'] = $product['cost_price'];
            }
            $item['origin'] = $product['origin'];
            $product_json[strtoupper($product['product_code'])] = $item;
        }
        $data['product_json'] = json_encode($product_json);

        $init_group = intval($this->input->get('group'));
        $data['init_group'] = $init_group;
        $data['type'] = $type;
        $data['negative_stock'] = $this->user->shop['negative_stock'];
        $data['whole_sale'] = $whole_sale;

        if (check_user_agent('mobile')) {
            $data['page'] = 'sales_order';
            $this->load->view('mobile_views/bill_product_app', $data);
            $this->load->view('mobile_views/html_footer_app', $data);
        } else {
            $this->load->view('bill_product1', $data);
            $this->load->view('headers/html_footer');
        }
        // $this->load->view('bill_product1', $data);
        // $this->load->view('headers/html_footer');
    }

    private function order2($type)
    {
        //$this->output->enable_profiler(TRUE);
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $shop_type = $this->user->shop['type'];

        $whole_sale = intval($this->input->get('whole_sale'));

        $internal = intval($this->input->get('internal'));
        if ($shop_type == 11 && $shop_id != 1011) {
            if ($type == 1) {
                redirect('/pharm/purchase_order');
            } else {
                //redirect('/pharm/sale_order');
                redirect('/m/p_sale');
            }
        }

        $child = intval($this->input->get('child'));
        $datetime1 = date_create(date('Y-m-d'));
        $datetime2 = date_create($this->user->shop['expired']);
        if ($datetime2 < $datetime1) {
            redirect("/expired");
        }


        $data = array();
        //var_dump($data);
        //return;
        $this->load->model('customer_model');

        $this->load->model('user_model');

        $this->load->model('product_model');

        $this->load->model('nationality_model');

        //$this->load->model('currency_model');

        $data["user"] = $this->user;
        $data["url"] = $this->config->base_url();
        //echo("sale");

        $this->load->view('headers/html_header', $data);

        $order_ids = $this->input->post('order_ids');
        $order_ids = json_decode($order_ids[0], true);

        //echo(json_encode($order_ids));

        if ($order_ids) {
            $materials = $this->product_model->get_material_items($shop_id, $order_ids);
            //echo(json_encode($order_ids));
            //echo(json_encode($materials));
            $data['init_purchase_products'] = $materials;
        }
        //return;

        //$groups = $this->product_model->get_product_groups_by_type($shop_id, $type);

        //$products = $this->product_model->get_shop_product_by_type1($shop_id, $type);

        $nationalities = $this->nationality_model->get_all_nationality();

        $img_path = "/img/$shop_id/";

        $this->load->model('shop_detail_model');
        $shop_detail = $this->shop_detail_model->get_shop_detail($shop_id);
        if (!$shop_detail) {
            $shop_detail = array();
            $shop_detail['name'] = $this->user->shop['name'];
            $shop_detail['unit'] = $this->user->shop['name'];
            $shop_detail['address'] = $this->user->shop['address'];
            $shop_detail['bill_book'] = '';
            $shop_detail['director'] = '';
            $shop_detail['chief_accountant'] = '';
            $shop_detail['cashier'] = '';
            $shop_detail['storekeeper'] = '';
        }

        $data['shop_detail'] = $shop_detail;

        $customers = $this->customer_model->get_all_customers($shop_id, $type);
        $cashiers = $this->user_model->get_all_users($shop_id);
        //$currencies = $this->currency_model->get_all_currencies();
        $data['groups'] = $groups;
        $data['customers'] = $customers;
        $data['cashiers'] = $cashiers;
        $data['email'] = $this->user->email;
        $data['type'] = $type;
        $data['products'] = $products;
        $data['nationalities'] = $nationalities;
        $data['img_path'] = $img_path;
        $data['child'] = $child;

        $subs = null;
        if ($this->user->shop['subs'] != '') {
            $subs = json_decode($this->user->shop['subs'], true);
        } else {
            if ($this->user->shop['parent'] != 0) {
                $this->load->model('shop_model');
                $shop = $this->shop_model->get_shop($this->user->shop['parent']);
                if ($shop) {
                    $subs = array();
                    $sub = array();
                    $sub['id'] = $shop['id'];
                    $sub['name'] = 'Cửa hàng mẹ';
                    $subs[] = $sub;

                    $subs = array_merge($subs, json_decode($shop['subs'], true));
                }
            } else {
                $internal = 0;
            }
        }
        $data['subs'] = $subs;
        $data['internal'] = $internal;

        if ($type == 0) {
            $data['title'] = tb_word('shops.sale.order');
            $data['customer_title'] = "Khách hàng";

            $product_ids = $this->input->post('sale_products');
            if (!empty($product_ids)) {
                //xxxyyyzzz
                $this->load->model('product_model');
                $products = $this->product_model->get_selected_products($shop_id, $product_ids);
                $data['init_products'] = $products;
                $s_date = $this->input->post('s_date');
                $e_date = $this->input->post('e_date');
                $data['s_date'] = $s_date;
                $data['e_date'] = $e_date;

                $start_date = $this->input->post('start_date');
                $end_date = $this->input->post('end_date');
                $data['start_date'] = $s_date;
                $data['end_date'] = $e_date;

                $earlier = new DateTime($s_date);
                $later = new DateTime($e_date);

                $days = $later->diff($earlier)->format("%a");

                $data['days'] = intval($days);
            }
            $url_redirect = "/sales_order";
        } else {
            $data['title'] = tb_word('shops.purchase.order');
            $data['customer_title'] = "Nhà cung cấp";
            $product_ids = $this->input->post('purchase_products');
            if (is_array($product_ids)) {
                //xxxyyyzzz
                $this->load->model('product_model');
                $products = $this->product_model->get_selected_products($shop_id, $product_ids);
                //echo(json_encode($products));
                $data['init_purchase_products'] = $products;
            }
            $url_redirect = "/purchase_order";
        }
        $order_id = intval($this->input->get('order_id'));
        $data['url_redirect'] = $url_redirect;

        $tentative = intval($this->input->get('tentative'));
        $data['tentative'] = $tentative;

        $copy_from = intval($this->input->get('copy_from'));
        if ($copy_from != 0) {
            $this->load->model('bill_item_model');
            $bill_items = $this->bill_item_model->get_order_bill_items($shop_id, $copy_from);
            $data['copy_items'] = $bill_items;
        }


        if ($order_id == 0) {
            if ($tentative != 2) {
                $this->load->view('bill_item', $data);
            } else {
                $order_id = intval($this->input->get('order'));
                $data['order_id'] = $order_id;

                $this->load->model('muongi_order_detail_model');
                $order_items = $this->muongi_order_detail_model->get_muongi_order_details($shop_id, $order_id);
                $data['order_items'] = $order_items;

                $this->load->view('muongi_item_edit', $data);
            }
        } else {
            $item_id = intval($this->input->get('item_id'));

            $data['order_id'] = $order_id;
            $data['bill_item_id'] = $item_id;

            $this->load->model('order_model');
            $this->load->model('bill_item_model');
            $order = $this->order_model->get_order($order_id, $shop_id);
            $bill_items = $this->bill_item_model->get_order_bill_items($shop_id, $order_id);
            $data['order'] = $order;
            $data['bill_items'] = $bill_items;
            $this->load->view('bill_item_edit', $data);
        }


        /*
        $product_json = array();
        foreach($products as $product){
            $item = array();
            $item['id'] = $product['id'];
            $item['code'] = $product['product_code'];
            $item['name'] = $product['product_name'];
            if ($type==0){
                $item['price'] = $product['list_price'];
            }
            else{
                $item['price'] = $product['cost_price'];
            }
            $item['origin'] = $product['origin'];
            $product_json[strtoupper($product['product_code'])] = $item;
        }
        $data['product_json'] = json_encode($product_json);
        */
        //$init_group = intval($this->input->get('group'));
        //$data['init_group'] = $init_group;
        $data['type'] = $type;
        $data['negative_stock'] = $this->user->shop['negative_stock'];
        $data['whole_sale'] = $whole_sale;

        $this->load->view('bill_product2', $data);
        //$this->load->view('layouts/sale', $data);
        $this->load->view('headers/html_footer');
    }

    function product_js()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }

        $data = array();
        $shop_id = $this->user->shop_id;
        $this->load->model('product_model');
        $products = $this->product_model->get_products($shop_id);
        header("Content-Type: text/javascript;charset=UTF-8: PASS");
        echo ('var product = ' . json_encode($products));
    }

    function product_js2()
    {
        //$this->output->enable_profiler(TRUE);
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        if ($this->check_user() === false) {
            $this->login();
            return;
        }

        $data = array();
        $shop_id = $this->user->shop_id;
        $shop_type = $this->user->shop['type'];
        if ($shop_type != 11) {
            $this->load->model('product_model');
            $products = $this->product_model->get_products2($shop_id);
        } else {
            $this->load->model('pharm_product_model');
            $products = $this->pharm_product_model->get_products2($shop_id);
        }
        header("Content-Type: text/javascript;charset=UTF-8: PASS");
        echo ('var product = ' . json_encode($products));
    }

    public function cafe_order()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }

        $data = array();
        //var_dump($data);
        //return;
        $this->load->model('customer_model');

        $this->load->model('order_model');
        $this->load->model('bill_model');
        $this->load->model('room_table_model');
        $this->load->model('table_position_model');
        $this->load->model('bill_item_model');
        $this->load->model('product_model');


        $this->load->library('product_lib');
        $this->load->model('product_group_model');
        $shop_id = $this->user->shop_id;
        //$abc = $this->product_lib->get_shop_product_prefix($shop_id);

        $product_groups = $this->product_model->get_product_groups_by_type($shop_id, 0);

        $products = $this->product_lib->get_shop_products_lite($shop_id);

        //echo(json_encode($products));
        $img_path = "/img/$shop_id/";

        $customers = $this->customer_model->get_all_customers($shop_id, 0);
        //$cashiers = $this->user_model->get_all_users($shop_id);

        $table_positions = $this->table_position_model->get_all_table_positions($shop_id);

        $room_tables = $this->room_table_model->get_all_room_tables($shop_id);
        $deni_tables = $this->room_table_model->get_deni_tables($shop_id);

        $table_id = intval($this->input->get("table_id"));
        $type = intval($this->input->get("type"));
        $order_no1 = $this->input->get("order_no");
        //$order_no = 'xxx';

        $bill_status = intval($this->input->get("status"));

        //$bill_items = $this->bill_item_model->get_current_table_bill_items($shop_id, $table_id);

        $order_id = $this->order_model->get_last_open_order_by_table($shop_id, $table_id);
        if ($order_id > 0) {
            $bills = $this->bill_model->get_order_bills($order_id, $shop_id);
            $data['bills'] = $bills;
            $bill_id1 = $bills[0]['id'];
        } else {
            $bill_id1 = 0;
        }

        $bill_id = intval($this->input->get("bill_id"));
        if ($bill_id > 0) {
            $data['bill_id'] = $bill_id;
            $bill_items = $this->bill_item_model->get_bill_items($shop_id, $bill_id);
        } else {
            //$bill_items = $this->bill_item_model->get_order_bill_items($shop_id, $order_id);
            $bill_items = $this->bill_item_model->get_bill_items($shop_id, $bill_id1);
        }

        $data['bill_status'] = $bill_status;
        $data['order_id'] = $order_id;
        $data['table_id'] = $table_id;

        $table = $this->room_table_model->get_room_table($table_id, $shop_id);
        if ($table != null) {
            $data['table_name'] = $table['name'];
        } else {
            $data['table_name'] = '';
        }
        $data['title'] = tb_word('shops.sale.order');
        $data['bill_items'] = $bill_items;
        $data['url'] = $this->config->base_url();
        //$data['abc'] = $abc;
        $data['customers'] = $customers;
        $data['table_positions'] = $table_positions;
        $data['product_groups'] = $product_groups;
        $data['room_tables'] = $room_tables;
        $data['deni_tables'] = $deni_tables;
        //echo(json_encode($room_tables));
        //$data['cashiers'] = $cashiers;
        $data['email'] = $this->user->email;
        $data['type'] = $type;
        $data['order_no1'] = $order_no1;
        $data['products'] = $products;
        //echo(json_encode($products));
        $data['img_path'] = $img_path;
        $data["user"] = $this->user;
        $data["url"] = $this->config->base_url();
        //echo("sale");
        $data['menu_off'] = true;
        //$this->load->view('headers/html_header', $data);
        //$this->load->view('cafe_bill', $data);
        //$this->load->view('layouts/sale', $data);

        $products = $this->product_model->get_shop_product_by_type1($shop_id, 0);

        $product_json = array();
        foreach ($products as $product) {
            $item = array();
            $item['id'] = $product['id'];
            $item['code'] = $product['product_code'];
            $item['name'] = $product['product_name'];
            if ($type == 0) {
                $item['price'] = $product['list_price'];
            } else {
                $item['price'] = $product['cost_price'];
            }
            $item['origin'] = $product['origin'];
            $product_json[strtoupper($product['product_code'])] = $item;
        }
        $data['product_json'] = json_encode($product_json);


        $this->load->view('cafe_order', $data);
        $this->load->view('headers/html_footer');
    }

    function customer_json()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('customer_model');
        $type = intval($this->input->get("type"));
        $customers = $this->customer_model->get_all_customers($shop_id, $type);
        $objects = array();

        foreach ($customers as $customer) {
            $object = (object)array();
            $object->id = $customer['id'];
            $object->name = $customer['name'];
            $object->phone = $customer['phone'];
            $object->email = $customer['email'];
            $object->address = $customer['address'];
            $object->search_field = $customer['name'] . ' (' .  $customer['phone'] . ')';
            $objects[] = $object;
        }
        echo (json_encode($objects));
    }

    public function cafe_order1()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $type = 0;

        $data = array();
        //var_dump($data);
        //return;
        $this->load->model('customer_model');

        $this->load->model('user_model');
        $this->load->model('room_table_model');
        $this->load->model('table_position_model');
        $this->load->model('bill_item_model');


        $this->load->library('product_lib');
        $this->load->model('product_group_model');
        $shop_id = $this->user->shop_id;
        $abc = $this->product_lib->get_shop_product_prefix($shop_id);

        $product_groups = $this->product_group_model->get_all_product_groups($shop_id);

        $products = $this->product_lib->get_shop_products_lite($shop_id);
        $img_path = "/img/$shop_id/";

        $customers = $this->customer_model->get_all_customers($shop_id, $type);
        $cashiers = $this->user_model->get_all_users($shop_id);

        $table_positions = $this->table_position_model->get_all_table_positions($shop_id);

        $room_tables = $this->room_table_model->get_all_room_tables($shop_id);

        $table_id = intval($this->input->get("table_id"));

        $bill_items = $this->bill_item_model->get_current_table_bill_items($shop_id, $table_id);

        $data['table_id'] = $table_id;

        $table = $this->room_table_model->get_room_table($table_id, $shop_id);
        if ($table != null) {
            $data['table_name'] = $table['name'];
        } else {
            $data['table_name'] = '';
        }

        $data['bill_items'] = $bill_items;
        $data['url'] = $this->config->base_url();
        $data['abc'] = $abc;
        $data['customers'] = $customers;
        $data['table_positions'] = $table_positions;
        $data['product_groups'] = $product_groups;
        $data['room_tables'] = $room_tables;
        $data['cashiers'] = $cashiers;
        $data['email'] = $this->user->email;
        $data['type'] = $type;
        $data['product_array'] = $products;
        $data['img_path'] = $img_path;
        if ($type == 0) {
            $data['title'] = tb_word('shops.sale.order');
        } else {
            $data['title'] = tb_word('shops.purchase.order');
        }
        $data["user"] = $this->user;
        $data["url"] = $this->config->base_url();
        //echo("sale");
        $data['menu_off'] = true;
        $this->load->view('headers/html_header', $data);
        $this->load->view('cafe_bill', $data);
        //$this->load->view('layouts/sale', $data);
        //$this->load->view('cafe_order', $data);
        $this->load->view('headers/html_footer');
    }

    public function home()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }

        if ($this->user->shop['type'] == 11 && check_user_agent('mobile')) {
            redirect('/pharm/home');
        }
        //echo(json_encode($this->user));
        //echo(is_array($this->user->shop));

        //return;

        //echo(json_encode($_SESSION));

        $shop_id = $this->user->shop_id;
        $data = array();
        $shop_type = $this->user->shop['type'];
        $user_role = $this->user->row['user_role']; //shops.lists.user-roles.manager
        //var_dump($user_role);
        $data["shop_type"] = $shop_type;
        $data["user_role"] = $user_role;
        $data["user"] = $this->user;
        $data["url"] = $this->config->base_url();
        $data['title'] = 'shops.summary.report';

        //toandk2 sửa
        if (check_user_agent('mobile')) {
            if ($shop_type != 11) {
                redirect('/m/home');
            }
        }

        //echo($shop_type);

        /*
        $datetime1 = date_create(date('Y-m-d'));
        $datetime2 = date_create($this->user->shop['registered']);
        
        $interval = date_diff($datetime2, $datetime1);
        $data['duration'] = intval($interval->format('%a'));
        //xxx
        */
        $datetime1 = date_create(date('Y-m-d'));
        $datetime2 = date_create($this->user->shop['expired']);

        $paid = intval($this->user->shop['paid']);
        $interval = date_diff($datetime1, $datetime2);
        if ($datetime2 < $datetime1) {
            $sign = -1;
        } else {
            $sign = 1;
        }

        $data['duration'] = 45 - $sign * intval($interval->format('%a'));
        $data['interval'] = $sign * intval($interval->format('%a'));
        $data['paid'] = $paid;

        $data['expired'] = vn_date($this->user->shop['expired']);

        $this->load->model('init_data_model');

        $init_data = $this->init_data_model->get_init_data1($shop_id);

        $data['init_data'] = $init_data;

        $this->load->view('headers/html_header', $data);

        $this->load->model('report_model');

        $revenue = $this->report_model->revenue7($shop_id);
        $count = $this->report_model->count7($shop_id);

        //$data['revenue'] = $revenue;
        //$data['count'] = $count;
        $labels = array();
        $revenue_data = array();
        foreach ($revenue as $item) {
            $labels[] = vn_date($item['order_date']);
            $revenue_data[] = $item['amount'] / 1000;
        }
        $count_data = array();
        foreach ($count as $item) {
            $count_data[] = $item['c'];
        }

        $data['revenue'] = $revenue_data;
        $data['count'] = $count_data;
        $data['labels'] = $labels;

        $this->load->model('admin_message_model');
        $row = $this->admin_message_model->get_admin_message(1);
        $message = $row['content'];
        $data['message'] = trim($message);

        $this->load->view("home$shop_type", $data);
        $data['type'] = $this->user->shop['type'];
        $this->load->view('home_message', $data);
        $this->load->view('headers/html_footer');

        //var_dump($_SESSION);
    }

    public function tree()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $this->load->view('tree');
    }

    public function summary_report()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }

        $data = array();
        $data["user"] = $this->user;
        $data["url"] = $this->config->base_url();
        $data['title'] = 'shops.summary.report';

        $shop_id = $this->user->shop_id;
        $this->load->model('report_model');
        $report0 = $this->report_model->profit_daily2($shop_id);
        $data['report0'] = $report0;

        $report1 = $this->report_model->top_10_product_revenue($shop_id, NULL, NULL);
        $data['report1'] = $report1;

        $report2 = $this->report_model->top_10_product_quantity($shop_id, NULL, NULL);
        $data['report2'] = $report2;

        $report3 = $this->report_model->top_revenue_date($shop_id, NULL, NULL);
        $data['report3'] = $report3;

        $report4 = $this->report_model->top_customer($shop_id, NULL, NULL);
        $data['report4'] = $report4;

        $report5 = $this->report_model->top_sale($shop_id, NULL, NULL);
        $data['report5'] = $report5;

        $report6 = $this->report_model->top_hour($shop_id, NULL, NULL);
        $data['report6'] = $report6;

        if (check_user_agent('mobile')) {
            $data['title_header'] = 'Báo cáo biểu đồ';
            $this->load->view('mobile_views/html_header_app', $data);
            $this->load->view('mobile_views/summary_report', $data);
            $this->load->view('mobile_views/html_footer_app');
        } else {
            $this->load->view('headers/html_header', $data);
            $this->load->view('summary_report', $data);
            $this->load->view('headers/html_footer');
        }
    }

    public function shop_users()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }

        $tk  = $this->uri->segment(3);
        if ($tk != NULL && ktk_check_token($tk) != 0) {
            $this->show_message('session.session-exprired');
            return;
        }

        $act = $this->uri->segment(4);
        $id  = $this->uri->segment(5);

        $act = tb_decode_value($act);
        $id  = tb_decode_value($id);
        $id  = intval($id);

        if ($tk == NULL) {
            $id = $this->input->post('_user_id');
        } else if ($id > 0) {
            $shop_id = $this->user->shop_id;
            $arr = array();
            if ($act == 'lock') {
                $arr['status'] = 'XX';
            } else if ($act == 'unlock' || $act == 'activate') {
                $arr['status'] = 'NN';
            }
            $this->user->_update(ktk_get_token(), $shop_id, $id, $arr);
        }

        $err = NULL;
        if (isset($_POST['btnSave'])) {
            $email   = std($this->input->post('_email'));
            $pass   = std($this->input->post('_user_pass'));
            $retype = std($this->input->post('_retype'));
            $change_pass = 'yes';
            if ($id > 0) {
                $change_pass = $_POST['changes'][0];
            }

            $arr = array();
            if ($this->user->check_name($name) == false) {
                $err = 'shop-user.name-is-too-short';
            } else if ($change_pass == 'yes' && $this->user->check_pass($pass, $retype) !== true) {
                $err = $this->user->check_pass($pass, $retype);
            } else {

                $arr['user_id']   = $id;
                $arr['email'] = $email;
                if ($change_pass == 'yes') {
                    $arr['user_pass'] = $pass;
                }
                $arr['shop_id']   = $this->user->shop_id;
                $arr['full_name'] = std($this->input->post('_full_name'));
                $arr['phone']     = std($this->input->post('_phone'));
                $arr['email']     = std($this->input->post('_email'));
                $arr['notes']     = std($this->input->post('_notes'));
                $arr['user_role'] = $_POST['roles'][0];
                $arr['title']     = $_POST['titles'][0];

                $shop_id = $this->user->shop_id;
                $token   = ktk_get_token();

                if ($id == 0) {

                    $nid = $this->user->_add($token, $shop_id, $arr);
                    if ($nid > 0) {
                        $act = '';
                    } else {
                        $err = 'shop-user.cannot-add-new-user';
                    }
                } else {
                    $this->user->_update($token, $shop_id, $id, $arr);
                    $act = '';
                    $id  = 0;
                }
            }
        }

        $data = array();
        $data['token']     = ktk_get_token();
        $data['title']     = 'shops.menu.configs.users';
        $data['user']      = $this->user;
        $data['action']    = $act;
        $data['menu_off']  = true;

        if ($id > 0) {
            $data['update_id'] = $id;
        }
        if ($err) {
            $data['error'] = 'system.messages.' . $err;
        }

        $this->load->view('headers/html_header', $data);
        $this->load->view('users_manager', $data);
        $this->load->view('headers/html_footer');
    }

    public function user_roles()
    {

        if ($this->check_user() === false) {
            $this->login();
            return;
        }

        $s1 = $this->uri->segment(2);
        $s2 = $this->uri->segment(3);
        $s3 = $this->uri->segment(4);
        $s4 = $this->uri->segment(5);

        /*
        echo '<br>1: ' . $s1;
        echo '<br>2: ' . tb_decode_value($s2);
        echo '<br>3: ' . tb_decode_value($s3);
        echo '<br>4: ' . tb_decode_value($s4);
        */

        $tk = $s2;
        if ($tk != NULL && ktk_check_token($tk) != 0) {
            $this->show_message('session.session-exprired');
            return;
        }

        tb_load_class('my_config');
        $con  = new my_config();

        $s4   = tb_decode_value($s4);
        $id   = tb_decode_value($s3);
        $cat  = new my_category();
        $cat->load_me($id, NULL);

        if (isset($_POST['btnSave'])) {
            $role_code = $cat->property('code');
            $arr = array();
            if (!empty($_POST['rights'])) {
                foreach ($_POST['rights'] as $item) {
                    //echo '<br>'.$item;
                    $i1 = strpos($item, '::');
                    if ($i1 !== false) {
                        $c1   = substr($item, 0, $i1);
                        $item = substr($item, $i1 + 2, strlen($item) - $i1 - 2);
                        if (!in_array($c1, $arr)) {
                            $arr[] = $c1;
                        }
                    }

                    $i1 = strpos($item, '___');
                    if ($i1 !== false) {
                        $c2   = substr($item, 0, $i1);
                        if (!in_array($c2, $arr)) {
                            $arr[] = $c2;
                        }
                    }
                    $arr[] = $item;
                }
            }
            $con->save_rights($this->user->shop_id, $role_code, $arr);
        } else if (isset($_POST['btnHome'])) {
            $this->index();
            return;
        }

        $maker = new html_maker('my-box');

        $data = array();
        $data['title']     = 'eshop';
        $data['maker']     = $maker;
        $data['user']      = $this->user;
        $data['menu_off']  = true;

        if ($s4 == 'role') {
            $data['role_id']    = $id;
            $data['role_title'] = $cat->property('title');
        } else {
            $data['role_id']    = 0;
            $data['role_title'] = $cat->property('title');
        }

        $this->load->view('headers/html_header', $data);
        $this->load->view('config_roles', $data);
        $this->load->view('headers/html_footer');
    }


    public function login()
    {
        //$this->output->enable_profiler(TRUE);
        if (isset($_POST['signup'])) {
            redirect('shop_create');
            return;
        }
        if (isset($_POST['forgot_password'])) {
            redirect('forgot_password');
            return;
        }
        $user = new shop_user();
        $user->clear_session();

        $this->load->helper('cookie');
        $token = get_cookie('token');
        if ($token == '') {
            $token = $this->input->get('token');
        }
        if ($token != '') {
            $this->load->model("shop_user_model");
            $login_user = $this->shop_user_model->login_by_token($token);
            if ($login_user) {
                $shop_id = $login_user['shop_id'];
                $id = $login_user['id'];
                $email = $login_user['email'];
                $token = ktk_get_token();
                $user->_get($token, $shop_id, $id, $email);
                // Get shop data
                $arr = array();
                $arr['id']    = $shop_id;
                $arr['code']  = 'shop-data';
                //$row = tb_call_api($arr, 'api/shop_info');
                $this->load->model('shop_model');
                $row = $this->shop_model->get_shop($shop_id);
                $this->load->model('shop_type_model');
                $shop_types = $this->shop_type_model->get_all_shop_types();
                $types = array();
                foreach ($shop_types as $type) {
                    $types[$type['id']] = $type['name'];
                }
                $row['type_name'] = $types[$row['type']];
                //var_dump($row);
                $user->shop = $row;

                $user->save_session();
                //$this->index();
                $this->check($shop_id);
                $p2 = array();
                $p2['shop_id'] = $shop_id;
                $this->load->model('stock_cron3_model');
                $this->stock_cron3_model->add_stock_cron3($p2);

                /*
                if ($login_user['cookie_code']!=''){
                    redirect('https://hokinhdoanh.online/login?token=' . $login_user['cookie_code']);
                    return;
                }
                */

                redirect("/home");
                return;
            }
        }

        if (!empty($_POST)) {
            $phone = std($this->input->post('phone'));
            $pass = std($this->input->post('user_pass'));
            $phone = trim($phone);
            $pass = trim($pass);

            $remember_login = intval($this->input->post('remember_login'));

            $phone = std($phone);
            $pass1 = dinhdq_encode($phone, $pass);

            $this->load->model('shop_user_model');
            $login_user = $this->shop_user_model->dinhdq_login($phone, $pass1);
            if (!$login_user) {
                //redirect('/login?message=Số+điện+thoại+hoặc+mật+khẩu+không+chính+xác+mời+thử+lại');    
                /*
                $row = $this->shop_user_model->get_user_by_phone($phone);
                if (!$row){
                    redirect('/login?message=Số+điện+thoại+hoặc+mật+khẩu+không+chính+xác+mời+thử+lại');    
                }
                $shop_id = $row['shop_id'];
                $email = $row['email'];
                $pass2 = dinhdq_encode($email, $pass);
                $login_user = $this->shop_user_model->dinhdq_login2($email, $pass2);
                if (!$login_user){
                    redirect('/login?message=Số+điện+thoại+hoặc+mật+khẩu+không+chính+xác+mời+thử+lại');
                }
                */
            }

            $shop_id = $login_user['shop_id'];
            $id = $login_user['id'];
            $email = $login_user['email'];
            $token = ktk_get_token();
            $user->_get($token, $shop_id, $id, $email);
            unset($user->user_pass);

            $this->load->model('shop_model');
            $row = $this->shop_model->get_shop($shop_id);

            $user->shop = $row;
            //echo(json_encode($user));
            //echo('<br><br><br><br>');
            $user->save_session();

            /*
            echo(json_encode($_SESSION));
            echo('<br><br><br><br>');
            $user->get_session();

            echo(json_encode($user));
            return;
            */

            $cookie = array(
                'name'   => 'phone',
                'value'  => $phone,
                'expire' => time() + 6 * 30 * 24 * 3600,
                'domain' => $_SERVER['HTTP_HOST'],
                'path'   => '/',
                'prefix' => '',
            );
            set_cookie($cookie);

            //xxx-
            $shop_name = $row['name'];
            $shops = get_cookie('shops');
            if ($shops == null) {
                $shops = array();
            } else {
                $shops = json_decode($shops, true);
            }
            $shops[$phone] = $phone;

            $shops = json_encode($shops);
            $cookie = array(
                'name'   => 'shops',
                'value'  => $shops,
                'expire' => time() + 6 * 30 * 24 * 3600,
                'domain' => $_SERVER['HTTP_HOST'],
                'path'   => '/',
                'prefix' => '',
            );
            set_cookie($cookie);

            if ($remember_login == 1) {
                $row = $this->shop_user_model->generate_token_by_login($phone, dinhdq_encode($phone, $pass));
                if ($row) {
                    $token = $row['token'];
                    $cookie = array(
                        'name'   => 'token',
                        'value'  => $token,
                        'expire' => time() + 6 * 30 * 24 * 3600,
                        'domain' => $_SERVER['HTTP_HOST'],
                        'path'   => '/',
                        'prefix' => '',
                    );
                    set_cookie($cookie);
                }
            }
            //Check
            $this->check($shop_id);

            $p2 = array();
            $p2['shop_id'] = $shop_id;
            $this->load->model('stock_cron3_model');
            $this->stock_cron3_model->add_stock_cron3($p2);

            /*
            if ($login_user['cookie_code']!=''){
                redirect('https://hokinhdoanh.online/login?token=' . $login_user['cookie_code']);
                return;
            }
            */



            //redirect
            if (($user->row['user_role'] == 'shops.lists.user-roles.order' || $user->row['user_role'] == 'shops.lists.user-roles.sales') && $user->shop['type'] == 0) {
                redirect("/cafe_order");
            }
            if (($user->row['user_role'] == 'shops.lists.user-roles.kitchen') && $user->shop['type'] == 0) {
                redirect("/kitchen");
            }

            if (($user->row['user_role'] == 'shops.lists.user-roles.receptionist') && ($user->shop['type'] == 31 || $user->shop['type'] == 23 || $user->shop['type'] == 0)) {
                $start_date = date('Y-m-1');
                $end_date = date('Y-m-d', strtotime("1 month", strtotime($start_date)));
                //$start_time = strtotime("1 month", $start_time);
                redirect("/home/report?type=17&start_date=$start_date&end_date=$end_date");
            }
            if (($user->row['user_role'] == 'shops.lists.user-roles.sales' || $user->row['user_role'] == 'shops.lists.user-roles.manager') && ($user->shop['type'] == 31 || $user->shop['type'] == 23)) {
                $start_date = date('Y-m-1');
                $end_date = date('Y-m-d', strtotime("1 month", strtotime($start_date)));
                //$start_time = strtotime("1 month", $start_time);
                redirect("/home/report?type=17&start_date=$start_date&end_date=$end_date");
            }

            if ($user->row['user_role'] == 'shops.lists.user-roles.sales') {
                redirect('/sales_order');
            }
            if ($user->row['user_role'] == 'shops.lists.user-roles.purchase') {
                redirect('/purchase_order');
            }


            redirect("/home");
            return;
        }

        $message = $this->input->get('message');
        $data['title'] = 'login';
        $data['message'] = $message;

        if ($this->input->get('phone') !== null) {
            $data['phone'] = '' . $this->input->get('phone');
        } else {
            //$this->load->helper('cookie');
            $cookie = get_cookie('phone');
            if ($cookie !== null) {
                $data['phone'] = $cookie;
            } else {
                $data['phone'] = "";
            }
        }
        $cookie = get_cookie('shops');
        if ($cookie !== null) {
            $data['shops'] = json_decode($cookie, true);
        } else {
            $data['shops'] = array();
        }
        $this->load->library('user_agent');
        $is_mobile = $this->agent->is_mobile();
        $data['is_mobile'] = $is_mobile;
        //toandk2 sửa
        if (check_user_agent('mobile')) {
            $this->load->view('mobile_views/login0', $data);
        } else {
            $this->load->view('login0', $data);
        }

        // $this->load->view('login0', $data);
    }

    function check($shop_id)
    {
        return;
        if (!isset($_SESSION)) {
            session_start();
        }
        $check_all = 0;
        $this->load->model('shop_model');
        $row = $this->shop_model->get_shop($shop_id);

        if (intval($row['product_group']) == 1) {
            $_SESSION['product_group'] = 1;
            $check_all++;
        } else {
            unset($_SESSION['product_group']);
        }

        if (intval($row['product']) == 1) {
            $_SESSION['product'] = 1;
            $check_all++;
        } else {
            unset($_SESSION['product']);
        }

        if (intval($row['customer']) == 1) {
            $_SESSION['customer'] = 1;
            $_SESSION['supplier'] = 1;
            $check_all++;
        } else {
            unset($_SESSION['customer']);
            unset($_SESSION['supplier']);
        }


        if (intval($row['position']) == 1) {
            $_SESSION['table_position'] = 1;
            $check_all++;
        } else {
            unset($_SESSION['table_position']);
        }


        if (intval($row['table']) == 1) {
            $_SESSION['table'] = 1;
            $check_all++;
        } else {
            unset($_SESSION['table']);
        }

        if ($check_all > 3) {
            $_SESSION['shop_user'] = 1;
        }

        if ($check_all >= 4) {
            $_SESSION['check_all'] = 1;
            return;
        }
        /*
        $shop_type = $this->user->shop['type'];
        if (($shop_type == 0 || $shop_type == 31) && $check_all==6){
            $_SESSION['check_all'] = 1;
            return;
        }
        if ($shop_type != 0 && $check_all==4){
            $_SESSION['check_all'] = 1;
            return;
        }
        */
        $_SESSION['check_all'] = 0;
    }



    public function logout()
    {
        $user = new shop_user();
        $user->clear_session();
        $this->load->helper('cookie');
        delete_cookie('token', $_SERVER['HTTP_HOST'], "/");

        //toandk2 sửa
        if (check_user_agent('mobile')) {
            $this->load->view('mobile_views/logout');
        } else {
            $this->load->view('logout');
        }
        // $this->load->view('logout');
        unset($_SESSION['show']);
        //$this->show_message('login.logout');
    }

    private function show_message($code)
    {

        $data['title'] = 'system.message';
        $data['code']  = $code;

        $this->load->view('headers/simple_header', $data);
        $this->load->view('system_message', $data);
        $this->load->view('headers/simple_footer');
    }

    private function check_user()
    {
        $user  = new shop_user();
        $check = $user->get_session();

        if ($check == true) {
            $this->user = $user;

            $this->load->model("log_model");
            $params = array();
            $params['username'] = $user->user_id;
            $params['shop_id'] = $user->shop_id;
            $params['url'] = current_url();
            $params['ip'] = $this->input->ip_address();
            $params['session'] = session_id();
            $params['data'] = json_encode($_POST) . '|' . json_encode($_GET);
            $params['agent'] = $_SERVER['HTTP_USER_AGENT'];
            $this->log_model->add_log($params);
            return true;
        }
        return false;
    }


    private function set_session()
    {
        if (is_null($this->user)) {
            $this->login();
        }
    }

    private function clean($data, $fields)
    {
        $arr = array();
        foreach ($data as $key => $value) {
            $arr[$key] = $value;
            if (in_array($key, $fields)) {
                $value = std($value);
                $value = tb_remove_script($value);
                $arr[$key] = $value;
            }
        }
        return $arr;
    }

    public function cafe_checkout()
    {
        $url = $this->config->base_url();
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        //echo(json_encode($this->user));
        $shop_id = $this->user->shop_id;
        $email = $this->user->email;
        //$bill = $this->input->post("data");
        $table_id = $this->input->post("table_id");
        $this->load->model('order_model');
        $order_id = $this->order_model->get_last_open_order_by_table($shop_id, $table_id);
        $this->load->library('bill_item_lib');
        $products = $this->bill_item_lib->get_cafe_bill_item_products($shop_id, $order_id);
        $data['products'] = $products;
        $this->load->model('user_model');
        $users = $this->user_model->get_all_users($shop_id);

        $data['url'] = $url;
        $data['products'] = $products;
        $data['users'] = $users;
        $data['i'] = count($products);
        $data['email'] = $email;
        $data['i'] = count($products);
        $this->load->view('cafe_checkout', $data);
    }

    public function checkout()
    {
        $url = $this->config->base_url();
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        //echo(json_encode($this->user));
        $shop_id = $this->user->shop_id;
        $email = $this->user->email;
        $bill = $this->input->post("data");
        //var_dump($bill);
        //return;
        $this->load->model('user_model');

        $users = $this->user_model->get_all_users($shop_id);

        $this->load->model('product_model');

        $data = array();
        $products = array();
        $i = 0;
        foreach ($bill as $key => $value) {
            $product_id = str_replace('d', '', $key);
            $pr = $this->product_model->get_product($product_id, $shop_id);
            if ($pr) {
                $product = (object)[];
                $product->name = $pr['product_name'];
                $product->price = $pr['list_price'];
                $product->quantity = $value;

                $products[] = $product;
                $i++;
            }
        }

        $data['url'] = $url;
        $data['products'] = $products;
        $data['users'] = $users;
        $data['i'] = $i;
        $data['email'] = $email;
        $this->load->view('cafe_checkout', $data);
        //var_dump($data);
    }

    public function normal_checkout()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        //$this->output->enable_profiler(TRUE);
        $url = $this->config->base_url();
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $this->load->model('user_model');
        $shop_id = $this->user->shop_id;
        $email = $this->user->email;
        $bill = $this->input->post("data");
        //echo(json_encode($bill));
        $type = intval($this->input->post("type"));
        $users = $this->user_model->get_all_users($shop_id);

        //$this->load->model('product_model');

        $data = array();
        $products = array();
        $i = 0;
        $data['url'] = $url;
        $data['products'] = $bill;
        $data['users'] = $users;
        $data['type'] = $type;
        $data['i'] = $i;
        $data['email'] = $email;
        $this->load->view('checkout', $data);
    }

    public function edit_order_checkout()
    {
        $url = $this->config->base_url();
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $this->load->model('user_model');
        $shop_id = $this->user->shop_id;
        $email = $this->user->email;
        $bill = $this->input->post("data");
        $order_id = $this->input->post("order_id");
        $type = intval($this->input->post("type"));
        $users = $this->user_model->get_all_users($shop_id);
        //$this->load->model('order_model');
        //$this->load->model('bill_item_model');
        //$order = $this->order_model->get_order($order_id, $shop_id);
        //$items = $this->bill_item_model->get_order_bill_items($shop_id, $order_id);
        //echo(json_encode($items));
        $data = array();
        $products = array();
        $i = 0;

        //$data['order'] = $order;
        //$data['items'] = $items;
        $data['url'] = $url;
        $data['products'] = $bill;
        $data['users'] = $users;
        $data['type'] = $type;
        $data['i'] = $i;
        $data['email'] = $email;
        $this->load->view('edit_order_checkout', $data);
    }


    public function preview_invoice()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        //$date = std($this->input->get("date"));
        $date = date('Y-m-d');

        $name = date('Y-m-d H:i');
        $date = date('Y-m-d');


        $name = date('Y-m-d H:i');
        $diners = intval($this->input->get("diners"));
        $note = std($this->input->get("note"));
        $cashier = $this->user->email;
        $discount = floatval($this->input->get("discount"));
        $payment_type = intval($this->input->get("payment_type"));
        $vat = intval($this->input->get("vat"));

        $customer_id = intval($this->input->get("customer_id"));
        $customer_name = std($this->input->get("customer_name"));
        //$order_id = intval($this->input->get("order_id"));
        $table_id = intval($this->input->get("table_id"));
        $this->load->model('order_model');
        $this->load->model('bill_model');
        $bill_id = intval($this->input->get("bill_id"));
        if ($bill_id == 0) {
            $order_id = $this->order_model->get_last_open_order_by_table($shop_id, $table_id);
        } else {
            $order_id = $this->order_model->get_order_by_bill($shop_id, $bill_id);
        }

        $data['cashier'] = $cashier;
        $data['vat'] = $vat;
        $data['discount'] = $discount;
        $data['date'] = $date;
        $this->load->library('bill_item_lib');
        $products = $this->bill_item_lib->get_cafe_bill_item_products($shop_id, $order_id);
        $data['products'] = $products;
        $data['i'] = count($products);
        $this->load->view('preview', $data);
    }
    public function print_invoice_before_checkout()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        //$date = std($this->input->get("date"));
        $date = date('Y-m-d');

        $name = date('Y-m-d H:i');
        $date = date('Y-m-d');

        $name = date('Y-m-d H:i');
        $diners = intval($this->input->get("diners"));
        $note = std($this->input->get("note"));

        $invoice_number = std($this->input->get("invoice_number"));
        $cashier = $this->user->email;
        $discount = floatval($this->input->get("discount"));
        $payment_type = intval($this->input->get("payment_type"));
        $vat = intval($this->input->get("vat"));

        $customer_id = intval($this->input->get("customer_id"));
        $customer_name = std($this->input->get("customer_name"));
        //$order_id = intval($this->input->get("order_id"));
        $table_id = intval($this->input->get("table_id"));
        $this->load->model('order_model');
        $this->load->model('bill_model');
        $bill_id = intval($this->input->get("bill_id"));
        if ($bill_id == 0) {
            $order_id = $this->order_model->get_last_open_order_by_table($shop_id, $table_id);
        } else {
            $order_id = $this->order_model->get_order_by_bill($shop_id, $bill_id);
        }

        $data['cashier'] = $cashier;
        $data['discount'] = $discount;
        $data['vat'] = $vat;
        $data['invoice_number'] = $invoice_number;
        $data['date'] = $date;
        $this->load->library('bill_item_lib');
        $products = $this->bill_item_lib->get_cafe_bill_item_products($shop_id, $order_id);
        $data['products'] = $products;
        $data['i'] = count($products);
        $message = $this->load->view('invoice', $data, true);
        $this->load->view('invoice', $data);
    }

    public function cafe_invoice()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        //$date = std($this->input->get("date"));
        $date = date('Y-m-d');

        //$name = date('Y-m-d H:i');
        $diners = intval($this->input->get("diners"));
        $invoice_number = std($this->input->get("invoice_number"));
        $note = std($this->input->get("note"));
        $cashier = $this->user->email;
        $discount = floatval($this->input->get("discount"));
        $payment_type = intval($this->input->get("payment_type"));
        $vat = intval($this->input->get("vat"));

        $customer_id = intval($this->input->get("customer_id"));
        $customer_name = std($this->input->get("customer_name"));
        //$order_id = intval($this->input->get("order_id"));
        $table_id = intval($this->input->get("table_id"));
        $this->load->model('order_model');
        $this->load->model('bill_model');
        $bill_id = intval($this->input->get("bill_id"));

        $paid = intval($this->input->get("paid"));

        $params = array();

        $params['shop_id'] = $shop_id;
        //$params['order_name'] = $name;

        $params['order_date'] = $date;
        $params['customer_id'] = $customer_id;
        $params['customer_name'] = $customer_name;
        $params['payment_type'] = $payment_type;

        $params['create_user'] = $this->user->user_id;
        $params['last_user'] = $this->user->user_id;
        $params['last_update'] = date('Y-m-d H:i:s', strtotime('now'));
        $params['status1'] = 4;
        if ($bill_id == 0) {
            $order_id = $this->order_model->get_last_open_order_by_table($shop_id, $table_id);
            $bill_id = $this->bill_model->get_bill_by_order($shop_id, $order_id);
            $params['bill_date'] = date('Y-m-d H:i:s', strtotime('now'));
            $params['cashier'] = $this->user->email;

            $params['bill_time'] = date('H:i:s', strtotime('now'));
            //$this->order_model->update_order($order_id, $shop_id, $params);
            unset($params['order_name']);
            unset($params['order_date']);
            unset($params['order_time']);
            unset($params['payment_type']);
            unset($params['content']);
            unset($params['diners']);

            unset($params['invoice_number']);
            $this->bill_model->update_bill($bill_id, $shop_id, $params);

            unset($params['bill_date']);
            unset($params['bill_time']);
            unset($params['cashier']);
            //$params['order_name'] = $name;
            $params['order_date'] = $date;
            $params['order_time'] = date('Y-m-d H:i:s', strtotime('now'));
            $params['paid'] = $paid;
            if ($paid == 1) {
                $params['paid_date'] = $date;
            }
            $params['vat'] = $vat;
            $params['content'] = $note;
            $params['diners'] = $diners;
            $params['invoice_number'] = $invoice_number;
            $params['deposit_amount'] = $discount;
            $this->order_model->update_order($order_id, $shop_id, $params);
            $this->order_model->update_order_memo($shop_id, $order_id);
        } else {
            $order_id = $this->order_model->get_order_by_bill($shop_id, $bill_id);

            unset($params['order_name']);
            unset($params['order_date']);
            unset($params['order_time']);

            unset($params['content']);
            unset($params['diners']);
            unset($params['invoice_number']);

            $params['bill_date'] = date('Y-m-d H:i:s', strtotime('now'));
            $params['cashier'] = $this->user->email;
            $params['vat'] = $vat;

            $params['bill_time'] = date('H:i:s', strtotime('now'));

            $this->bill_model->update_bill($bill_id, $shop_id, $params);
            //$this->output->enable_profiler(TRUE);
            if ($this->order_model->check_order_finished($shop_id, $order_id)) {
                $params['paid'] = $paid;
                if ($paid == 1) {
                    $params['paid_date'] = $date;
                }

                $params['vat'] = $vat;
                $params['content'] = $note;
                $params['diners'] = $diners;
                $params['invoice_number'] = $invoice_number;
                $params['deposit_amount'] = $discount;
                $this->order_model->update_order($order_id, $shop_id, $params);
                $this->order_model->update_order_memo($shop_id, $order_id);
            }
            //$this->output->enable_profiler(FALSE);
        }


        $this->load->model('bill_item_model');
        $this->load->model('product_model');

        $this->bill_item_model->finish_items($shop_id, $bill_id, 0);

        $items = $this->bill_item_model->get_order_items2($shop_id, $order_id);

        foreach ($items as $item) {
            $bill_item_id = $item['id'];
            $product_id = $item['product_id'];
            $rows = $this->product_model->get_product_materials($shop_id, $product_id);
            foreach ($rows as $row) {
                $material_id = $row['id'];
                $material_name = $row['product_name'];
                $quantity = $row['quantity'];
                $params1 = array();
                $params1['shop_id'] = $shop_id;
                $params1['order_id'] = 0;
                $params1['parent'] = $bill_item_id;

                $params1['product_id'] = $material_id;
                $params1['product_name'] = $material_name;
                $params1['quantity'] = $quantity * $item['quantity'];

                $params1['create_user'] = $this->user->user_id;
                $params1['last_user'] = $this->user->user_id;
                $params1['last_update'] = date('Y-m-d H:i:s', strtotime('now'));
                $average_price = $row['average_price'];
                $params1['price'] = $average_price;
                $params1['amount'] = $average_price * $params1['quantity'];

                $this->bill_item_model->add_bill_item($params1);

                $params1 = array();
                $params1['stock'] = $row['stock'] - $quantity * $item['quantity'];
                $this->product_model->update_product($material_id, $shop_id, $params1);
            }
        }

        $data['cashier'] = $cashier;
        $data['vat'] = $vat;
        //$data['discount'] = $discount;
        $data['date'] = $date;
        $this->load->library('bill_item_lib');
        $products = $this->bill_item_lib->get_cafe_bill_item_products($shop_id, $order_id);
        $data['products'] = $products;
        $data['invoice_number'] = $invoice_number;
        $data['i'] = count($products);

        //var_dump($data);
        $sendmail = intval($this->input->get("sendmail"));
        /*
        if ($sendmail == 1){
            $message = $this->load->view('invoice', $data, true);
        }
        else{
            $this->load->view('invoice', $data);
        }
        */
        $message = $this->load->view('invoice', $data, true);
        $this->load->view('invoice', $data);

        if ($sendmail == 1) {
            $email = std($this->input->get("email"));
            $this->mail($email, 'Hóa đơn', $message);
        }
    }

    public function send_invoice()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get("order_id"));
        $this->load->model('order_model');
        $this->load->model('bill_model');
        $this->load->model('bill_item_model');

        $order = $this->order_model->get_order($order_id, $shop_id);
        $bill = $this->bill_model->get_order_bill($order_id, $shop_id);

        $bill_items = $this->bill_item_model->get_bill_items($shop_id, $bill['id']);

        $this->load->model('shipping_model');
        $shipping = $this->shipping_model->get_order_shipping($shop_id, $order_id);

        $data = array();
        $data['order'] = $order;
        $data['shipping'] = $shipping;
        $data['products'] = $bill_items;
        $data['i'] = count($bill);

        $data['cashier'] = $order['create_user'];
        $data['date'] = $order['order_date'];
        $data['invoice_number'] = $order['invoice_number'];
        $message = $this->load->view('normal_invoice', $data, true);
        $email = std($this->input->get("email"));
        $this->mail($email, 'Hóa đơn', $message);
    }

    public function send_merged_invoice()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get("order_id"));
        $this->load->model('order_model');
        $this->load->model('bill_item_model');

        $order = $this->order_model->get_order($order_id, $shop_id);
        $ids = $order['order_items'];
        $ids = json_decode($ids);

        $bill_items = $this->bill_item_model->get_items($shop_id, $ids);
        //var_dump(bill_items);

        $this->load->model('shipping_model');
        $shipping = $this->shipping_model->get_order_shipping($shop_id, $order_id);

        $data = array();
        $data['shipping'] = $shipping;
        $data['products'] = $bill_items;
        //$data['i'] = count($bill);

        $data['cashier'] = $order['create_user'];
        $data['date'] = $order['order_date'];
        $data['invoice_number'] = $order['invoice_number'];
        $message = $this->load->view('normal_invoice', $data, true);
        $email = std($this->input->get("email"));
        $this->mail($email, 'Hóa đơn', $message);
    }


    public function send_merged_invoice1()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get("order_id"));
        $this->load->model('order_model');
        $this->load->model('bill_item_model');

        $order = $this->order_model->get_order($order_id, $shop_id);
        $ids = $order['order_items'];
        $ids = json_decode($ids);

        $bill_items = $this->bill_item_model->get_items($shop_id, $ids);
        //var_dump(bill_items);

        $this->load->model('shipping_model');
        $shipping = $this->shipping_model->get_order_shipping($shop_id, $order_id);

        $data = array();
        $data['shipping'] = $shipping;
        $data['products'] = $bill_items;
        $data['order'] = $order;
        //$data['i'] = count($bill);

        $data['cashier'] = $order['create_user'];
        $data['date'] = $order['order_date'];
        $data['invoice_number'] = $order['invoice_number'];
        $message = $this->load->view('normal_invoice', $data, true);
        $email = std($this->input->get("email"));
        $this->mail($email, 'Hóa đơn', $message);
    }



    public function print_invoice()
    {
        //$this->output->enable_profiler(TRUE);
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get("order_id"));
        $this->load->model('order_model');
        //$this->load->model('bill_model');
        $this->load->model('bill_item_model');

        $order = $this->order_model->get_order($order_id, $shop_id);
        //$bill = $this->bill_model->get_order_bill($order_id, $shop_id);

        $bill_items = $this->bill_item_model->get_bill_items0($shop_id, $order_id);
        //echo(json_encode($bill_items));

        $this->load->model('shipping_model');
        $shipping = $this->shipping_model->get_order_shipping($shop_id, $order_id);

        $data = array();
        $data['shop_id'] = $shop_id;
        $data['order'] = $order;
        $data['shipping'] = $shipping;
        $data['products'] = $bill_items;

        $data['cashier'] = $order['create_user'];
        $data['date'] = $order['order_date'];
        $data['invoice_number'] = $order['invoice_number'];

        $data['phone'] = $this->user->shop['phone_in_recept'];
        if ($data['phone'] == '') {
            $data['phone'] = $this->user->shop['phone'];
        }

        $this->load->model('shop_detail_model');
        $shop_detail = $this->shop_detail_model->get_shop_detail($shop_id);
        $data['shop_detail'] = $shop_detail;

        $this->load->model('user_model');
        $rows = $this->user_model->get_all_users($shop_id);
        $users = array();
        foreach ($rows as $row) {
            if ($row['full_name'] != '') {
                $users[$row['id']] = $row['full_name'];
            } else {
                if ($row['phone'] != '') {
                    $users[$row['id']] = $row['phone'];
                }
            }
        }
        $data["users"] = $users;


        $this->load->view('normal_invoice', $data);
    }

    public function print_invoices()
    {
        //$this->output->enable_profiler(TRUE);
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $orders = $this->input->get("orders");
        $this->load->model('order_model');
        $this->load->model('bill_item_model');
        $datas = array();
        $temp = array();
        foreach ($orders as $order_id) {
            $order_id = intval($order_id);
            $order = $this->order_model->get_order($order_id, $shop_id);

            $bill_items = $this->bill_item_model->get_bill_items0($shop_id, $order_id);
            //echo(json_encode($bill_items));

            $data = array();
            $data['shop_id'] = $shop_id;
            $data['order'] = $order;
            $data['products'] = $bill_items;

            $data['cashier'] = $order['create_user'];
            $data['date'] = $order['order_date'];
            $data['invoice_number'] = $order['invoice_number'];

            $temp[] = $data;
        }
        $datas['datas'] = $temp;
        $datas['shop_id'] = $shop_id;
        $datas['phone'] = $this->user->shop['phone_in_recept'];
        if ($datas['phone'] == '') {
            $datas['phone'] = $this->user->shop['phone'];
        }

        $this->load->model('shop_detail_model');
        $shop_detail = $this->shop_detail_model->get_shop_detail($shop_id);
        $datas['shop_detail'] = $shop_detail;

        $this->load->model('user_model');
        $rows = $this->user_model->get_all_users($shop_id);
        $users = array();
        foreach ($rows as $row) {
            if ($row['full_name'] != '') {
                $users[$row['id']] = $row['full_name'];
            } else {
                if ($row['phone'] != '') {
                    $users[$row['id']] = $row['phone'];
                }
            }
        }
        $datas["users"] = $users;

        $this->load->view('normal_invoices', $datas);
    }

    public function print_invoice1()
    {
        /*
		error_reporting(-1);
		ini_set('display_errors', 1);
        */
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get("order_id"));
        $this->load->model('order_model');
        $this->load->model('bill_model');
        $this->load->model('bill_item_model');
        $data = array();
        $order = $this->order_model->get_order2($order_id, $shop_id);
        $data['mccqthue'] = '';
        $ebill = json_decode($order['bill_book'], true);
        if (array_key_exists('minvoice', $ebill)) {
            $ebill = $ebill['minvoice'];
            if (array_key_exists('mccqthue', $ebill)) {
                $mccqthue = $ebill['mccqthue'];
                $data['mccqthue'] = $mccqthue;
            }
        }

        $bill_items = $this->bill_item_model->get_bill_items2($shop_id, $order_id);
        //echo(json_encode($bill_items));

        $this->load->model('shipping_model');
        $shipping = $this->shipping_model->get_order_shipping($shop_id, $order_id);


        $data['shop_id'] = $shop_id;
        $data['order'] = $order;
        $data['shipping'] = $shipping;
        $data['products'] = $bill_items;
        //$data['i'] = count($bill);

        $data['cashier'] = $order['create_user'];
        $data['date'] = $order['order_date'];
        $data['d'] = date('d', strtotime($order['order_date']));
        $data['m'] = date('m', strtotime($order['order_date']));
        $data['y'] = date('Y', strtotime($order['order_date']));


        $data['invoice_number'] = $order['invoice_number'];

        $this->load->model('shop_detail_model');
        $shop_detail = $this->shop_detail_model->get_shop_detail($shop_id);
        $data['shop_detail'] = $shop_detail;

        $this->load->model('shop_model');
        $shop = $this->shop_model->get_shop($shop_id);
        $data['shop'] = $shop;


        $this->load->model('user_model');
        $rows = $this->user_model->get_all_users($shop_id);
        $users = array();
        foreach ($rows as $row) {
            if ($row['full_name'] != '') {
                $users[$row['id']] = $row['full_name'];
            } else {
                if ($row['phone'] != '') {
                    $users[$row['id']] = $row['phone'];
                }
            }
        }
        $data["users"] = $users;
        $data['phone'] = $this->user->shop['phone_in_recept'];
        if ($data['phone'] == '') {
            $data['phone'] = $this->user->shop['phone'];
        }


        $this->load->view('sale_invoice1', $data);
    }

    public function print_main_order_invoice()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get("order_id"));

        $this->load->model('order_model');
        $this->load->model('bill_item_model');

        $main_order = $this->order_model->get_order($order_id, $shop_id);


        $orders = $main_order['order_items'];
        $order_ids = json_decode($orders);

        //echo(json_encode($bill_items));

        $data = array();
        if ($main_order['order_type'] == 'B') {
            $data['title'] = tb_word('shops.sale.order');
        } else {
            $data['title'] = tb_word('shops.purchase.order');
        }

        $this->load->model('shipping_model');

        $orders = array();
        foreach ($order_ids as $id) {
            $order = $this->order_model->get_order($id, $shop_id);
            $items = $this->order_model->get_order_bill_items($shop_id, $id);
            $order['items'] = $items;
            $shipping = $this->shipping_model->get_order_shipping($shop_id, $order_id);
            $order['shipping'] = $shipping;
            $orders[] = $order;
        }


        $items = $this->order_model->get_order_bill_items($shop_id, $main_order['id']);
        $main_order['items'] = $items;
        $shipping = $this->shipping_model->get_order_shipping($shop_id, $main_order['id']);
        $main_order['shipping'] = $shipping;

        $data["order"] = $main_order;
        $data["orders"] = $orders;

        $this->load->view('merged_invoice', $data);
    }


    public function print_merged_invoice()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get("order_id"));

        $this->load->model('order_model');
        $this->load->model('bill_item_model');

        $order = $this->order_model->get_order($order_id, $shop_id);


        $orders = $order['order_items'];
        $order_ids = json_decode($orders);

        //echo(json_encode($bill_items));

        $data = array();
        if ($order['order_type'] == 'B') {
            $data['title'] = tb_word('shops.sale.order');
        } else {
            $data['title'] = tb_word('shops.purchase.order');
        }

        $this->load->model('shipping_model');

        $orders = array();
        foreach ($order_ids as $id) {
            $order = $this->order_model->get_order($id, $shop_id);
            $items = $this->order_model->get_order_bill_items($shop_id, $id);
            $order['items'] = $items;
            $shipping = $this->shipping_model->get_order_shipping($shop_id, $order_id);
            $order['shipping'] = $shipping;
            $orders[] = $order;
        }

        $data["orders"] = $orders;
        $this->load->view('merged_invoice', $data);
    }

    public function print_merged_invoice1()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get("order_id"));

        $this->load->model('order_model');
        $this->load->model('bill_item_model');

        $order = $this->order_model->get_order($order_id, $shop_id);

        $items = $order['order_items'];
        $item_ids = json_decode($items);

        //echo(json_encode($bill_items));

        $data = array();
        $data['title'] = tb_word('shops.sale.order');

        $orders = array();
        $items = array();
        foreach ($item_ids as $id) {
            $item = $this->bill_item_model->get_bill_item($id, $shop_id);
            $items[] = $item;
        }
        $order['items'] = $items;
        $order['shipping'] = null;
        $orders[] = $order;

        $data["orders"] = $orders;
        $this->load->view('merged_invoice1', $data);
    }

    public function edit_order_invoice()
    {
        //2023-04-16
        //error_reporting(-1);
        //ini_set('display_errors', 1);
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $bill = std($this->input->post("data"));
        if ($bill == NULL) {
            return;
        }

        $this->load->model('order_model');
        $this->load->model('bill_item_model');

        $order_id = intval($this->input->post('order_id'));

        $order = $this->order_model->get_order($order_id, $shop_id);
        if (!$order) {
            return;
        }
        $amount = $order['amount'];

        foreach ($bill as $item) {
            $product_id = $item['product_id'];
            $product_name = $item['product_name'];
            $params['shop_id'] = $shop_id;
            $params['order_id'] = $order_id;
            $params['product_id'] = $product_id;
            $params['product_code'] = '';
            $params['product_name'] = $product_name;

            $nog = 0;
            if (array_key_exists('nog', $item)) {
                $nog = intval($item['nog']);
            }
            $params['price'] = $item['price'];;
            $params['quantity'] = $item['quantity'];
            if (array_key_exists('start_date', $item)) {
                $params['start_date'] = $item['start_date'];
                $params['end_date'] = $item['end_date'];
                $this->occupy_model->update_product_occupy($shop_id, $product_id, $item['start_date'], $item['end_date'], '1');
            } else {
                unset($params['start_date']);
                unset($params['end_date']);
            }
            $params['nog'] = $nog;
            $params['status1'] = 4;
            $params['amount'] = $params['price'] * $item['quantity'];
            $amount += $params['amount'];
            $params['create_user'] = $this->user->user_id;

            $this->bill_item_model->add_bill_item($params);
        }

        $params = array('id' => $order_id, 'amount' => $amount);
        $discount = $order['deposit_amount'];
        $discount2 = floatval($this->input->post("discount2"));
        $discount2 = $discount2 + $discount;
        $params['deposit_amount'] = $discount2;

        $this->order_model->update_order($order_id, $shop_id, $params);

        $data['products'] = $new_bill;
        $data['i'] = count($bill);
        $data['invoice_number'] = $invoice_number;
        $data['cashier'] = $cashier;
        $data['date'] = $date;
        $data['order'] = $order;

        $message = $this->load->view('normal_invoice', $data, true);
        if (trim($email) != '') {
            $this->mail($email, 'Hóa đơn', $message);
            return;
        }

        if ($type1 != 0) {
            $result = array();
            $result['order_id'] = $old_order_id;
            echo (json_encode($result));
            return;
        }
        $this->load->model('user_model');
        $rows = $this->user_model->get_all_users($shop_id);
        $users = array();
        foreach ($rows as $row) {
            if ($row['full_name'] != '') {
                $users[$row['id']] = $row['full_name'];
            } else {
                if ($row['phone'] != '') {
                    $users[$row['id']] = $row['phone'];
                }
            }
        }
        $data["users"] = $users;


        $this->load->view('normal_invoice', $data);
    }



    public function invoice()
    {
        /*
		error_reporting(-1);
		ini_set('display_errors', 1);        
        $this->output->enable_profiler(TRUE);
        */
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $bill = $this->input->post("data");
        $i = 0;
        foreach ($bill as $item) {

            $i++;
        }
        if ($bill == NULL) {
            return;
        }

        $date = std($this->input->post("date"));

        $date = str_replace('/', '-', $date);
        $date = date('Y-m-d', strtotime($date));

        $name = std($this->input->post("name"));
        $tentative = std($this->input->post("tentative"));

        $content = std($this->input->post("content"));
        $cashier = std($this->input->post("cashier"));
        $type = intval($this->input->post("type"));
        $customer_id = intval($this->input->post("customer_id"));
        $customer_name = std($this->input->post("customer_name"));
        $invoice_number = std($this->input->post("invoice_number"));
        $this->load->model('customer_model');
        $this->load->model('map_model');
        if ($customer_id == 0 && $customer_name != '') {
            $id = $this->customer_model->check_existence1($shop_id, 0, $customer_name);
            if ($id == 0) {
                $params = array();
                $params['name'] = $customer_name;
                $params['type'] = $type;
                $params['shop_id'] = $shop_id;

                $customer_id = $this->customer_model->add_customer($shop_id, $params);
            } else {
                $customer_id = $id;
            }
        }

        $shipper = std($this->input->post("shipper"));
        $address = std($this->input->post("address"));
        $cost = intval($this->input->post("cost"));
        $discount2 = floatval($this->input->post("discount2"));
        $payment_type = intval($this->input->post("payment_type"));
        $vat = intval($this->input->post("vat"));
        $vat_rate = intval($this->input->post("vat_rate"));

        $vat_type = intval($this->input->post("vat_type"));
        $currency = std($this->input->post("currency"));
        $type1 = intval($this->input->post("type1"));

        $email = std($this->input->post("email"));

        $tax = intval($this->input->post("tax"));
        $diners = intval($this->input->post("diners"));

        $unit = std($this->input->post("unit"));
        $c_address = std($this->input->post("c_address"));
        //$bill_book = std($this->input->post("bill_book"));
        $director = std($this->input->post("director"));
        $chief_accountant = std($this->input->post("chief_accountant"));
        $c_cashier = std($this->input->post("c_cashier"));
        $storekeeper = std($this->input->post("storekeeper"));

        $params = array();

        $params['shop_id'] = $shop_id;
        $params['order_name'] = $name;
        if ($type == 0) {
            $params['order_type'] = 'B';
        } else {
            $params['order_type'] = 'M';
        }


        $params['order_date'] = $date;
        $params['customer_id'] = $customer_id;
        $params['customer_name'] = $customer_name;
        $params['order_time'] = date('Y-m-d H:i:s', strtotime('now'));
        $params['create_user'] = $this->user->user_id;
        $params['last_user'] = $this->user->user_id;
        $params['last_update'] = date('Y-m-d H:i:s', strtotime('now'));
        $params['payment_type'] = $payment_type;
        $params['currency'] = $currency;
        $params['deposit_amount'] = $discount2;
        $params['vat'] = $vat;
        $params['vat_rate'] = $vat_rate;
        $params['vat_type'] = $vat_type;
        $params['invoice_number'] = $invoice_number;
        $params['content'] = $content;
        $params['user_id'] = $this->user->user_id;

        $params['tax'] = $tax;
        $params['diners'] = $diners;
        $params['unit'] = $unit;
        $params['address'] = $c_address;
        //$params['bill_book'] = $bill_book;
        $params['director'] = $director;
        $params['chief_accountant'] = $chief_accountant;
        $params['cashier'] = $c_cashier;
        $params['storekeeper'] = $storekeeper;

        //xxx-add

        $params1 = array();
        $params1['unit'] = $unit;
        $params1['address'] = $c_address;
        //$params1['bill_book'] = $bill_book;
        $params1['director'] = $director;
        $params1['chief_accountant'] = $chief_accountant;
        $params1['cashier'] = $c_cashier;
        $params1['storekeeper'] = $storekeeper;

        $this->load->model('shop_detail_model');
        //$this->shop_detail_model->update_shop_detail($shop_id, $params1);


        if ($type1 == 2 || $type1 == 3) {
            $params['paid'] = 0;
        } else {
            $params['paid_date'] = $date;
        }

        $this->load->model('order_model');

        $order_id = $this->order_model->add_order($params);
        $this->load->model('shipping_model');
        if ($cost > 0) {
            $params = array();
            $params['order_id'] = $order_id;
            $params['shop_id'] = $shop_id;
            $params['shipper'] = $shipper;
            $params['address'] = $address;
            $params['cost'] = $cost;

            $this->shipping_model->add_shipping($params);
        }

        $params = array();
        $params['shop_id'] = $shop_id;
        $params['order_id'] = $order_id;
        $params['bill_date'] = $date;
        $params['cashier'] = $cashier;


        $params['bill_time'] = date('H:i:s', strtotime('now'));
        $params['create_user'] = $this->user->user_id;
        $params['last_user'] = $this->user->user_id;
        $params['last_update'] = date('Y-m-d H:i:s', strtotime('now'));
        $this->load->model('bill_model');
        $bill_id = $this->bill_model->add_bill($params);


        //$bill = json_decode($bill);

        $this->load->model('product_model');

        $params = array('shop_id' => $shop_id, 'bill_id' => $bill_id);
        $params['create_user'] = $this->user->user_id;
        $params['last_user'] = $this->user->user_id;
        $params['last_update'] = date('Y-m-d H:i:s', strtotime('now'));

        $data = array();
        $products = array();
        $i = 0;

        $this->load->model('bill_item_model');

        $amount = 0;
        $new_bill = array();
        $this->load->model('occupy_model');
        $this->load->model('visitor_model');
        $this->load->model('occupy_order_model');
        foreach ($bill as $item) {
            $product_id = $item['product_id'];
            $product_name = std($item['product_name']);
            //$nog = intval($item['nog']);
            $pr = $this->product_model->get_product($product_id, $shop_id);
            //$params = array();
            $params['order_id'] = $order_id;
            $params['product_id'] = $product_id;
            $params['product_code'] = '';
            $params['product_name'] = $product_name;
            $params['price'] = floatval($item['price']) - $item['price'] * $discount / 100;
            $params['cost_price'] = $pr['avg_cost_price'];

            $item['price'] = $params['price'];
            $params['quantity'] = floatval($item['quantity']);
            if (array_key_exists('start_date', $item)) {
                $params['start_date'] = $item['start_date'];
                $params['end_date'] = $item['end_date'];
                $this->occupy_model->update_product_occupy($shop_id, $product_id, $item['start_date'], $item['end_date'], '1');
            } else {
                unset($params['start_date']);
                unset($params['end_date']);
            }
            $params['status1'] = 4;
            $params['amount'] = $params['price'] * $item['quantity'];
            $amount += $params['amount'];

            //$params['nog'] = $nog;
            if ($type != 0 && $tentative == 0) {
                $this->product_model->update_product_average_price($shop_id, $product_id, $params['price'], $item['quantity']);
            }
            $bill_item_id = $this->bill_item_model->add_bill_item($params);

            /*
            if ($type==0){
                $product_params['stock'] = $pr['stock']-$item['quantity'];
            }
            else{
                $product_params['stock'] = $pr['stock']+$item['quantity'];
            }
            $this->product_model->update_product($product_id, $shop_id, $product_params);
            */
            //$this->product_model->update_product_stock2($shop_id, $product_id);

            if ($type == 0) {
                //Tru di nguyen lieu
                $rows = $this->product_model->get_product_materials($shop_id, $product_id);
                foreach ($rows as $row) {
                    $material_id = $row['id'];
                    $material_name = $row['product_name'];
                    $quantity = $row['quantity'];
                    $params1 = array();
                    $params1['shop_id'] = $shop_id;
                    $params1['order_id'] = $order_id;
                    $params1['parent'] = $bill_item_id;

                    $params1['product_id'] = $material_id;
                    $params1['product_name'] = $material_name;
                    $params1['quantity'] = $quantity * $item['quantity'];

                    $params1['create_user'] = $this->user->user_id;
                    $params1['last_user'] = $this->user->user_id;
                    $params1['last_update'] = date('Y-m-d H:i:s', strtotime('now'));
                    $average_price = $row['average_price'];
                    $params1['price'] = 0; //$average_price;
                    $params1['amount'] = 0; //$average_price * $params1['quantity'];
                    $params1['status1'] = 4;

                    $this->bill_item_model->add_bill_item($params1);

                    /*
                    $params1 = array();
                    $params1['stock'] = $row['stock'] - $quantity * $item['quantity'];
                    $this->product_model->update_product($material_id, $shop_id, $params1);
                    */
                    //$this->product_model->update_product_stock2($shop_id, $material_id);
                }
            } else {
            }

            if (array_key_exists('visitors', $item)) {
                $visitors = $item['visitors'];
                foreach ($visitors as $visitor) {
                    $params1 = array();
                    $params1['shop_id'] = $shop_id;
                    $params1['order_id'] = $order_id;
                    $params1['bill_item_id'] = $bill_item_id;
                    $params1['name'] = std($visitor['name']);
                    $params1['phone'] = std($visitor['phone']);
                    $params1['dob'] = date_from_vn(std($visitor['dob']));
                    $params1['gender'] = std($visitor['gender']);
                    $params1['nationality'] = std($visitor['nationality']);
                    $params1['passport_id'] = std($visitor['passport_id']);
                    $params1['room_number'] = $product_name;
                    $params1['checkin_date'] = std($item['start_date']);
                    $params1['checkout_date'] = std($item['end_date']);
                    $date = str_replace('/', '-', $visitor['estimated_leaving_date']);
                    $date = date('Y-m-d', strtotime($date));
                    $params1['estimated_leaving_date'] = $date;

                    $params1['paper_type'] = std($visitor['paper_type']);
                    $params1['profession'] = std($visitor['profession']);
                    $params1['ethnic'] = std($visitor['ethnic']);
                    $params1['religion'] = std($visitor['religion']);
                    $params1['purpose'] = std($visitor['purpose']);
                    $params1['province'] = std($visitor['province']);
                    $params1['district'] = std($visitor['district']);
                    $params1['ward'] = std($visitor['ward']);
                    $params1['address'] = std($visitor['address']);
                    $params1['note'] = std($visitor['note']);

                    $this->visitor_model->add_visitor($params1);
                }
            }

            if ($type == 0) {
                $product_params['stock'] = $pr['stock'] - $item['quantity'];
            } else {
                //Update product avg_cost_price
                $last_avg_cost_price = floatval($pr['avg_cost_price']);
                $stock = abs(floatval($pr['stock']));

                $avg_cost_price = ($last_avg_cost_price * $stock + $item['price'] * floatval($item['quantity'])) / ($stock + floatval($item['quantity']));

                $product_params['avg_cost_price'] = $avg_cost_price;
                //$this->product_model->update_product($product_id, $shop_id, $params1);
                $product_params['stock'] = $pr['stock'] + $item['quantity'];
            }
            if ($tentative == 0) {
                $this->product_model->update_product($product_id, $shop_id, $product_params);
            }
            $new_bill[] = $item;
        }

        $params = array('id' => $bill_id, 'amount' => $amount);
        $this->bill_model->update_bill($bill_id, $shop_id, $params);
        if ($tentative == 0) {
            $params = array('id' => $order_id, 'amount' => $amount, 'status1' => 4);
        } else {
            $params = array('id' => $order_id, 'amount' => $amount, 'status1' => 50);
        }
        $this->order_model->update_order($order_id, $shop_id, $params);
        $this->order_model->update_order_memo($shop_id, $order_id);

        $order = $this->order_model->get_order($order_id, $shop_id);
        $shipping = $this->shipping_model->get_order_shipping($shop_id, $order_id);
        if ($shipping) {
            $data['shipping'] = $shipping;
        }

        $this->update_occupy_order($shop_id, $order_id);

        $data['products'] = $new_bill;
        $data['i'] = count($bill);
        $data['invoice_number'] = $invoice_number;
        $data['cashier'] = $cashier;
        $data['date'] = $date;
        $data['order'] = $order;
        $this->load->model('shop_detail_model');
        $shop_detail = $this->shop_detail_model->get_shop_detail($shop_id);
        $data['shop_detail'] = $shop_detail;

        $message = $this->load->view('normal_invoice', $data, true);
        if (trim($email) != '') {
            $this->mail($email, 'Hóa đơn', $message);
        }

        $this->load->model('user_model');
        $rows = $this->user_model->get_all_users($shop_id);
        $users = array();
        foreach ($rows as $row) {
            if ($row['full_name'] != '') {
                $users[$row['id']] = $row['full_name'];
            } else {
                if ($row['phone'] != '') {
                    $users[$row['id']] = $row['phone'];
                }
            }
        }
        $data["users"] = $users;

        $this->load->view('normal_invoice', $data);
    }

    function update_checkin_date()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $bill_item_id = intval($this->input->get("id"));
        $shop_id = $this->user->shop_id;
        $this->_update_checkin_date($shop_id, $bill_item_id);
    }

    function _update_checkin_date($shop_id, $bill_item_id)
    {
        $this->load->model('bill_item_model');
        $bill_item = $this->bill_item_model->get_bill_item($bill_item_id, $shop_id);
        $checkin_date = $bill_item['start_date'];
        $checkout_date = $bill_item['end_date'];
        $product_id = $bill_item['product_id'];
        $params = array();
        $params['checkin_date'] = $checkin_date;
        $this->bill_item_model->update_bill_item($shop_id, $bill_item_id, $params);
        //update occupy 2
        $this->load->model('occupy_model');
        $this->occupy_model->update_product_occupy($shop_id, $product_id, $checkin_date, $checkout_date, '2');
    }

    function update_checkout_date()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $bill_item_id = intval($this->input->get("id"));
        $type = intval($this->input->get("type"));
        $shop_id = $this->user->shop_id;

        $this->load->model('bill_item_model');
        $bill_item = $this->bill_item_model->get_bill_item($bill_item_id, $shop_id);
        if ($type == 0) {
            $checkout_date = $bill_item['end_date'];
        } else {
            $checkout_date = null;
        }
        $checkin_date = $bill_item['start_date'];
        $checkout_date = $bill_item['end_date'];
        $product_id = $bill_item['product_id'];

        $params = array();
        if ($type == 0) {
            $params['checkout_date'] = $checkout_date;
        } else {
            $params['checkout_date'] = null;
        }
        $this->bill_item_model->update_bill_item($shop_id, $bill_item_id, $params);
        $checkout_date = date('Y-m-d', strtotime("-1 days", strtotime($bill_item['end_date'])));
        //update occupy 3
        //if ($type!=0){
        $this->load->model('occupy_model');
        $this->occupy_model->update_checkout_date_occupy($shop_id, $product_id, $checkout_date, $type);
        //}
    }


    function update_checkout_date2()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $bill_item_id = intval($this->input->get("id"));
        $shop_id = $this->user->shop_id;
        $date = std($this->input->get("date"));

        $this->load->model('bill_item_model');
        $params = array();
        $checkout_date = date_create_from_format('d/m/Y', $date);
        $params['checkout_date'] = $checkout_date->format('Y-m-d');
        $this->bill_item_model->update_bill_item($shop_id, $bill_item_id, $params);
    }


    public function sale_orders()
    {
        $this->orders(0);
    }

    public function purchase_orders()
    {
        $this->orders(1);
    }

    private function orders($type, $page = 0)
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $shop_type = $this->user->shop['type'];

        $this->load->model("order_model");
        $keyword = $this->input->post("keyword");
        $data = array();
        $paid = -1;
        $vat = -1;
        $customer = 0;
        $user = 0;
        $from_date = '';
        $to_date = '';
        $from_amount = '';
        $to_amount = '';
        $is_reference = 0;
        $from_date = date('01/m/Y');
        $to_date = date("d/m/Y");
        $advanced = 0;
        $currency = '';
        $payment_type = -1;
        $checkin_from = '';
        $checkin_to = '';
        $checkout_from = '';
        $checkout_to = '';
        $report777 = -1;
        $this->load->model('customer_model');
        $customers = $this->customer_model->get_all_customers($shop_id, $type);
        $this->load->model('shop_user_model');
        $users = $this->shop_user_model->users($shop_id);


        if (!empty($_POST)) {
            $keyword = std($keyword);
            $product_id = intval($this->input->post("product_id"));
            $data['product_id'] = $product_id;

            $paid = intval($this->input->post("type"));
            $hddt = intval($this->input->post("hddt"));
            $report777 = intval($this->input->post("report777"));
            $customer = intval($this->input->post("customer"));
            $user = intval($this->input->post("user"));
            $vat = intval($this->input->post("vat"));
            $is_reference = intval($this->input->post("is_reference"));
            $payment_type = intval($this->input->post("payment_type"));
            $currency = std($this->input->post("currency"));
            $advanced = intval($this->input->post("advanced"));
            $from_date = std($this->input->post('from_date'));
            $from_date = date_create_from_format('d/m/Y', $from_date);
            if (!is_bool($from_date)) {
                $from_date = $from_date->format('Y-m-d');
            } else {
                $from_date = '';
            }

            $to_date = std($this->input->post('to_date'));
            $to_date = date_create_from_format('d/m/Y', $to_date);
            if (!is_bool($to_date)) {
                $to_date = $to_date->format('Y-m-d');
            } else {
                $end_date = '';
            }

            $from_amount = intval($this->input->post("rom_amount"));
            $to_amount = intval($this->input->post("to_amount"));

            $checkin_from = std($this->input->post('checkin_from'));
            $checkin_from = date_from_vn($checkin_from);

            $checkin_to = std($this->input->post('checkin_to'));
            $checkin_to = date_from_vn($checkin_to);


            $checkout_from = std($this->input->post('checkout_from'));
            $checkout_from = date_from_vn($checkout_from);

            $checkout_to = std($this->input->post('checkout_to'));
            $checkout_to = date_from_vn($checkout_to);


            $orders = $this->order_model->search_orders_by_type2($shop_id, $type, $paid, $keyword, $product_id, $vat, $customer, $user, $from_date, $to_date, $from_amount, $to_amount, $is_reference, $payment_type, $currency, $report777, $hddt);

            if ($checkin_from != '' || $checkin_to != '') {
                $order_ids = $this->order_model->search_order_by_checkin_date($shop_id, $checkin_from, $checkin_to);
                $data['order_ids'] = $order_ids;
                if ($checkin_from != '') {
                    $checkin_from = date('d/m/Y', strtotime($checkin_from));
                }
                if ($checkin_to != '') {
                    $checkin_to = date('d/m/Y', strtotime($checkin_to));
                }
            }

            if ($checkout_from != '' || $checkout_to != '') {
                $order_ids = $this->order_model->search_order_by_checkout_date($shop_id, $checkout_from, $checkout_to);
                $data['order_checkout_ids'] = $order_ids;
                if ($checkout_from != '') {
                    $checkout_from = date('d/m/Y', strtotime($checkout_from));
                }
                if ($checkout_to != '') {
                    $checkout_to = date('d/m/Y', strtotime($checkout_to));
                }
            }

            //$from_date = date('d/m/y', )
            if ($from_date != '') {
                $from_date = date('d/m/Y', strtotime($from_date));
            }
            if ($to_date != '') {
                $to_date = date('d/m/Y', strtotime($to_date));
            }
        } else {
            $lot_number = std($this->input->get("lot"));
            if ($lot_number != '') {
                $product = intval($this->input->get("product"));
                $orders = $this->order_model->search_pharm_orders($shop_id, $product, $lot_number);
            } else {

                $page = intval($this->input->get("page"));

                $ipp = $this->config->item('ipp');
                $page = intval($this->input->get('page'));
                if ($page == 0) {
                    $page = 1;
                }
                $data['page'] = $page;
                $offset = ($page - 1) * $ipp;

                $count = $this->order_model->get_orders_count($shop_id, $type);
                //echo($ipp . ",");
                //echo($offset);
                $orders = $this->order_model->get_orders($shop_id, $type, $ipp, $offset);

                $pages = intval($count / $ipp);
                if ($ipp * $pages < $count) {
                    $pages++;
                }
                $data['pages'] = $pages;
            }
        }

        $order_ids = array(0);
        foreach ($orders as $row) {
            $order_ids[] = $row['id'];
        }

        $ebill = intval($this->user->shop['ebill']);
        if ($ebill != 0) {
            $this->load->model('ebill_model');
            $bills = $this->ebill_model->get_bills($shop_id, $order_ids);
            $ebills = array();
            foreach ($bills as $row) {
                $ebills[$row['order_id']] = $row['id'];
            }
            $data['ebills'] = $ebills;
        }

        $data['checkin_from'] = $checkin_from;
        $data['checkin_to'] = $checkin_to;
        $data['checkout_from'] = $checkout_from;
        $data['checkout_to'] = $checkout_to;

        $data['hddt'] = $hddt;

        if ($type == 0) {
            $data['title'] = tb_word('shops.sale.orders');
        } else {
            $data['title'] = tb_word('shops.purchase.orders');
        }
        $data["user"] = $this->user;
        $data["keyword"] = $keyword;
        $data["type"] = $type;
        $data['from_date'] = $from_date;
        $data['to_date'] = $to_date;

        /*
        $this->load->model("room_table_model");
        $_tables = $this->room_table_model->get_room_tables($shop_id);
        $tables = array();
        $tables['0'] = "";
        foreach($_tables as $table){
            $tables[$table['id']] = $table['name'];
        }
        $data["tables"] = $tables;
        */
        $data["url"] = $this->config->base_url();
        //echo($data["url"]);


        $data["orders"] = $orders;
        $data["paid"] = $paid;
        $data["vat"] = $vat;
        $data["currency"] = $currency;
        $data["type"] = $type;
        $data["customer"] = $customer;
        $data["customers"] = $customers;
        $data["is_reference"] = $is_reference;
        $data["payment_type"] = $payment_type;
        $data["advanced"] = $advanced;
        $data["saler"] = $user;
        //$data["users"] = $users;
        $today = date('d/m/Y');
        $data["today"] = $today;
        $data['shop_type'] = $shop_type;
        $data['report777'] = $report777;
        if ($from_amount == 0) {
            $from_amount = '';
        }
        if ($to_amount == 0) {
            $to_amount = '';
        }
        $data["from_amount"] = $from_amount;
        $data["to_amount"] = $to_amount;


        $this->load->model('user_model');
        $rows = $this->user_model->get_all_users($shop_id);
        $users = array();
        foreach ($rows as $row) {
            if ($row['full_name'] != '') {
                $users[$row['id']] = $row['full_name'];
            } else {
                if ($row['phone'] != '') {
                    $users[$row['id']] = $row['phone'];
                }
            }
        }
        $users[0] = '';
        $data["users"] = $users;
        //toandk2 sửa
        if (check_user_agent('mobile')) {
            $data['page'] = 'sale_orders';
            if ($type == 0) {
                $data['title_header'] = 'Đơn thu';
            } else {
                $data['title_header'] = 'Bảng kê, chi tiền';
            }

            $this->load->view('mobile_views/html_header_app', $data);
            $this->load->view('mobile_views/orders', $data);
            $this->load->view('mobile_views/html_footer_app', $data);
        } else {
            $this->load->view('headers/html_header', $data);
            $this->load->view('orders', $data);
            $this->load->view('headers/html_footer');
        }
    }


    public function order_detail()
    {

        //error_reporting(-1);
        //ini_set('display_errors', 1);
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $pre = $this->user->shop['precision'];
        $order_id = intval($this->input->get("id"));

        $this->load->model('order_model');
        //$this->load->model('bill_model');
        $this->load->model('bill_item_model');


        $order = $this->order_model->get_order1($order_id, $shop_id);

        $visitors = $this->order_model->get_order_visitors($shop_id, $order_id);
        //$bill = $this->bill_model->get_order_bill($order_id, $shop_id);

        $bill_items = $this->bill_item_model->get_order_bill_items($shop_id, $order_id);

        //echo(json_encode($bill_items));

        $data = array();
        if ($order['order_type'] == 'B') {
            $data['title'] = tb_word('shops.sale.order');
            $type = 0;
        } else {
            $data['title'] = tb_word('shops.purchase.order');
            $type = 1;
        }
        $data["user"] = $this->user;

        $data["url"] = $this->config->base_url();

        $this->load->model('shipping_model');
        $shipping = $this->shipping_model->get_order_shipping($shop_id, $order_id);

        $data["shipping"] = $shipping;
        $data["order"] = $order;
        $this->load->model('nationality_model');
        $nationalities = $this->nationality_model->get_all_nationality();
        $data["nationalities"] = $nationalities;
        //$data["bill"] = $order;
        $data["bill_items"] = $bill_items;

        $data['order_id'] = $order_id;
        $data['cashier'] = $this->user->row['full_name'];
        $this->load->model('customer_model');
        $customers = $this->customer_model->get_all_customers($shop_id, $type);
        $data['customers'] = $customers;
        $data['type'] = $type;
        $data['shop_id'] = $shop_id;
        $data['user_id'] = $this->user->user_id;
        $data['visitors'] = $visitors;
        $period_closed_date = $this->user->shop['period_closed_date'];
        $period_closed_date1 = vn_date($period_closed_date);

        $data['period_closed_date'] = $period_closed_date;
        $data['period_closed_date1'] = $period_closed_date1;

        $this->load->model('shop_detail_model');
        $shop_detail = $this->shop_detail_model->get_shop_detail($shop_id);
        if (!$shop_detail) {
            $shop_detail = array();
            $shop_detail['name'] = $this->user->shop['name'];
            $shop_detail['unit'] = $this->user->shop['name'];
            $shop_detail['address'] = $this->user->shop['address'];
            $shop_detail['bill_book'] = '';
            $shop_detail['director'] = '';
            $shop_detail['chief_accountant'] = '';
            $shop_detail['cashier'] = '';
            $shop_detail['storekeeper'] = '';
        }

        $data['shop_detail'] = $shop_detail;

        $this->load->model('shop_ebill_model');
        $vnpt_content = $this->shop_ebill_model->get_shop_ebill($shop_id, 'vnpt');
        if ($vnpt_content) {
            $data['vnpt_content'] = $vnpt_content;
        }


        $this->load->model('shop_model');
        $shop = $this->shop_model->get_shop($shop_id);
        $data['shop'] = $shop;

        $this->load->model('product_model');
        $groups = $this->product_model->get_rental_product_groups($shop_id);
        $products = $this->product_model->get_rental_product($shop_id);

        $data['groups'] = $groups;
        $data['products'] = $products;
        $data['img_path'] = "/img/$shop_id/";

        if ($shop_id == 2707) {
            $this->load->model('ebill_model');
            $ebill = $this->ebill_model->get_order_ebill($shop_id, $order_id);
        } else {
            $ebill = array();
        }
        $data['ebill'] = $ebill;
        $data['pre'] = $pre;

        //toandk2 sửa
        if (check_user_agent('mobile')) {
            $data['title_header'] = 'Đơn thu';
            $data['icon_header'] = 'images/orderapp/bill-fill-gray.png';
            $this->load->view('mobile_views/html_header_app', $data);
            $this->load->view('mobile_views/order', $data);
            // $this->load->view('mobile_views/html_footer_app', $data);
        } else {
            $this->load->view('headers/html_header', $data);
            $this->load->view('order', $data);
            $this->load->view('headers/html_footer');
        }
    }

    function remove_child_order()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get("order_id"));
        $parent_order_id = intval($this->input->get("parent_order_id"));
        $this->load->model('order_model');
        $order = $this->order_model->get_order($order_id, $shop_id);
        $parent_order = $this->order_model->get_order($parent_order_id, $shop_id);
        if (!$parent_order) {
            return;
        }
        if (!$order) {
            return;
        }
        $total_amount = $parent_order['total_amount'];
        $amount = $order['amount'];
        $total_amount = $total_amount - $amount;

        $order_items = $parent_order['order_items'];
        if (!$order_items) {
            return;
        }

        $order_items = json_decode($order_items, true);
        $order_items = array_diff($order_items, [$order['id']]);
        $order_items = json_encode($order_items);
        $params = array();
        $params['total_amount'] = $total_amount;
        $params['order_items'] = $order_items;
        $this->order_model->update_order($parent_order_id, $shop_id, $params);

        $params = array();
        $params['is_child'] = 0;
        $this->order_model->update_order($order_id, $shop_id, $params);
    }

    function parent_order_detail()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        //echo(json_encode($this->user));
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get("id"));

        $this->load->model('order_model');
        $this->load->model('bill_item_model');

        $main_order = $this->order_model->get_order($order_id, $shop_id);

        $orders = $main_order['order_items'];
        $order_ids = json_decode($orders);

        //echo(json_encode($bill_items));

        $data = array();
        if ($main_order['order_type'] == 'B') {
            $data['title'] = tb_word('shops.sale.order');
            $type = 0;
        } else {
            $type = 1;
            $data['title'] = tb_word('shops.purchase.order');
        }
        $data["user"] = $this->user;

        $data["url"] = $this->config->base_url();

        $this->load->view('headers/html_header', $data);
        $this->load->model('shipping_model');

        $orders = array();
        foreach ($order_ids as $id) {
            $order = $this->order_model->get_order($id, $shop_id);
            $visitors =  $this->order_model->get_order_visitors($shop_id, $id);
            $items = $this->order_model->get_order_bill_items($shop_id, $id);
            $order['items'] = $items;
            $order['visitors'] = $visitors;
            $shipping = $this->shipping_model->get_order_shipping($shop_id, $order_id);
            $order['shipping'] = $shipping;
            $orders[] = $order;
        }

        $items = $this->order_model->get_order_bill_items($shop_id, $main_order['id']);
        $main_order['items'] = $items;
        $visitors =  $this->order_model->get_order_visitors($shop_id, $order_id);
        $main_order['visitors'] = $visitors;

        $shipping = $this->shipping_model->get_order_shipping($shop_id, $main_order['id']);
        $main_order['shipping'] = $shipping;
        $data["order"] = $main_order;
        $this->load->model('nationality_model');
        $nationalities = $this->nationality_model->get_all_nationality();
        $data["nationalities"] = $nationalities;

        $data["orders"] = $orders;
        $data['order_id'] = $order_id;
        $data['cashier'] = $this->user->row['full_name'];

        $this->load->model('product_model');
        $groups = $this->product_model->get_rental_product_groups($shop_id);
        $products = $this->product_model->get_rental_product($shop_id);
        $data['groups'] = $groups;
        $data['products'] = $products;


        $this->load->model('customer_model');
        $customers = $this->customer_model->get_all_customers($shop_id, $type);
        $data['customers'] = $customers;
        $data['type'] = $type;
        $data['shop_id'] = $shop_id;

        $this->load->model('shop_detail_model');
        $shop_detail = $this->shop_detail_model->get_shop_detail($shop_id);
        if (!$shop_detail) {
            $shop_detail = array();
            $shop_detail['name'] = $this->user->shop['name'];
            $shop_detail['unit'] = $this->user->shop['name'];
            $shop_detail['address'] = $this->user->shop['address'];
            $shop_detail['bill_book'] = '';
            $shop_detail['director'] = '';
            $shop_detail['chief_accountant'] = '';
            $shop_detail['cashier'] = '';
            $shop_detail['storekeeper'] = '';
        }

        $data['shop_detail'] = $shop_detail;

        $this->load->model('shop_model');
        $shop = $this->shop_model->get_shop($shop_id);
        $data['shop'] = $shop;


        $this->load->view('parent_order', $data);
        $this->load->view('headers/html_footer');
    }

    function merged_order_detail2()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $item_id = intval($this->input->get("id"));
        $this->load->model('bill_item_model');

        $item = $this->bill_item_model->get_bill_item($item_id, $shop_id);

        $items = $this->bill_item_model->get_children_items($item_id, $shop_id);
        if (!$items) {
            $items = array();
        }

        array_unshift($items, $item);

        $data = array();
        $data['title'] = tb_word('shops.sale.order');
        $data["user"] = $this->user;

        $data["url"] = $this->config->base_url();

        $this->load->view('headers/html_header', $data);

        $data["bill_items"] = $items;
        $data["item_id"] = $item_id;
        $data['shop_id'] = $shop_id;
        $this->load->view('merged_order2', $data);
        $this->load->view('headers/html_footer');
    }


    function merged_order_detail1()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get("id"));
        $this->load->model('order_model');
        $this->load->model('bill_item_model');

        $order = $this->order_model->get_order($order_id, $shop_id);
        $data = array();
        $data['title'] = tb_word('shops.sale.order');
        $data["user"] = $this->user;

        $data["url"] = $this->config->base_url();

        $this->load->view('headers/html_header', $data);

        $items = $order['order_items'];
        $items_ids = json_decode($items);

        $items = array();
        foreach ($items_ids as $id) {
            $item = $this->bill_item_model->get_bill_item($id, $shop_id);
            $items[] = $item;
        }
        $data["order"] = $order;
        $data["bill_items"] = $items;
        $data['order_id'] = $order_id;
        $data['cashier'] = $this->user->row['full_name'];
        $data['shop_id'] = $shop_id;
        $this->load->view('merged_order1', $data);
        $this->load->view('headers/html_footer');
    }


    function merged_order_detail()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get("id"));

        $this->load->model('order_model');
        $this->load->model('bill_item_model');

        $order = $this->order_model->get_order($order_id, $shop_id);


        $orders = $order['order_items'];
        $order_ids = json_decode($orders);

        //echo(json_encode($bill_items));

        $data = array();
        if ($order['order_type'] == 'B') {
            $data['title'] = tb_word('shops.sale.order');
            $type = 0;
        } else {
            $data['title'] = tb_word('shops.purchase.order');
            $type = 1;
        }
        $data["user"] = $this->user;

        $data["url"] = $this->config->base_url();

        $this->load->view('headers/html_header', $data);
        $this->load->model('shipping_model');

        $orders = array();
        foreach ($order_ids as $id) {
            $order = $this->order_model->get_order($id, $shop_id);
            $items = $this->order_model->get_order_bill_items($shop_id, $id);
            $order['items'] = $items;
            $shipping = $this->shipping_model->get_order_shipping($shop_id, $order_id);
            $order['shipping'] = $shipping;
            $orders[] = $order;
        }
        $this->load->model('nationality_model');
        $nationalities = $this->nationality_model->get_all_nationality();
        $data["nationalities"] = $nationalities;

        $data["orders"] = $orders;
        $data['order_id'] = $order_id;
        $data['cashier'] = $this->user->row['full_name'];
        $this->load->model('customer_model');
        $customers = $this->customer_model->get_all_customers($shop_id, $type);
        $data['customers'] = $customers;
        $data['type'] = $type;
        $data['shop_id'] = $shop_id;
        $this->load->view('merged_order', $data);
        $this->load->view('headers/html_footer');
    }

    public function customers()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }

        $type = intval($this->input->get("type"));
        $keyword = std($this->input->get('customer_keyword'));
        $data = array();
        if ($type == 0) {
            $type_name = 'customer';
        } else {
            $type_name = 'supplier';
        }
        //echo(json_encode($this->user));
        $shop_id = $this->user->shop_id;

        $this->load->model('customer_model');
        $ipp = $this->config->item('ipp');
        $page = intval($this->input->get('page'));
        if ($page == 0) {
            $page = 1;
        }
        $data['page'] = $page;
        $offset = ($page - 1) * $ipp;
        if ($keyword != '') {
            $count = $this->customer_model->count_search_customers($shop_id, $type, $keyword);
        } else {
            $count = $this->customer_model->count_customers($shop_id, $type);
        }
        $count = $count['c'];

        $pages = intval($count / $ipp);
        if ($ipp * $pages < $count) {
            $pages++;
        }
        $data['pages'] = $pages;
        //toandk2 sửa
        if (check_user_agent('mobile')) {
            $ipp = 1000000;
        }
        if ($keyword != '') {
            $customers = $this->customer_model->search_customers($shop_id, $type, $keyword, $ipp, $offset);
        } else {
            $customers = $this->customer_model->get_all_customers($shop_id, $type, $ipp, $offset);
        }
        $suppliers = $this->customer_model->get_all_customers($shop_id, 1, $ipp, $offset);
        $data['title'] = tb_word('customers');
        $this->load->model('init_data_model');
        $init_data = $this->init_data_model->get_init_data1($shop_id);
        $data['init_data'] = $init_data;

        $data['user'] = $this->user;
        $data['type_name'] = $type_name;
        $data['type'] = $type;
        $data['keyword'] = $keyword;
        $data["url"] = $this->config->base_url();

        //echo(json_encode($a));
        // $this->load->view('headers/html_header', $data);
        $data['customers'] = $customers;
        $data['suppliers'] = $suppliers;
        //toandk2 sửa
        if (check_user_agent('mobile')) {
            $data['page'] = 'customers';
            $data['title_header'] = 'Khách hàng/Nhà cung cấp';
            $this->load->view('mobile_views/html_header_app', $data);
            $this->load->view('mobile_views/customers', $data);
            $this->load->view('mobile_views/html_footer_app', $data);
        } else {
            $this->load->view('headers/html_header', $data);
            $this->load->view('customers', $data);
            $this->load->view('headers/html_footer');
        }

        // $this->load->view('customers', $data);
        // $this->load->view('headers/html_footer');
    }

    public function customer()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('customer_model');

        $id = intval($this->input->get("id"));
        $type = intval($this->input->get("type"));
        $url = $this->config->base_url();
        if ($id > 0) {
            if (empty($_POST)) {
                $customer = $this->customer_model->get_customer($id, $shop_id);
                $data['title'] = tb_word('customers');
                $data['user'] = $this->user;
                $data['id'] = $id;
                $data['url'] = $url;
                $data['customer'] = $customer;
                //toandk2 sửa
                if (check_user_agent('mobile')) {
                    $data['title_header'] = 'Cập nhật khách hàng';
                    $this->load->view('mobile_views/html_header_app', $data);
                    $this->load->view('mobile_views/customer', $data);
                } else {
                    $this->load->view('headers/html_header', $data);
                    $this->load->view('customer', $data);
                    $this->load->view('headers/html_footer');
                }
                // $this->load->view('customer', $data);
                // $this->load->view('headers/html_footer');
            } else { //do update
                $name = std($this->input->post("name"));
                $phone = std($this->input->post("phone"));
                $last_name = std($this->input->post("last_name"));
                $email = std($this->input->post("email"));
                $address = std($this->input->post("address"));
                $mst = std($this->input->post("mst"));
                $params = array();
                $params['name'] = $name;
                $params['last_name'] = $last_name;
                $params['phone'] = $phone;
                $params['email'] = $email;
                $params['address'] = $address;
                $params['note'] = $mst;

                $this->customer_model->update_customer($id, $shop_id, $params);
                $this->load->helper('url');
                $url = $this->config->base_url();
                redirect($url . 'index.php/home/customers?type=' . $type);
            }
        } else {
            if (empty($_POST)) {
                $data['title'] = tb_word('customers');
                $data['user'] = $this->user;
                $data['url'] = $url;
                $data['id'] = 0;
                //toandk2 sửa
                if (check_user_agent('mobile')) {
                    $data['title_header'] = 'Thêm mới khách hàng';
                    $this->load->view('mobile_views/html_header_app', $data);
                    $this->load->view('mobile_views/customer', $data);
                } else {
                    $this->load->view('headers/html_header', $data);
                    $this->load->view('customer', $data);
                    $this->load->view('headers/html_footer');
                }
            } else { //do add
                //$this->output->enable_profiler(TRUE);
                $name = std($this->input->post("name"));
                $phone = std($this->input->post("phone"));
                $email = std($this->input->post("email"));
                $address = std($this->input->post("address"));
                $mst = std($this->input->post("mst"));
                $last_name = std($this->input->post("last_name"));
                $params = array();
                $params['shop_id'] = $shop_id;
                $params['name'] = $name;
                $params['phone'] = $phone;
                $params['email'] = $email;
                $params['address'] = $address;
                $params['type'] = $type;
                $params['note'] = $mst;
                $params['last_name'] = $last_name;

                $this->customer_model->add_customer($shop_id, $params);

                $params = array();
                $this->load->model('shop_model');
                $params['customer'] = 1;
                $this->shop_model->update_shop($shop_id, $params);

                redirect('/customers?type=' . $type);
            }
        }
    }
    public function export_product_excel()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $this->load->model('product_model');
        $shop_id = $this->user->shop_id;
        $products = $this->product_model->get_shop_products($shop_id);
        $data['products'] = $products;
        $this->load->view('export_product_excel', $data);
    }

    public function products()
    {

        // if ($this->user->shop['type'] != 11 && check_user_agent('mobile')) {
        //     redirect('/m/product');
        // }

        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $this->load->model('product_model');
        $shop_id = $this->user->shop_id;
        $shop_type = $this->user->shop['type'];
        if ($shop_type == 11 && $shop_id != 1011) {
            redirect('/pharm/products/');
        }

        $ipp = $this->config->item('ipp');

        $page = intval($this->input->get('page'));
        if ($page == 0) {
            $page = 1;
        }
        $data['page'] = $page;
        $offset = ($page - 1) * $ipp;
        $keyword = std($this->input->get('product_keyword'));
        $material = intval($this->input->get('material'));

        if ($keyword != '') {
            $count = $this->product_model->count_search_shop_products($shop_id, $keyword);
        } else {
            if ($material != 0) {
                $count = $this->product_model->count_material_products($shop_id);
            } else {
                $count = $this->product_model->count_shop_products($shop_id);
            }
        }

        $count = $count['c'];

        $pages = intval($count / $ipp);
        if ($ipp * $pages < $count) {
            $pages++;
        }
        $data['pages'] = $pages;
        $status = intval($this->input->get('status'));
        //toandk2 sửa
        if (check_user_agent('mobile')) {
            $ipp = 10000000;
        }
        if ($status == 0) {
            if ($keyword != '') {
                $products = $this->product_model->search_shop_products($shop_id, $keyword, $ipp, $offset);
            } else {
                if ($material != 0) {
                    $products = $this->product_model->get_material_products($shop_id, $ipp, $offset);
                } else {
                    $products = $this->product_model->get_shop_products($shop_id, $ipp, $offset);
                }
            }
        } else {
            $products = $this->product_model->get_deni_products($shop_id);
        }
        $data['title'] = tb_word('products');
        $data['status'] = $status;
        $this->load->model('init_data_model');
        $init_data = $this->init_data_model->get_init_data1($shop_id);
        $data['init_data'] = $init_data;


        $data['user'] = $this->user;
        $data["url"] = $this->config->base_url();
        $this->load->model('product_group_model');

        $product_groups = $this->product_group_model->get_all_product_groups($shop_id);
        $data['product_groups'] = $product_groups;
        $data['products'] = $products;
        $data["keyword"] = $keyword;
        $data['material'] = $material;
        $data['shop_id'] = $shop_id;
        //toandk2 sửa
        if (check_user_agent('mobile')) {
            $data['page'] = 'products';
            $data['title_header'] = 'Sản phẩm/Dịch vụ';
            $data['icon_header'] = 'images/orderapp/export_mobile.png';
            $this->load->view('mobile_views/html_header_app', $data);
            $this->load->view('mobile_views/products', $data);
            $this->load->view('mobile_views/html_footer_app', $data);
        } else {
            $this->load->view('headers/html_header', $data);
            $this->load->view('products', $data);
            $this->load->view('headers/html_footer');
        }
    }
    public function product()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $url = $this->config->base_url();
        $this->load->model('product_model');
        $this->load->model('product_group_model');
        $product_groups = $this->product_group_model->get_all_product_groups($shop_id);
        $id = intval($this->input->get("id"));
        $data = array();
        $data["url"] = $this->config->base_url();
        $path = FCPATH;
        $path = $path . 'img/0/';
        $files = scandir($path);
        $data["files"] = $files;
        //$img_path = $data["url"] . "img/";
        //var_dump($img_path);

        if ($id > 0) { //Edit
            if (empty($_POST)) {
                $product = $this->product_model->get_product($id, $shop_id);
                $data['title'] = tb_word('customers');
                $data['user'] = $this->user;
                $data['product'] = $product;
                $data['url'] = $url;
                $data['product_groups'] = $product_groups;
                $data["shop_id"] = $shop_id;

                $data["type"] = 1;
                $data["id"] = $id;

                $materials = $this->product_model->get_product_materials($shop_id, $id);
                $data['materials'] = $materials;

                $m = array();
                foreach ($materials as $material) {
                    $item = array();
                    $item['quantity'] = $material['quantity'];
                    $m[$material['id']] = $item;
                }
                $data['m'] = json_encode($m);
                //toandk2 sửa
                if (check_user_agent('mobile')) {
                    $data['title_header'] = 'Sửa sản phẩm';
                    $this->load->view('mobile_views/html_header_app', $data);
                    $this->load->view('mobile_views/product_add', $data);
                } else {
                    $this->load->view('headers/html_header', $data);
                    $this->load->view('product', $data);
                    $this->load->view('headers/html_footer');
                }
                // $this->load->view('product', $data);
                // $this->load->view('headers/html_footer');
            } else {
                //do update
                if ($this->input->post('delete') != '') {
                    $params['product_status'] = -1;
                    $this->product_model->update_product($id, $shop_id, $params);
                    redirect($url . 'index.php/home/products');
                    return;
                }
                $product_name = std($this->input->post("product_name"));
                $product_code = std($this->input->post("product_code"));
                $unit = std($this->input->post("unit_default"));
                $product_group = intval($this->input->post("product_group"));
                $stock_min = intval($this->input->post("stock_min"));
                $origin = std($this->input->post("origin"));
                $material = intval($this->input->post("material"));

                $this->load->model('product_group_model');
                $group = $this->product_group_model->get_product_group($product_group, $shop_id);
                if ($group) {
                    $type = $group['type'];
                } else {
                    $type = 0;
                }
                $list_price = intval(str_replace(",", "", $this->input->post("list_price")));
                $whole_price = intval(str_replace(",", "", $this->input->post("whole_price")));
                $cost_price = intval(str_replace(",", "", $this->input->post("cost_price")));
                //$stock = intval(str_replace(',', '',$this->input->post("stock")));

                $is_new = intval($this->input->post("is_new"));
                $is_hot = intval($this->input->post("is_hot"));

                $s_c_tax = intval($this->input->post("s_c_tax"));

                $gtgt = floatval($this->input->post("gtgt"));
                $stock_max = floatval($this->input->post("stock_max"));

                $product_status = intval($this->input->post("product_status"));
                $params = array();
                $params['shop_id'] = $shop_id;
                $params['product_name'] = $product_name;
                if ($product_code != '') {
                    $params['product_code'] = $product_code;
                } else {
                    $params['product_code'] = $id;
                }
                $params['product_group'] = $product_group;
                $params['list_price'] = $list_price;
                $params['whole_price'] = $whole_price;
                $params['cost_price'] = $cost_price;
                $params['unit_default'] = $unit;
                //$params['stock'] = $stock;
                $params['stock_min'] = $stock_min;
                $params['type'] = $type;
                $params['product_status'] = $product_status;
                $params['material'] = $material;
                $params['origin'] = $origin;

                $params['is_new'] = $is_new;
                $params['is_hot'] = $is_hot;
                $params['s_c_tax'] = $s_c_tax;

                $params['gtgt'] = $gtgt;
                $params['stock_max'] = $stock_max;

                $tags = $this->input->post('tags');
                if ($tags) {
                    $tags = json_encode($tags);
                }
                $params['tags'] = $tags;

                $this->product_model->update_product($id, $shop_id, $params);
                $product_id = $id;
                $product_image = std($this->input->post("product_image"));
                if (!file_exists(FCPATH . 'img/' . $shop_id)) {
                    mkdir(FCPATH . 'img/' . $shop_id);
                }

                $new_path = FCPATH . 'img/' . $shop_id . '/product_' . $product_id;

                if ($product_image != '') {
                    $path = $path . $product_image;
                    echo ($path . '-' . $new_path);
                    copy($path, $new_path);
                    $params = array();
                    $params['image_file'] = '/product_' . $product_id;
                    $this->product_model->update_product($product_id, $shop_id, $params);
                } else {
                    $config['upload_path']          = '/tmp/';
                    $config['allowed_types']        = 'gif|jpg|png';
                    $config['max_size']             = 10000;
                    $config['max_width']            = 30000;
                    $config['max_height']           = 30000;
                    $this->load->library('upload', $config);
                    if ($this->upload->do_upload('image_file')) {
                        $path = $this->upload->data()['full_path'];
                        copy($path, $new_path);
                        $params = array();
                        $params['image_file'] = '/product_' . $product_id;
                        $this->product_model->update_product($product_id, $shop_id, $params);
                    }
                }

                redirect('/home/products');
            }
        } else { //Add
            if (empty($_POST)) {
                $data['title'] = 'Thêm sản phẩm';
                $data['user'] = $this->user;
                $data['product_groups'] = $product_groups;
                $data["type"] = $this->user->shop['type'];
                $data["id"] = 0;
                $data['url'] = $url;
                //toandk2 thêm
                if (check_user_agent('mobile')) {
                    $data['title_header'] = 'Thêm sản phẩm';
                    $this->load->view('mobile_views/html_header_app', $data);
                    $this->load->view('mobile_views/product_add', $data);
                } else {
                    $this->load->view('headers/html_header', $data);
                    $this->load->view('product', $data);
                    $this->load->view('headers/html_footer');
                }
            } else {
                //do add
                //$this->output->enable_profiler(TRUE);
                $product_name = std($this->input->post("product_name"));
                $product_code = std($this->input->post("product_code"));
                $unit_default = std($this->input->post("unit_default"));
                $product_group = intval($this->input->post("product_group"));
                $list_price = intval(str_replace(",", "", $this->input->post("list_price")));
                $whole_price = intval(str_replace(",", "", $this->input->post("whole_price")));
                $cost_price = intval(str_replace(",", "", $this->input->post("cost_price")));
                //$stock = intval(str_replace(',', '',$this->input->post("stock")));
                $material = intval($this->input->post("material"));
                $origin = std($this->input->post("origin"));
                $stock_min = intval($this->input->post("stock_min"));
                $is_new = intval($this->input->post("is_new"));
                $is_hot = intval($this->input->post("is_hot"));
                $s_c_tax = intval($this->input->post("s_c_tax"));
                $gtgt = floatval($this->input->post("gtgt"));
                $stock_max = floatval($this->input->post("stock_max"));

                $group = $this->product_group_model->get_product_group($product_group, $shop_id);
                if ($group) {
                    $type = $group['type'];
                } else {
                    $type = 0;
                }

                $params = array();
                $params['shop_id'] = $shop_id;
                $params['product_name'] = $product_name;
                $params['product_code'] = $product_code;
                $params['unit_default'] = $unit_default;
                $params['product_group'] = $product_group;
                $params['list_price'] = $list_price;
                $params['whole_price'] = $whole_price;
                $params['cost_price'] = $cost_price;
                //$params['stock'] = $stock;
                $params['type'] = $type;
                $params['material'] = $material;
                $params['origin'] = $origin;
                $params['stock_min'] = $stock_min;

                $params['is_new'] = $is_new;
                $params['is_hot'] = $is_hot;

                $params['s_c_tax'] = $s_c_tax;
                $params['gtgt'] = $gtgt;
                $params['stock_max'] = $stock_max;


                $tags = $this->input->post('tags');
                if ($tags) {
                    $tags = json_encode($tags);
                }
                $params['tags'] = $tags;

                $product_id = $this->product_model->add_product($params);

                if ($product_code == '') {
                    $params = array();
                    $params['product_code'] = $product_id;
                    $this->product_model->update_product($product_id, $shop_id, $params);
                }
                $product_image = std($this->input->post("product_image"));
                if (!file_exists(FCPATH . 'img/' . $shop_id)) {
                    mkdir(FCPATH . 'img/' . $shop_id);
                }

                $new_path = FCPATH . 'img/' . $shop_id . '/product_' . $product_id;
                if ($product_image != '') {
                    $path = $path . $product_image;
                    echo ($path . '-' . $new_path);
                    copy($path, $new_path);
                    $params = array();
                    $params['image_file'] = '/product_' . $product_id;
                    $this->product_model->update_product($product_id, $shop_id, $params);
                } else {
                    $config['upload_path']          = '/tmp/';
                    $config['allowed_types']        = 'gif|jpg|png';
                    $config['max_size']             = 10000;
                    $config['max_width']            = 4096;
                    $config['max_height']           = 4096;
                    $this->load->library('upload', $config);
                    if ($this->upload->do_upload('image_file')) {
                        $path = $this->upload->data()['full_path'];
                        //echo($path);
                        copy($path, $new_path);
                        $params = array();
                        $params['image_file'] = '/product_' . $product_id;
                        $this->product_model->update_product($product_id, $shop_id, $params);
                    }
                }

                $params = array();
                $this->load->model('shop_model');
                $params['product'] = 1;
                $this->shop_model->update_shop($shop_id, $params);

                redirect('/products');
            }
        }
    }



    public function get_bill_items()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $table_id = intval($this->input->get("table_id"));
        //$order_id = $this->order_model->get_last_open_order_by_table($shop_id, $table_id);
        $this->load->model('order_model');
        $this->load->model('bill_item_model');

        $bill_id = intval($this->input->get("bill_id"));
        $bill_items = array();
        if ($bill_id == 0) {
            //$bill_items = $this->bill_item_model->get_current_table_bill_items($shop_id, $table_id);
            $order_id = $this->order_model->get_last_open_order_by_table($shop_id, $table_id);
            if ($order_id > 0) {
                $this->load->model('bill_model');
                $bill = $this->bill_model->get_order_bill($order_id, $shop_id);
                $bill_id = $bill['id'];
                $bill_items = $this->bill_item_model->get_bill_items($shop_id, $bill_id);
            }
        } else {
            $bill_items = $this->bill_item_model->get_bill_items($shop_id, $bill_id);
        }
        echo (json_encode($bill_items));
    }

    public function update_item_price()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $table_id = intval($this->input->get("table_id"));
        $item_id = intval($this->input->get("item_id"));
        $price = intval($this->input->get("price"));
        $this->load->model('order_model');
        $order_id = $this->order_model->get_last_open_order_by_table($shop_id, $table_id);
        $this->load->model('bill_item_model');
        $this->bill_item_model->update_item_price($shop_id, $order_id, $item_id, $price);
    }

    public function add_bill_item()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $table_id = intval($this->input->get("table_id"));
        $product_id = intval($this->input->get("product_id"));
        $quantity = intval($this->input->get("quantity"));
        $order_no = std($this->input->get("order_no"));

        $tag = std($this->input->get("tag"));

        if ($tag) {
            $tags = array();
            $tags[] = $tag;
            $tags = json_encode($tags);
        } else {
            $tags = '';
        }

        $this->load->model('order_model');
        $this->load->model('product_model');
        $product = $this->product_model->get_product($product_id, $shop_id);
        if (!$product) {
            return;
        }


        $order_id = $this->order_model->get_last_open_order_by_table($shop_id, $table_id);
        if ($order_id == 0) { //Add order and bill
            $params = array();
            $params['order_type'] = 'B';
            $params['room_table'] = $table_id;
            $params['shop_id'] = $shop_id;
            $params['order_date'] = date('Y-m-d', strtotime('now'));
            $params['customer_id'] = 0;
            $params['order_time'] = date('Y-m-d H:i:s', strtotime('now'));
            $params['create_user'] = $this->user->user_id;
            $params['last_user'] = $this->user->user_id;
            $params['last_update'] = date('Y-m-d H:i:s', strtotime('now'));
            $params['user_id'] = $this->user->user_id;

            $params['order_no'] = $order_no;
            $params['order_name'] = $order_no;

            $order_id = $this->order_model->add_order($params);


            $params = array();
            $params['shop_id'] = $shop_id;
            $params['order_id'] = $order_id;
            $params['bill_date'] = date('Y-m-d H:i:s', strtotime('now'));
            $params['cashier'] = $this->user->email;

            $params['bill_time'] = date('H:i:s', strtotime('now'));
            $params['create_user'] = $this->user->user_id;
            $params['last_user'] = $this->user->user_id;
            $params['last_update'] = date('Y-m-d H:i:s', strtotime('now'));
            $this->load->model('bill_model');
            $bill_id = $this->bill_model->add_bill($params);


            $this->load->model('bill_item_model');
            $params = array();
            $params['shop_id'] = $shop_id;
            $params['bill_id'] = $bill_id;
            $params['order_id'] = $order_id;
            $params['product_id'] = $product['id'];
            $params['product_name'] = $product['product_name'];
            $params['quantity'] = $quantity;
            $params['create_user'] = $this->user->user_id;
            $params['last_user'] = $this->user->user_id;
            $params['last_update'] = date('Y-m-d H:i:s', strtotime('now'));
            $params['price'] = $product['list_price'];
            $params['amount'] = $product['list_price'] * $quantity;
            $params['tag'] = $tags;

            $id = $this->bill_item_model->add_bill_item($params);

            $bill_item = $this->bill_item_model->get_bill_item($id, $shop_id);
            echo (json_encode($bill_item));
        } else { //add item
            $this->load->model('bill_model');
            $bill = $this->bill_model->get_order_bill($order_id, $shop_id);
            if (!$bill) {
                return;
            }
            $bill_id = $bill['id'];
            $this->load->model('bill_item_model');
            $product_id = $product['id'];
            $old_bill_item = $this->bill_item_model->get_same_product_bill_item($shop_id, $order_id, $bill_id, $product_id, $tags);
            if (!$old_bill_item) {
                $params = array();
                $params['shop_id'] = $shop_id;
                $params['bill_id'] = $bill_id;
                $params['order_id'] = $order_id;
                $params['product_id'] = $product['id'];
                $params['product_name'] = $product['product_name'];
                $params['quantity'] = $quantity;
                $params['create_user'] = $this->user->user_id;
                $params['last_user'] = $this->user->user_id;
                $params['last_update'] = date('Y-m-d H:i:s', strtotime('now'));
                $params['price'] = $product['list_price'];
                $params['amount'] = $product['list_price'] * $quantity;
                $params['tag'] = $tags;
                //echo($this->bill_item_model->add_bill_item($params));
                $id = $this->bill_item_model->add_bill_item($params);
            } else {
                $id = $old_bill_item['id'];
                $params = array();
                $params['quantity'] = $old_bill_item['quantity'] + 1;
                $params['amount'] = ($old_bill_item['quantity'] + 1) * $old_bill_item['price'];
                $params['last_update'] = date('Y-m-d H:i:s', strtotime('now'));
                $this->bill_item_model->update_bill_item($shop_id, $id, $params);
            }

            $bill_item = $this->bill_item_model->get_bill_item($id, $shop_id);
            echo (json_encode($bill_item));
        }

        //$this->product_model->update_product_stock2($shop_id, $product_id);


    }

    public function change_bill_item()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $table_id = intval($this->input->get("table_id"));
        $product_id = intval($this->input->get("product_id"));
        $quantity = intval($this->input->get("quantity"));
        $price = intval($this->input->get("price"));

        $this->load->model('bill_item_model');
        $params = array();
        $params['quantity'] = $quantity;
        $params['amount'] = $quantity * $price;
        $this->bill_item_model->update_bill_item($shop_id, $table_id, $product_id, $params);
    }

    public function remove_bill_item()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $item_id = intval($this->input->get("item_id"));
        $this->load->model('bill_item_model');

        $item = $this->bill_item_model->get_bill_item($item_id, $shop_id);

        $this->bill_item_model->remove_bill_item($shop_id, $item_id);

        $order_id = $item['order_id'];
        $count = $this->bill_item_model->count_order_bill_item($shop_id, $order_id);
        if ($count == 0) {
            $this->load->model('order_model');
            $this->order_model->delete_order($order_id, $shop_id);
        }
    }

    public function products_json()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }

        $shop_id = $this->user->shop_id;
        $this->load->library('product_lib');
        $products = $this->product_lib->get_shop_products_lite($shop_id);
        echo (json_encode($products));
    }

    function test_json()
    {
        $this->output->enable_profiler(TRUE);

        if ($this->check_user() === false) {
            $this->login();
            return;
        }

        $shop_id = $this->user->shop_id;
        $this->load->library('product_lib');
        $products = $this->product_lib->get_shop_products_lite($shop_id);
        echo (json_encode($products));
    }
    public function products_by_group_json()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->library('product_lib');
        $this->load->model('product_group_model');
        $product_groups = $this->product_group_model->get_all_product_groups($shop_id);
        $all_products = array();
        foreach ($product_groups as $product_group) {
            $products = $this->product_lib->get_shop_product_by_group($shop_id, $product_group['id']);
            $all_products[$product_group['id']] = $products;
        }
        $products = $this->product_lib->get_shop_products_lite($shop_id);
        $all_products["0"] = $products;
        echo (json_encode($all_products));
    }

    public function table_by_position_json()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('table_position_model');
        $this->load->model('room_table_model');
        $table_positions = $this->table_position_model->get_all_table_positions($shop_id);
        $all_tables = array();
        foreach ($table_positions as $table_position) {
            $tables = $this->room_table_model->get_room_table_by_position($shop_id, $table_position['id']);
            $all_tables[$table_position['id']] = $tables;
        }

        $tables = $this->room_table_model->get_all_room_tables($shop_id);
        $all_tables["0"] = $tables;
        echo (json_encode($all_tables));
    }

    public function tables()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $data = array();
        $shop_id = $this->user->shop_id;
        $this->load->model('room_table_model');
        $ipp = $this->config->item('ipp');
        $page = intval($this->input->get('page'));
        if ($page == 0) {
            $page = 1;
        }
        $data['page'] = $page;
        $offset = ($page - 1) * $ipp;

        $count = $this->room_table_model->count_room_tables($shop_id);
        $count = $count['c'];

        $pages = intval($count / $ipp);
        if ($ipp * $pages < $count) {
            $pages++;
        }
        $data['pages'] = $pages;

        $tables = $this->room_table_model->get_all_room_tables($shop_id, $ipp, $offset);

        $data['title'] = tb_word('tables');
        $data['user']  = $this->user;
        $data["url"] = $this->config->base_url();
        $this->load->model('init_data_model');
        $init_data = $this->init_data_model->get_init_data1($shop_id);
        $data['init_data'] = $init_data;

        $this->load->view('headers/html_header', $data);
        $data['tables'] = $tables;
        $this->load->view('tables', $data);
        $this->load->view('headers/html_footer');
    }

    public function table_positions()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }

        $shop_id = $this->user->shop_id;
        $this->load->model('table_position_model');
        $table_positions = $this->table_position_model->get_all_table_positions($shop_id);

        $data = array();
        $data['title'] = tb_word('table_positions');
        $this->load->model('init_data_model');
        $init_data = $this->init_data_model->get_init_data1($shop_id);
        $data['init_data'] = $init_data;

        $data['user']  = $this->user;
        $data["url"] = $this->config->base_url();
        $this->load->view('headers/html_header', $data);
        $data['table_positions'] = $table_positions;
        $this->load->view('table_positions', $data);
        $this->load->view('headers/html_footer');
    }

    public function units()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }

        $shop_id = $this->user->shop_id;
        $this->load->model('unit_model');
        $table_positions = $this->unit_model->get_all_units($shop_id);

        $data = array();
        $data['title'] = 'Đơn vị tính';

        $data['user']  = $this->user;
        $data["url"] = $this->config->base_url();
        $this->load->view('headers/html_header', $data);
        $data['units'] = $table_positions;
        $this->load->view('units', $data);
        $this->load->view('headers/html_footer');
    }


    public function product_groups()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }

        $shop_id = $this->user->shop_id;

        $this->load->model('product_group_model');
        $product_groups = $this->product_group_model->get_all_product_groups($shop_id);

        $data = array();
        $this->load->model('init_data_model');
        $init_data = $this->init_data_model->get_init_data1($shop_id);
        $data['init_data'] = $init_data;

        $data['title'] = tb_word('product_groups');
        $data['user']  = $this->user;
        $data["url"] = $this->config->base_url();
        // $this->load->view('headers/html_header', $data);
        $data['product_groups'] = $product_groups;
        //toandk2 sửa

        if (check_user_agent('mobile')) {
            $data['title_header'] = 'Nhóm sản phẩm';
            $data['icon_header'] = 'images/orderapp/export_mobile.png';
            $this->load->view('mobile_views/html_header_app', $data);
            $this->load->view('mobile_views/product_groups', $data);
        } else {
            $this->load->view('headers/html_header', $data);
            $this->load->view('product_groups', $data);
            $this->load->view('headers/html_footer');
        }

        // $this->load->view('product_groups', $data);
        // $this->load->view('headers/html_footer');
    }


    public function split_bill()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        //$order_id = intval($this->input->get("order_id"));

        $table_id = intval($this->input->get("table_id"));
        $this->load->model('order_model');
        $order_id = $this->order_model->get_last_open_order_by_table($shop_id, $table_id);


        $items = $this->input->get("items");

        $params = array();
        $params['shop_id'] = $shop_id;
        $params['order_id'] = $order_id;
        $params['bill_date'] = date('Y-m-d H:i:s', strtotime('now'));
        $params['cashier'] = $this->user->email;

        $params['bill_time'] = date('H:i:s', strtotime('now'));
        $params['create_user'] = $this->user->user_id;
        $params['last_user'] = $this->user->user_id;
        $params['last_update'] = date('Y-m-d H:i:s', strtotime('now'));

        $this->load->model('bill_model');
        $bill_id = $this->bill_model->add_bill($params);

        $this->load->model('bill_item_model');

        foreach ($items as $item) {
            $params = array();
            $params['shop_id'] = $shop_id;
            $params['bill_id'] = $bill_id;
            $params['order_id'] = $order_id;
            $item_id = intval($item);
            $this->bill_item_model->update_bill_item($shop_id, $item_id, $params);
        }
        echo ($bill_id);
    }

    public function move_to()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $table_from_id = intval($this->input->get("table_from_id"));
        $table_to_id = intval($this->input->get("table_to_id"));

        $items = $this->input->get("items");
        $this->load->model('order_model');
        $this->load->model('bill_model');

        $order_from_id = $this->order_model->get_last_open_order_by_table($shop_id, $table_from_id);
        $order_id = $this->order_model->get_last_open_order_by_table($shop_id, $table_to_id);
        echo ($order_id);
        if ($order_id == 0) {
            //xxx

            $params = array();
            $params['order_type'] = 'B';
            $params['room_table'] = $table_to_id;
            $params['shop_id'] = $shop_id;
            $params['order_date'] = date('Y-m-d', strtotime('now'));
            $params['customer_id'] = 0;
            $params['order_time'] = date('Y-m-d H:i:s', strtotime('now'));
            $params['create_user'] = $this->user->user_id;
            $params['last_user'] = $this->user->user_id;
            $params['last_update'] = date('Y-m-d H:i:s', strtotime('now'));
            $params['user_id'] = $this->user->user_id;
            $order_id = $this->order_model->add_order($params);

            $params = array();
            $params['shop_id'] = $shop_id;
            $params['order_id'] = $order_id;
            $params['bill_date'] = date('Y-m-d H:i:s', strtotime('now'));
            $params['cashier'] = $this->user->email;

            $params['bill_time'] = date('H:i:s', strtotime('now'));
            $params['create_user'] = $this->user->user_id;
            $params['last_user'] = $this->user->user_id;
            $params['last_update'] = date('Y-m-d H:i:s', strtotime('now'));

            $bill_id = $this->bill_model->add_bill($params);
        } else {
            $bill = $this->bill_model->get_order_bill($order_id, $shop_id);
            if (!$bill) {
                return;
            }
            $bill_id = $bill['id'];
        }

        $this->load->model('bill_item_model');
        foreach ($items as $item) {
            $params = array();
            $params['shop_id'] = $shop_id;
            $params['bill_id'] = $bill_id;
            $params['order_id'] = $order_id;
            $item_id = intval($item);
            $this->bill_item_model->update_bill_item($shop_id, $item_id, $params);
        }


        $items = $this->bill_item_model->get_order_bill_items($shop_id, $order_from_id);
        if (count($items) == 0) {
            $params = array();
            $params['status1'] = 4;
            $this->order_model->update_order($order_from_id, $shop_id, $params);
        }
    }

    public function table()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('room_table_model');
        $this->load->model('table_position_model');
        $table_position = $this->table_position_model->get_all_table_positions($shop_id);


        $id = intval($this->input->get("id"));
        //$type = intval($this->input->get("type"));
        if ($id > 0) {
            if (empty($_POST)) {
                $table = $this->room_table_model->get_room_table($id, $shop_id);
                $data['title'] = tb_word('table');
                $data['user'] = $this->user;
                $this->load->view('headers/html_header', $data);
                $data['table'] = $table;
                $data['id'] = $id;
                $data["url"] = $this->config->base_url();
                //$data['table_position'] = $table_position;
                $table_position_dropdown = generate_dropdown_1('table_position', '', $table_position, 'id', 'name', $table['position'], NULL, NULL);
                $data['table_position_dropdown'] = $table_position_dropdown;

                $this->load->view('table', $data);
                $this->load->view('headers/html_footer');
            } else { //do update
                $name = std($this->input->post("name"));
                $description = std($this->input->post("description"));
                $position = intval($this->input->post("table_position"));
                $sits = intval($this->input->post("sits"));
                $type = intval($this->input->post("type0"));
                $params = array();
                $params['name'] = $name;
                $params['description'] = $description;
                $params['position'] = $position;
                $params['type'] = $type;
                $params['sits'] = $sits;
                //$data['table_position'] = $table_position;

                $this->room_table_model->update_room_table($id, $shop_id, $params);
                $this->load->helper('url');
                $url = $this->config->base_url();
                redirect($url . 'index.php/home/tables');
            }
        } else {
            if (empty($_POST)) {
                $data['title'] = tb_word('table');
                $data['user'] = $this->user;
                //$data['table_position'] = $table_position;
                $table_position_dropdown = generate_dropdown_1('table_position', '', $table_position, 'id', 'name', NULL, NULL, NULL);
                $data['table_position_dropdown'] = $table_position_dropdown;
                $data["url"] = $this->config->base_url();
                $data["id"] = 0;
                $this->load->view('headers/html_header', $data);
                $this->load->view('table', $data);
                $this->load->view('headers/html_footer');
            } else { //do add
                //$this->output->enable_profiler(TRUE);
                $name = std($this->input->post("name"));
                $description = std($this->input->post("description"));
                $position = intval($this->input->post("table_position"));
                $sits = intval($this->input->post("sits"));
                $type = intval($this->input->post("type0"));
                $params = array();
                $params['shop_id'] = $shop_id;
                $params['name'] = $name;
                $params['position'] = $position;
                $params['description'] = $description;
                $params['type'] = $type;
                $params['sits'] = $sits;

                $this->room_table_model->add_room_table($params);

                $params = array();
                $this->load->model('shop_model');
                $params['table'] = 1;
                $this->shop_model->update_shop($shop_id, $params);

                redirect('/tables');
            }
        }
    }

    public function table_position()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('table_position_model');

        $id = intval($this->input->get("id"));
        //$type = intval($this->input->get("type"));
        if ($id > 0) {
            if (empty($_POST)) {
                $table_position = $this->table_position_model->get_table_position($id, $shop_id);
                $data['title'] = tb_word('table_position');
                $data['user'] = $this->user;
                $data['id'] = $id;
                $data["url"] = $this->config->base_url();
                $this->load->view('headers/html_header', $data);
                $data['table_position'] = $table_position;
                $this->load->view('table_position', $data);
                $this->load->view('headers/html_footer');
            } else { //do update
                $name = std($this->input->post("name"));
                $description = std($this->input->post("description"));
                $params = array();
                $params['name'] = $name;
                $params['description'] = $description;

                $this->table_position_model->update_table_position($id, $shop_id, $params);
                $this->load->helper('url');
                $url = $this->config->base_url();
                redirect($url . 'index.php/home/table_positions');
            }
        } else {
            if (empty($_POST)) {
                $data['title'] = tb_word('table');
                $data['user'] = $this->user;
                $data['id'] = 0;
                $data["url"] = $this->config->base_url();
                $this->load->view('headers/html_header', $data);
                $this->load->view('table_position', $data);
                $this->load->view('headers/html_footer');
            } else { //do add
                //$this->output->enable_profiler(TRUE);
                $name = std($this->input->post("name"));
                $description = std($this->input->post("description"));
                $params = array();
                $params['shop_id'] = $shop_id;
                $params['name'] = $name;
                $params['description'] = $description;

                $this->table_position_model->add_table_position($shop_id, $params);


                $params = array();
                $this->load->model('shop_model');
                $params['position'] = 1;
                $this->shop_model->update_shop($shop_id, $params);


                redirect('/table_positions');
            }
        }
    }

    public function unit()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('unit_model');

        $id = intval($this->input->get("id"));
        //$type = intval($this->input->get("type"));
        if ($id > 0) {
            if (empty($_POST)) {
                $unit = $this->unit_model->get_unit($id, $shop_id);

                $parent_units = $this->unit_model->get_root_units($shop_id);
                $data['parent_units'] = $parent_units;
                $parent_unit_dropdown = generate_dropdown_2('parent', '', $parent_units, 'id', 'name', $unit['parent'], NULL, 1, 'Gốc');
                $data['parent_unit_dropdown'] = $parent_unit_dropdown;


                $data['title'] = 'Đơn vị';
                $data['user'] = $this->user;
                $data['id'] = $id;
                $data["url"] = $this->config->base_url();
                $this->load->view('headers/html_header', $data);
                $data['unit'] = $unit;
                $this->load->view('unit', $data);
                $this->load->view('headers/html_footer');
            } else { //do update
                $name = std($this->input->post("name"));
                $ratio = intval($this->input->post("ratio"));
                $parent = intval($this->input->post("parent"));
                $params = array();
                $params['name'] = $name;
                $params['ratio'] = $ratio;
                $params['parent'] = $parent;

                $this->unit_model->update_unit($id, $shop_id, $params);
                $this->load->helper('url');
                redirect('/units');
            }
        } else {
            if (empty($_POST)) {
                $data['title'] = tb_word('table');
                $data['user'] = $this->user;
                $data['id'] = 0;
                $parent_units = $this->unit_model->get_root_units($shop_id);
                $parent_unit_dropdown = generate_dropdown_2('parent', '', $parent_units, 'id', 'name', 0, NULL, 1, 'Gốc');
                $data['parent_unit_dropdown'] = $parent_unit_dropdown;
                $data['parent_units'] = $parent_units;

                $data["url"] = $this->config->base_url();
                $this->load->view('headers/html_header', $data);
                $this->load->view('unit', $data);
                $this->load->view('headers/html_footer');
            } else { //do add
                //$this->output->enable_profiler(TRUE);
                $name = std($this->input->post("name"));
                $ratio = intval($this->input->post("ratio"));
                $parent = intval($this->input->post("parent"));
                $params = array();
                $params['name'] = $name;
                $params['ratio'] = $ratio;
                $params['parent'] = $parent;
                $params['shop_id'] = $shop_id;

                $this->unit_model->add_unit($params);
                $this->load->helper('url');
                redirect('/units');
            }
        }
    }


    public function delete_table()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('room_table_model');

        $id = intval($this->input->get("id"));
        $this->room_table_model->delete_room_table($id, $shop_id);
        $this->load->helper('url');
        $url = $this->config->base_url();
        redirect($url . 'index.php/home/tables');
    }


    public function delete_product_group()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('product_group_model');

        $id = intval($this->input->get("id"));
        $this->product_group_model->delete_product_group($id, $shop_id);
        $this->load->helper('url');
        $url = $this->config->base_url();
        redirect($url . 'index.php/home/product_groups');
    }

    public function delete_table_position()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('table_position_model');

        $id = intval($this->input->get("id"));
        $this->table_position_model->delete_table_position($id, $shop_id);
        $this->load->helper('url');
        $url = $this->config->base_url();
        redirect($url . 'index.php/home/table_positions');
    }


    public function product_group()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('product_group_model');
        $url = $this->config->base_url();

        $id = intval($this->input->get("id"));
        if ($id > 0) {
            if (empty($_POST)) {
                $product_group = $this->product_group_model->get_product_group($id, $shop_id);
                $data['title'] = tb_word('product_group');
                $data['user'] = $this->user;

                $data['product_group'] = $product_group;
                $data['url'] = $url;
                $data['id'] = $id;
                //toandk2 sửa

                if (check_user_agent('mobile')) {
                    $data['title_header'] = 'Sửa nhóm sản phẩm';
                    $this->load->view('mobile_views/html_header_app', $data);
                    $this->load->view('mobile_views/product_group', $data);
                } else {
                    $this->load->view('headers/html_header', $data);
                    $this->load->view('product_group', $data);
                    $this->load->view('headers/html_footer');
                }
            } else { //do update
                $name = std($this->input->post("name"));
                $type = intval($this->input->post("type"));

                $params = array();
                $params['name'] = $name;
                $params['shop_id'] = $shop_id;
                $params['type'] = $type;
                $this->product_group_model->update_product_group($id, $shop_id, $params);

                redirect($url . 'index.php/home/product_groups');
            }
        } else {
            if (empty($_POST)) {
                $data['title'] = tb_word('table');
                $data['user'] = $this->user;
                $data['url'] = $url;
                $data['id'] = 0;
                // $this->load->view('product_group', $data);
                // $this->load->view('headers/html_footer');

                //toandk2 sửa

                if (check_user_agent('mobile')) {
                    $data['title_header'] = 'Thêm mới nhóm sản phẩm';
                     $data['icon_header'] = 'images/orderapp/export_mobile.png';
                    $this->load->view('mobile_views/html_header_app', $data);
                    $this->load->view('mobile_views/product_group', $data);
                } else {
                    $this->load->view('headers/html_header', $data);
                    $this->load->view('product_group', $data);
                    $this->load->view('headers/html_footer');
                }
            } else { //do add
                //$this->output->enable_profiler(TRUE);
                $name = std($this->input->post("name"));
                $type = intval($this->input->post("type"));
                $params = array();
                $params['shop_id'] = $shop_id;
                $params['name'] = $name;
                $params['type'] = $type;
                $this->product_group_model->add_product_group($params);

                $params = array();
                $this->load->model('shop_model');
                $params['product_group'] = 1;
                $this->shop_model->update_shop($shop_id, $params);

                redirect($url . 'index.php/home/product_groups');
            }
        }
    }

    public function inform_to_kitchen()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }

        $shop_id = $this->user->shop_id;
        $table_id = intval($this->input->get("table_id"));
        $items = $this->input->get("items");
        $status = intval($this->input->get("status"));

        $this->load->model('bill_item_model');
        $this->bill_item_model->inform_item_kitchen($shop_id, $items, $status);
    }

    public function kitchen()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('bill_item_model');

        $statuses = array(-2, 1);
        $items = $this->bill_item_model->get_item_by_statuses($shop_id, $statuses);

        $data = array();
        $data['title'] = tb_word('kitchen');
        $data["user"] = $this->user;

        $data["url"] = $this->config->base_url();

        $this->load->view('headers/html_header', $data);
        $data["items"] = $items;
        $this->load->view('kitchen', $data);
        $this->load->view('headers/html_footer');
    }

    public function bar()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('bill_item_model');

        $statuses = array(-3, 5);
        $items = $this->bill_item_model->get_item_by_statuses($shop_id, $statuses);

        $data = array();
        $data['title'] = 'Quầy bar';
        $data["user"] = $this->user;

        $data["url"] = $this->config->base_url();

        $this->load->view('headers/html_header', $data);
        $data["items"] = $items;
        $this->load->view('bar', $data);
        $this->load->view('headers/html_footer');
    }


    public function serve()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('bill_item_model');

        $items = $this->bill_item_model->get_item_by_status($shop_id, 2);

        $data = array();
        $data['title'] = tb_word('kitchen');
        $data["user"] = $this->user;

        $data["url"] = $this->config->base_url();

        $this->load->view('headers/html_header', $data);
        $data["items"] = $items;
        $this->load->view('serve', $data);
        $this->load->view('headers/html_footer');
    }

    public function done_cooking()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $item_id = intval($this->input->get("item_id"));
        $this->load->model('bill_item_model');
        $this->bill_item_model->done_cooking($shop_id, $item_id, 0);
    }
    public function done_process()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $item_id = intval($this->input->get("item_id"));
        $this->load->model('bill_item_model');
        $this->bill_item_model->done_cooking($shop_id, $item_id, 1);
    }

    public function stop_serving()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $item_id = intval($this->input->get("item_id"));
        $product_id = intval($this->input->get("product_id"));
        $remain = intval($this->input->get("remain"));
        $quantity = intval($this->input->get("quantity"));

        $this->update_quantity($shop_id, $item_id, $remain);
        if ($remain == 0) {
            $params['product_status'] = 0;
            $this->load->model('product_model');
            $this->product_model->update_product($product_id, $shop_id, $params);
        }
        $this->load->model('bill_item_model');
        $this->bill_item_model->stop_serving($shop_id, $item_id, 0);
    }

    public function stop_serving_bar()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $item_id = intval($this->input->get("item_id"));
        $product_id = intval($this->input->get("product_id"));
        $remain = intval($this->input->get("remain"));
        $quantity = intval($this->input->get("quantity"));

        $this->update_quantity($shop_id, $item_id, $remain);
        if ($remain == 0) {
            $params['product_status'] = 0;
            $this->load->model('product_model');
            $this->product_model->update_product($product_id, $shop_id, $params);
        }

        $this->load->model('bill_item_model');
        $this->bill_item_model->stop_serving($shop_id, $item_id, 1);
    }


    public function done_serve()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $item_id = intval($this->input->get("item_id"));
        $this->load->model('bill_item_model');
        $this->bill_item_model->done_serve($shop_id, $item_id);
    }

    public function returns()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $item_id = intval($this->input->get("item_id"));
        $type = intval($this->input->get("type"));
        $this->load->model('bill_item_model');
        $this->bill_item_model->return($shop_id, $item_id, $type);
    }

    function delete_order()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        if ($this->user->row['user_group'] != 'admin') {
            return;
        }

        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->post("order_id"));
        $type = intval($this->input->post("type"));
        $this->load->model('order_model');


        $this->load->model('bill_model');
        $this->load->model('bill_item_model');
        $bill = $this->bill_model->get_order_bill($order_id, $shop_id);

        $bill_items = $this->bill_item_model->get_bill_items($shop_id, $bill['id']);

        foreach ($bill_items as $item) {
            $this->bill_item_model->return($shop_id, $item['id'], $type);
        }
        $this->order_model->delete_order($order_id, $shop_id);
    }

    public function rt()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $item_id = intval($this->input->get("item_id"));
        $type = intval($this->input->get("type"));
        $this->load->model('bill_item_model');
        $this->bill_item_model->return($shop_id, $item_id, $type);
        $bill_item = $this->bill_item_model->get_bill_item($item_id, $shop_id);
        $start_date = $bill_item['start_date'];
        $end_date = $bill_item['end_date'];
        $product_id = $bill_item['product_id'];
        $this->load->model('occupy_model');
        $this->occupy_model->return_booking($shop_id, $product_id, $start_date, $end_date);
    }



    public function plus()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $item_id = intval($this->input->get("item_id"));
        $quantity = intval($this->input->get("quantity"));
        if ($quantity == 0) {
            $quantity = 1;
        }
        $this->load->model('bill_item_model');
        $this->bill_item_model->plus($shop_id, $item_id, $quantity);
    }


    public function gettags()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $product_id = intval($this->input->get("product_id"));
        $this->load->model('product_tag_model');
        $product_tags = $this->product_tag_model->get_one_product_tags($shop_id, $product_id);

        $tbl   = new my_table('list');
        $tbl->set_caption(tb_word('product tags'));
        $tbl->set_config('width', '100%', 'mydata');
        $tbl->header_on();
        $tbl->remove_columns();
        $class = 'member';
        $tbl->set_translate(TRUE);
        $tbl->add_column($tbl->col_by_data('tag|tag|100%|left'));
        $tbl->set_defaults();
        $tbl->add_header();

        if (is_array($product_tags) == true) {
            foreach ($product_tags as $product_tag) {
                $a = array();
                $a[] = $product_tag['name'];
                $tbl->add_row($a);
            }
        }
        $a = array();
        $a[] = '<input id="tag" type="text" style="width:350px;">  <button onclick="javascript:addTag(' . $product_id . ')" type="button" class="btn btn-default" >' . tb_word('add tag') . '</button>';
        $tbl->add_row($a);

        $tbl->add_end();
    }

    public function addtag()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $product_id = intval($this->input->get("product_id"));
        $tag   = std($this->input->get('tag'));
        $this->load->model('product_tag_model');
        $params = array();
        $params['shop_id'] = $shop_id;
        $params['product_id'] = $product_id;
        $params['name'] = $tag;

        $this->product_tag_model->add_product_tag($params);
    }

    public function savetag()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $product_id = intval($this->input->get("product_id"));
        $this->load->model('product_tag_model');
        $this->load->model('product_model');
        $product_tags = $this->product_tag_model->get_one_product_tags($shop_id, $product_id);
        $tags = array();
        foreach ($product_tags as $product_tag) {
            $tags[] = $product_tag['name'];
        }

        $params = array();
        $params['tags'] = json_encode($tags);

        $this->product_model->update_product($product_id, $shop_id, $params);
    }

    public function additemtag()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        //$product_id = intval($this->input->get("product_id"));
        $item_id = intval($this->input->get("item_id"));
        $tag   = std($this->input->get('tag'));
        $this->load->model('bill_item_model');
        $item = $this->bill_item_model->get_bill_item($item_id, $shop_id);
        if (!$item) {
            echo (1);
            return;
        }

        if ($item['tag'] != null || $item['tag'] != '') {
            $tags = json_decode($item['tag']);
            if (in_array($tag, $tags)) {
                echo (1);
                return;
            }
        }
        $tags[] = $tag;
        $tag = json_encode($tags);

        $params = array();
        $params['tag'] = $tag;

        $this->bill_item_model->update_bill_item($shop_id, $item_id, $params);
        echo (0);
    }

    public function removeitemtag()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        //$product_id = intval($this->input->get("product_id"));
        $item_id = intval($this->input->get("item_id"));
        $tag   = std($this->input->get('tag'));
        $this->load->model('bill_item_model');
        $item = $this->bill_item_model->get_bill_item($item_id, $shop_id);
        if (!$item) {
            return;
        }

        if ($item['tag'] != null || $item['tag'] != '') {
            $tags = json_decode($item['tag']);
            if (in_array($tag, $tags)) {
                $tags = array_diff($tags, [$tag]);
            }
        }
        //$tags[] = $tag;
        $tag = json_encode($tags);

        $params = array();
        $params['tag'] = $tag;

        $this->bill_item_model->update_bill_item($shop_id, $item_id, $params);
    }
    public function a1()
    {
        $this->load->model('init_data_model');
        $row = $this->init_data_model->get_init_data(296);
        var_dump($row);
    }

    public function init_table_data()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }

        $shop_type = $this->user->shop['type'];
        $data['title'] = tb_word('Init data');
        $shop_id = $this->user->shop_id;
        $this->load->model('init_data_model');
        if ($this->init_data_model->get_init_data($shop_id) != null) {
            $data['message'] = tb_word('init data failled1');
            $data["user"] = $this->user;

            $this->load->view('headers/html_header', $data);
            $this->load->view('init', $data);
            $this->load->view('headers/html_footer');
            return;
        }

        $this->load->model('shop_user_model');

        try {
            $result = $this->shop_user_model->init_table_data($shop_id, $shop_type);
            if ($result == "") {
                $data['message'] = tb_word('init data successfully');
                $params = array();
                $params['shop_id'] = $shop_id;
                $this->init_data_model->add_init_data($params);
            } else {
                $data['message'] = tb_word('init data failled1');
            }
        } catch (Exception $ex) {
            $data['message'] = tb_word('init data failled1');
        }

        $data["user"] = $this->user;

        $this->load->view('headers/html_header', $data);
        $this->load->view('init', $data);
        $this->load->view('headers/html_footer');
    }

    function customer_autocomplete()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }

        $shop_id = $this->user->shop_id;
        $type   = intval($this->input->get('type'));
        $this->load->model('customer_model');
        $customers = $this->customer_model->get_all_customers($shop_id, $type);
        $data = array();
        $data['customers'] = $customers;
        $this->load->view('customer_autocomplete', $data);
    }

    public function report()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $data = array();
        $data['title'] = tb_word('Init data');
        $start_date = date('01/m/Y');
        $end_date = date("d/m/Y");
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;
        $data['shop_id'] = $shop_id;

        $data["user"] = $this->user;
        $this->load->view('headers/html_header', $data);
        if (empty($_POST)) {
            if ($this->input->get('start_date') && intval($this->input->get('type')) == 17) {

                $start_date = std($this->input->get('start_date'));
                $end_date = std($this->input->get('end_date'));

                $sdate = std($this->input->get('start_date'));
                $edate = std($this->input->get('end_date'));

                $this->load->model('occupy_model');

                $checkin_from = std($this->input->get('checkin_from_date'));
                $checkin_from = date_create_from_format('d/m/Y', $checkin_from);
                if (!is_bool($checkin_from)) {
                    $checkin_from = $checkin_from->format('Y-m-d');
                } else {
                    $checkin_from = '';
                }

                $checkin_to = std($this->input->get('checkin_to_date'));
                $checkin_to = date_create_from_format('d/m/Y', $checkin_to);
                if (!is_bool($checkin_to)) {
                    $checkin_to = $checkin_to->format('Y-m-d');
                } else {
                    $checkin_to = '';
                }

                $rows = $this->occupy_model->occupy_report($shop_id, $start_date, $end_date, $checkin_from, $checkin_to);
                //echo(json_encode($rows));
                $orders = $this->occupy_model->get_product_checkin_range($shop_id, $checkin_from, $checkin_to);
                //echo(json_encode($orders));
                $data['rows'] = $rows;
                $data['orders'] = $orders;
                $data['start_date'] = date('d/m/Y', strtotime($start_date));
                $data['end_date'] = date('d/m/Y', strtotime($end_date));
                $data['s_date'] = strtotime($start_date);
                $data['e_date'] = strtotime($end_date);
                $data['checkin_from'] = $this->input->post('checkin_from_date');
                $data['checkin_to'] = $this->input->post('checkin_to_date');

                $init_type = intval($this->input->get('type'));
                $data["init_type"] = $init_type;
                $this->load->view('report', $data);
                $this->load->model('occupy_order_model');

                $occupy_orders = $this->occupy_order_model->select_occupy_order($shop_id, $sdate, $edate);
                $data['occupy_orders'] = $occupy_orders;
                $this->load->model('product_model');
                $nogs = $this->product_model->get_current_product_nog($shop_id);
                $data['nogs'] = $nogs;
                $this->load->view('report17', $data);
                return;
            }
            $this->load->model('product_model');
            $products = $this->product_model->get_shop_products($shop_id);
            $data["products"] = $products;
            $data["shop"] = $shop_id;
            $init_type = intval($this->input->get('type'));
            $data["init_type"] = $init_type;
            $this->load->view('report', $data);
        } else {
            $type = intval($this->input->post('type'));
            $currency = std($this->input->post('currency'));
            $start_date = std($this->input->post('start_date'));
            $start_date = date_create_from_format('d/m/Y', $start_date);
            if (!is_bool($start_date)) {
                $start_date = $start_date->format('Y-m-d');
            }

            $end_date = std($this->input->post('end_date'));
            $end_date = date_create_from_format('d/m/Y', $end_date);
            if (!is_bool($end_date)) {
                $end_date = $end_date->format('Y-m-d');
            }

            $paid = intval($this->input->post('paid'));

            $shop = intval($this->input->post('shop'));
            $data['shop'] = $shop;

            $this->load->model('report_model');
            if ($type == 1) {
                if ($shop == 0) {
                    $shop = $shop_id;
                } else {
                    if ($shop != -1) {
                        $this->load->model('shop_model');
                        if (!$this->shop_model->get_sub_shop($shop_id, $shop)) {
                            $shop = $shop_id;
                        }
                    }
                }
                if ($shop != -1) {
                    $orders = $this->report_model->report1($shop, $start_date, $end_date, $paid, $currency);
                    $data['orders'] = $orders;
                    $data['user'] = $this->user;
                    $this->load->view('report1', $data);
                } else {
                    $reports = array();

                    $report = array();
                    $orders = $this->report_model->report1($shop_id, $start_date, $end_date, $paid, $currency);
                    $report['shop_id'] = $shop_id;
                    $report['shop_name'] = 'Cửa hàng chính';
                    $report['orders'] = $orders;

                    $reports[] = $report;


                    $subs = null;
                    if (isset($this->user->shop['subs'])) {
                        $subs = json_decode($this->user->shop['subs'], true);
                    }
                    if ($subs) {
                        foreach ($subs as $sub) {
                            $report = array();
                            $report['shop_id'] = $sub['id'];
                            $report['shop_name'] = $sub['name'];
                            $orders = $this->report_model->report1($sub['id'], $start_date, $end_date, $paid, $currency);
                            $report['orders'] = $orders;
                            $reports[] = $report;
                        }
                    }
                    $data['reports'] = $reports;
                    $this->load->view('report1_1', $data);
                }
            }
            if ($type == 2) {
                if ($shop == 0) {
                    $shop = $shop_id;
                } else {
                    if ($shop != -1) {
                        $this->load->model('shop_model');
                        if (!$this->shop_model->get_sub_shop($shop_id, $shop)) {
                            $shop = $shop_id;
                        }
                    }
                }
                if ($shop != -1) {
                    $orders = $this->report_model->report2($shop, $start_date, $end_date, $paid, $currency);
                    $data['orders'] = $orders;
                    $data['user'] = $this->user;
                    $this->load->view('report2', $data);
                } else {
                    $reports = array();

                    $report = array();
                    $orders = $this->report_model->report2($shop_id, $start_date, $end_date, $paid, $currency);
                    $report['shop_id'] = $shop_id;
                    $report['shop_name'] = 'Cửa hàng chính';
                    $report['orders'] = $orders;

                    $reports[] = $report;

                    $subs = null;
                    if (isset($this->user->shop['subs'])) {
                        $subs = json_decode($this->user->shop['subs'], true);
                    }
                    if ($subs) {
                        foreach ($subs as $sub) {
                            $report = array();
                            $report['shop_id'] = $sub['id'];
                            $report['shop_name'] = $sub['name'];
                            $orders = $this->report_model->report2($sub['id'], $start_date, $end_date, $paid, $currency);
                            $report['orders'] = $orders;
                            $reports[] = $report;
                        }
                    }
                    $data['reports'] = $reports;
                    $this->load->view('report2_1', $data);
                }

                /*
                $orders = $this->report_model->report2($shop_id, $start_date, $end_date, $paid, $currency);
                $data['orders'] = $orders;
                $this->load->view('report2', $data);
                */
            }
            if ($type == 3) {
                if ($shop == 0) {
                    $shop = $shop_id;
                } else {
                    if ($shop != -1) {
                        $this->load->model('shop_model');
                        if (!$this->shop_model->get_sub_shop($shop_id, $shop)) {
                            $shop = $shop_id;
                        }
                    }
                }
                if ($shop != -1) {
                    $orders = $this->report_model->report3($shop, $start_date, $end_date, $paid, $currency);
                    $data['orders'] = $orders;
                    $data['user'] = $this->user;
                    $this->load->view('report3', $data);
                } else {
                    $reports = array();

                    $report = array();
                    $orders = $this->report_model->report3($shop_id, $start_date, $end_date, $paid, $currency);
                    $report['shop_id'] = $shop_id;
                    $report['shop_name'] = 'Cửa hàng chính';
                    $report['orders'] = $orders;

                    $reports[] = $report;

                    $subs = null;
                    if (isset($this->user->shop['subs'])) {
                        $subs = json_decode($this->user->shop['subs'], true);
                    }
                    if ($subs) {
                        foreach ($subs as $sub) {
                            $report = array();
                            $report['shop_id'] = $sub['id'];
                            $report['shop_name'] = $sub['name'];
                            $orders = $this->report_model->report3($sub['id'], $start_date, $end_date, $paid, $currency);
                            $report['orders'] = $orders;
                            $reports[] = $report;
                        }
                    }
                    $data['reports'] = $reports;
                    $this->load->view('report3_1', $data);
                }

                /*
                $orders = $this->report_model->report3($shop_id, $start_date, $end_date, $paid);
                $data['orders'] = $orders;
                $this->load->view('report3', $data);
                */
            }
            if ($type == 4) {
                $product_id = intval($this->input->post('product'));
                $orders = $this->report_model->report4($shop_id, $product_id, $start_date, $end_date);
                $data['orders'] = $orders;
                $this->load->view('report4', $data);
            }
            if ($type == 5) {
                $product_id = intval($this->input->post('product'));
                $orders = $this->report_model->report5($shop_id, $product_id, $start_date, $end_date);
                $data['orders'] = $orders;
                $this->load->view('report5', $data);
            }
            if ($type == 6) {
                $product_id = intval($this->input->post('product'));
                $orders = $this->report_model->report6($shop_id, $product_id, $start_date, $end_date);
                $data['orders'] = $orders;
                $this->load->view('report6', $data);
            }
            if ($type == 7) {
                if ($this->user->shop['type'] != 11) {
                    //error_reporting(-1);
                    //ini_set('display_errors', 1);
                    //$this->output->enable_profiler(TRUE);

                    $orders = $this->report_model->report7($shop_id, $start_date, $end_date);
                    $data['orders'] = $orders;

                    $this->load->model('shop_stock_model');

                    $start_date = date('Y-m-d', strtotime('-1 days', strtotime($start_date)));
                    $stock1 = $this->shop_stock_model->get_shop_stock_date($shop_id, $start_date);
                    //$stock2 = $this->shop_stock_model->get_shop_stock_date($shop_id, $end_date);

                    $data['stock1'] = $stock1;
                    //$data['stock2'] = $stock2;
                    $this->load->view('report7', $data);
                } else {
                    $orders = $this->report_model->report7_pharmacy($shop_id, $start_date, $end_date);
                    $data['orders'] = $orders;
                    $this->load->view('report7_pharm', $data);
                }
            }
            if ($type == 8) {
                $orders = $this->report_model->report8($shop_id, $start_date, $end_date);
                $data['orders'] = $orders;
                $this->load->view('report8', $data);
            }
            if ($type == 9) {
                $orders = $this->report_model->report9($shop_id, $start_date, $end_date);
                $data['orders'] = $orders;
                $this->load->view('report9', $data);
            }
            if ($type == 10) {
                $orders = $this->report_model->report10($shop_id, $start_date, $end_date);
                $data['orders'] = $orders;
                $this->load->view('report10', $data);
            }
            if ($type == 11) {
                $orders = $this->report_model->report11($shop_id, $start_date, $end_date);
                $data['orders'] = $orders;
                $this->load->view('report11', $data);
            }
            if ($type == 12) {
                $orders = $this->report_model->report12($shop_id, $start_date, $end_date);
                $data['orders'] = $orders;
                $this->load->view('report12', $data);
            }
            if ($type == 13) {
                $orders = $this->report_model->report13($shop_id, $start_date, $end_date);
                $data['orders'] = $orders;
                $this->load->view('report13', $data);
            }
            if ($type == 14) {
                $orders = $this->report_model->report14($shop_id, $start_date, $end_date);
                $data['orders'] = $orders;
                $this->load->view('report14', $data);
            }
            if ($type == 15) {
                $orders = $this->report_model->report14_1($shop_id, $start_date, $end_date);
                $data['orders'] = $orders;
                $this->load->view('report14', $data);
            }
            if ($type == 16) {
                $orders = $this->report_model->report14_0($shop_id, $start_date, $end_date);
                $data['orders'] = $orders;
                $this->load->view('report14', $data);
            }
            if ($type == 18) {
                //$this->output->enable_profiler(TRUE);
                $visitor_type = intval($this->input->post('visitor_type'));
                $keyword = std($this->input->post('keyword'));
                $visitors = $this->report_model->report18($shop_id, $keyword, $start_date, $end_date, $visitor_type);
                $data['visitors'] = $visitors;
                $data['start_date'] = $start_date;
                $data['end_date'] = $end_date;
                $data['visitor_type'] = $visitor_type;
                $this->load->view('report18', $data);
            }
            if ($type == 17) {
                $this->load->model('occupy_model');
                $keyword = std($this->input->post('keyword'));
                $checkin_from = std($this->input->post('checkin_from_date'));
                $checkin_from = date_create_from_format('d/m/Y', $checkin_from);
                if (!is_bool($checkin_from)) {
                    $checkin_from = $checkin_from->format('Y-m-d');
                } else {
                    $checkin_from = '';
                }

                $checkin_to = std($this->input->post('checkin_to_date'));
                $checkin_to = date_create_from_format('d/m/Y', $checkin_to);
                if (!is_bool($checkin_to)) {
                    $checkin_to = $checkin_to->format('Y-m-d');
                } else {
                    $checkin_to = '';
                }

                $rows = $this->occupy_model->occupy_report($shop_id, $start_date, $end_date, $checkin_from, $checkin_to, $keyword);
                //echo(json_encode($rows));
                $orders = $this->occupy_model->get_product_checkin_range($shop_id, $checkin_from, $checkin_to);
                //echo(json_encode($orders));
                $data['rows'] = $rows;
                $data['orders'] = $orders;
                $data['start_date'] = date('d/m/Y', strtotime($start_date));
                $data['end_date'] = date('d/m/Y', strtotime($end_date));
                $data['s_date'] = strtotime($start_date);
                $data['e_date'] = strtotime($end_date);
                $data['checkin_from'] = $this->input->post('checkin_from_date');
                $data['checkin_to'] = $this->input->post('checkin_to_date');
                $data['keyword'] = $this->input->post('keyword');

                $init_type = intval($this->input->get('type'));
                $data["init_type"] = $init_type;

                $this->load->view('report', $data);
                $this->load->model('occupy_order_model');
                //echo($start_date . '.' . $end_date);
                $occupy_orders = $this->occupy_order_model->select_occupy_order($shop_id, $start_date, $end_date);
                $data['occupy_orders'] = $occupy_orders;
                $this->load->model('product_model');
                $nogs = $this->product_model->get_current_product_nog($shop_id);
                $data['nogs'] = $nogs;
                //xxx17
                $this->load->view('report17', $data);
            }

            if ($type == 19) {
                //$this->output->enable_profiler(TRUE);
                $keyword = std($this->input->post('keyword'));
                $data['keyword'] = $keyword;
                $data['start_date'] = $start_date;
                $data['end_date'] = $end_date;
                $rows = $this->report_model->report19($shop_id, $start_date, $end_date, $keyword);
                $data['rows'] = $rows;

                $today = date('d/m/Y');
                $data["today"] = $today;
                $this->load->view('report19', $data);
            }

            if ($type == 20) {
                //$this->output->enable_profiler(TRUE);
                $keyword = std($this->input->post('keyword'));
                $data['keyword'] = $keyword;
                $rows = $this->report_model->report20($shop_id, $keyword);
                $data['rows'] = $rows;

                $today = date('d/m/Y');
                $data["today"] = $today;
                $this->load->view('report20', $data);
            }

            if ($type == 21) {
                //$this->output->enable_profiler(TRUE);

                $keyword = std($this->input->post('keyword'));
                $data['keyword'] = $keyword;
                $rows = $this->report_model->report21($shop_id, $keyword);
                $data['rows'] = $rows;

                $today = date('d/m/Y');
                $data["today"] = $today;
                $this->load->view('report21', $data);
            }
            if ($type == 22) {
                //$this->output->enable_profiler(TRUE);
                $keyword = std($this->input->post('keyword'));
                $data['keyword'] = $keyword;
                $rows = $this->report_model->report22($shop_id, $keyword);
                $data['rows'] = $rows;

                $today = date('d/m/Y');
                $data["today"] = $today;
                $this->load->view('report22', $data);
            }
            if ($type == 23) {
                $rows = $this->report_model->report23($shop_id, $start_date, $end_date);
                $data['rows'] = $rows;

                $today = date('d/m/Y');
                $data["today"] = $today;
                $this->load->view('report23', $data);
            }

            if ($type == 24) {
                $rows = $this->report_model->report24($shop_id);
                $data['rows'] = $rows;
                $this->load->view('report24', $data);
            }

            if ($type == 101) {
                //error_reporting(-1);
                //ini_set('display_errors', 1);
                //$this->output->enable_profiler(TRUE);

                $data = array();
                $rows = $this->report_model->report101($shop_id, $start_date, $end_date);
                $data['rows'] = $rows;
                $product_ids = array();
                foreach ($rows as $row) {
                    if (!in_array($row['product_id'], $product_ids)) {
                        $product_ids[] = $row['product_id'];
                    }
                }

                $stocks = $this->report_model->report101_stock($shop_id, $start_date, $end_date, $product_ids);
                $stock = array();
                foreach ($stocks as $row) {
                    $stock[$row['product_id'] . '-' . $row['date']] = $row['average_price'];
                }
                $data['stock'] = $stock;
                //echo(json_encode($stock));
                $this->load->view('report101', $data);
            }

            $data = array();
            $data['type'] = $type;
            $this->load->view('report_back', $data);
        }

        $this->load->view('headers/html_footer');
    }

    function report21()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $keyword = std($this->input->get('keyword'));
        $data['keyword'] = $keyword;
        $this->load->model('report_model');
        $rows = $this->report_model->report21($shop_id, $keyword);
        $data['rows'] = $rows;

        $this->load->view('report21_1', $data);
    }


    function report20()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $keyword = std($this->input->get('keyword'));
        $data['keyword'] = $keyword;
        $this->load->model('report_model');
        $rows = $this->report_model->report20($shop_id, $keyword);
        $data['rows'] = $rows;
        $this->load->view('report20_1', $data);
    }

    function report172()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $start_date = date('Y-m-d', $this->input->get('start_date'));
        $end_date = date('Y-m-d', $this->input->get('end_date'));
        $this->load->model('occupy_order_model');
        //$this->load->model('visitor_model');

        /*
        $items = $this->occupy_order_model->get_0_bill_items($shop_id, $start_date, $end_date);
        foreach($items as $item){
            $nogs = $this->visitor_model->count_bill_item_visitor($shop_id, $item['id'], $item['start_date'], $item['end_date']);
            $this->load->model('occupy_order_model');
            foreach($nogs as $nog){
                $date = $nog['date'];
                $count = $nog['count'];
                $this->occupy_order_model->update_occupy_order_nog($shop_id, $item['id'], $date, $count);
            }
            return;
        }
        */
        //return;


        $this->load->model('occupy_model');
        $rows = $this->occupy_model->occupy_report($shop_id, $start_date, $end_date, '', '', '');
        $data['rows'] = $rows;
        $data['start_date'] = date('d/m/Y', strtotime($start_date));
        $data['end_date'] = date('d/m/Y', strtotime($end_date));
        $data['s_date'] = strtotime($start_date);
        $data['e_date'] = strtotime($end_date);


        $occupy_orders = $this->occupy_order_model->select_occupy_order($shop_id, $start_date, $end_date);
        //echo(json_encode($occupy_orders));
        $data['occupy_orders'] = $occupy_orders;

        $this->load->view('report172', $data);
    }

    function report18()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $start_date = std($this->input->get('start_date'));
        $end_date = std($this->input->get('end_date'));
        $visitor_type = std($this->input->get('visitor_type'));
        $this->load->model('report_model');
        $visitors = $this->report_model->report18($shop_id, $start_date, $end_date, $visitor_type);
        $data['visitors'] = $visitors;
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;
        if ($visitor_type != 0) {
            $this->load->view('report181', $data);
        } else {
            $this->load->view('report182', $data);
        }
    }

    public function get_district()
    {
        $this->load->model('vietnam_model');
        $province_id = intval($this->input->get('province_id'));
        $districts = $this->vietnam_model->get_district($province_id);
        echo (json_encode($districts));
    }

    public function check_existence()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $id = intval($this->input->get('id'));
        $name = std($this->input->get('name'));
        $type = $this->input->get('type');
        if ($type == 'product_group') {
            $this->load->model('product_group_model');
            echo ($this->product_group_model->check_existence($shop_id, $name, $id));
            return;
        }

        if ($type == 'customer') {
            $phone = std($this->input->get('phone'));
            $this->load->model('customer_model');
            echo ($this->customer_model->check_existence($shop_id, $id, $name, $phone));
            return;
        }
        if ($type == 'position') {
            $this->load->model('table_position_model');
            echo ($this->table_position_model->check_existence($shop_id, $name, $id));
            return;
        }
        if ($type == 'table') {
            $this->load->model('room_table_model');
            echo ($this->room_table_model->check_existence($shop_id, $name, $id));
            return;
        }

        if ($type == 'product_code') {
            if ($code == '') {
                echo (0);
                return;
            }
            $this->load->model('product_model');
            $code = std($this->input->get('code'));
            echo ($this->product_model->check_code_existence($shop_id, $code, $id));
            return;
        }

        if ($type == 'product_name') {
            $this->load->model('product_model');
            $name = std($this->input->get('name'));
            if ($name == 'Đặt cọc') {
                echo (0);
                return;
            }
            echo ($this->product_model->check_existence($shop_id, $name, $id));
            return;
        }
        if ($type == 'user') {
            $this->load->model('shop_user_model');
            $email = std($this->input->get('email'));
            $id = intval($this->input->get('id'));
            echo ($this->shop_user_model->check_existence($shop_id, $email, $id));
            return;
        }
    }

    public function delete_data()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $data = array();
        if (!empty($_POST)) {
            $shop_type = intval($this->user->shop['type']);

            $arr = array();
            $arr['product'] = 'DELETE FROM `xxxxxx_products` WHERE id<=67;';
            $arr['product_group'] = 'DELETE FROM `xxxxxx_product_groups` WHERE id<=6';
            $arr['customer'] = 'DELETE FROM `xxxxxx_customers` WHERE id<=80';
            $arr['table_position'] = 'DELETE FROM `xxxxxx_table_positions` WHERE id<=4;';
            $arr['table'] = 'DELETE FROM `xxxxxx_room_tables` WHERE id<=17';

            if ($shop_type == 2) {
                $arr['product'] = 'DELETE FROM `xxxxxx_products` WHERE id<=100;';
                $arr['product_group'] = 'DELETE FROM `xxxxxx_product_groups` WHERE id<=12';
                $arr['customer'] = 'DELETE FROM `xxxxxx_customers` WHERE id<=88';
            }
            if ($shop_type == 3) {
                $arr['product'] = 'DELETE FROM `xxxxxx_products` WHERE id<=107;';
                $arr['product_group'] = 'DELETE FROM `xxxxxx_product_groups` WHERE id<=11';
                $arr['customer'] = 'DELETE FROM `xxxxxx_customers` WHERE id<=88';
            }
            if ($shop_type == 4) {
                $arr['product'] = 'DELETE FROM `xxxxxx_products` WHERE id<=123;';
                $arr['product_group'] = 'DELETE FROM `xxxxxx_product_groups` WHERE id<=19';
                $arr['customer'] = 'DELETE FROM `xxxxxx_customers` WHERE id<=95';
            }

            if ($shop_type == 5) {
                $arr['product'] = 'DELETE FROM `xxxxxx_products` WHERE id<=109;';
                $arr['product_group'] = 'DELETE FROM `xxxxxx_product_groups` WHERE id<=15';
                $arr['customer'] = 'DELETE FROM `xxxxxx_customers` WHERE id<=90';
            }

            if ($shop_type == 6) {
                $arr['product'] = 'DELETE FROM `xxxxxx_products` WHERE id<=92;';
                $arr['product_group'] = 'DELETE FROM `xxxxxx_product_groups` WHERE id<=11';
                $arr['customer'] = 'DELETE FROM `xxxxxx_customers` WHERE id<=83';
            }

            if ($shop_type == 7) {
                $arr['product'] = 'DELETE FROM `xxxxxx_products` WHERE id<=92;';
                $arr['product_group'] = 'DELETE FROM `xxxxxx_product_groups` WHERE id<=11';
                $arr['customer'] = 'DELETE FROM `xxxxxx_customers` WHERE id<=83';
            }

            if ($shop_type == 8) {
                $arr['product'] = 'DELETE FROM `xxxxxx_products` WHERE id<=90;';
                $arr['product_group'] = 'DELETE FROM `xxxxxx_product_groups` WHERE id<=11';
                $arr['customer'] = 'DELETE FROM `xxxxxx_customers` WHERE id<=83';
            }

            if ($shop_type == 9) {
                $arr['product'] = 'DELETE FROM `xxxxxx_products` WHERE id<=97;';
                $arr['product_group'] = 'DELETE FROM `xxxxxx_product_groups` WHERE id<=11';
                $arr['customer'] = 'DELETE FROM `xxxxxx_customers` WHERE id<=87';
            }

            if ($shop_type == 10) {
                $arr['product'] = 'DELETE FROM `xxxxxx_products` WHERE id<=97;';
                $arr['product_group'] = 'DELETE FROM `xxxxxx_product_groups` WHERE id<=10';
                $arr['customer'] = 'DELETE FROM `xxxxxx_customers` WHERE id<=86';
            }

            if ($shop_type == 11) {
                $arr['product'] = 'DELETE FROM `xxxxxx_products` WHERE id<=125;';
                $arr['product_group'] = 'DELETE FROM `xxxxxx_product_groups` WHERE id<=16';
                $arr['customer'] = 'DELETE FROM `xxxxxx_customers` WHERE id<=83';
            }

            if ($shop_type == 12) {
                $arr['product'] = 'DELETE FROM `xxxxxx_products` WHERE id<=86;';
                $arr['product_group'] = 'DELETE FROM `xxxxxx_product_groups` WHERE id<=11';
                $arr['customer'] = 'DELETE FROM `xxxxxx_customers` WHERE id<=83';
            }

            if ($shop_type == 13) {
                $arr['product'] = 'DELETE FROM `xxxxxx_products` WHERE id<=92;';
                $arr['product_group'] = 'DELETE FROM `xxxxxx_product_groups` WHERE id<=12';
                $arr['customer'] = 'DELETE FROM `xxxxxx_customers` WHERE id<=83';
            }

            //$result = $this->shop_user_model->delete_data($shop_id);
            $this->load->model('shop_user_model');

            if (intval($this->input->post('product_group')) > 0) {
                $this->shop_user_model->execute_query($shop_id, $arr['product_group']);
            }
            if (intval($this->input->post('product')) > 0) {
                $this->shop_user_model->execute_query($shop_id, $arr['product']);
            }
            if (intval($this->input->post('customer')) > 0) {
                $this->shop_user_model->execute_query($shop_id, $arr['customer']);
            }
            if (intval($this->input->post('table_position')) > 0) {
                $this->shop_user_model->execute_query($shop_id, $arr['table_position']);
            }
            if (intval($this->input->post('table')) > 0) {
                $this->shop_user_model->execute_query($shop_id, $arr['table']);
            }
            $data['message'] = "Dữ liệu chạy thử đã xóa!";
            $this->load->model("init_data_model");
            $this->init_data_model->delete_init_data($shop_id);
        }

        $data['title'] = 'Xóa dữ liệu chạy thử';
        $data["user"] = $this->user;

        $this->load->view('headers/html_header', $data);
        $this->load->view('delete_data', $data);
        $this->load->view('headers/html_footer');
    }
    public function users()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $data = array();
        $data['title'] = 'Quản trị người sử dụng';
        $data["user"] = $this->user;
        $data["url"] = $this->config->base_url();

        $this->load->model('category_model');
        $types = $this->category_model->get_user_type();
        $types[''] = 'Chủ cửa hàng';
        $this->load->model('shop_user_model');
        $users = $this->shop_user_model->users($shop_id);
        $data["users"] = $users;
        $data['types'] = $types;

        //toandk2 sửa
        if (check_user_agent('mobile')) {
            $data['title_header'] = 'Quản trị người sử dụng';
            $this->load->view('mobile_views/html_header_app', $data);
            $this->load->view('mobile_views/users', $data);
            $this->load->view('mobile_views/html_footer_app', $data);
        } else {
            $this->load->view('headers/html_header', $data);
            $this->load->view('users', $data);
            $this->load->view('headers/html_footer');
        }
    }

    public function user()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        //echo(json_encode($this->user));

        if ($this->user->row['user_role'] != 'shops.lists.user-roles.manager') {
            return;
        }
        if (!empty($_POST)) {
            $id = intval($this->input->get('id'));
            $params = array();
            if ($id != 0) {
                $phone = std($this->input->post('phone'));
                $email = std($this->input->post('email'));
                $password = $this->input->post('password');
                if ($password != '') {
                    $params['user_pass'] = dinhdq_encode($phone, $password);
                }
                $role = std($this->input->post('role'));
                $params['user_role'] = $role;
                $full_name = std($this->input->post('full_name'));
                $title = std($this->input->post('title'));
                $email = std($this->input->post('email'));


                $params['full_name'] = $full_name;
                $params['title'] = $title;
                $params['email'] = $email;
                $params['phone'] = $phone;
                $this->load->model('shop_user_model');
                $this->shop_user_model->update_user($id, $shop_id, $params);
            } else {
                $phone = std($this->input->post('phone'));
                $password = $this->input->post('password');
                if ($password != '') {
                    $params['user_pass'] = dinhdq_encode($phone, $password);
                }
                $role = std($this->input->post('role'));
                $params['user_role'] = $role;
                $full_name = std($this->input->post('full_name'));
                $title = std($this->input->post('title'));
                $email = std($this->input->post('email'));
                $phone = std($this->input->post('phone'));

                $params['email'] = $email;
                $params['full_name'] = $full_name;
                $params['title'] = $title;
                $params['phone'] = $phone;
                $this->load->model('shop_user_model');
                $this->shop_user_model->add_user($shop_id, $params);
            }
            $url = $this->config->base_url();
            redirect($url . 'index.php/home/users');
        }

        $data = array();
        $data['title'] = 'Quản trị người sử dụng';
        $data["user"] = $this->user;
        $data["url"] = $this->config->base_url();
        $this->load->model('category_model');
        $roles = $this->category_model->get_user_roles();
        $data['roles'] = $roles;
        $data['title_header'] = 'Thêm mới người sử dụng';
        if (!empty($_GET)) {
            $id = intval($this->input->get('id'));
            $this->load->model('shop_user_model');
            $user = $this->shop_user_model->user($id, $shop_id);
            $data['edit_user'] = $user;
            $data['title_header'] = 'Chi tiết người sử dụng';
        }

        //toandk2 sửa
        if (check_user_agent('mobile')) {
            $this->load->view('mobile_views/html_header_app', $data);
            $this->load->view('mobile_views/user', $data);
            $this->load->view('mobile_views/html_footer_app', $data);
        } else {
            $this->load->view('headers/html_header', $data);
            $this->load->view('user1', $data);
            $this->load->view('headers/html_footer');
        }
    }

    public function delete_shop_user()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        //echo(json_encode($this->user));

        if ($this->user->row['user_group'] != 'admin') {
            return;
        }
        $id = intval($this->input->get('id'));
        $this->load->model('shop_user_model');
        $this->shop_user_model->delete_user($id, $shop_id);
        echo (0);
    }
    public function header()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $data = array();
        $data["user"] = $this->user;
        $data["url"] = $this->config->base_url();
        $data['title'] = 'shops.summary.report';
        $this->load->view('headers/header', $data);
        $this->load->view('home');
        $this->load->view('headers/html_footer');
    }

    function mail($to, $subject, $message)
    {
        $from = "EffectShop <effe.hokinhdoanh.online@gmail.com>";

        $config = array(
            'protocol' => 'smtp',
            'smtp_host' => 'ssl://smtp.googlemail.com',
            'smtp_port' => 465,
            'smtp_user' => 'effe.hokinhdoanh.online@gmail.com', // change it to yours
            'smtp_pass' => 'tiem@2018-1+2', // change it to yours
            'mailtype' => 'html',
            'charset' => 'utf-8',
            'wordwrap' => TRUE
        );

        //$message = '';
        $this->load->library('email', $config);
        $this->email->set_newline("\r\n");
        $this->email->from($from); // change it to yours
        $this->email->to($to); // change it to yours
        $this->email->subject($subject);
        $this->email->message($message);
        if ($this->email->send()) {
            //echo 'Email sent.';
        } else {
            //show_error($this->email->print_debugger());
        }
    }


    public function forgot_password()
    {
        if (isset($_POST['back'])) {
            redirect('login');
            return;
        }

        $data = array();
        $data['title'] = "Quên mật khẩu";
        if (!empty($_POST)) {
            $phone = std($this->input->post('phone'));
            $email = std($this->input->post('email'));

            $this->load->model('shop_user_model');
            $row = $this->shop_user_model->request_forget_pasword($phone, $email);
            if ($row) {
                $code = $row['code'];
                $url = $this->config->base_url() . "index.php/reset_password?code=" .  $code;
                $url = '<a href="' . $url . '">' . $url . '</a>';
                $subject = "Effect hokinhdoanh.online đổi mật khẩu";
                $body = "Chào " . $row['full_name'] . ",<br>";
                $body .= "Mời bạn nhấn vào link này để đổi mật khẩu :" . $url;
                $body .= "<br>Trân trọng,";
                $body .= "<br>Effect hokinhdoanh.online";
                $this->mail($email, $subject, $body);
                $message = "Link đổi mật khẩu đã được gửi đi";
            } else {
                $message = "Email hoặc số điện thoại không đúng, mời nhập lại";
            }
            $data['message'] = $message;
        }
        $this->load->helper('cookie');
        $cookie = get_cookie('shop_id');
        if ($cookie !== null) {
            $data['shop_id'] = $cookie;
        } else {
            $data['shop_id'] = "";
        }

        $this->load->view('forgot_password', $data);
        //$this->load->view('headers/simple_footer');       
    }
    public function reset_password()
    {
        if (isset($_POST['back'])) {
            redirect('login');
            return;
        }

        $code = $this->input->get('code');
        if ($code == '') {
            return;
        }
        $data = array();
        $this->load->model('shop_user_model');
        $user = $this->shop_user_model->get_user_by_change_pass_code($code);
        if (!$user) {
            return;
        }
        $data['message'] = "";
        if (!empty($_POST)) {
            $password = $this->input->post('password');
            $password = dinhdq_encode($user['phone'], $password);
            $params = array();
            $params['user_pass'] = $password;
            $params['change_pass_code'] = '';
            $this->shop_user_model->update_user($user['id'], $user['shop_id'], $params);
            $data['message'] = "Đã đổi mật khẩu thành công";
        }


        $data['title'] = "Đổi mật khẩu";
        $this->load->view('headers/simple_header', $data);
        $this->load->view('reset_password', $data);
        $this->load->view('headers/simple_footer');
    }

    public function import_order()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $products = $this->input->post('products');
        $this->load->model('product_model');
        $result = array();
        $ps = array();

        $i = 0;

        foreach ($products as $product) {
            $row = $this->product_model->get_product_existence($shop_id, $product['code']);
            if (!$row) {
                $ps[] = $i;
                $i++;
                continue;
            } else {
                $r = array();
                $r['id'] = $row['id'];
                $r['product_code'] = $row['product_code'];
                $r['product_name'] = $row['product_name'];
                $r['quantity'] = $product['quantity'];
                $r['price'] = $product['price'];
                $r['amount'] = $product['price'] * $product['quantity'];
                $result[] = $r;
            }
        }
        echo (json_encode($result));
    }

    public function import_product()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $products = $this->input->post('products');
        //echo(json_encode($products));
        //return;
        $ps = array();
        $i = 0;
        $this->load->model('product_model');
        $this->load->model('product_group_model');
        //echo(count($products));
        foreach ($products as $product) {
            if (!isset($product['code'])) {
                $ps[] = $i;
                $i++;
                continue;
            }
            if (strlen(trim($product['code'])) == 0) {
                $ps[] = $i;
                $i++;
                continue;
            }
            if (strlen(trim($product['name'])) == 0) {
                $ps[] = $i;
                $i++;
                continue;
            }
            if ($this->product_model->check_code_existence($shop_id, $product['code'], 0) == 1) {
                /*
                $ps[] = $i;
                $i++;
                continue;
                */
                $params = array();
                $product_group  = $this->product_group_model->get_by_name($shop_id, $product['group']);
                if ($product_group) {
                    $params['product_group'] = $product_group['id'];
                    $params['type'] = $product_group['type'];
                } else {
                    $params1 = array();
                    $params1['name'] = $product['group'];
                    $params1['type'] = 0;
                    $params1['shop_id'] = $shop_id;
                    $product_group_id = $this->product_group_model->add_product_group($params1);
                    $params['product_group'] = $product_group_id;
                }
                $params['product_name'] = $product['name'];
                $params['unit_default'] = $product['unit'];
                $params['cost_price'] = $product['cost_price'];
                $params['list_price'] = $product['list_price'];

                $params['is_new'] = floatval($product['quantity']);

                $params['gtgt'] = floatval($product['gtgt']);
                $params['stock_max'] = floatval($product['tncn']);
                $params['product_status'] = 1;

                if (isset($product['tags'])) {
                    $params['tags'] = json_encode($product['tags']);
                }
                $product['code'] = std($product['code']);
                $this->product_model->update_product_by_code($shop_id, $product['code'], $params);
                continue;
            }

            if (!isset($product['unit'])) {
                $product['unit'] = '';
            }

            $product_group  = $this->product_group_model->get_by_name($shop_id, $product['group']);
            $params = array();
            $params['shop_id'] = $shop_id;
            $params['product_code'] = $product['code'];
            $params['product_name'] = $product['name'];
            $params['unit_default'] = $product['unit'];
            if ($product_group) {
                $params['product_group'] = $product_group['id'];
                $params['type'] = $product_group['type'];
            } else {
                $params1 = array();
                $params1['name'] = $product['group'];
                $params1['type'] = 0;
                $params1['shop_id'] = $shop_id;
                $product_group_id = $this->product_group_model->add_product_group($params1);
                $params['product_group'] = $product_group_id;
            }
            $params['cost_price'] = $product['cost_price'];
            $params['list_price'] = $product['list_price'];

            $params['is_new'] = floatval($product['quantity']);

            $params['gtgt'] = floatval($product['gtgt']);
            $params['stock_max'] = floatval($product['tncn']);

            if (isset($product['tags'])) {
                $params['tags'] = json_encode($product['tags']);
            }
            $this->product_model->add_product($params);
            $i++;
        }
        echo (json_encode($ps));
    }

    public function change_product_status()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $product_id = intval($this->input->get('id'));
        $product_status = intval($this->input->get('status'));
        $this->load->model('product_model');
        $params = array();
        $params['shop_id'] = $shop_id;
        $params['product_status'] = $product_status;
        $this->product_model->update_product($product_id, $shop_id, $params);
    }
    public function change_product_group()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $product_id = intval($this->input->get('id'));
        $product_group = intval($this->input->get('group'));
        $this->load->model('product_model');
        $params = array();
        $params['shop_id'] = $shop_id;
        $params['product_group'] = $product_group;
        $this->product_model->update_product($product_id, $shop_id, $params);
    }

    function extend()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        //echo(json_encode($this->user));
        //return;
        if ($this->user->shop['type'] == 36) {
            redirect('/dealer_shop');
            return;
        }
        //echo(json_encode($this->user));
        $shop_id = $this->user->shop_id;

        $data = array();
        $data['title'] = 'Gia hạn sử dụng';
        $data["user"] = $this->user;
        if (!empty($_POST)) {
            $company_name = std($this->input->post('company_name'));
            $tax_code = std($this->input->post('tax_code'));
            $bill_address = std($this->input->post('bill_address'));
            $bill_name = std($this->input->post('bill_name'));
            $email = std($this->input->post('email'));
            $phone = std($this->input->post('phone'));
            $payment = intval($this->input->post('payment'));

            $amount = intval($this->input->post('amount'));
            $transaction_id = ''; //std($this->input->post('transaction_id'));
            $promotion_code = std($this->input->post('promotion_code'));

            $params = array();
            $params['shop_id'] = $shop_id;
            $params['company_name'] = $company_name;
            $params['tax_code'] = $tax_code;
            $params['bill_address'] = $bill_address;
            $params['bill_name'] = $bill_name;
            $params['email'] = $email;
            $params['amount'] = $amount;
            $params['transaction_id'] = $transaction_id;
            $params['promotion_code'] = $promotion_code;
            $this->load->model('shop_order_model');
            $id = $this->shop_order_model->add_shop_order($params);

            if ($payment == 1) {
                redirect('/extend2?id=' . $id);
            }
            if ($payment == 0) {
                $this->onepay_do($id);
            }
            if ($payment == 2) {
                $this->onepay_international($id);
            }

            return;
        }
        $this->load->model('shop_model');
        $shop = $this->shop_model->get_shop($shop_id);

        $success = intval($this->input->get('success'));
        $data['success'] = $success;

        $datetime1 = date_create(date('Y-m-d'));
        $datetime2 = date_create($shop['expired']);

        $interval = date_diff($datetime1, $datetime2);
        $data['duration'] = intval($interval->format('%a'));
        $data['expired'] = vn_date($shop['expired']);

        $datetime1 = date_create(date('Y-m-d'));
        $datetime2 = date_create($shop['registered']);

        $interval = date_diff($datetime2, $datetime1);

        $duration1 = 45 - intval($interval->format('%a'));
        if ($duration1 > $data['duration']) {
            $duration1 = $data['duration'];
        }

        $data['duration1'] = $duration1;
        $data['shop_name'] = $this->user->shop['name'];
        $data['shop_fullname'] = $this->user->row['full_name'];
        $data['shop_address'] = $this->user->shop['address'];
        $data['shop_email'] = $this->user->shop['email'];
        $data['shop_phone'] = $this->user->shop['phone'];
        $data['promotion_code'] = $this->user->shop['promotion_code'];
        $data['type'] = $this->user->shop['type'];

        $this->load->model('shop_order_model');
        $shop_orders = $this->shop_order_model->get_shop_shop_order($shop_id);
        $data['shop_orders'] = $shop_orders;
        //toandk2 sửa 
        if (check_user_agent('mobile')) {
            $data['title_header'] = 'Gia hạn sử dụng phần mềm';
            $this->load->view('mobile_views/html_header_app', $data);
            $this->load->view('mobile_views/extend', $data);
        } else {
            $this->load->view('headers/html_header', $data);
            $this->load->view('extend', $data);
            $this->load->view('headers/html_footer');
        }

        // $this->load->view('headers/html_header', $data);
        // $this->load->view('extend', $data);
        // $this->load->view('headers/html_footer');
    }

    function guest_extend()
    {
        if (!empty($_POST)) {
            $shop_id = intval($this->input->post('shop_id'));
            $company_name = std($this->input->post('company_name'));
            $tax_code = std($this->input->post('tax_code'));
            $bill_address = std($this->input->post('bill_address'));
            $bill_name = std($this->input->post('bill_name'));
            $email = std($this->input->post('email'));
            $phone = std($this->input->post('phone'));
            $payment = intval($this->input->post('payment'));

            $amount = intval($this->input->post('amount'));
            $transaction_id = ''; //std($this->input->post('transaction_id'));
            $promotion_code = std($this->input->post('promotion_code'));
            $url = $this->config->item('myadmin_url');
            $token = ktk_get_token();
            $url = $url . "members/shop_extend?shop_id=" . urlencode($shop_id) . "&token=" . $token . "&company_name=" . urlencode($company_name)
                . "&bill_address=" . urlencode($bill_address) . "&bill_name=" . urlencode($bill_name) . "&phone=" . urlencode($phone) . "&email=" . urlencode($email)
                . "&amount=" . urlencode($amount) . "&transaction_id=" . urlencode($transaction_id) . "&promotion_code=" . urlencode($promotion_code);

            //echo($url);
            $result = trim(get_content($url));
            $result = json_decode($result, true);
            $id = 0;
            if ($result) {
                $id = intval($result['id']);
            }
            if ($result != null) {
                if ($payment == 1) {
                    redirect('/extend2?id=' . $id);
                }
                if ($payment == 0) {
                    $this->guest_onepay_do($id);
                }
                if ($payment == 2) {
                    $this->guest_onepay_international($id);
                }
            }
            return;
        }

        $this->load->view('headers/guest_header');
        $this->load->view('guest_extend');
        $this->load->view('headers/html_footer');
    }

    function extend2()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $id = intval($this->input->get('id'));
        $transaction_id = std($this->input->post('transaction_id'));

        $data = array();
        $data['title'] = 'Gia hạn sử dụng';
        $data["user"] = $this->user;

        if (!empty($_POST)) {
            $id = intval($this->input->get('id'));
            $transaction_id = std($this->input->post('transaction_id'));
            $url = $this->config->item('myadmin_url');
            $token = ktk_get_token();
            $url = $url . "members/shop_extend2?shop_id=" . urlencode($shop_id) . "&token=" . $token . "&id=" . $id . "&transaction_id=" . $transaction_id;

            $result = trim(get_content($url));
            $result = json_decode($result, true);
            redirect('/extend21');
        }

        // $this->load->view('headers/html_header', $data);
        $data = array();

        $data['id'] = $id;
        $data['shop_id'] = $shop_id;
        $data['name'] = $this->user->shop['name'];
        $data['phone'] = $this->user->shop['phone'];

        //toandk2 sửa
        if (check_user_agent('mobile')) {
            $data['title_header'] = 'Gia hạn sử dụng phần mềm';
            $this->load->view('mobile_views/html_header_app', $data);
            $this->load->view('mobile_views/extend2', $data);
        } else {
            $this->load->view('headers/html_header', $data);
            $this->load->view('extend2', $data);
            $this->load->view('headers/html_footer');
        }
        // $this->load->view('extend2', $data);
        // $this->load->view('headers/html_footer');
    }

    function extend21()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $data = array();
        $data['title'] = 'Gia hạn sử dụng';
        $data["user"] = $this->user;

        $this->load->view('headers/html_header', $data);
        $this->load->view('extend21');
        $this->load->view('headers/html_footer');
    }


    function extend0()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $id = intval($this->input->get('id'));

        $data = array();
        $data['title'] = 'Gia hạn sử dụng';
        $data["user"] = $this->user;

        $this->load->view('headers/html_header', $data);
        $this->load->view('extend0');
        $this->load->view('headers/html_footer');
    }

    function change_vat()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $id = intval($this->input->get('order_id'));
        $vat = intval($this->input->get('vat'));
        $this->load->model('order_model');
        //$order = $this->order_model->get_order($id, $shop_id);
        $params = array();
        $params['vat'] = $vat;
        $this->order_model->update_order($id, $shop_id, $params);

        echo ($vat);
    }

    function change_payment_type()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $id = intval($this->input->get('order_id'));
        $payment_type = intval($this->input->get('payment_type'));
        $this->load->model('order_model');
        //$order = $this->order_model->get_order($id, $shop_id);
        $params = array();
        $params['payment_type'] = $payment_type;
        $this->order_model->update_order($id, $shop_id, $params);

        echo ($payment_type);
    }


    function change_tax()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $id = intval($this->input->get('order_id'));
        $tax = intval($this->input->get('tax'));
        $this->load->model('order_model');
        //$order = $this->order_model->get_order($id, $shop_id);
        $params = array();
        $params['tax'] = $tax;
        $this->order_model->update_order($id, $shop_id, $params);
        echo ($tax);
    }


    function onepay_do($id)
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $amount = "" . 100 * intval($this->input->post('amount'));
        $address = strip_unicode($this->input->post('bill_address'));
        $phone = $this->input->post('phone');
        $email = $this->input->post('email');

        /* -----------------------------------------------------------------------------
        
         Version 2.0
        
         @author OnePAY
        
        ------------------------------------------------------------------------------*/

        // *********************
        // START OF MAIN PROGRAM
        // *********************

        // Define Constants
        // ----------------
        // This is secret for encoding the MD5 hash
        // This secret will vary from merchant to merchant
        // To not create a secure hash, let SECURE_SECRET be an empty string - ""
        // $SECURE_SECRET = "secure-hash-secret";
        // Khóa bí mật - được cấp bởi OnePAY
        $SECURE_SECRET = get_secure_secret();

        // add the start of the vpcURL querystring parameters
        // *****************************Lấy giá trị url cổng thanh toán*****************************
        $vpcURL = onepay_url() . "?";
        $data = array();
        $data['vpc_Merchant'] = get_merchant_id();
        $data['vpc_AccessCode'] = get_access_code_domestic();
        $data['vpc_MerchTxnRef'] = date('YmdHis') . rand();
        $data['vpc_OrderInfo'] = $id;
        $data['vpc_Amount'] = $amount;
        $data['vpc_ReturnURL'] = 'http://hokinhdoanh.online/after_onepay?id=' . $id;
        $data['vpc_Version'] = 2;
        $data['vpc_Command'] = 'pay';
        $data['vpc_Locale'] = 'vn';
        $data['vpc_Currency'] = 'VND';
        $data['vpc_TicketNo'] = $_SERVER['REMOTE_ADDR'];
        $data['vpc_SHIP_Street01'] = $address;
        $data['vpc_SHIP_Provice'] = 'Hoan Kiem';
        $data['vpc_SHIP_City'] = 'Ha Noi';
        $data['vpc_SHIP_Country'] = 'Viet Nam';
        $data['vpc_Customer_Phone'] = $phone;
        $data['vpc_Customer_Email'] = $email;
        $data['vpc_Customer_Id'] = $shop_id;
        $data['Title'] = "VPC 3-Party";

        // Remove the Virtual Payment Client URL from the parameter hash as we 
        // do not want to send these fields to the Virtual Payment Client.
        // bỏ giá trị url và nút submit ra khỏi mảng dữ liệu
        //$stringHashData = $SECURE_SECRET; *****************************Khởi tạo chuỗi dữ liệu mã hóa trống*****************************
        $stringHashData = "";
        // sắp xếp dữ liệu theo thứ tự a-z trước khi nối lại
        // arrange array data a-z before make a hash
        ksort($data);


        //echo(json_encode($data));
        //return;

        // set a parameter to show the first pair in the URL
        // đặt tham số đếm = 0
        $appendAmp = 0;

        foreach ($data as $key => $value) {

            // create the md5 input and URL leaving out any fields that have no value
            // tạo chuỗi đầu dữ liệu những tham số có dữ liệu
            if (strlen($value) > 0) {
                // this ensures the first paramter of the URL is preceded by the '?' char
                if ($appendAmp == 0) {
                    $vpcURL .= urlencode($key) . '=' . urlencode($value);
                    $appendAmp = 1;
                } else {
                    $vpcURL .= '&' . urlencode($key) . "=" . urlencode($value);
                }
                //$stringHashData .= $value; *****************************sử dụng cả tên và giá trị tham số để mã hóa*****************************
                if ((strlen($value) > 0) && ((substr($key, 0, 4) == "vpc_") || (substr($key, 0, 5) == "user_"))) {
                    $stringHashData .= $key . "=" . $value . "&";
                }
            }
        }
        //*****************************xóa ký tự & ở thừa ở cuối chuỗi dữ liệu mã hóa*****************************
        $stringHashData = rtrim($stringHashData, "&");
        // Create the secure hash and append it to the Virtual Payment Client Data if
        // the merchant secret has been provided.
        // thêm giá trị chuỗi mã hóa dữ liệu được tạo ra ở trên vào cuối url
        if (strlen($SECURE_SECRET) > 0) {
            //$vpcURL .= "&vpc_SecureHash=" . strtoupper(md5($stringHashData));
            // *****************************Thay hàm mã hóa dữ liệu*****************************
            $vpcURL .= "&vpc_SecureHash=" . strtoupper(hash_hmac('SHA256', $stringHashData, pack('H*', $SECURE_SECRET)));
        }

        // FINISH TRANSACTION - Redirect the customers using the Digital Order
        // ===================================================================
        // chuyển trình duyệt sang cổng thanh toán theo URL được tạo ra
        //header("Location: ".$vpcURL);
        redirect($vpcURL);
        //echo($vpcURL);

        // *******************
        // END OF MAIN PROGRAM
        // *******************


    }

    function onepay_international($id)
    {

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $amount = "" . 100 * intval($this->input->post('amount'));
        $address = strip_unicode($this->input->post('bill_address'));
        $phone = $this->input->post('phone');
        $email = $this->input->post('email');

        $SECURE_SECRET = get_secure_secret_international();

        $vpcURL = onepay_url_international() . "?";

        $data = array();
        $data['AgainLink'] = urlencode($_SERVER['HTTP_REFERER']);

        $data['vpc_Merchant'] = get_merchant_id();
        $data['vpc_AccessCode'] = get_access_code_international();
        $data['vpc_MerchTxnRef'] = date('YmdHis') . rand();
        $data['vpc_OrderInfo'] = $id;
        $data['vpc_Amount'] = $amount;
        $data['vpc_ReturnURL'] = 'http://hokinhdoanh.online/after_onepay_international?id=' . $id;
        $data['vpc_Version'] = 2;
        $data['vpc_Command'] = 'pay';
        $data['vpc_Locale'] = 'en';
        //$data['vpc_Currency'] = 'VND';
        $data['vpc_TicketNo'] = $_SERVER['REMOTE_ADDR'];
        $data['vpc_SHIP_Street01'] = $address;
        $data['vpc_SHIP_Provice'] = 'Hoan Kiem';
        $data['vpc_SHIP_City'] = 'Ha Noi';
        $data['vpc_SHIP_Country'] = 'Viet Nam';
        $data['vpc_Customer_Phone'] = $phone;
        $data['vpc_Customer_Email'] = $email;
        $data['vpc_Customer_Id'] = $shop_id;
        $data['Title'] = "VPC 3-Party";
        $data['AVS_Street01'] = '';
        $data['AVS_City'] = '';
        $data['AVS_StateProv'] = '';
        $data['AVS_PostCode'] = '';
        $data['AVS_Country'] = '';
        $data['display'] = '';

        $md5HashData = "";

        ksort($data);

        // set a parameter to show the first pair in the URL
        $appendAmp = 0;

        foreach ($data as $key => $value) {

            // create the md5 input and URL leaving out any fields that have no value
            if (strlen($value) > 0) {

                // this ensures the first paramter of the URL is preceded by the '?' char
                if ($appendAmp == 0) {
                    $vpcURL .= urlencode($key) . '=' . urlencode($value);
                    $appendAmp = 1;
                } else {
                    $vpcURL .= '&' . urlencode($key) . "=" . urlencode($value);
                }
                //$md5HashData .= $value; sử dụng cả tên và giá trị tham số để mã hóa
                if ((strlen($value) > 0) && ((substr($key, 0, 4) == "vpc_") || (substr($key, 0, 5) == "user_"))) {
                    $md5HashData .= $key . "=" . $value . "&";
                }
            }
        }
        //xóa ký tự & ở thừa ở cuối chuỗi dữ liệu mã hóa
        $md5HashData = rtrim($md5HashData, "&");
        // Create the secure hash and append it to the Virtual Payment Client Data if
        // the merchant secret has been provided.
        if (strlen($SECURE_SECRET) > 0) {
            //$vpcURL .= "&vpc_SecureHash=" . strtoupper(md5($md5HashData));
            // Thay hàm mã hóa dữ liệu
            $vpcURL .= "&vpc_SecureHash=" . strtoupper(hash_hmac('SHA256', $md5HashData, pack('H*', $SECURE_SECRET)));
        }

        // FINISH TRANSACTION - Redirect the customers using the Digital Order
        // ===================================================================
        redirect($vpcURL);
    }

    function after_onepay()
    {
        /*        
        if ($this->check_user()===false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        */
        //echo(json_encode($_GET));
        $SECURE_SECRET = get_secure_secret();
        if (!isset($_GET["vpc_SecureHash"])) {
            $data = array();
            $data['title'] = 'Gia hạn sử dụng';
            $data["user"] = $this->user;
            $data["cancel"] = 1;

            $this->load->view('headers/html_header', $data);
            $this->load->view('after_onepay', $data);
            $this->load->view('headers/html_footer');

            return;
        }
        if (!array_key_exists('vpc_SecureHash', $_GET)) {
            return;
        }

        $vpc_Txn_Secure_Hash = $_GET["vpc_SecureHash"];
        unset($_GET["vpc_SecureHash"]);

        // set a flag to indicate if hash has been validated
        $errorExists = false;

        ksort($_GET);

        if (strlen($SECURE_SECRET) > 0 && $_GET["vpc_TxnResponseCode"] != "7" && $_GET["vpc_TxnResponseCode"] != "No Value Returned") {

            //$stringHashData = $SECURE_SECRET;
            //*****************************khởi tạo chuỗi mã hóa rỗng*****************************
            $stringHashData = "";

            // sort all the incoming vpc response fields and leave out any with no value
            foreach ($_GET as $key => $value) {
                //        if ($key != "vpc_SecureHash" or strlen($value) > 0) {
                //            $stringHashData .= $value;
                //        }
                //      *****************************chỉ lấy các tham số bắt đầu bằng "vpc_" hoặc "user_" và khác trống và không phải chuỗi hash code trả về*****************************
                if ($key != "vpc_SecureHash" && (strlen($value) > 0) && ((substr($key, 0, 4) == "vpc_") || (substr($key, 0, 5) == "user_"))) {
                    $stringHashData .= $key . "=" . $value . "&";
                }
            }
            //  *****************************Xóa dấu & thừa cuối chuỗi dữ liệu*****************************
            $stringHashData = rtrim($stringHashData, "&");


            //    if (strtoupper ( $vpc_Txn_Secure_Hash ) == strtoupper ( md5 ( $stringHashData ) )) {
            //    *****************************Thay hàm tạo chuỗi mã hóa*****************************
            //echo(strtoupper ( $vpc_Txn_Secure_Hash ) . "<br>");
            //echo(strtoupper(hash_hmac('SHA256', $stringHashData, pack('H*',$SECURE_SECRET))));
            if (strtoupper($vpc_Txn_Secure_Hash) == strtoupper(hash_hmac('SHA256', $stringHashData, pack('H*', $SECURE_SECRET)))) {
                // Secure Hash validation succeeded, add a data field to be displayed
                // later.
                $hashValidated = "CORRECT";
            } else {
                // Secure Hash validation failed, add a data field to be displayed
                // later.
                $hashValidated = "INVALID HASH";
            }
        } else {
            // Secure Hash was not validated, add a data field to be displayed later.
            $hashValidated = "INVALID HASH";
        }

        // Define Variables
        // ----------------
        // Extract the available receipt fields from the VPC Response
        // If not present then let the value be equal to 'No Value Returned'
        // Standard Receipt Data
        $amount = null2unknown($_GET["vpc_Amount"]);
        $locale = null2unknown($_GET["vpc_Locale"]);
        //$batchNo = null2unknown ( $_GET ["vpc_BatchNo"] );
        $command = null2unknown($_GET["vpc_Command"]);
        //$message = null2unknown ( $_GET ["vpc_Message"] );
        $version = null2unknown($_GET["vpc_Version"]);
        //$cardType = null2unknown ( $_GET ["vpc_Card"] );
        $orderInfo = null2unknown($_GET["vpc_OrderInfo"]);
        //$receiptNo = null2unknown ( $_GET ["vpc_ReceiptNo"] );
        $merchantID = null2unknown($_GET["vpc_Merchant"]);
        //$authorizeID = null2unknown ( $_GET ["vpc_AuthorizeId"] );
        $merchTxnRef = null2unknown($_GET["vpc_MerchTxnRef"]);
        $transactionNo = null2unknown($_GET["vpc_TransactionNo"]);
        //$acqResponseCode = null2unknown ( $_GET ["vpc_AcqResponseCode"] );
        $txnResponseCode = null2unknown($_GET["vpc_TxnResponseCode"]);

        $transStatus = "";
        if ($hashValidated == "CORRECT" && $txnResponseCode == "0") {
            $transStatus = "Giao dịch thành công";
        } elseif ($hashValidated == "INVALID HASH" && $txnResponseCode == "0") {
            $transStatus = "Giao dịch thất bại";
        } else {
            $transStatus = "Giao dịch thất bại";
        }

        $amount = intval($this->input->get('vpc_Amount'));
        $amount = $amount / 100;

        if ($hashValidated == "CORRECT" && $txnResponseCode == "0") {
            //Update order
            //Tiến hành gia hạn

            $hash = $vpc_Txn_Secure_Hash;
            //echo($hash);
            $this->load->model('shop_order_model');
            $order = $this->shop_order_model->check_exist_order($hash);

            if ($order) {
                $transStatus = "Giao dịch thất bại";
            } else {

                $id = intval($this->input->get('id'));
                $order = $this->shop_order_model->get_one_shop_order($id);
                if ($order) {
                    $promotion_code = $order['promotion_code'];
                    $shop_id = $order['shop_id'];
                    $this->load->model('dealer_model');
                    $dealer = $this->dealer_model->get_dealer_by_code($promotion_code);

                    if ($dealer) {
                        $days = ceil($amount / 4500);
                    } else {
                        $days = ceil($amount / 4500);
                    }

                    $params = array();
                    $params['hash'] = $hash;
                    $params['type'] = 1;
                    $params['status'] = 1;
                    $params['day'] = $days;
                    $commission = $amount * 40.5 / 100;
                    $params['commission'] = $commission;
                    $this->shop_order_model->update_shop_order($id, $shop_id, $params);



                    //Extend
                    $this->load->model('shop_model');
                    $shop = $this->shop_model->get_shop($shop_id);
                    $paid = intval($shop['paid']);
                    $old_expired = $shop['expired'];
                    $registered = $shop['registered'];
                    $now = date('Y-m-d');

                    $datetime1 = date_create($registered);
                    $datetime2 = date_create($now);
                    $interval = date_diff($datetime1, $datetime2);
                    $d = $interval->format('%a');
                    $d1 = 0;
                    if (($d < 45) && $paid == 0) {
                        $d1 = (45 - $d) * 2;
                        $days = intval($days + $d1);
                    }
                    if (strtotime($old_expired) < strtotime($now)) {
                        $new_expired = date('Y-m-d H:i:s', strtotime($now . ' +' . $days . ' days'));
                        echo ($days);
                    } else {
                        $new_expired = date('Y-m-d H:i:s', strtotime($old_expired . ' +' . $days . ' days'));
                    }
                    if ($d1 > 0) {
                        $discount_days = "(45 - $d) x 2 = $d1";
                    } else {
                        $discount_days = "0";
                    }
                    $params = array();
                    $params['discount_day'] = $discount_days;
                    $this->shop_order_model->update_shop_order($id, $shop_id, $params);


                    $params = array();
                    $params['expired'] = $new_expired;
                    $params['paid'] = 1;
                    $this->shop_model->update_shop($shop_id, $params);
                    $transStatus = $transStatus . " - Cửa hàng đã được gia hạn";
                }
            }
        }

        $data = array();
        $data['merchantID'] = $merchantID;
        $data['merchTxnRef'] = $merchTxnRef;
        $data['amount'] = $amount;
        $data['orderInfo'] = $orderInfo;
        $data['txnResponseCode'] = $txnResponseCode;
        $data['transactionNo'] = $transactionNo;
        $data['transStatus'] = $transStatus;


        $data['title'] = 'Gia hạn sử dụng';

        if ($this->check_user()) {
            $data["user"] = $this->user;
            $this->load->view('headers/html_header', $data);
        } else {
            $this->load->view('headers/guest_header');
        }
        $this->load->view('after_onepay', $data);
        $this->load->view('headers/html_footer');
    }

    function after_onepay_international()
    {
        /*
        if ($this->check_user()===false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        */

        if (!isset($_GET["vpc_Message"]) || $_GET["vpc_Message"] == 'Cancel' || $_GET["vpc_3DSstatus"] == 'N') {

            $data = array();
            $data['title'] = 'Gia hạn sử dụng';
            $data["user"] = $this->user;
            $data["cancel"] = 1;
            $data["transStatus"] = 'Giao dịch thất bại';

            if ($this->check_user()) {
                $data["user"] = $this->user;
                //$this->load->view('headers/html_header', $data);
                $this->load->view('headers/guest_header');
            } else {
                $this->load->view('headers/guest_header');
            }

            $this->load->view('after_onepay', $data);
            $this->load->view('headers/html_footer');

            return;
        }
        if (!array_key_exists('vpc_SecureHash', $_GET)) {
            return;
        }


        $SECURE_SECRET = get_secure_secret_international();

        // get and remove the vpc_TxnResponseCode code from the response fields as we
        // do not want to include this field in the hash calculation
        $vpc_Txn_Secure_Hash = $_GET["vpc_SecureHash"];
        $vpc_MerchTxnRef = $_GET["vpc_MerchTxnRef"];
        $vpc_AcqResponseCode = $_GET["vpc_AcqResponseCode"];
        unset($_GET["vpc_SecureHash"]);
        // set a flag to indicate if hash has been validated
        $errorExists = false;

        if (strlen($SECURE_SECRET) > 0 && $_GET["vpc_TxnResponseCode"] != "7" && $_GET["vpc_TxnResponseCode"] != "No Value Returned") {

            ksort($_GET);
            //$md5HashData = $SECURE_SECRET;
            //khởi tạo chuỗi mã hóa rỗng
            $md5HashData = "";
            // sort all the incoming vpc response fields and leave out any with no value
            foreach ($_GET as $key => $value) {
                //        if ($key != "vpc_SecureHash" or strlen($value) > 0) {
                //            $md5HashData .= $value;
                //        }
                //      chỉ lấy các tham số bắt đầu bằng "vpc_" hoặc "user_" và khác trống và không phải chuỗi hash code trả về
                if ($key != "vpc_SecureHash" && (strlen($value) > 0) && ((substr($key, 0, 4) == "vpc_") || (substr($key, 0, 5) == "user_"))) {
                    $md5HashData .= $key . "=" . $value . "&";
                }
            }
            //  Xóa dấu & thừa cuối chuỗi dữ liệu
            $md5HashData = rtrim($md5HashData, "&");

            //    if (strtoupper ( $vpc_Txn_Secure_Hash ) == strtoupper ( md5 ( $md5HashData ) )) {
            //    Thay hàm tạo chuỗi mã hóa
            if (strtoupper($vpc_Txn_Secure_Hash) == strtoupper(hash_hmac('SHA256', $md5HashData, pack('H*', $SECURE_SECRET)))) {
                // Secure Hash validation succeeded, add a data field to be displayed
                // later.
                $hashValidated = "CORRECT";
            } else {
                // Secure Hash validation failed, add a data field to be displayed
                // later.
                $hashValidated = "INVALID HASH";
            }
        } else {
            // Secure Hash was not validated, add a data field to be displayed later.
            $hashValidated = "INVALID HASH";
        }

        // Define Variables
        // ----------------
        // Extract the available receipt fields from the VPC Response
        // If not present then let the value be equal to 'No Value Returned'

        // Standard Receipt Data
        $amount = null2unknown($_GET["vpc_Amount"]);
        $locale = null2unknown($_GET["vpc_Locale"]);
        $batchNo = null2unknown($_GET["vpc_BatchNo"]);
        $command = null2unknown($_GET["vpc_Command"]);
        $message = null2unknown($_GET["vpc_Message"]);
        $version = null2unknown($_GET["vpc_Version"]);
        $cardType = null2unknown($_GET["vpc_Card"]);
        $orderInfo = null2unknown($_GET["vpc_OrderInfo"]);
        $receiptNo = null2unknown($_GET["vpc_ReceiptNo"]);
        $merchantID = null2unknown($_GET["vpc_Merchant"]);
        //$authorizeID = null2unknown($_GET["vpc_AuthorizeId"]);
        $merchTxnRef = null2unknown($_GET["vpc_MerchTxnRef"]);
        $transactionNo = null2unknown($_GET["vpc_TransactionNo"]);
        $acqResponseCode = null2unknown($_GET["vpc_AcqResponseCode"]);
        $txnResponseCode = null2unknown($_GET["vpc_TxnResponseCode"]);
        // 3-D Secure Data
        $verType = array_key_exists("vpc_VerType", $_GET) ? $_GET["vpc_VerType"] : "No Value Returned";
        $verStatus = array_key_exists("vpc_VerStatus", $_GET) ? $_GET["vpc_VerStatus"] : "No Value Returned";
        $token = array_key_exists("vpc_VerToken", $_GET) ? $_GET["vpc_VerToken"] : "No Value Returned";
        $verSecurLevel = array_key_exists("vpc_VerSecurityLevel", $_GET) ? $_GET["vpc_VerSecurityLevel"] : "No Value Returned";
        $enrolled = array_key_exists("vpc_3DSenrolled", $_GET) ? $_GET["vpc_3DSenrolled"] : "No Value Returned";
        $xid = array_key_exists("vpc_3DSXID", $_GET) ? $_GET["vpc_3DSXID"] : "No Value Returned";
        $acqECI = array_key_exists("vpc_3DSECI", $_GET) ? $_GET["vpc_3DSECI"] : "No Value Returned";
        $authStatus = array_key_exists("vpc_3DSstatus", $_GET) ? $_GET["vpc_3DSstatus"] : "No Value Returned";

        // *******************
        // END OF MAIN PROGRAM
        // *******************

        // FINISH TRANSACTION - Process the VPC Response Data
        // =====================================================
        // For the purposes of demonstration, we simply display the Result fields on a
        // web page.

        // Show 'Error' in title if an error condition
        $errorTxt = "";

        // Show this page as an error page if vpc_TxnResponseCode equals '7'
        if ($txnResponseCode == "7" || $txnResponseCode == "No Value Returned" || $errorExists) {
            $errorTxt = "Error ";
        }

        // This is the display title for 'Receipt' page 
        $title = $_GET["Title"];

        // The URL link for the receipt to do another transaction.
        // Note: This is ONLY used for this example and is not required for 
        // production code. You would hard code your own URL into your application
        // to allow customers to try another transaction.
        //TK//$againLink = URLDecode($_GET["AgainLink"]);


        $transStatus = "";
        if ($hashValidated == "CORRECT" && $txnResponseCode == "0") {
            $transStatus = "Giao dịch thành công";
        } elseif ($hashValidated == "INVALID HASH" && $txnResponseCode == "0") {
            $transStatus = "Giao dịch thất bại";
        } else {
            $transStatus = "Giao dịch thất bại";
        }

        if ($hashValidated == "CORRECT" && $txnResponseCode == "0") {
            //Update order
            //Tiến hành gia hạn

            $hash = $vpc_Txn_Secure_Hash;
            //echo($hash);
            $this->load->model('shop_order_model');
            $order = $this->shop_order_model->check_exist_order($hash);

            if ($order) {
                $transStatus = "Giao dịch thất bại";
                $amount = intval($this->input->get('vpc_Amount'));
                $amount = $amount / 100;
            } else {

                $id = intval($this->input->get('id'));
                $order = $this->shop_order_model->get_one_shop_order($id);
                if ($order) {
                    $promotion_code = $order['promotion_code'];
                    $shop_id = $order['shop_id'];
                    $this->load->model('dealer_model');
                    $dealer = $this->dealer_model->get_dealer_by_code($promotion_code);
                    $amount = intval($this->input->get('vpc_Amount'));
                    $amount = $amount / 100;

                    if ($dealer) {
                        $days = ceil($amount / 4500);
                    } else {
                        $days = ceil($amount / 4500);
                    }

                    $params = array();
                    $params['hash'] = $hash;
                    $params['type'] = 1;
                    $params['status'] = 1;
                    $params['day'] = $days;
                    $commission = $amount * 40.5 / 100;
                    $params['commission'] = $commission;

                    $this->shop_order_model->update_shop_order($id, $shop_id, $params);

                    //Extend
                    $this->load->model('shop_model');
                    $shop = $this->shop_model->get_shop($shop_id);
                    $paid = intval($shop['paid']);
                    $old_expired = $shop['expired'];
                    $registered = $shop['registered'];
                    $now = date('Y-m-d');

                    $datetime1 = date_create($registered);
                    $datetime2 = date_create($now);
                    $interval = date_diff($datetime1, $datetime2);
                    $d = $interval->format('%a');

                    if (($d < 45) && $paid == 0) {
                        $d1 = (45 - $d) * 2;
                        $days = intval($days + $d1);
                    }
                    if (strtotime($old_expired) < strtotime($now)) {
                        $new_expired = date('Y-m-d H:i:s', strtotime($now . ' +' . $days . ' days'));
                        echo ($days);
                    } else {
                        $new_expired = date('Y-m-d H:i:s', strtotime($old_expired . ' +' . $days . ' days'));
                    }
                    $params = array();
                    $params['expired'] = $new_expired;
                    $params['paid'] = 1;
                    $this->shop_model->update_shop($shop_id, $params);
                    $transStatus = $transStatus . " - Cửa hàng đã được gia hạn";
                }
            }
        }

        $data = array();
        $data['merchantID'] = $merchantID;
        $data['merchTxnRef'] = $merchTxnRef;
        $data['amount'] = $amount;
        $data['orderInfo'] = $orderInfo;
        $data['txnResponseCode'] = $txnResponseCode;
        $data['transactionNo'] = $transactionNo;
        $data['message'] = $message;

        $data['title'] = 'Gia hạn sử dụng';
        $data['transStatus'] = $transStatus;

        if ($this->check_user()) {
            $data["user"] = $this->user;
            $this->load->view('headers/html_header', $data);
        } else {
            $this->load->view('headers/guest_header');
        }
        $this->load->view('after_onepay', $data);
        $this->load->view('headers/html_footer');
    }

    function get_product_id_by_code()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $code = std($this->input->get('code'));
        $this->load->model('product_model');
        $product_id = $this->product_model->get_product_by_code($code, $shop_id);
        $result = array();
        $result['product_id'] = $product_id;
        echo (json_encode($result));
    }


    function home_page()
    {
        $this->load->view('home_page');
    }

    function landing_page()
    {
        $this->load->view('landing_page');
    }
    function landing_page1()
    {
        $this->load->view('landing_page1');
    }
    function landing_page2()
    {
        $this->load->view('landing_page2');
    }

    function landing_page3()
    {
        $this->load->view('landing_page3');
    }

    function shops()
    {
        $this->load->view('shops');
    }

    function test12345()
    {
        $this->output->enable_profiler(TRUE);
        $this->load->model('report7888_model');
        $this->report7888_model->tt40_tncn(4154, '2022-01-01', '2022-03-31', 1);
    }


    function guest_onepay_do($id)
    {
        $shop_id = intval($this->input->post('shop_id'));
        $amount = "" . 100 * intval($this->input->post('amount'));
        $address = strip_unicode($this->input->post('bill_address'));
        $phone = $this->input->post('phone');
        $email = $this->input->post('email');

        /* -----------------------------------------------------------------------------
        
         Version 2.0
        
         @author OnePAY
        
        ------------------------------------------------------------------------------*/

        // *********************
        // START OF MAIN PROGRAM
        // *********************

        // Define Constants
        // ----------------
        // This is secret for encoding the MD5 hash
        // This secret will vary from merchant to merchant
        // To not create a secure hash, let SECURE_SECRET be an empty string - ""
        // $SECURE_SECRET = "secure-hash-secret";
        // Khóa bí mật - được cấp bởi OnePAY
        $SECURE_SECRET = get_secure_secret();

        // add the start of the vpcURL querystring parameters
        // *****************************Lấy giá trị url cổng thanh toán*****************************
        $vpcURL = onepay_url() . "?";
        $data = array();
        $data['vpc_Merchant'] = get_merchant_id();
        $data['vpc_AccessCode'] = get_access_code_domestic();
        $data['vpc_MerchTxnRef'] = date('YmdHis') . rand();
        $data['vpc_OrderInfo'] = $id;
        $data['vpc_Amount'] = $amount;
        $data['vpc_ReturnURL'] = 'http://hokinhdoanh.online/after_onepay?id=' . $id;
        $data['vpc_Version'] = 2;
        $data['vpc_Command'] = 'pay';
        $data['vpc_Locale'] = 'vn';
        $data['vpc_Currency'] = 'VND';
        $data['vpc_TicketNo'] = $_SERVER['REMOTE_ADDR'];
        $data['vpc_SHIP_Street01'] = $address;
        $data['vpc_SHIP_Provice'] = 'Hoan Kiem';
        $data['vpc_SHIP_City'] = 'Ha Noi';
        $data['vpc_SHIP_Country'] = 'Viet Nam';
        $data['vpc_Customer_Phone'] = $phone;
        $data['vpc_Customer_Email'] = $email;
        $data['vpc_Customer_Id'] = $shop_id;
        $data['Title'] = "VPC 3-Party";

        // Remove the Virtual Payment Client URL from the parameter hash as we 
        // do not want to send these fields to the Virtual Payment Client.
        // bỏ giá trị url và nút submit ra khỏi mảng dữ liệu
        //$stringHashData = $SECURE_SECRET; *****************************Khởi tạo chuỗi dữ liệu mã hóa trống*****************************
        $stringHashData = "";
        // sắp xếp dữ liệu theo thứ tự a-z trước khi nối lại
        // arrange array data a-z before make a hash
        ksort($data);


        //echo(json_encode($data));
        //return;

        // set a parameter to show the first pair in the URL
        // đặt tham số đếm = 0
        $appendAmp = 0;

        foreach ($data as $key => $value) {

            // create the md5 input and URL leaving out any fields that have no value
            // tạo chuỗi đầu dữ liệu những tham số có dữ liệu
            if (strlen($value) > 0) {
                // this ensures the first paramter of the URL is preceded by the '?' char
                if ($appendAmp == 0) {
                    $vpcURL .= urlencode($key) . '=' . urlencode($value);
                    $appendAmp = 1;
                } else {
                    $vpcURL .= '&' . urlencode($key) . "=" . urlencode($value);
                }
                //$stringHashData .= $value; *****************************sử dụng cả tên và giá trị tham số để mã hóa*****************************
                if ((strlen($value) > 0) && ((substr($key, 0, 4) == "vpc_") || (substr($key, 0, 5) == "user_"))) {
                    $stringHashData .= $key . "=" . $value . "&";
                }
            }
        }
        //*****************************xóa ký tự & ở thừa ở cuối chuỗi dữ liệu mã hóa*****************************
        $stringHashData = rtrim($stringHashData, "&");
        // Create the secure hash and append it to the Virtual Payment Client Data if
        // the merchant secret has been provided.
        // thêm giá trị chuỗi mã hóa dữ liệu được tạo ra ở trên vào cuối url
        if (strlen($SECURE_SECRET) > 0) {
            //$vpcURL .= "&vpc_SecureHash=" . strtoupper(md5($stringHashData));
            // *****************************Thay hàm mã hóa dữ liệu*****************************
            $vpcURL .= "&vpc_SecureHash=" . strtoupper(hash_hmac('SHA256', $stringHashData, pack('H*', $SECURE_SECRET)));
        }

        // FINISH TRANSACTION - Redirect the customers using the Digital Order
        // ===================================================================
        // chuyển trình duyệt sang cổng thanh toán theo URL được tạo ra
        //header("Location: ".$vpcURL);
        redirect($vpcURL);
        //echo($vpcURL);

        // *******************
        // END OF MAIN PROGRAM
        // *******************


    }

    function guest_onepay_international($id)
    {
        $shop_id = intval($this->input->post('shop_id'));
        $amount = "" . 100 * intval($this->input->post('amount'));
        $address = strip_unicode($this->input->post('bill_address'));
        $phone = $this->input->post('phone');
        $email = $this->input->post('email');

        $SECURE_SECRET = get_secure_secret_international();

        $vpcURL = onepay_url_international() . "?";

        $data = array();
        $data['AgainLink'] = urlencode($_SERVER['HTTP_REFERER']);

        $data['vpc_Merchant'] = get_merchant_id();
        $data['vpc_AccessCode'] = get_access_code_international();
        $data['vpc_MerchTxnRef'] = date('YmdHis') . rand();
        $data['vpc_OrderInfo'] = $id;
        $data['vpc_Amount'] = $amount;
        $data['vpc_ReturnURL'] = 'http://hokinhdoanh.online/after_onepay_international?id=' . $id;
        $data['vpc_Version'] = 2;
        $data['vpc_Command'] = 'pay';
        $data['vpc_Locale'] = 'en';
        //$data['vpc_Currency'] = 'VND';
        $data['vpc_TicketNo'] = $_SERVER['REMOTE_ADDR'];
        $data['vpc_SHIP_Street01'] = $address;
        $data['vpc_SHIP_Provice'] = 'Hoan Kiem';
        $data['vpc_SHIP_City'] = 'Ha Noi';
        $data['vpc_SHIP_Country'] = 'Viet Nam';
        $data['vpc_Customer_Phone'] = $phone;
        $data['vpc_Customer_Email'] = $email;
        $data['vpc_Customer_Id'] = $shop_id;
        $data['Title'] = "VPC 3-Party";
        $data['AVS_Street01'] = '194 Tran Quang Khai';
        $data['AVS_City'] = 'Hanoi';
        $data['AVS_StateProv'] = 'Hoan Kiem';
        $data['AVS_PostCode'] = '10000';
        $data['AVS_Country'] = '';
        $data['display'] = '';

        $md5HashData = "";

        ksort($data);

        // set a parameter to show the first pair in the URL
        $appendAmp = 0;

        foreach ($data as $key => $value) {

            // create the md5 input and URL leaving out any fields that have no value
            if (strlen($value) > 0) {

                // this ensures the first paramter of the URL is preceded by the '?' char
                if ($appendAmp == 0) {
                    $vpcURL .= urlencode($key) . '=' . urlencode($value);
                    $appendAmp = 1;
                } else {
                    $vpcURL .= '&' . urlencode($key) . "=" . urlencode($value);
                }
                //$md5HashData .= $value; sử dụng cả tên và giá trị tham số để mã hóa
                if ((strlen($value) > 0) && ((substr($key, 0, 4) == "vpc_") || (substr($key, 0, 5) == "user_"))) {
                    $md5HashData .= $key . "=" . $value . "&";
                }
            }
        }
        //xóa ký tự & ở thừa ở cuối chuỗi dữ liệu mã hóa
        $md5HashData = rtrim($md5HashData, "&");
        // Create the secure hash and append it to the Virtual Payment Client Data if
        // the merchant secret has been provided.
        if (strlen($SECURE_SECRET) > 0) {
            //$vpcURL .= "&vpc_SecureHash=" . strtoupper(md5($md5HashData));
            // Thay hàm mã hóa dữ liệu
            $vpcURL .= "&vpc_SecureHash=" . strtoupper(hash_hmac('SHA256', $md5HashData, pack('H*', $SECURE_SECRET)));
        }

        // FINISH TRANSACTION - Redirect the customers using the Digital Order
        // ===================================================================
        redirect($vpcURL);
    }


    function dealer_extend()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = intval($this->input->get('shop_id'));
        $this->load->model('shop_model');
        $shop = $this->shop_model->get_shop($shop_id);

        $data = array();
        $data['title'] = 'Gia hạn sử dụng';
        $data["user"] = $this->user;
        if (!empty($_POST)) {
            $company_name = std($this->input->post('company_name'));
            $tax_code = std($this->input->post('tax_code'));
            $bill_address = std($this->input->post('bill_address'));
            $bill_name = std($this->input->post('bill_name'));
            $email = std($this->input->post('email'));
            $phone = std($this->input->post('phone'));
            $payment = intval($this->input->post('payment'));

            $amount = intval($this->input->post('amount'));
            $transaction_id = ''; //std($this->input->post('transaction_id'));
            $promotion_code = std($this->input->post('promotion_code'));

            $params = array();
            $params['shop_id'] = $shop_id;
            $params['company_name'] = $company_name;
            $params['tax_code'] = $tax_code;
            $params['bill_address'] = $bill_address;
            $params['bill_name'] = $bill_name;
            $params['email'] = $email;
            $params['amount'] = $amount;
            $params['transaction_id'] = $transaction_id;
            $params['promotion_code'] = $promotion_code;
            $this->load->model('shop_order_model');
            $id = $this->shop_order_model->add_shop_order($params);

            if ($payment == 1) {
                redirect('/extend2?id=' . $id);
            }
            if ($payment == 0) {
                $this->onepay_do($id);
            }
            if ($payment == 2) {
                $this->onepay_international($id);
            }

            return;
        }
        $success = intval($this->input->get('success'));
        $data['success'] = $success;

        $datetime1 = date_create(date('Y-m-d'));
        $datetime2 = date_create($shop['expired']);

        $interval = date_diff($datetime1, $datetime2);
        $data['duration'] = intval($interval->format('%a'));
        $data['expired'] = vn_date($shop['expired']);

        $datetime1 = date_create(date('Y-m-d'));
        $datetime2 = date_create($shop['registered']);

        $interval = date_diff($datetime2, $datetime1);

        $duration1 = 45 - intval($interval->format('%a'));
        if ($duration1 > $data['duration']) {
            $duration1 = $data['duration'];
        }

        $data['duration1'] = $duration1;
        $data['shop_name'] = $shop['name'];
        $data['shop_fullname'] = $shop['name'];
        $data['shop_address'] = $shop['address'];
        $data['shop_email'] = $shop['email'];
        $data['shop_phone'] = $shop['phone'];
        $data['promotion_code'] = $this->user->shop['promotion_code'];

        $this->load->view('headers/html_header', $data);
        $this->load->view('dealer_extend', $data);
        $this->load->view('headers/html_footer');
    }

    function dealer_shop()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('dealer_model');
        $dealer = $this->dealer_model->get_dealer_by_shop_id($shop_id);
        $expired_date = $dealer['expired_date'];
        $today = date("Y-m-d H:i:s");
        if (strtotime($expired_date) > strtotime($today)) {
            $expired = 0;
        } else {
            $expired = 1;
        }
        //echo($expired);

        $promotion_code = $this->user->shop['promotion_code'];
        $this->load->model('shop_model');
        $shops = $this->shop_model->get_dealer_shop($promotion_code);
        $data = array();
        $data['shops'] = $shops;
        $data['expired'] = $expired;
        $data["user"] = $this->user;
        $data["url"] = $this->config->base_url();
        $data["title"] = "Danh sách cửa hàng";

        $this->load->view('headers/html_header', $data);
        $dealer_revenue = $this->dealer_model->get_dealer_revenue($dealer['code']);
        $data['dealer_revenue'] = $dealer_revenue;

        $dealers = $this->dealer_model->get_sub_dealer($dealer['id']);
        $sub_dealers = array();
        foreach ($dealers as $dealer) {
            $code = $dealer['code'];
            $dealer_revenue = $this->dealer_model->get_dealer_revenue($dealer['code']);
            $dealer['dealer_revenue'] = $dealer_revenue;
            $shops = $this->shop_model->get_dealer_shop($dealer['code']);
            $dealer['shops'] = $shops;
            $sub_dealers[] = $dealer;
        }
        //echo(json_encode($sub_dealers));
        $data['dealers'] = $sub_dealers;

        //echo(json_encode($dealer_revenue));

        $this->load->view('dealer_shop', $data);
        $this->load->view('headers/html_footer');
    }

    function dealer_reg()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('shop_model');
        $shop = $this->shop_model->get_shop($shop_id);

        $data = array();
        $data['title'] = 'Thanh toán phí Đại lý hàng năm';
        $data["user"] = $this->user;
        if (!empty($_POST)) {
            $company_name = std($this->input->post('company_name'));
            $tax_code = std($this->input->post('tax_code'));
            $bill_address = std($this->input->post('bill_address'));
            $bill_name = std($this->input->post('bill_name'));
            $email = std($this->input->post('email'));
            $phone = std($this->input->post('phone'));
            $payment = intval($this->input->post('payment'));

            $amount = intval($this->input->post('amount'));

            $this->load->model('dealer_paid_model');
            $params = array();
            $params['company_name'] = $company_name;

            $params['company_name'] = $company_name;
            $params['tax_code'] = $tax_code;
            $params['address'] = $bill_address;
            $params['phone'] = $phone;
            $params['name'] = $bill_name;
            $params['payment_type'] = $payment;
            $params['shop_id'] = $shop_id;
            $params['amount'] = 50000;
            $id = $this->dealer_paid_model->add_dealer_paid($params);


            //echo($url);
            if ($payment == 0) {
                $this->dealer_onepay_do($id);
            }
            if ($payment == 2) {
                $this->dealer_onepay_international($id);
            }
        }
        $success = intval($this->input->get('success'));
        $data['success'] = $success;

        $datetime1 = date_create(date('Y-m-d'));
        $datetime2 = date_create($shop['expired']);

        $interval = date_diff($datetime1, $datetime2);
        $data['duration'] = intval($interval->format('%a'));
        $data['expired'] = vn_date($shop['expired']);

        $datetime1 = date_create(date('Y-m-d'));
        $datetime2 = date_create($shop['registered']);

        $interval = date_diff($datetime2, $datetime1);

        $duration1 = 45 - intval($interval->format('%a'));
        if ($duration1 > $data['duration']) {
            $duration1 = $data['duration'];
        }

        $data['duration1'] = $duration1;
        $data['shop_name'] = $shop['name'];
        $data['shop_fullname'] = $shop['name'];
        $data['shop_address'] = $shop['address'];
        $data['shop_email'] = $shop['email'];
        $data['shop_phone'] = $shop['phone'];
        $data['promotion_code'] = $this->user->shop['promotion_code'];

        $this->load->view('headers/html_header', $data);
        $this->load->view('dealer_reg', $data);
        $this->load->view('headers/html_footer');
    }


    function dealer_onepay_do($id)
    {
        $shop_id = intval($this->input->post('shop_id'));
        $amount = "" . 100 * intval($this->input->post('amount'));
        $address = strip_unicode($this->input->post('bill_address'));
        $phone = $this->input->post('phone');
        $email = $this->input->post('email');

        /* -----------------------------------------------------------------------------
        
         Version 2.0
        
         @author OnePAY
        
        ------------------------------------------------------------------------------*/

        // *********************
        // START OF MAIN PROGRAM
        // *********************

        // Define Constants
        // ----------------
        // This is secret for encoding the MD5 hash
        // This secret will vary from merchant to merchant
        // To not create a secure hash, let SECURE_SECRET be an empty string - ""
        // $SECURE_SECRET = "secure-hash-secret";
        // Khóa bí mật - được cấp bởi OnePAY
        $SECURE_SECRET = get_secure_secret();

        // add the start of the vpcURL querystring parameters
        // *****************************Lấy giá trị url cổng thanh toán*****************************
        $vpcURL = onepay_url() . "?";
        $data = array();
        $data['vpc_Merchant'] = get_merchant_id();
        $data['vpc_AccessCode'] = get_access_code_domestic();
        $data['vpc_MerchTxnRef'] = date('YmdHis') . rand();
        $data['vpc_OrderInfo'] = $id;
        $data['vpc_Amount'] = $amount;
        $data['vpc_ReturnURL'] = 'http://hokinhdoanh.online/dealer_after_onepay?id=' . $id;
        $data['vpc_Version'] = 2;
        $data['vpc_Command'] = 'pay';
        $data['vpc_Locale'] = 'vn';
        $data['vpc_Currency'] = 'VND';
        $data['vpc_TicketNo'] = $_SERVER['REMOTE_ADDR'];
        $data['vpc_SHIP_Street01'] = $address;
        $data['vpc_SHIP_Provice'] = 'Hoan Kiem';
        $data['vpc_SHIP_City'] = 'Ha Noi';
        $data['vpc_SHIP_Country'] = 'Viet Nam';
        $data['vpc_Customer_Phone'] = $phone;
        $data['vpc_Customer_Email'] = $email;
        $data['vpc_Customer_Id'] = $shop_id;
        $data['Title'] = "VPC 3-Party";

        // Remove the Virtual Payment Client URL from the parameter hash as we 
        // do not want to send these fields to the Virtual Payment Client.
        // bỏ giá trị url và nút submit ra khỏi mảng dữ liệu
        //$stringHashData = $SECURE_SECRET; *****************************Khởi tạo chuỗi dữ liệu mã hóa trống*****************************
        $stringHashData = "";
        // sắp xếp dữ liệu theo thứ tự a-z trước khi nối lại
        // arrange array data a-z before make a hash
        ksort($data);


        //echo(json_encode($data));
        //return;

        // set a parameter to show the first pair in the URL
        // đặt tham số đếm = 0
        $appendAmp = 0;

        foreach ($data as $key => $value) {

            // create the md5 input and URL leaving out any fields that have no value
            // tạo chuỗi đầu dữ liệu những tham số có dữ liệu
            if (strlen($value) > 0) {
                // this ensures the first paramter of the URL is preceded by the '?' char
                if ($appendAmp == 0) {
                    $vpcURL .= urlencode($key) . '=' . urlencode($value);
                    $appendAmp = 1;
                } else {
                    $vpcURL .= '&' . urlencode($key) . "=" . urlencode($value);
                }
                //$stringHashData .= $value; *****************************sử dụng cả tên và giá trị tham số để mã hóa*****************************
                if ((strlen($value) > 0) && ((substr($key, 0, 4) == "vpc_") || (substr($key, 0, 5) == "user_"))) {
                    $stringHashData .= $key . "=" . $value . "&";
                }
            }
        }
        //*****************************xóa ký tự & ở thừa ở cuối chuỗi dữ liệu mã hóa*****************************
        $stringHashData = rtrim($stringHashData, "&");
        // Create the secure hash and append it to the Virtual Payment Client Data if
        // the merchant secret has been provided.
        // thêm giá trị chuỗi mã hóa dữ liệu được tạo ra ở trên vào cuối url
        if (strlen($SECURE_SECRET) > 0) {
            //$vpcURL .= "&vpc_SecureHash=" . strtoupper(md5($stringHashData));
            // *****************************Thay hàm mã hóa dữ liệu*****************************
            $vpcURL .= "&vpc_SecureHash=" . strtoupper(hash_hmac('SHA256', $stringHashData, pack('H*', $SECURE_SECRET)));
        }

        // FINISH TRANSACTION - Redirect the customers using the Digital Order
        // ===================================================================
        // chuyển trình duyệt sang cổng thanh toán theo URL được tạo ra
        //header("Location: ".$vpcURL);
        redirect($vpcURL);
        //echo($vpcURL);

        // *******************
        // END OF MAIN PROGRAM
        // *******************


    }


    function dealer_after_onepay()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        //echo(json_encode($_GET));
        $SECURE_SECRET = get_secure_secret();
        if (!isset($_GET["vpc_SecureHash"])) {
            $data = array();
            $data['title'] = 'Gia hạn sử dụng';
            $data["user"] = $this->user;
            $data["cancel"] = 1;

            $this->load->view('headers/html_header', $data);
            $this->load->view('after_onepay', $data);
            $this->load->view('headers/html_footer');

            return;
        }
        if (!array_key_exists('vpc_SecureHash', $_GET)) {
            return;
        }

        $vpc_Txn_Secure_Hash = $_GET["vpc_SecureHash"];
        unset($_GET["vpc_SecureHash"]);

        // set a flag to indicate if hash has been validated
        $errorExists = false;

        ksort($_GET);

        if (strlen($SECURE_SECRET) > 0 && $_GET["vpc_TxnResponseCode"] != "7" && $_GET["vpc_TxnResponseCode"] != "No Value Returned") {

            //$stringHashData = $SECURE_SECRET;
            //*****************************khởi tạo chuỗi mã hóa rỗng*****************************
            $stringHashData = "";

            // sort all the incoming vpc response fields and leave out any with no value
            foreach ($_GET as $key => $value) {
                //        if ($key != "vpc_SecureHash" or strlen($value) > 0) {
                //            $stringHashData .= $value;
                //        }
                //      *****************************chỉ lấy các tham số bắt đầu bằng "vpc_" hoặc "user_" và khác trống và không phải chuỗi hash code trả về*****************************
                if ($key != "vpc_SecureHash" && (strlen($value) > 0) && ((substr($key, 0, 4) == "vpc_") || (substr($key, 0, 5) == "user_"))) {
                    $stringHashData .= $key . "=" . $value . "&";
                }
            }
            //  *****************************Xóa dấu & thừa cuối chuỗi dữ liệu*****************************
            $stringHashData = rtrim($stringHashData, "&");


            //    if (strtoupper ( $vpc_Txn_Secure_Hash ) == strtoupper ( md5 ( $stringHashData ) )) {
            //    *****************************Thay hàm tạo chuỗi mã hóa*****************************
            //echo(strtoupper ( $vpc_Txn_Secure_Hash ) . "<br>");
            //echo(strtoupper(hash_hmac('SHA256', $stringHashData, pack('H*',$SECURE_SECRET))));
            if (strtoupper($vpc_Txn_Secure_Hash) == strtoupper(hash_hmac('SHA256', $stringHashData, pack('H*', $SECURE_SECRET)))) {
                // Secure Hash validation succeeded, add a data field to be displayed
                // later.
                $hashValidated = "CORRECT";
            } else {
                // Secure Hash validation failed, add a data field to be displayed
                // later.
                $hashValidated = "INVALID HASH";
            }
        } else {
            // Secure Hash was not validated, add a data field to be displayed later.
            $hashValidated = "INVALID HASH";
        }

        // Define Variables
        // ----------------
        // Extract the available receipt fields from the VPC Response
        // If not present then let the value be equal to 'No Value Returned'
        // Standard Receipt Data
        $amount = null2unknown($_GET["vpc_Amount"]);
        $locale = null2unknown($_GET["vpc_Locale"]);
        //$batchNo = null2unknown ( $_GET ["vpc_BatchNo"] );
        $command = null2unknown($_GET["vpc_Command"]);
        //$message = null2unknown ( $_GET ["vpc_Message"] );
        $version = null2unknown($_GET["vpc_Version"]);
        //$cardType = null2unknown ( $_GET ["vpc_Card"] );
        $orderInfo = null2unknown($_GET["vpc_OrderInfo"]);
        //$receiptNo = null2unknown ( $_GET ["vpc_ReceiptNo"] );
        $merchantID = null2unknown($_GET["vpc_Merchant"]);
        //$authorizeID = null2unknown ( $_GET ["vpc_AuthorizeId"] );
        $merchTxnRef = null2unknown($_GET["vpc_MerchTxnRef"]);
        $transactionNo = null2unknown($_GET["vpc_TransactionNo"]);
        //$acqResponseCode = null2unknown ( $_GET ["vpc_AcqResponseCode"] );
        $txnResponseCode = null2unknown($_GET["vpc_TxnResponseCode"]);

        $transStatus = "";
        if ($hashValidated == "CORRECT" && $txnResponseCode == "0") {
            $transStatus = "Giao dịch thành công";
        } elseif ($hashValidated == "INVALID HASH" && $txnResponseCode == "0") {
            $transStatus = "Giao dịch thất bại";
        } else {
            $transStatus = "Giao dịch thất bại";
        }

        if ($hashValidated == "CORRECT" && $txnResponseCode == "0") {
            //Update order
            //Tiến hành gia hạn

            $hash = $vpc_Txn_Secure_Hash;
            //echo($hash);
            $this->load->model('dealer_paid_model');
            $order = $this->dealer_paid_model->check_existed_paid($hash);

            if ($order) {
                $transStatus = "Giao dịch thất bại";
            } else {

                $id = intval($this->input->get('id'));
                $order = $this->dealer_paid_model->get_dealer_paid($id);
                if ($order) {
                    $amount = intval($this->input->get('vpc_Amount'));

                    if ($amount < 100) {
                        $transStatus = "Giao dịch thất bại";
                    } else {
                        $params = array();
                        $params['hash'] = $hash;
                        $params['status'] = 1;
                        $this->dealer_paid_model->update_dealer_paid($id, $params);
                        $transStatus = $transStatus . " - Đại lý đã được đăng ký";
                        $this->load->model('dealer_model');
                        $dealer = $this->dealer_model->get_dealer_by_shop_id($shop_id);
                        $expired_date = $dealer['expired_date'];
                        $expired_date = date('Y-m-d', strtotime("+1 year", strtotime($expired_date)));
                        $params = array();
                        $params['expired_date'] = $expired_date;
                        $this->dealer_model->update_dealer($dealer['id'], $params);
                    }
                }
            }
        }

        $data = array();
        $data['merchantID'] = $merchantID;
        $data['merchTxnRef'] = $merchTxnRef;
        $data['amount'] = $amount;
        $data['orderInfo'] = $orderInfo;
        $data['txnResponseCode'] = $txnResponseCode;
        $data['transactionNo'] = $transactionNo;
        $data['transStatus'] = $transStatus;


        $data['title'] = 'Gia hạn sử dụng';
        $data["user"] = $this->user;

        $this->load->view('headers/html_header', $data);
        $this->load->view('after_onepay', $data);
        $this->load->view('headers/html_footer');
    }
    function check_phone_existed()
    {
        //$this->output->enable_profiler(TRUE);
        $phone = std($this->input->get('phone'));
        $this->load->model('shop_model');
        $phone1 = $this->shop_model->check_phone_existed($phone);
        $result = array();
        $result['phone'] = $phone1;
        if ($phone == $phone1) {
            $result['existed'] = 0;
        } else {
            $result['existed'] = 1;
        }
        echo (json_encode($result));
    }

    function shop_profile()
    {
        //$this->output->enable_profiler(TRUE);
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('vietnam_model');
        $this->load->model('shop_model');
        $data = array();
        $data['post'] = 0;
        if (!empty($_POST)) {
            $data['post'] = 1;
            $shop_name = std($this->input->post('shop_name'));
            //$name = std($this->input->post('full_name'));

            $email = std($this->input->post('email'));
            $address = std($this->input->post('address'));
            $province = std($this->input->post('province1'));
            $district = std($this->input->post('district1'));
            $ward = intval($this->input->post('ward'));
            $location = $this->vietnam_model->get_vietnam($ward);
            $ward_name = $location['name'];
            $location_id = $location['aux_id'];

            $type = intval($this->input->post('type'));
            $promotion_code = std($this->input->post('promotion_code'));
            $gpp = std($this->input->post('gpp'));

            $pharm_representative = std($this->input->post('pharm_representative'));
            $pharm_representative_id = std($this->input->post('pharm_representative_id'));
            $pharm_representative_email = std($this->input->post('pharm_representative_email'));


            $pharm_responsible = std($this->input->post('pharm_responsible'));
            $pharm_responsible_id = std($this->input->post('pharm_responsible_id'));
            $pharm_responsible_no = std($this->input->post('pharm_responsible_no'));
            $pharm_responsible_level = std($this->input->post('pharm_responsible_level'));
            $pharm_responsible_phone = std($this->input->post('pharm_responsible_phone'));
            $pharm_responsible_email = std($this->input->post('pharm_responsible_email'));

            $pharm_usr = std($this->input->post('pharm_usr'));
            $pharm_pwd = $this->input->post('pharm_pwd');
            if ($pharm_pwd != '') {
                $pharm_pwd = encrypt($pharm_pwd, $shop_id . $pharm_usr . '123456');
            }


            $params = array();
            $params['name'] = $shop_name;
            $params['email'] = $email;

            $this->user->email = $email;

            $params['state'] = $province;
            $params['district'] = $district;
            $params['ward'] = $ward_name;
            $params['address'] = $address;
            $params['location_id'] = $location_id;
            $params['promotion_code'] = $promotion_code;
            $params['gpp'] = $gpp;

            $params['pharm_representative'] = $pharm_representative;
            $params['pharm_representative_id'] = $pharm_representative_id;

            $params['pharm_responsible'] = $pharm_responsible;
            $params['pharm_responsible_no'] = $pharm_responsible_no;
            $params['pharm_responsible_id'] = $pharm_responsible_id;
            $params['pharm_responsible_level'] = $pharm_responsible_level;
            $params['pharm_responsible_phone'] = $pharm_responsible_phone;
            $params['pharm_responsible_email'] = $pharm_responsible_email;

            $code = std($this->input->post('code'));
            $code1 = std($this->input->post('code1'));
            if ($code != '') {
                $params['code'] = $code;
            }
            if ($code1 != '') {
                $params['code1'] = $code1;
            }


            $params['pharm_usr'] = $pharm_usr;
            if ($pharm_pwd != '') {
                $params['pharm_pwd'] = $pharm_pwd;
            }

            $this->shop_model->update_shop($shop_id, $params);
            //$shop_id = $this->shop_model->add_shop($params);
            //xxx-yyy

            $token = ktk_get_token();
            $user = $this->user;
            $user->email = $email;
            $user->row['email'] = $email;
            $user->save_session();
        }
        //echo(json_encode($this->user));

        $data['title'] = 'Cập nhật thông tin';
        //echo(json_encode($this->user));
        $shop = $this->shop_model->get_shop($shop_id);
        if ($shop) {
            $gpp = $shop['note1'];
            if ($gpp) {
                $gpp = json_decode($gpp, true);
                $shop['app_name'] = $gpp['app_name'];
                $shop['app_key'] = $gpp['app_key'];
            }
        }
        $data['shop'] = $shop;
        $this->load->model('shop_user_model');
        $user = $this->shop_user_model->user($this->user->user_id, $shop_id);
        //echo(json_encode($user));
        $data['user'] = $this->user;
        $data['shop_user'] = $user;

        // $this->load->view('headers/html_header', $data);


        $provinces = $this->vietnam_model->get_all_province();
        $data['provinces'] = $provinces;

        $location_id = $shop['location_id'];
        if ($location_id) {
            $location = explode(".", $location_id);
            $shop_province = intval($location[0]);
            $shop_district = intval($location[1]);
            if (isset($location[2])) {
                $shop_ward = intval($location[2]);
            } else {
                $shop_ward = '';
            }
            $data['shop_province'] = $shop_province;
            $data['shop_district'] = $shop_district;
            $data['shop_ward'] = $shop_ward;
        }
        //toandk2 sửa
        if (check_user_agent('mobile')) {
            $data['title_header'] = 'Cập nhật thông tin';
            $this->load->view('mobile_views/html_header_app', $data);
            $this->load->view('mobile_views/shop_profile', $data);
        } else {
            $this->load->view('headers/html_header', $data);
            $this->load->view('shop_profile', $data);
            $this->load->view('headers/html_footer');
        }
        // $this->load->view('shop_profile', $data);
        // $this->load->view('headers/html_footer');
    }
    //toandk2
    function shop_config()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('vietnam_model');
        $this->load->model('shop_model');
        $data = array();
        $data['post'] = 0;
        if (!empty($_POST)) {
            $data['post'] = 1;
            $shop_name = std($this->input->post('shop_name'));
            //$name = std($this->input->post('full_name'));

            $email = std($this->input->post('email'));
            $address = std($this->input->post('address'));
            $province = std($this->input->post('province1'));
            $district = std($this->input->post('district1'));
            $ward = intval($this->input->post('ward'));
            $location = $this->vietnam_model->get_vietnam($ward);
            $ward_name = $location['name'];
            $location_id = $location['aux_id'];

            $type = intval($this->input->post('type'));
            $promotion_code = std($this->input->post('promotion_code'));
            $gpp = std($this->input->post('gpp'));

            $pharm_representative = std($this->input->post('pharm_representative'));
            $pharm_representative_id = std($this->input->post('pharm_representative_id'));
            $pharm_representative_email = std($this->input->post('pharm_representative_email'));


            $pharm_responsible = std($this->input->post('pharm_responsible'));
            $pharm_responsible_id = std($this->input->post('pharm_responsible_id'));
            $pharm_responsible_no = std($this->input->post('pharm_responsible_no'));
            $pharm_responsible_level = std($this->input->post('pharm_responsible_level'));
            $pharm_responsible_phone = std($this->input->post('pharm_responsible_phone'));
            $pharm_responsible_email = std($this->input->post('pharm_responsible_email'));

            $pharm_usr = std($this->input->post('pharm_usr'));
            $pharm_pwd = $this->input->post('pharm_pwd');
            if ($pharm_pwd != '') {
                $pharm_pwd = encrypt($pharm_pwd, $shop_id . $pharm_usr . '123456');
            }


            $params = array();
            $params['name'] = $shop_name;
            $params['email'] = $email;

            $this->user->email = $email;

            $params['state'] = $province;
            $params['district'] = $district;
            $params['ward'] = $ward_name;
            $params['address'] = $address;
            $params['location_id'] = $location_id;
            $params['promotion_code'] = $promotion_code;
            $params['gpp'] = $gpp;

            $params['pharm_representative'] = $pharm_representative;
            $params['pharm_representative_id'] = $pharm_representative_id;

            $params['pharm_responsible'] = $pharm_responsible;
            $params['pharm_responsible_no'] = $pharm_responsible_no;
            $params['pharm_responsible_id'] = $pharm_responsible_id;
            $params['pharm_responsible_level'] = $pharm_responsible_level;
            $params['pharm_responsible_phone'] = $pharm_responsible_phone;
            $params['pharm_responsible_email'] = $pharm_responsible_email;

            $code = std($this->input->post('code'));
            $code1 = std($this->input->post('code1'));
            if ($code != '') {
                $params['code'] = $code;
            }
            if ($code1 != '') {
                $params['code1'] = $code1;
            }


            $params['pharm_usr'] = $pharm_usr;
            if ($pharm_pwd != '') {
                $params['pharm_pwd'] = $pharm_pwd;
            }

            $this->shop_model->update_shop($shop_id, $params);
            //$shop_id = $this->shop_model->add_shop($params);
            //xxx-yyy

            $token = ktk_get_token();
            $user = $this->user;
            $user->email = $email;
            $user->row['email'] = $email;
            $user->save_session();
        }
        //echo(json_encode($this->user));

        $data['title'] = 'Cập nhật thông tin';
        //echo(json_encode($this->user));
        $shop = $this->shop_model->get_shop($shop_id);
        if ($shop) {
            $gpp = $shop['note1'];
            if ($gpp) {
                $gpp = json_decode($gpp, true);
                $shop['app_name'] = $gpp['app_name'];
                $shop['app_key'] = $gpp['app_key'];
            }
        }
        $data['shop'] = $shop;
        $this->load->model('shop_user_model');
        $user = $this->shop_user_model->user($this->user->user_id, $shop_id);
        //echo(json_encode($user));
        $data['user'] = $this->user;
        $data['shop_user'] = $user;



        $provinces = $this->vietnam_model->get_all_province();
        $data['provinces'] = $provinces;

        $location_id = $shop['location_id'];
        if ($location_id) {
            $location = explode(".", $location_id);
            $shop_province = intval($location[0]);
            $shop_district = intval($location[1]);
            if (isset($location[2])) {
                $shop_ward = intval($location[2]);
            } else {
                $shop_ward = '';
            }
            $data['shop_province'] = $shop_province;
            $data['shop_district'] = $shop_district;
            $data['shop_ward'] = $shop_ward;
        }
        //toandk2 sửa
        if (check_user_agent('mobile')) {
            $data['title_header'] = 'Cập nhật thông tin';
            $this->load->view('mobile_views/html_header_app', $data);
            $this->load->view('mobile_views/shop_config', $data);
        } else {
            $this->load->view('headers/html_header', $data);
            $this->load->view('shop_config', $data);
            $this->load->view('headers/html_footer');
        }
    }

    public function paid()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get('order_id'));
        $this->load->model('order_model');
        $params = array();
        $params['paid'] = 1;
        $params['paid_date'] = date('Y-m-d'); //date('Y-m-d H:i:s');
        $this->order_model->update_order0($order_id, $shop_id, $params);
    }

    function get_occupied_calendar()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $month = intval($this->input->get('month'));
        $product_id = intval($this->input->get('product_id'));
        $this_month = date('m');
        $date = date("Y-m-1");
        if ($month > 0) {
            $date = date("Y-m-d", strtotime("+" . $month . " months", strtotime($date)));
        }
        $month = date("m", strtotime($date));
        $year = date("Y", strtotime($date));

        $this->load->model('occupy_model');
        $row = $this->occupy_model->get_occupy($shop_id, $product_id, $year, $month);
        if ($row) {
            $occupied = str_replace('2', '1', $row['occupied']);
            $occupied = str_replace('3', '1', $occupied);
        } else {
            $occupied = "0000000000000000000000000000000";
        }
        //echo($occupied);
        $date = "$year-$month-1";
        $w = date("w", strtotime($date));
        //echo($w);
        //$this->load->view('calendar');
        echo ('<div class="container">');
        echo ('<div class="month_title">' . "$month - $year" . '</div>');
        echo ('<div class="occupied">S</div><div class="calendar_title">M</div><div class="calendar_title">T</div><div class="calendar_title">W</div><div class="calendar_title">T</div><div class="calendar_title">F</div><div class="occupied">S</div>');
        for ($i = 0; $i <= $w - 1; $i++) {
            echo ('<div class="pre"></div>');
        }
        $max_days = intval(date('t', strtotime($date)));
        for ($i = 0; $i < $max_days; $i++) {
            $d = intval(substr($occupied, $i, 1));
            if ($d == 1) {
                echo ('<div class="occupied">' . ($i + 1) . '</div>');
            } else {
                if ($d != 5 && $d != 7) {
                    echo ('<div class="empty" onclick="fill_date_from(' . $year . ',' . $month . ', ' . ($i + 1) . ', ' . $d . ')">' . ($i + 1) . '</div>');
                } else {
                    echo ('<div class="half" onclick="fill_date_from(' . $year . ',' . $month . ', ' . ($i + 1) . ', ' . $d . ')">' . ($i + 1) . '</div>');
                }
            }
        }

        $rest = 0;
        if ($max_days == 30) {
            if ($w == 5) {
                $rest = 0;
            }
            if ($w == 0) {
                $rest = 6;
            }
            if ($w == 6) {
                $rest = 6;
            }
            if ($w == 1) {
                $rest = 4;
            }
            if ($w == 3) {
                $rest = 2;
            }
            if ($w == 4) {
                $rest = 1;
            }
            if ($w == 2) {
                $rest = 3;
            }
            if ($w == 0) {
                $rest = 5;
            }
        }
        if ($max_days == 31) {
            if ($w == 0) {
                $rest = 4;
            }
            if ($w == 3) {
                $rest = 1;
            }
            if ($w == 6) {
                $rest = 5;
            }
            if ($w == 5) {
                $rest = 6;
            }
            if ($w == 1) {
                $rest = 4;
            }
            if ($w == 2) {
                $rest = 2;
            }
        }
        if ($max_days == 28) {
            $rest = 7 - $w;
        }

        for ($i = 1; $i <= $rest; $i++) {
            echo ('<div class="pre"></div>');
        }
        echo ('</div>');
        //echo("<br><br>$max_days-$w-$rest");
    }

    function check_occupied_product()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $product_id = intval($this->input->get('product_id'));
        $start_date = std($this->input->get('start_date'));
        $end_date = std($this->input->get('end_date'));
        $this->load->model('occupy_model');
        if ($this->occupy_model->check_occupied_product($shop_id, $product_id, $start_date, $end_date)) {
            echo (round(abs(strtotime($start_date) - strtotime($end_date)) / 86400));
        } else {
            echo (0);
        }
        //echo(json_encode($result));        
    }

    function merge_order()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_name = std($this->input->get('order_name'));
        $order_date = std($this->input->get('order_date'));
        $order_date = date_from_vn($order_date);

        $order_type = std($this->input->get('order_type'));
        $note = std($this->input->get('note'));
        $order_ids = $this->input->get('order_ids');
        $order_ids = array_check($order_ids);
        $customer_id = intval($this->input->get('customer_id'));

        $customer_name = std($this->input->get('customer_name'));
        //$this->load->model('bill_item_model');
        //$items = $this->bill_item_model->get_order_items($shop_id, $order_ids);
        $amount = 0;
        $this->load->model('order_model');
        $order_items = json_encode($order_ids);
        foreach ($order_ids as $id) {
            $order = $this->order_model->get_order($id, $shop_id);
            $amount = $amount + $order['amount'];
        }

        $params = array();
        $params['shop_id'] = $shop_id;
        $params['order_name'] = $order_name;
        $params['order_date'] = $order_date;
        $params['order_type'] = $order_type;
        $params['customer_id'] = $customer_id;
        $params['customer_name'] = $customer_name;
        $params['amount'] = $amount;
        $params['vat'] = 0;
        $params['paid'] = 1;
        $params['status1'] = 4;
        $params['last_update'] = date('Y-m-d');
        $params['is_reference'] = 1;
        $params['content'] = $note;
        $params['order_items'] = $order_items;
        $params['user_id'] = $this->user->user_id;
        $id = $this->order_model->add_order($params);
        echo ($id);
    }

    function merge_order1()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_name = std($this->input->get('order_name'));
        $order_date = std($this->input->get('order_date'));
        $order_date = date_from_vn($order_date);


        $unit = std($this->input->get('unit'));
        $address = std($this->input->get('address'));
        $bill_book = std($this->input->get('bill_book'));
        $director = std($this->input->get('director'));
        $chief_accountant = std($this->input->get('chief_accountant'));
        $cashier = std($this->input->get('cashier'));
        $storekeeper = std($this->input->get('storekeeper'));

        $order_type = 'B';
        $bills_item_ids = $this->input->get('bills_items');
        $bills_item_ids = array_check($bills_item_ids);
        //$this->load->model('bill_item_model');
        //$items = $this->bill_item_model->get_order_items($shop_id, $order_ids);
        $amount = 0;
        $this->load->model('bill_item_model');
        $bills_items = json_encode($bills_item_ids);
        foreach ($bills_item_ids as $id) {
            $bill_item = $this->bill_item_model->get_bill_item($id, $shop_id);
            $amount = $amount + $bill_item['amount'];
        }
        $this->load->model('order_model');
        $params = array();
        $params['shop_id'] = $shop_id;
        $params['order_name'] = $order_name;
        $params['order_date'] = $order_date;
        $params['order_type'] = $order_type;

        $params['address'] = $address;
        $params['unit'] = $unit;
        //$params['bill_book'] = $bill_book;
        $params['director'] = $director;
        $params['chief_accountant'] = $chief_accountant;
        $params['cashier'] = $cashier;
        $params['storekeeper'] = $storekeeper;

        $params['amount'] = $amount;
        $params['vat'] = 0;
        $params['paid'] = 1;
        $params['status1'] = 4;
        $params['last_update'] = date('Y-m-d');
        $params['is_reference'] = 2;
        $params['order_items'] = $bills_items;
        $params['user_id'] = $this->user->user_id;
        $odrer_id = $this->order_model->add_order($params);
        echo ($odrer_id);
    }



    function get_reference_shop($type)
    {
        $reference_shop_id = 849;
        if ($type == 31) {
            $reference_shop_id = 849;
        }
        if ($type == 0) {
            $reference_shop_id = 890;
        }
        if ($type == 1) {
            $reference_shop_id = 893;
        }
        if ($type == 2) {
            $reference_shop_id = 892;
        }
        if ($type == 33) {
            $reference_shop_id = 899;
        }
        if ($type == 331) {
            $reference_shop_id = 908;
        }
        if ($type == 29) {
            $reference_shop_id = 902;
        }
        if ($type == 24) {
            $reference_shop_id = 903;
        }
        if ($type == 9) {
            $reference_shop_id = 904;
        }
        if ($type == 6) {
            $reference_shop_id = 905;
        }
        return $reference_shop_id;
    }

    function get_reference_product_groups()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $type = intval($this->input->get('type'));
        $reference_shop_id = $this->get_reference_shop($type);

        $this->load->model('product_group_model');

        $groups = $this->product_group_model->get_all_product_referrence_groups($reference_shop_id);

        $data = array();
        $data['groups'] = $groups;
        $this->load->view('reference_groups', $data);
    }

    function get_reference_products()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $type = intval($this->input->get('type'));
        $group_id = intval($this->input->get('group_id'));
        $reference_shop_id = $this->get_reference_shop($type);

        $this->load->model('product_model');

        $products = $this->product_model->get_shop_product_by_group($reference_shop_id, $group_id);

        $data = array();
        $data['products'] = $products;
        $data['shop_id'] = $reference_shop_id;
        $this->load->view('reference_products', $data);
    }

    function copy_product($shop_id, $reference_shop_id, $product_id)
    {
        $this->load->model('product_model');
        $source_product = $this->product_model->get_product($product_id, $reference_shop_id);
        if (!$source_product) {
            return;
        }
        $source_product_code = $source_product['product_code'];
        if ($this->product_model->check_code_existence($shop_id, $source_product_code, 0) == 1) {
            return;
        }

        $product_group = 0;
        if (intval($source_product['product_group']) != 0) {
            $this->load->model('product_group_model');
            $group = $this->product_group_model->get_product_group_by_product($reference_shop_id, $product_id);
            if ($group) {
                $group_name = $group['name'];
                $group_type = $group['type'];
                $new_group = $this->product_group_model->get_product_group_by_name($shop_id, $group_name, $group_type);
                if ($new_group) {
                    $product_group = $new_group['id'];
                } else {
                    $params = array();
                    $params['shop_id'] = $shop_id;
                    $params['name'] = $group_name;
                    $params['type'] = $group_type;
                    $product_group = $this->product_group_model->add_product_group($params);
                }
            }
        }
        $params = array();
        $params['shop_id'] = $shop_id;
        $params['product_code'] = $source_product['product_code'];
        $params['product_name'] = $source_product['product_name'];
        $params['list_price'] = $source_product['list_price'];
        $params['cost_price'] = $source_product['cost_price'];
        $params['product_group'] = $product_group;
        $params['type'] = $source_product['type'];;

        $params['product_status'] = 1;
        $params['tags'] = $source_product['tags'];
        $product_id = $this->product_model->add_product($params);

        if (!file_exists(FCPATH . 'img/' . $shop_id)) {
            mkdir(FCPATH . 'img/' . $shop_id);
        }
        $old_path = FCPATH . 'img/' . $reference_shop_id . '/' . $source_product['image_file'];
        $new_path = FCPATH . 'img/' . $shop_id . '/product_' . $product_id;
        try {
            copy($old_path, $new_path);
        } catch (Exception $ex) {
        }

        $params = array();
        $params['image_file'] = "product_$product_id";
        $this->product_model->update_product($product_id, $shop_id, $params);
    }

    function copy_products()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $type = intval($this->input->get('type'));
        $products = std($this->input->get('products'));
        $reference_shop_id = $this->get_reference_shop($type);

        foreach ($products as $product) {
            $product_id = intval($product);
            $this->copy_product($shop_id, $reference_shop_id, $product_id);
        }
    }

    function copy_products2()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        //$type = intval($this->input->get('type'));
        $products = std($this->input->get('products'));
        $reference_shop_id = intval($this->input->get('shop_id'));

        foreach ($products as $product) {
            $product_id = intval($product);
            $this->copy_product($shop_id, $reference_shop_id, $product_id);
        }
    }

    function copy_product0($shop_id, $product_id)
    {
        $this->load->model('product_model');
        $source_product = $this->product_model->get_product0($product_id);
        if (!$source_product) {
            return;
        }
        $source_product_code = $source_product['product_code'];
        if ($this->product_model->check_code_existence($shop_id, $source_product_code, 0) == 1) {
            return;
        }

        $this->load->model('product_group_model');
        $group_name = $source_product['product_group'];
        $group_type = 0;
        $new_group = $this->product_group_model->get_product_group_by_name($shop_id, $group_name, $group_type);
        if ($new_group) {
            $product_group = $new_group['id'];
        } else {
            $params = array();
            $params['shop_id'] = $shop_id;
            $params['name'] = $group_name;
            $params['type'] = $group_type;
            $product_group = $this->product_group_model->add_product_group($params);
        }


        $params = array();
        $params['shop_id'] = $shop_id;
        $params['product_code'] = $source_product['product_code'];
        $params['product_name'] = $source_product['product_name'];
        $params['product_group'] = $product_group;
        //$params['type'] = $source_product['type'];

        $params['product_status'] = 1;
        //$params['tags'] = $source_product['tags'];
        $product_id = $this->product_model->add_product($params);

        if (!file_exists(FCPATH . 'img/' . $shop_id)) {
            mkdir(FCPATH . 'img/' . $shop_id);
        }
        $old_path = FCPATH . 'img/' . $reference_shop_id . '/' . $source_product['image_file'];
        $new_path = FCPATH . 'img/' . $shop_id . '/product_' . $product_id;
        try {
            copy($old_path, $new_path);
        } catch (Exception $ex) {
        }

        $params = array();
        $params['image_file'] = "product_$product_id";
        $this->product_model->update_product($product_id, $shop_id, $params);
    }
    function copy_products3()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        //$type = intval($this->input->get('type'));
        $products = std($this->input->get('products'));

        foreach ($products as $product) {
            $product_id = intval($product);
            $this->copy_product0($shop_id, $product_id);
        }
    }


    function copy_groups()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $type = intval($this->input->get('type'));
        $groups = std($this->input->get('groups'));
        $reference_shop_id = $this->get_reference_shop($type);

        $this->load->model('product_model');
        foreach ($groups as $group) {
            $group_id = intval($group);
            $products = $this->product_model->get_shop_product_by_group($reference_shop_id, $group_id);
            foreach ($products as $product) {
                $product_id = $product['id'];
                $this->copy_product($shop_id, $reference_shop_id, $product_id);
            }
        }
    }

    function test77()
    {
        //$this->output->enable_profiler(TRUE);
        //echo(fill_text("0",1));
        $this->load->model('occupy_model');
        $this->occupy_model->update_product_occupy(710, 2662, '2018-8-1', '2018-8-31');
    }

    function test78()
    {
        //$this->output->enable_profiler(TRUE);
        //echo(fill_text("0",1));
        $this->load->model('occupy_model');
        $occupied = $this->occupy_model->product_occupy_report(710, 2662, '2018-8-1', '2018-9-30');
        echo ($occupied);
    }

    function test79()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $data['title'] = tb_word('shops.purchase.orders');
        $data["user"] = $this->user;
        $this->load->view('headers/html_header', $data);
        $this->load->view('test1');
        $this->load->view('headers/html_footer');
    }

    function test80()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $data['title'] = tb_word('shops.purchase.orders');
        $data["user"] = $this->user;
        $this->load->view('headers/html_header', $data);
        $this->load->view('test2');
        $this->load->view('headers/html_footer');
    }


    function test81()
    {
        $this->create_sub_shop(0, "Bách hóa Tràng Tiền");
    }

    function test82()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        echo (json_encode($this->user));
    }

    function test83()
    {
        var_dump(json_decode(''));
    }


    function test84()
    {
        $this->load->model('order_model');
        $orders = $this->order_model->get_full_orders(710, '2018-01-01', '2018-09-01');
        //echo();
        $data = array();
        $data['data'] = tb_encode_value(tb_encode_value(json_encode($orders)));
        $this->load->view('export_orders', $data);
    }

    function test85()
    {
        $this->load->model('order_model');
        $this->order_model->delete_orders(710, '2018-01-01', '2018-09-01');
    }

    function delete_orders()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        if ($this->user->row['user_group'] != 'admin') {
            return;
        }

        $data['message'] = "Đây là chức năng rất nguy hiểm các đơn hàng sẽ bị xóa trên máy chủ Tiệm<br>Trước khi xóa phần mềm sẽ sao lưu 1 bản để bạn có thể lưu ở máy mình";
        $data['title'] = 'Xóa đơn hàng';
        $data["user"] = $this->user;

        $this->load->library('user_agent');
        $is_mobile = $this->agent->is_mobile();
        $data['is_mobile'] = $is_mobile;
        if ($is_mobile) {
            $data['message'] = "Chức năng này không hoạt động trên điện thoại";
        }

        if (!empty($_POST)) {
            $start_date = std($this->input->post('start_date'));
            $start_date = date_create_from_format('d/m/Y', $start_date);
            if (!is_bool($start_date)) {
                $start_date = $start_date->format('Y-m-d');
            } else {
                $start_date = '';
            }

            $end_date = std($this->input->post('end_date'));
            $end_date = date_create_from_format('d/m/Y', $end_date);
            if (!is_bool($end_date)) {
                $end_date = $end_date->format('Y-m-d');
            } else {
                $end_date = '';
            }

            if ($start_date != '' && $end_date != '') {
                $this->load->model('order_model');
                $orders = $this->order_model->get_full_orders($shop_id, $start_date, $end_date);
                $order_to_save = tb_encode_value(tb_encode_value(json_encode($orders)));

                $filename = "orders $start_date - $end_date.txt";
                header("Content-Disposition: attachment; filename=\"$filename\"");
                //header("Content-Disposition: attachment;");
                header("Content-Type: text/plain");
                header("Pragma: no-cache");
                header("Expires: 0");
                echo ($order_to_save);
                //$data['message'] = "Đã xóa dữ liệu";
                $this->order_model->delete_orders($shop_id, $start_date, $end_date);
                return;
            }
        }
        //toandk2 sửa
        if (check_user_agent('mobile')) {
            $data['title_header'] = 'Xóa đơn hàng';
            $this->load->view('mobile_views/html_header_app', $data);
            $this->load->view('mobile_views/delete_orders', $data);
        } else {
            $this->load->view('headers/html_header', $data);
            $this->load->view('delete_orders', $data);
            $this->load->view('headers/html_footer');
        }

        // $this->load->view('headers/html_header', $data);

        // $this->load->view('delete_orders', $data);
        // $this->load->view('headers/html_footer');
    }

    function import_orders()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        if ($this->user->row['user_group'] != 'admin') {
            return;
        }
        $data = array();
        if (!empty($_POST)) {
            $config['upload_path']          = '/tmp/';
            $config['allowed_types']        = 'txt';
            $config['max_size']             = 20000;

            $this->load->library('upload', $config);
            if ($this->upload->do_upload('file')) {
                $path = $this->upload->data()['full_path'];
                $content = trim(file_get_contents($path));
                $content = tb_decode_value(tb_decode_value($content));
                //echo($content);

                $order_data = json_decode($content, true);
                $orders = $order_data['orders'];
                $bills = $order_data['bills'];
                $items = $order_data['items'];

                $this->load->model('order_model');
                try {
                    $this->order_model->import_orders($shop_id, $orders, $bills, $items);
                } catch (Exception $ex) {
                }
                $data['message'] = "Đã import xong";
            } else {
                $error = array('error' => $this->upload->display_errors());
                echo (json_encode($error));
            }
        }

        $data['title'] = 'Import đơn hàng';
        $data["user"] = $this->user;
        if (check_user_agent('mobile')) {
            $data['title_header'] = 'Tải lên đơn hàng';
            $this->load->view('mobile_views/html_header_app', $data);
            $this->load->view('mobile_views/import_orders', $data);
        } else {
            $this->load->view('headers/html_header', $data);
            $this->load->view('import_orders', $data);
            $this->load->view('headers/html_footer');
        }
        // $this->load->view('headers/html_header', $data);
        // $this->load->view('import_orders', $data);
        // $this->load->view('headers/html_footer');
    }

    function add_customer()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $name = std($this->input->post("name"));
        $phone = std($this->input->post("phone"));
        $email = std($this->input->post("email"));
        $address = std($this->input->post("address"));
        $params = array();
        $params['shop_id'] = $shop_id;
        $params['name'] = $name;
        $params['phone'] = $phone;
        $params['email'] = $email;
        $params['address'] = $address;
        $params['type'] = 0;
        $this->load->model('customer_model');
        $customer_id = $this->customer_model->add_customer($shop_id, $params);
        $result = array();
        $result['customer_id'] = $customer_id;
        echo (json_encode($result));
    }

    function test86()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        echo (json_encode($this->user));
    }

    function visitors()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $bill_item_id = intval($this->input->get('bill_item_id'));
        $this->load->model('visitor_model');
        $visitors = $this->visitor_model->get_bill_item_visitor($shop_id, $bill_item_id);

        if ($visitors) {
            $table_text = '<table width="100%" align="center" cellspacing="0px" empty-cells="show" style="background-color: transparent; border-collapse: collapse; border: 1px solid #cccccc; cellpadding: 0px; cellspacing: 0px; font-style: normal; font-weight: normal"><colgroup><col width="30%" align="center"><col width="15%" align="left"><col width="10%" align="right"><col width="15%" align="right"><col width="20%" align="right"><col width="10%"></colgroup><tbody>';
            $table_text .= '<tr><td align="left" class="header_cell">Tên khách</td><td align="left" class="header_cell">Số điện thoại</td><td align="left" class="header_cell">Giới tính</td><td align="left" class="header_cell">Quốc tịch</td><td align="left" class="header_cell">Số hộ chiếu/CMT</td><td class="header_cell"></td></tr>';
            foreach ($visitors as $visitor) {
                $table_text = $table_text . '<tr><td align="left" class="cell">' . $visitor['name'] . '</td><td align="left" class="cell">' . $visitor['phone'] . '</td><td align="left" class="cell">' . $visitor['gender'] . '</td><td align="left" class="cell">' . $visitor['nationality'] . '</td><td align="left" class="cell">' . $visitor['passport_id'] . '</td><td class="cell"><a href="javascript:edit_visitor(' . $visitor['id']  . ', ' . $bill_item_id . ')">Sửa</a> <a href="javascript:remove_visitor(' . $visitor['id']  . ', ' . $bill_item_id . ')">Xóa</a></td></tr>';
            }
            $table_text .= '</tbody></table><input type="hidden" id="vs" value="' . count($visitors) . '">';
            echo ($table_text);
        } else {
            echo ('<input type="hidden" id="vs" value="0">');
        }
    }

    function visitor_add()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $bill_item_id = intval($this->input->get('bill_item_id'));

        $this->load->model('bill_item_model');
        $bill_item = $this->bill_item_model->get_bill_item($bill_item_id, $shop_id);
        if (!$bill_item) {
            return;
        }

        $order_id = $bill_item['order_id'];
        //$checkin_date = $bill_item['start_date'];
        //$checkout_date = $bill_item['end_date'];

        $name = std($this->input->get('name'));
        $dob = std($this->input->get('dob'));
        $phone = std($this->input->get('phone'));
        $gender = std($this->input->get('gender'));
        $room_number = std($this->input->get('room_number'));
        $nationality = std($this->input->get('nationality'));
        $passport_id = std($this->input->get('passport_id'));
        $estimated_leaving_date = std($this->input->get('estimated_leaving_date'));

        $estimated_leaving_date = str_replace('/', '-', $estimated_leaving_date);
        $estimated_leaving_date = date('Y-m-d', strtotime($estimated_leaving_date));

        $checkin_date = std($this->input->get('checkin_date'));
        $checkin_date = date_from_vn($checkin_date);

        $checkout_date = std($this->input->get('checkout_date'));
        $checkout_date = date_from_vn($checkout_date);


        $paper_type = std($this->input->get('paper_type'));
        $profession = std($this->input->get('profession'));
        $ethnic = std($this->input->get('ethnic'));
        $religion = std($this->input->get('religion'));
        $purpose = std($this->input->get('purpose'));
        $province = std($this->input->get('province'));
        $district = std($this->input->get('district'));
        $ward = std($this->input->get('ward'));
        $address = std($this->input->get('address'));
        $note = std($this->input->get('note'));


        $params = array();
        $params['shop_id'] = $shop_id;
        $params['bill_item_id'] = $bill_item_id;
        $params['order_id'] = $order_id;
        $params['name'] = $name;
        $params['phone'] = $phone;
        $params['gender'] = $gender;
        $params['dob'] = date_from_vn($dob);
        $params['nationality'] = $nationality;
        $params['room_number'] = $room_number;
        $params['checkin_date'] = $checkin_date;
        $params['checkout_date'] = $checkout_date;
        $params['passport_id'] = $passport_id;
        $params['estimated_leaving_date'] = $estimated_leaving_date;

        $params['paper_type'] = $paper_type;
        $params['profession'] = $profession;
        $params['ethnic'] = $ethnic;
        $params['religion'] = $religion;
        $params['purpose'] = $purpose;
        $params['province'] = $province;
        $params['district'] = $district;
        $params['ward'] = $ward;
        $params['address'] = $address;
        $params['note'] = $note;


        $this->load->model('visitor_model');
        $this->visitor_model->add_visitor($params);

        $nogs = $this->visitor_model->count_bill_item_visitor($shop_id, $bill_item_id, $bill_item['start_date'], $bill_item['end_date']);
        $this->load->model('occupy_order_model');
        foreach ($nogs as $nog) {
            $date = $nog['date'];
            $count = $nog['count'];
            $this->occupy_order_model->update_occupy_order_nog($shop_id, $bill_item_id, $date, $count);
        }
    }



    function visitor_update()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $id = intval($this->input->get('id'));
        $bill_item_id = intval($this->input->get('bill_item_id'));

        $this->load->model('bill_item_model');
        $bill_item = $this->bill_item_model->get_bill_item($bill_item_id, $shop_id);
        if (!$bill_item) {
            return;
        }

        $order_id = $bill_item['order_id'];
        //$checkin_date = $bill_item['start_date'];
        //$checkout_date = $bill_item['end_date'];

        $name = std($this->input->get('name'));
        $dob = std($this->input->get('dob'));
        $phone = std($this->input->get('phone'));
        $gender = std($this->input->get('gender'));
        $room_number = std($this->input->get('room_number'));
        $nationality = std($this->input->get('nationality'));
        $passport_id = std($this->input->get('passport_id'));
        $estimated_leaving_date = std($this->input->get('estimated_leaving_date'));

        $estimated_leaving_date = str_replace('/', '-', $estimated_leaving_date);
        $estimated_leaving_date = date('Y-m-d', strtotime($estimated_leaving_date));

        $checkin_date = std($this->input->get('checkin_date'));
        $checkin_date = date_from_vn($checkin_date);

        $checkout_date = std($this->input->get('checkout_date'));
        $checkout_date = date_from_vn($checkout_date);

        $paper_type = std($this->input->get('paper_type'));
        $profession = std($this->input->get('profession'));
        $ethnic = std($this->input->get('ethnic'));
        $religion = std($this->input->get('religion'));
        $purpose = std($this->input->get('purpose'));
        $province = std($this->input->get('province'));
        $district = std($this->input->get('district'));
        $ward = std($this->input->get('ward'));
        $address = std($this->input->get('address'));
        $note = std($this->input->get('note'));


        $params = array();

        $params['bill_item_id'] = $bill_item_id;
        $params['order_id'] = $order_id;
        $params['name'] = $name;
        $params['phone'] = $phone;
        $params['gender'] = $gender;
        $params['dob'] = date_from_vn($dob);
        $params['nationality'] = $nationality;
        $params['room_number'] = $room_number;
        $params['checkin_date'] = $checkin_date;
        $params['checkout_date'] = $checkout_date;
        $params['passport_id'] = $passport_id;
        $params['estimated_leaving_date'] = $estimated_leaving_date;

        $params['paper_type'] = $paper_type;
        $params['profession'] = $profession;
        $params['ethnic'] = $ethnic;
        $params['religion'] = $religion;
        $params['purpose'] = $purpose;
        $params['province'] = $province;
        $params['district'] = $district;
        $params['ward'] = $ward;
        $params['address'] = $address;
        $params['note'] = $note;


        $this->load->model('visitor_model');
        $this->visitor_model->update_visitor($id, $shop_id, $params);


        $nogs = $this->visitor_model->count_bill_item_visitor($shop_id, $bill_item_id, $bill_item['start_date'], $bill_item['end_date']);
        $this->load->model('occupy_order_model');
        foreach ($nogs as $nog) {
            $date = $nog['date'];
            $count = $nog['count'];
            $this->occupy_order_model->update_occupy_order_nog($shop_id, $bill_item_id, $date, $count);
        }
    }

    function delete_visitor()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $bill_item_id = intval($this->input->get('bill_item_id'));
        $id = intval($this->input->get('id'));

        $this->load->model('bill_item_model');
        $bill_item = $this->bill_item_model->get_bill_item($bill_item_id, $shop_id);

        $this->load->model('visitor_model');
        $this->visitor_model->delete_visitor($id, $bill_item_id, $shop_id);

        $nogs = $this->visitor_model->count_bill_item_visitor($shop_id, $bill_item_id, $bill_item['start_date'], $bill_item['end_date']);
        $this->load->model('occupy_order_model');
        foreach ($nogs as $nog) {
            $date = $nog['date'];
            $count = $nog['count'];
            $this->occupy_order_model->update_occupy_order_nog($shop_id, $bill_item_id, $date, $count);
        }
    }


    function get_occupied_calendar_for_update_item()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $bill_item_id = intval($this->input->get('bill_item_id'));
        $this->load->model('bill_item_model');
        $bill_item = $this->bill_item_model->get_bill_item($bill_item_id, $shop_id);
        if (!$bill_item) {
            return;
        }
        $product_id = $bill_item['product_id'];
        $item_start_date = $bill_item['start_date'];
        $item_end_date = $bill_item['end_date'];

        $month = intval($this->input->get('month'));

        $this_month = date('m', strtotime($item_start_date));

        $date = date("Y-m-1", strtotime($item_start_date));
        if ($month > 0) {
            $date = date("Y-m-d", strtotime("+" . $month . " month", strtotime($date)));
        }
        if ($month < 0) {
            $date = date("Y-m-d", strtotime("-" . abs($month) . " month", strtotime($date)));
        }
        $month = date("m", strtotime($date));
        $year = date("Y", strtotime($date));

        $this->load->model('occupy_model');
        $row = $this->occupy_model->get_occupy($shop_id, $product_id, $year, $month);
        if ($row) {
            $occupied = str_replace('2', '1', $row['occupied']);
            $occupied = str_replace('3', '1', $occupied);
            //$occupied = occupy_mask($occupied, $month, $year, $item_start_date, $item_end_date);
            //need to rewrite function occupy_mask
        } else {
            $occupied = "0000000000000000000000000000000";
        }
        //echo($occupied);
        $date = "$year-$month-1";
        $w = date("w", strtotime($date));
        //echo($w);
        //$this->load->view('calendar');
        echo ('<div class="container">');
        echo ('<div class="month_title">' . "$month - $year" . '</div>');
        echo ('<div class="occupied">S</div><div class="calendar_title">M</div><div class="calendar_title">T</div><div class="calendar_title">W</div><div class="calendar_title">T</div><div class="calendar_title">F</div><div class="occupied">S</div>');
        for ($i = 0; $i <= $w - 1; $i++) {
            echo ('<div class="pre"></div>');
        }
        $max_days = intval(date('t', strtotime($date)));
        for ($i = 0; $i < $max_days; $i++) {
            $d = intval(substr($occupied, $i, 1));
            if ($d == 1) {
                echo ('<div class="occupied" onclick="fill_date_from(' . $year . ',' . $month . ', ' . ($i + 1) . ', ' . $d . ')">' . ($i + 1) . '</div>');
            } else {
                if ($d != 5 && $d != 7) {
                    echo ('<div class="empty" onclick="fill_date_from(' . $year . ',' . $month . ', ' . ($i + 1) . ', ' . $d . ')">' . ($i + 1) . '</div>');
                } else {
                    echo ('<div class="half" onclick="fill_date_from(' . $year . ',' . $month . ', ' . ($i + 1) . ', ' . $d . ')">' . ($i + 1) . '</div>');
                }
            }
        }

        $rest = 0;
        if ($max_days == 30) {
            if ($w == 5) {
                $rest = 0;
            }
            if ($w == 0) {
                $rest = 6;
            }
            if ($w == 6) {
                $rest = 6;
            }
            if ($w == 1) {
                $rest = 4;
            }
            if ($w == 3) {
                $rest = 2;
            }
            if ($w == 4) {
                $rest = 1;
            }
            if ($w == 2) {
                $rest = 3;
            }
            if ($w == 0) {
                $rest = 5;
            }
        }
        if ($max_days == 31) {
            if ($w == 0) {
                $rest = 4;
            }
            if ($w == 3) {
                $rest = 1;
            }
            if ($w == 6) {
                $rest = 5;
            }
            if ($w == 5) {
                $rest = 6;
            }
            if ($w == 1) {
                $rest = 4;
            }
            if ($w == 2) {
                $rest = 2;
            }
        }
        if ($max_days == 28) {
            $rest = 7 - $w;
        }

        for ($i = 1; $i <= $rest; $i++) {
            echo ('<div class="pre"></div>');
        }
        echo ('</div>');
        //echo("<br><br>$max_days-$w-$rest");
    }


    function check_occupied_product_update()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $bill_item_id = intval($this->input->get('bill_item_id'));
        $start_date = std($this->input->get('start_date'));
        $start_date = date_from_vn($start_date);
        $end_date = std($this->input->get('end_date'));
        $end_date = date_from_vn($end_date);
        $nog = intval($this->input->get('nog'));
        $this->load->model('bill_item_model');
        $bill_item  = $this->bill_item_model->get_bill_item($bill_item_id, $shop_id);
        if (!$bill_item) {
            echo (0);
            return;
        }
        $order_id = $bill_item['order_id'];
        $product_id = $bill_item['product_id'];
        $old_start_date = $bill_item['start_date'];
        $old_end_date = $bill_item['end_date'];
        $old_product_name = $bill_item['product_name'];

        $this->load->model('occupy_model');

        $c = $this->occupy_model->check_occupied_product_update($shop_id, $product_id, $start_date, $end_date, $old_start_date, $old_end_date);
        if ($c) {
            $this->load->model('product_model');
            $product = $this->product_model->get_product($product_id, $shop_id);
            $product_name = $product['product_name'];
            $product_price = $bill_item['price'];
            $quantity = round(abs(strtotime($start_date) - strtotime($end_date)) / 86400);
            $amount = $quantity * $product_price;
            $params = array();
            $params['start_date'] = $start_date;
            $params['end_date'] = $end_date;
            $params['quantity'] = $quantity;
            $params['nog'] = $nog;
            $params['amount'] = $amount;

            $params['product_name'] = $product_name . ' ' . vn_date($start_date) . ' - ' . vn_date($end_date);
            //$new_product_name = str_replace(vn_date($old_start_date), vn_date($start_date), $old_product_name);
            //$new_product_name = str_replace(vn_date($old_end_date), vn_date($end_date), $new_product_name);

            //$params['product_name'] = $new_product_name;

            $this->bill_item_model->update_bill_item($shop_id, $bill_item_id, $params);
            $this->occupy_model->update_product_occupy($shop_id, $product_id, $old_start_date, $old_end_date, '0');
            $this->occupy_model->update_product_occupy($shop_id, $product_id, $start_date, $end_date, '1');
            //Update order
            $order_id = $bill_item['order_id'];
            $bill_items = $this->bill_item_model->get_order_bill_items($shop_id, $order_id);
            $amount = 0;
            foreach ($bill_items as $item) {
                $amount += $item['amount'];
            }
            $this->load->model('order_model');
            $params = array();
            $params['amount'] = $amount;

            $order = $this->order_model->get_order($order_id, $shop_id);
            $order_items = $order['order_items'];

            if ($order_items != '') {
                $order_items = json_decode($order_items, true);
                foreach ($order_items as $item) {
                    $order = $this->order_model->get_order($item, $shop_id);
                    if ($order) {
                        $amount += $order['amount'];
                    }
                }
                $params['total_amount'] = $amount;
            }

            $this->order_model->update_order0($order_id, $shop_id, $params);

            echo (round(abs(strtotime($start_date) - strtotime($end_date)) / 86400));
            //xxxupdate
            if ($bill_item['checkin_date']) {
                $this->_update_checkin_date($shop_id, $bill_item_id);
            }
            $this->update_occupy_order($shop_id, $order_id);
        } else {
            echo (0);
        }

        //echo(json_encode($result));        
    }

    function test87()
    {
        $occupy = "1111111111111111111111111111111";
        echo (visualize_occupy($occupy));
        $occupy = occupy_mask($occupy, 8, 2018, '2018-7-3', '2018-8-29');

        echo (visualize_occupy($occupy));
    }

    function test88()
    {
        print_r(explode(" ", "Hello world"));
    }

    function update_all_occupy_order($shop_id)
    {
        $this->load->model('bill_item_model');
        $rows = $this->bill_item_model->get_all_full_bill_items($shop_id);
        $this->load->model('occupy_order_model');
        $this->occupy_order_model->delete_all_occupy_order($shop_id);

        foreach ($rows as $row) {
            $product_id = $row['product_id'];
            $start_date = $row['start_date'];
            $end_date = $row['end_date'];
            $bill_item_id = $row["id"];
            if ($start_date) {
                //$start_time = 
                $start_time = strtotime($start_date);
                $end_time = strtotime($end_date);
                $order_id = $row['order_id'];
                $order_name = $row['order_name'];
                $customer_name = $row['customer_name'];
                $bill_items = $this->bill_item_model->get_serial_bill_items($shop_id, $order_id);

                while ($start_time < $end_time) {
                    $year = date('Y', $start_time);
                    $month = date('m', $start_time);
                    $day = date('d', $start_time);

                    $params = array();
                    $params['shop_id'] = $shop_id;
                    $params['product_id'] = $product_id;
                    $params['bill_item_id'] = $bill_item_id;
                    $params['year'] = $year;
                    $params['month'] = $month;
                    $params['day'] = $day;
                    $params['date'] = date('Y-m-d', $start_time);

                    $params['order_id'] = $order_id;
                    $params['day'] = $day;
                    $params['order_name'] = 'Tên đơn hàng: ' .   $order_name . "\nChi tiết:\n" . $bill_items;
                    $params['customer_name'] = $customer_name;
                    echo ($product_id . "." . $year . "." . $month . "." . $day . "<br>" . $order_name . "\n" . $bill_items . "<br>");

                    $this->occupy_order_model->add_occupy_order($params);
                    $start_time = strtotime("+1 day", $start_time);
                }
            }
        }
    }

    function update_occupy_order($shop_id, $order_id)
    {
        $this->load->model('bill_item_model');
        $this->load->model('visitor_model');
        $rows = $this->bill_item_model->get_all_full_bill_items_by_order($shop_id, $order_id);
        $this->load->model('occupy_order_model');
        $this->occupy_order_model->delete_occupy_order($shop_id, $order_id);
        $bill_items = $this->bill_item_model->get_serial_bill_items($shop_id, $order_id);

        foreach ($rows as $row) {
            $product_id = $row['product_id'];
            $start_date = $row['start_date'];
            $end_date = $row['end_date'];
            $bill_item_id = $row["id"];

            if ($start_date) {
                //$start_time = 
                $start_time = strtotime($start_date);
                $end_time = strtotime($end_date);
                $order_id = $row['order_id'];
                $order_name = $row['order_name'];
                $customer_name = $row['customer_name'];

                while ($start_time < $end_time) {
                    $year = date('Y', $start_time);
                    $month = date('m', $start_time);
                    $day = date('d', $start_time);

                    $params = array();
                    $params['shop_id'] = $shop_id;
                    $params['product_id'] = $product_id;
                    $params['bill_item_id'] = $bill_item_id;
                    $params['year'] = $year;
                    $params['month'] = $month;
                    $params['day'] = $day;
                    $params['date'] = date('Y-m-d', $start_time);

                    $params['order_id'] = $order_id;
                    $params['order_name'] = 'Tên đơn hàng: ' .   $order_name . "\nChi tiết:\n" . $bill_items;
                    $params['customer_name'] = $customer_name;

                    $this->occupy_order_model->add_occupy_order($params);


                    $start_time = strtotime("+1 day", $start_time);
                }
            }

            $nogs = $this->visitor_model->count_bill_item_visitor($shop_id, $bill_item_id, $start_date, $end_date);
            //echo(json_encode($nogs));
            foreach ($nogs as $nog) {
                $date = $nog['date'];
                $count = $nog['count'];
                $this->occupy_order_model->update_occupy_order_nog($shop_id, $bill_item_id, $date, $count);
            }
        }
    }

    function test90()
    {
        $shop_id = 710;
        $this->load->model('occupy_order_model');
        $rows = $this->occupy_order_model->select_occupy_order($shop_id, '2018-08-01', '2018-09-01');
        var_dump($rows);
    }

    function test91()
    {
        $this->load->model('table_model');
        $tables = $this->table_model->get_all_table();
        //echo(json_encode($tables));
        foreach ($tables as $table) {
            if (strpos($table, '_orders')) {
                $shop_id = str_replace("_orders", "", $table);
                $shop_id = str_replace("shop", "", $shop_id);
                echo ($shop_id . "<br>");
                $this->update_all_occupy_order($shop_id);
            }
        }
    }

    function test92()
    {
        $this->load->view('test');
    }

    function test93()
    {

        $shop_id = 944;
        $this->update_all_occupy_order($shop_id);
    }
    //toandk2 thêm update order
    function update_order()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }

        $this->load->model('order_model');
        $params = array();



        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get('order_id'));
        $content = std($this->input->get('content'));
        $params['content'] = $content;

        $customer_id = intval($this->input->get('customer_id'));
        $customer_name = std($this->input->get('customer_name'));
        $order_date = std($this->input->get('order_date'));
        $paid_date = std($this->input->get('paid_date'));
        $payment_type = std($this->input->get('payment_type'));
        $vat = std($this->input->get('vat'));
        $order_name = std($this->input->get('order_name'));
        $order_no = std($this->input->get('order_no'));

        $params['customer_id'] = $customer_id;
        $params['customer_name'] = $customer_name;
        $params['invoice_number'] = $order_no;
        $params['order_date'] = date_from_vn($order_date);
        $params['paid_date'] = date_from_vn($paid_date);
        $params['payment_type'] = $payment_type;
        $params['vat'] = $vat;
        $params['order_name'] = $order_name;
        $params['order_no'] = $order_no;

        $this->order_model->update_order0($order_id, $shop_id, $params);

        $order = $this->order_model->get_order($order_id, $shop_id);
        $old_name = std($order['order_name']);
        $this->load->model('occupy_order_model');
        $this->occupy_order_model->update_occupy_order_name($shop_id, $order_id, $old_name, $order_name);
    }
    function update_order_note()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get('order_id'));
        $content = std($this->input->get('content'));

        $this->load->model('order_model');
        $params = array();
        $params['content'] = $content;
        $this->order_model->update_order0($order_id, $shop_id, $params);
    }

    function update_order_name()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get('order_id'));
        $order_name = std($this->input->get('order_name'));

        $this->load->model('order_model');
        $order = $this->order_model->get_order($order_id, $shop_id);
        if (!$order) {
            return;
        }
        $old_name = std($order['order_name']);
        $params = array();
        $params['order_name'] = $order_name;
        $this->order_model->update_order0($order_id, $shop_id, $params);

        $this->load->model('occupy_order_model');
        $this->occupy_order_model->update_occupy_order_name($shop_id, $order_id, $old_name, $order_name);
    }


    function update_order_no()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get('order_id'));
        $order_no = std($this->input->get('order_no'));

        $this->load->model('order_model');
        $order = $this->order_model->get_order($order_id, $shop_id);
        if (!$order) {
            return;
        }
        $params = array();
        $params['invoice_number'] = $order_no;
        $this->order_model->update_order0($order_id, $shop_id, $params);

        //$this->load->model('occupy_order_model');
        //$this->occupy_order_model->update_occupy_order_name($shop_id, $order_id, $old_name, $order_name);

    }


    function update_order_date()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get('order_id'));
        $order_date = date_from_vn($this->input->get('order_date'));

        $this->load->model('order_model');
        $order = $this->order_model->get_order($order_id, $shop_id);
        if (!$order) {
            return;
        }
        $params = array();
        $params['order_date'] = $order_date;
        $this->order_model->update_order0($order_id, $shop_id, $params);

        //$this->load->model('occupy_order_model');
        //$this->occupy_order_model->update_occupy_order_name($shop_id, $order_id, $old_name, $order_name);

    }

    function update_paid_date()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get('order_id'));
        $paid_date = date_from_vn($this->input->get('paid_date'));

        $this->load->model('order_model');
        $order = $this->order_model->get_order($order_id, $shop_id);
        if (!$order) {
            return;
        }
        $params = array();
        $params['paid_date'] = $paid_date;
        $this->order_model->update_order0($order_id, $shop_id, $params);

        //$this->load->model('occupy_order_model');
        //$this->occupy_order_model->update_occupy_order_name($shop_id, $order_id, $old_name, $order_name);

    }


    function test94()
    {
        $this->output->enable_profiler(TRUE);
        $this->load->model('product_model');
        $rows = $this->product_model->get_current_product_nog(710);
        echo (json_encode($rows));
    }

    function release_room()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $product_id = intval($this->input->get('product_id'));
        $date = std($this->input->get('date'));
        $checkout_date = $date;
        $bill_item_id = intval($this->input->get('bill_item_id'));
        $this->load->model('bill_item_model');

        $bill_item = $this->bill_item_model->get_bill_item($bill_item_id, $shop_id);
        if (!$bill_item) {
            return;
        }
        $end_date = $bill_item['end_date'];

        $now = strtotime(date('Y-m-d'));
        $now = strtotime("+1 day", $now);

        $is_normal_checkout = false;
        if ($now > strtotime($end_date . " 12:00:00")) {
            $is_normal_checkout = true;
        }
        //echo(date("Y-m-d", strtotime("+1 day", strtotime($checkout_date))));
        ///echo("<br>");
        //echo($end_date);
        if (strtotime("+1 day", strtotime($checkout_date)) == strtotime($end_date)) {
            $is_normal_checkout = true;
        }

        $this->load->model('occupy_model');
        if (!$is_normal_checkout) {

            while (strtotime($date) <= strtotime($end_date)) {
                $this->occupy_model->release_occupy($shop_id, $product_id, $date);
                $date = date('Y-m-d', strtotime('+1 days', strtotime($date)));
            }
        } else {
            $checkout_date = $end_date;
        }
        //echo($is_normal_checkout);
        //return;

        $params = array();
        $params['checkout_date'] = $checkout_date;
        $this->bill_item_model->update_bill_item($shop_id, $bill_item_id, $params);
        $type = 0;
        if (!$is_normal_checkout) {
            $this->occupy_model->update_checkout_date_occupy($shop_id, $product_id, $checkout_date, $type);
        } else {
            $checkout_date = date('Y-m-d', strtotime('-1 days', strtotime($bill_item['end_date'])));
            //echo($checkout_date);
            $this->occupy_model->update_checkout_date_occupy($shop_id, $product_id, $checkout_date, $type);
        }
        //xxx
    }

    function delete_selected_product()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $ids = $this->input->get('ids');
        $ids = array_check($ids);
        $this->load->model('product_model');
        foreach ($ids as $id) {
            $this->product_model->delete_product($id, $shop_id);
        }
    }

    function nkcp_receipt()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get('order_id'));
        $cashier = $this->input->get('cashier');
        //$visitor = $this->input->get('visitor');
        $type = intval($this->input->get('type'));

        $data = array();
        $data['order_id'] = $order_id;
        $data['cashier'] = $cashier;
        //$data['visitor'] = $visitor;

        $this->load->model('report_model');
        $order = $this->report_model->nkcp($shop_id, $order_id, $type);

        $parent = intval($this->input->get('parent'));
        if ($parent > 0) {
            $this->load->model('order_model');
            $order1 = $this->order_model->get_order($parent, $shop_id);
            $room_name = $order1['order_name'];
            $data['room_name'] = $room_name;

            $this->load->model('visitor_model');
            $visitors = $this->visitor_model->get_order_visitor($parent, $shop_id);
            $vs = '';
            foreach ($visitors as $row) {
                $vs .= $row['name'] . ', ';
            }
            if (strlen($vs) > 0) {
                $vs = substr($vs, 0, strlen($vs) - 2);
            }
            $data['vs'] = $vs;
        }

        $data['order'] = $order;
        $data['day'] = date('d');
        $data['month'] = date('m');
        $data['year'] = date('Y');
        $this->load->view('nkcp', $data);
    }

    function nkcp_receipt2()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_ids = $this->input->get('order_ids');
        $cashier = $this->input->get('cashier');
        $visitor = $this->input->get('visitor');

        $data = array();
        $data['cashier'] = $cashier;
        $data['visitor'] = $visitor;

        $this->load->model('report_model');
        $order = $this->report_model->nkcp2($shop_id, $order_ids);
        if (!empty($order_ids)) {
            $data['order_id'] = intval($order_ids[0]);
        } else {
            $data['order_id'] = 0;
        }
        $data['order'] = $order;
        $data['day'] = date('d');
        $data['month'] = date('m');
        $data['year'] = date('Y');
        $this->load->view('nkcp', $data);
    }


    function nkcp_receipt3()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = $this->input->get('order_id');
        $cashier = $this->input->get('cashier');
        $visitor = $this->input->get('visitor');

        $data = array();
        $data['cashier'] = $cashier;
        $data['visitor'] = $visitor;

        $this->load->model('report_model');
        $order = $this->report_model->nkcp3($shop_id, $order_id);

        $data['order_id'] = $order_id;

        $data['order'] = $order;
        $data['day'] = date('d');
        $data['month'] = date('m');
        $data['year'] = date('Y');
        $this->load->view('nkcp', $data);
    }


    function nkcp_receipt4()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $bill_item_id = $this->input->get('item_id');

        $this->load->model('bill_item_model');
        $item = $this->bill_item_model->get_bill_item($bill_item_id, $shop_id);

        $r = $item['product_name'];

        $cashier = $this->input->get('cashier');
        $visitor = $this->input->get('visitor');
        $selected_items = $this->input->get('items');

        $data = array();
        $data['cashier'] = $cashier;
        $data['visitor'] = $visitor;
        $data['r'] = $r;

        //$selected_items = array();
        /*
        foreach($items as $item){
            $selected_items[$item] = 1;
        }
        */
        //$data['selected_items'] = $selected_items;

        //echo(json_encode($selected_items));
        $this->load->model('report_model');
        $order = $this->report_model->nkcp4($shop_id, $bill_item_id, $selected_items);
        //echo(json_encode($order));
        //return;

        $data['order'] = $order;
        $data['day'] = date('d');
        $data['month'] = date('m');
        $data['year'] = date('Y');
        $this->load->view('nkcp2', $data);
    }


    function test95()
    {
        $this->output->enable_profiler(TRUE);
        $shop_id = 710;
        $this->load->model('occupy_model');
        $this->occupy_model->update_product_occupy($shop_id, 2103, '2018-10-3', '2018-10-6', '0');
    }

    function search_visitor()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $passport_id = std($this->input->get('passport_id'));
        $this->load->model('visitor_model');
        $row = $this->visitor_model->search_visitor($shop_id, $passport_id);
        if ($row) {
            $row['dob'] = vn_date($row['dob']);
            echo (json_encode($row));
        }
    }

    function update_order_customer()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get('order_id'));
        $customer_id = intval($this->input->get('id'));
        $customer_name = std($this->input->get('name'));
        $this->load->model('order_model');
        $params = array();
        $params['customer_id'] = $customer_id;
        $params['customer_name'] = $customer_name;
        $this->order_model->update_order0($order_id, $shop_id, $params);
    }

    function cafe_update_item_quantity()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $item_id = intval($this->input->get('item_id'));
        $quantity = floatval($this->input->get('quantity'));

        $this->update_quantity($shop_id, $item_id, $quantity);
    }

    function update_quantity($shop_id, $item_id, $quantity)
    {
        $this->load->model('bill_item_model');
        $item = $this->bill_item_model->get_bill_item($item_id, $shop_id);

        if (!$item) {
            return;
        }
        if ($item['status1'] == 4) {
            return;
        }

        $price = $item['price'];
        $amount = $price * $quantity;
        $params = array();
        $params['quantity'] = $quantity;
        $params['amount'] = $amount;
        $this->bill_item_model->update_bill_item($shop_id, $item_id, $params);

        $order_id = $item['order_id'];
        $this->load->model('order_model');
        //$order = $this->order_model->get_order($order_id, $shop_id);
        $params = array();
        $this->order_model->update_order($order_id, $shop_id, $params);
    }

    function count_done_item()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $this->load->model('bill_item_model');
        $noi = $this->bill_item_model->count_items($shop_id, 2, 300);
        $result = array();
        $result['done_cooking'] = $noi;
        echo (json_encode($result));
    }

    function get_visitor()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $id = intval($this->input->get('id'));

        $this->load->model('visitor_model');
        $visitor = $this->visitor_model->get_visitor($id, $shop_id);
        if (!$visitor) {
            return;
        }
        $visitor['dob'] = vn_date($visitor['dob']);
        $visitor['estimated_leaving_date'] = vn_date($visitor['estimated_leaving_date']);
        $visitor['checkin_date'] = vn_date($visitor['checkin_date']);
        $visitor['checkout_date'] = vn_date($visitor['checkout_date']);
        echo (json_encode($visitor));
    }

    function test99()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        echo (json_encode($this->user));
    }

    function change_password()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        //echo(json_encode($this->user));
        $message = "";
        if (!empty($_POST)) {
            //$password
            $user_id = $this->user->user_id;
            $phone = $this->user->row['phone'];
            $this->load->model('shop_user_model');
            $old_password = $this->input->post('old_password');
            $old_password = dinhdq_encode($phone, $old_password);
            $this->load->model('shop_user_model');
            if ($this->shop_user_model->dinhdq_login($phone, $old_password)) {
                $new_password = $this->input->post('new_password');
                $new_password = dinhdq_encode($phone, $new_password);
                $params = array();
                $params['user_pass'] = $new_password;
                $this->shop_user_model->update_user($user_id, $shop_id, $params);
                $message = "Đổi mật khẩu thành công";
            } else {
                $message = "Sai mật khẩu cũ, đổi mật khẩu không thành công";
            }
        }
        $data = array();
        $data['title'] = tb_word('shops.purchase.orders');
        $data["user"] = $this->user;
        // $this->load->view('headers/html_header', $data);
        $data = array();
        $data['message'] = $message;

        //toandk2 sửa
        if (check_user_agent('mobile')) {
            $data['title_header'] = 'Đổi mật khẩu';
            $this->load->view('mobile_views/html_header_app', $data);
            $this->load->view('mobile_views/change_password', $data);
        } else {
            $this->load->view('headers/html_header', $data);
            $this->load->view('change_password', $data);
            $this->load->view('headers/html_footer');
        }
        // $this->load->view('change_password', $data);
        // $this->load->view('headers/html_footer');
    }

    function change_price()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $user_id = $this->user->user_id;

        $this->load->model('bill_item_model');
        $bill_item_id = intval($this->input->get('bill_item_id'));
        $price = floatval($this->input->get('price'));
        $bill_item = $this->bill_item_model->get_bill_item($bill_item_id, $shop_id);
        if ($bill_item) {
            if ($bill_item['create_user'] == $user_id || $this->user->row['user_role'] == 'shops.lists.user-roles.manager') {
                $params = array();
                $params['price'] = $price;
                $params['amount'] = $price * $bill_item['quantity'];
                $this->bill_item_model->update_bill_item($shop_id, $bill_item_id, $params);

                $this->load->model('order_model');
                $params = array();
                $this->order_model->update_order($bill_item['order_id'], $shop_id, $params);
            }
        }
    }

    function change_quantity()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $user_id = $this->user->user_id;

        $this->load->model('bill_item_model');
        $bill_item_id = intval($this->input->get('bill_item_id'));
        $quantity = floatval($this->input->get('quantity'));
        $bill_item = $this->bill_item_model->get_bill_item($bill_item_id, $shop_id);
        if ($bill_item) {
            if ($bill_item['create_user'] == $user_id || $this->user->row['user_role'] == 'shops.lists.user-roles.manager') {
                $params = array();
                $params['quantity'] = $quantity;
                $params['amount'] = $quantity * $bill_item['price'];
                $this->bill_item_model->update_bill_item($shop_id, $bill_item_id, $params);

                $this->load->model('order_model');
                $params = array();
                $this->order_model->update_order($bill_item['order_id'], $shop_id, $params);
            }
        }
    }


    function change_amount()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $user_id = $this->user->user_id;

        $this->load->model('bill_item_model');
        $bill_item_id = intval($this->input->get('bill_item_id'));
        $amount = floatval($this->input->get('amount'));
        $bill_item = $this->bill_item_model->get_bill_item($bill_item_id, $shop_id);
        if ($bill_item) {
            if ($bill_item['create_user'] == $user_id || $this->user->row['user_role'] == 'shops.lists.user-roles.manager') {
                $params = array();
                //$params['price'] = $price;
                //$params['amount'] = $price * $bill_item['quantity'];
                $params['amount'] = $amount;
                $params['price'] = $amount / $bill_item['quantity'];
                $this->bill_item_model->update_bill_item($shop_id, $bill_item_id, $params);

                $this->load->model('order_model');
                $params = array();
                $this->order_model->update_order($bill_item['order_id'], $shop_id, $params);
            }
        }
    }

    function convert_date()
    {
        $data = array();
        $data['des'] = "";
        if (!empty($_POST)) {
            $source = $this->input->post('source');
            $source = explode(",", $source);
            $des = "";
            foreach ($source as $item) {
                $des = $des . date_from_vn($item) . ",";
            }
            $data['des'] = $des;
        }
        $this->load->view('date', $data);
    }


    function get_materials()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('product_model');
        $materials = $this->product_model->get_materials($shop_id);
        $data = array();
        $data['materials'] = $materials;
        $this->load->view('materials', $data);
    }

    function delete_map()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $product_id = intval($this->input->get('product_id'));
        $material_id = intval($this->input->get('material_id'));

        $this->load->model('map_model');
        $this->map_model->delete_map($shop_id, $product_id, $material_id);
    }


    function update_map()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $product_id = intval($this->input->get('product_id'));
        $material_id = intval($this->input->get('material_id'));
        $quantity = floatval($this->input->get('quantity'));

        $this->load->model('map_model');
        $this->map_model->delete_map($shop_id, $product_id, $material_id);

        $params = array();
        $params['product_id'] = $product_id;
        $params['material_id'] = $material_id;
        $params['quantity'] = $quantity;

        $this->map_model->add_map($shop_id, $params);
    }

    function impersonate()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        if ($shop_id != 710) {
            return;
        }

        $sid = intval($this->input->get('sid'));
        $this->user->shop_id = $sid;
        $row = $this->user->shop;
        $row['id'] = $sid;
        $this->user->row["shop_id"] = $sid;
        $this->user->shop = $row;
        $this->user->save_session();
    }

    function sid()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        echo (json_encode($this->user));
    }

    function test999()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $this->load->model('bill_item_model');
        $this->load->model('visitor_model');
        $this->load->model('occupy_order_model');
        $items = $this->bill_item_model->get_all_bill_items($shop_id);
        foreach ($items as $item) {
            $bill_item_id = $item['id'];
            $nogs = $this->visitor_model->count_bill_item_visitor($shop_id, $bill_item_id, $item['start_date'], $item['end_date']);
            //echo(json_encode($nogs));
            foreach ($nogs as $nog) {
                $date = $nog['date'];
                $count = $nog['count'];
                $this->occupy_order_model->update_occupy_order_nog($shop_id, $bill_item_id, $date, $count);
                $afftectedRows = $this->db->affected_rows();
                echo ($afftectedRows . '<br>');
            }
        }
    }
    function test_encrypt()
    {
        $pw = encrypt('123456a', '1112' . 'admin_01_004156_01e8010504' . '123456');
        //echo($pw);
        echo ('1112' . 'admin_01_004156_01e8010504' . '123456' . '<br>');
        echo (decrypt('RWiOk/qR1nGiGY7RBHzu71Sri2HUgXyMhKcawqq9bF4=', '1112' . 'admin_01_004156_01e8010504' . '123456'));
    }

    function message()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $message0 = '';
        $this->load->model('notification_model');
        $messages = $this->notification_model->get_shop_notifications($shop_id);
        foreach ($messages as $message) {
            $message0 .= '<div class="alert alert-warning" style="margin:0"><a href="#" onclick="dismiss(' . $message['id'] . ')" class="close" data-dismiss="alert">&times;</a>' . $message['content'] . '</div>';
        }


        if (isset($_SESSION['dismissed'])) {
            if (intval($_SESSION['dismissed']) == 1) {
                if ($message0 != '') {
                    $result['code'] = 1;
                    $result['message'] = $message0;
                    echo (json_encode($result));
                }
                return;
            }
        }

        $shop_type = intval($this->user->shop['type']);
        $result = array();
        if ($shop_type != 11) {
            $this->load->model('product_model');
            $row = $this->product_model->count_alert_stock_products($shop_id);
            $c = intval($row['c']);
            if ($c > 0) {
                $message = 'Có <b><a href="/report?type=24">' . $c . ' sản phẩm</a></b> sắp hết hàng trong kho';
                $message = '<div class="alert alert-warning" style="margin:0"><a href="#" onclick="dismiss(0)" class="close" data-dismiss="alert">&times;</a>' . $message . '</div>';
                $result['code'] = 1;
                $result['message'] = $message;
            } else {
                $result['code'] = 0;
                $result['message'] = '';
            }
            echo (json_encode($result));
            return;
        }
        $this->load->model('shop_model');
        $shop = $this->shop_model->get_shop($shop_id);
        if (!$shop) {
            $result['code'] = 0;
            $result['message'] = '';
            echo (json_encode($result));
            return;
        }

        $last_777 = $shop['last_777'];

        $today = date('y-m-d');
        $diff = diff($last_777, $today);
        $message = '';
        if ($diff > 3) {
            $message = 'Đã <b>' . $diff . ' ngày</b> rồi bạn chưa gửi báo cáo <b>777/QĐ-QLD</b> tới Sở Y tế.';
            $message = '<div class="alert alert-warning" style="margin:0"><a href="#" onclick="dismiss(0)" class="close" data-dismiss="alert">&times;</a>' . $message . '</div>';
            $result['code'] = 1;
            $message = $message . $message0;
            $result['message'] = $message;
        } else {
            $result['code'] = 1;
            $result['message'] = $message0;
        }

        echo (json_encode($result));
    }

    function dismiss()
    {
        $id = intval($this->input->get('id'));
        if ($id == 0) {
            session_start();
            $_SESSION['dismissed'] = 1;
            return;
        }
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('notification_model');
        $this->notification_model->dismiss_notification($shop_id, $id);
    }

    function pharm_help()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $data = array();
        $data['title'] = tb_word('shops.purchase.orders');
        $data["user"] = $this->user;
        $this->load->view('headers/html_header', $data);
        $this->load->view('pharmacy/help');
        $this->load->view('headers/html_footer');
    }

    function update_shop_ebill()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        $this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('shop_ebill_model');
        $company = std($this->input->post('company'));

        $this->load->model('shop_model');

        if ($company == 'nacencomm') {
            $content = array();
            $content['company'] = 'nacencomm';
            $content['ncc_url'] = std($this->input->post('ncc_url'));
            $content['ma_so_thue'] = std($this->input->post('ma_so_thue'));
            $content['username'] = std($this->input->post('username'));
            $content['password'] = std($this->input->post('password'));
            $content['symbol'] = std($this->input->post('symbol'));
            $content['symbol0'] = std($this->input->post('symbol0'));
            $content['default'] = intval($this->input->post('nacencomm_default'));
            $content = json_encode($content);
            $params = array();
            $params['content'] = $content;
            $params['company'] = 'nacencomm';

            $this->shop_ebill_model->update($shop_id, $company, $params);
            $params = array();
            $params['e_bill'] = 'nacencomm';
            $this->shop_model->update_shop($shop_id, $params);
        }

        if ($company == 'minvoice') {
            $params = array();
            $content = array();
            $content['minvoice_username'] = std($this->input->post('minvoice_username'));
            $content['minvoice_password'] = $this->input->post('minvoice_password');
            $content['minvoice_url'] = $this->input->post('minvoice_url');
            $content['default'] = intval($this->input->post('minvoice_default'));

            $content = json_encode($content);
            $params['content'] = $content;
            $params['company'] = 'minvoice';
            $this->shop_ebill_model->update($shop_id, $company, $params);

            $params = array();
            $params['e_bill'] = 'minvoice';
            $this->shop_model->update_shop($shop_id, $params);
        }

        if ($company == 'vnpt') {
            $params = array();
            $content = array();
            $content['account'] = std($this->input->post('vnpt_account'));
            $content['acpass'] = $this->input->post('vnpt_acpass');
            $content['username'] = std($this->input->post('vnpt_username'));
            $content['password'] = $this->input->post('vnpt_password');
            $content['url'] = std($this->input->post('vnpt_url'));
            $content['serial'] = std($this->input->post('vnpt_serial'));
            $content['pattern'] = std($this->input->post('vnpt_pattern'));
            if ($content['serial'] == '') {
                //$content['serial'] = 'C23TAA';
            }
            if ($content['pattern'] == '') {
                //$content['pattern'] = '2/001';
            }
            $content['default'] = intval($this->input->post('vnpt_default'));

            $content = json_encode($content);
            $params['content'] = $content;
            $params['company'] = 'vnpt';
            $this->shop_ebill_model->update($shop_id, $company, $params);
            $params = array();
            $params['e_bill'] = 'vnpt';
            $this->shop_model->update_shop($shop_id, $params);
        }

        if ($company == 'viettel') {
            $params = array();
            $content = array();
            $content['viettel_username'] = std($this->input->post('viettel_username'));
            $content['viettel_password'] = $this->input->post('viettel_password');
            $content['default'] = intval($this->input->post('viettel_default'));

            $content = json_encode($content);
            $params['content'] = $content;
            $params['company'] = 'viettel';
            $this->shop_ebill_model->update($shop_id, $company, $params);

            $params = array();
            $params['e_bill'] = 'viettel';
            $this->shop_model->update_shop($shop_id, $params);
        }
    }


    function shop_ebill()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);


        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $data = array();
        $this->load->model('shop_model');
        $shop = $this->shop_model->get_shop($shop_id);
        $data['shop'] = $shop;

        $this->load->model('shop_ebill_model');
        $post = 0;
        $shop_ebill = $this->shop_ebill_model->get_shop_ebill($shop_id, 'nacencomm');
        if (!$shop_ebill) {
            $shop_ebill = array();
            $shop_ebill['shop_id'] = $shop_id;
            $shop_ebill['ma_so_thue'] = '';
            $shop_ebill['usernamews'] = '';
            $shop_ebill['passwordws'] = '';
            $shop_ebill['mau_so_hd'] = '';
            $shop_ebill['ky_hieu'] = '';
            $shop_ebill['ma_loai_hd'] = '';
        }


        $shop_ebills = $this->shop_ebill_model->get_shop_ebills($shop_id);
        $s = array();
        foreach ($shop_ebills as $item) {
            $s[$item['company']] = json_decode($item['content'], true);
        }


        $data['title'] = 'Thông tin hóa đơn điện tử';
        $data["user"] = $this->user;
        $data['post'] = $post;
        $data['shop_ebill'] = $shop_ebill;
        $data['s'] = $s;

        // toandk2 sửa

        if (check_user_agent('mobile')) {
            $data['title_header'] = 'Thông tin hóa đơn điện tử';
            $this->load->view('mobile_views/html_header_app', $data);
            $this->load->view('mobile_views/shop_ebill', $data);
        } else {
            $this->load->view('headers/html_header', $data);
            $this->load->view('shop_ebill', $data);
            $this->load->view('headers/html_footer');
        }
    }



    function update_shop_detail()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('shop_detail_model');


        $params = array();
        $params['name'] = std($this->input->post('name'));
        $params['unit'] = std($this->input->post('unit'));
        $params['address'] = std($this->input->post('address'));
        $params['bill_book'] = std($this->input->post('bill_book'));
        $params['director'] = std($this->input->post('director'));
        $params['chief_accountant'] = std($this->input->post('chief_accountant'));
        $params['cashier'] = std($this->input->post('cashier'));
        $params['storekeeper'] = std($this->input->post('storekeeper'));

        $params['base_salary'] = floatval($this->input->post('base_salary'));
        $params['social_insurance_rate'] = floatval($this->input->post('social_insurance_rate'));
        $params['health_insurance_rate'] = floatval($this->input->post('health_insurance_rate'));
        $params['unemployment_insurance_rate'] = floatval($this->input->post('unemployment_insurance_rate'));

        $this->shop_detail_model->update_shop_detail($shop_id, $params);
    }

    function shop_detail()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('shop_detail_model');
        $post = 0;
        $shop_detail = $this->shop_detail_model->get_shop_detail($shop_id);
        if (!$shop_detail) {
            $shop_detail = array();
            $shop_detail['name'] = $this->user->shop['name'];
            $shop_detail['unit'] = $this->user->shop['name'];
            $shop_detail['address'] = $this->user->shop['address'];
            $shop_detail['bill_book'] = '';
            $shop_detail['director'] = '';
            $shop_detail['chief_accountant'] = '';
            $shop_detail['cashier'] = '';
            $shop_detail['storekeeper'] = '';
            $shop_detail['director'] = '';
            $shop_detail['chief_accountant'] = '';
            $shop_detail['cashier'] = '';
            $shop_detail['base_salary'] = '';
            $shop_detail['social_insurance_rate'] = '';
            $shop_detail['health_insurance_rate'] = '';
            $shop_detail['unemployment_insurance_rate'] = '';
        }

        $data = array();
        $data['title'] = tb_word('shops.purchase.orders');
        $data["user"] = $this->user;
        $data['post'] = $post;
        $data['shop_detail'] = $shop_detail;
        // toandk2 sửa

        if (check_user_agent('mobile')) {
            $data['title_header'] = 'Thông tin trên số kế toán';
            $this->load->view('mobile_views/html_header_app', $data);
            $this->load->view('mobile_views/shop_detail', $data);
        } else {
            $this->load->view('headers/html_header', $data);
            $this->load->view('shop_detail', $data);
            $this->load->view('headers/html_footer');
        }

        // $this->load->view('headers/html_header', $data);
        // $this->load->view('shop_detail', $data);
        // $this->load->view('headers/html_footer');
    }

    function ktsn_receipt()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get("order_id"));
        $this->load->model('order_model');

        $order = $this->order_model->get_order2($order_id, $shop_id);
        //echo(json_encode($order));
        $data['order'] = $order;
        $data['date'] = $order['order_date'];
        $data['invoice_number'] = $order['invoice_number'];
        if ($order['order_type'] == 'B') {
            $data['template'] = '01';
            $data['title'] = 'Phiếu thu';
            $data['customer_title'] = 'Họ tên người nộp tiền';
        } else {
            $data['template'] = '02';
            $data['title'] = 'Phiếu chi';
            $data['customer_title'] = 'Họ tên người nhận tiền';
        }
        $this->load->view('ktsn_receipt', $data);
    }

    function ktsn_receipt1()
    {

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get("order_id"));

        $this->load->model('shop_model');
        $shop = $this->shop_model->get_shop($shop_id);
        $data['shop'] = $shop;

        $this->load->model('shop_detail_model');
        $shop_detail = $this->shop_detail_model->get_shop_detail($shop_id);

        $data['shop_detail'] = $shop_detail;


        $this->load->model('order_model');

        $order = $this->order_model->get_order2($order_id, $shop_id);
        //echo(json_encode($order));
        $data['order'] = $order;
        $data['date'] = $order['order_date'];
        $data['invoice_number'] = $order['invoice_number'];
        if ($order['order_type'] == 'B') {
            $data['template'] = '01';
            $data['title'] = 'PHIẾU THU';
            $data['customer_title'] = 'Họ tên người nộp tiền';
        } else {
            $data['template'] = '02';
            $data['title'] = 'PHIẾU CHI';
            $data['customer_title'] = 'Họ tên người nhận tiền';
        }
        $this->load->model('bill_item_model');
        $bill_items = $this->bill_item_model->get_order_bill_items($shop_id, $order_id);
        $discount = 0;
        foreach ($bill_items as $item) {
            if ($order['order_type'] == 'B' && $order['diners'] != 0) {
                $tax = $item['gtgt'];
                $tax = $item['amount'] * $tax / 100;
                $discount += $tax * 20 / 100;
            }
        }
        $data['discount'] = $discount;
        if ($order['order_type'] == 'B') {
            $this->load->view('ktsn_receipt1', $data);
        } else {
            $this->load->view('ktsn_receipt2', $data);
        }
    }


    function ktsn_stock()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get("order_id"));

        $this->load->model('order_model');
        $order = $this->order_model->get_order2($order_id, $shop_id);
        $data['order'] = $order;
        $data['date'] = $order['order_date'];
        $data['invoice_number'] = $order['invoice_number'];

        $this->load->model('bill_model');
        $this->load->model('bill_item_model');

        $bill = $this->bill_model->get_order_bill($order_id, $shop_id);
        $bill_items = $this->bill_item_model->get_bill_items($shop_id, $bill['id']);


        if ($order['order_type'] == 'B') {
            $data['template'] = '02';
            $data['title'] = 'Phiếu xuất kho';
            $data['customer_title'] = 'Họ tên người nhận hàng';
            $data['customer_title1'] = 'Người nhận hàng';
        } else {
            $data['template'] = '01';
            $data['title'] = 'Phiếu nhập kho';
            $data['customer_title'] = 'Họ tên người giao';
            $data['customer_title1'] = 'Người giao hàng';
        }
        $data['products'] = $bill_items;
        $this->load->view('ktsn_stock', $data);
    }

    function ktsn_stock1()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);
        //$this->output->enable_profiler(TRUE);

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get("order_id"));
        $this->load->model('shop_model');
        $shop = $this->shop_model->get_shop($shop_id);
        $data['shop'] = $shop;
        $data['shop_id'] = $shop_id;
        $this->load->model('shop_detail_model');
        $shop_detail = $this->shop_detail_model->get_shop_detail($shop_id);

        $data['shop_detail'] = $shop_detail;

        $this->load->model('order_model');
        $order = $this->order_model->get_order2($order_id, $shop_id);
        $data['order'] = $order;
        $data['date'] = $order['order_date'];
        $data['invoice_number'] = $order['invoice_number'];

        //$this->load->model('bill_model');
        $this->load->model('bill_item_model');

        //$bill = $this->bill_model->get_order_bill($order_id, $shop_id);
        $bill_items = $this->bill_item_model->get_bill_items0($shop_id, $order_id);


        if ($order['order_type'] == 'B') {
            $data['template'] = '04';
            $data['title'] = 'PHIẾU XUẤT KHO';
            $data['customer_title'] = 'Họ tên người nhận hàng';
            $data['customer_title1'] = 'NGƯỜI NHẬN HÀNG';
            $data["type"] = 'xuất';
        } else {
            $data['template'] = '03';
            $data['title'] = 'PHIẾU NHẬP KHO';
            $data['customer_title'] = 'Họ tên người giao hàng';
            $data['customer_title1'] = 'NGƯỜI GIAO HÀNG';
            $data["type"] = 'nhập';
        }
        $data['products'] = $bill_items;
        $this->load->view('ktsn_stock1', $data);
    }


    function ktsn_merged_stock()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get("order_id"));

        $this->load->model('order_model');
        $order = $this->order_model->get_order($order_id, $shop_id);
        $data['order'] = $order;
        $data['date'] = $order['order_date'];
        $data['invoice_number'] = $order['invoice_number'];

        $this->load->model('bill_model');
        $this->load->model('bill_item_model');

        $items = $order['order_items'];
        $items_ids = json_decode($items);
        $this->load->model('bill_item_model');
        $items = array();
        foreach ($items_ids as $id) {
            $item = $this->bill_item_model->get_bill_item0($id, $shop_id);
            $items[] = $item;
        }
        //$data["bill_items"] = $items;


        if ($order['order_type'] == 'B') {
            $data['template'] = '02';
            $data['title'] = 'Phiếu xuất kho';
            $data['customer_title'] = 'Họ tên người nhận hàng';
            $data['customer_title1'] = 'Người nhận hàng';
        } else {
            $data['template'] = '01';
            $data['title'] = 'Phiếu nhập kho';
            $data['customer_title'] = 'Họ tên người giao';
            $data['customer_title1'] = 'Người giao hàng';
        }
        $data['products'] = $items;
        $this->load->view('ktsn_stock', $data);
    }

    function employees()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('employee_model');
        $employees = $this->employee_model->get_all_employees($shop_id);

        $data = array();
        $data['title'] = tb_word('shops.purchase.orders');
        $data["user"] = $this->user;

        $data['employees'] = $employees;

        if (check_user_agent('mobile')) {
            $data['title_header'] = 'Bảng lương người lao động';
            $this->load->view('mobile_views/html_header_app', $data);
            $this->load->view('mobile_views/employees', $data);
            $this->load->view('mobile_views/html_footer_app', $data);
        } else {
            $this->load->view('headers/html_header', $data);
            $this->load->view('employees', $data);
            $this->load->view('headers/html_footer');
        }
    }

    function employee()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('employee_model');
        $id = intval($this->input->get("id"));
        if (!empty($_POST)) {

            $params = array();
            $params['shop_id'] = $shop_id;
            $params['name'] = std($this->input->post('name'));
            $params['description'] = std($this->input->post('description'));
            $params['level'] = floatval($this->input->post('level'));
            $params['product_salary'] = floatval($this->input->post('product_salary'));
            $params['time_salary'] = floatval($this->input->post('time_salary'));
            $params['salary_allowance'] = floatval($this->input->post('salary_allowance'));
            $params['allowance'] = floatval($this->input->post('allowance'));
            $params['bonus'] = floatval($this->input->post('bonus'));
            $params['dependents'] = floatval($this->input->post('dependents'));
            /*
            $params['social_insurance'] = floatval($this->input->post('social_insurance'));
            $params['health_insurance'] = floatval($this->input->post('health_insurance'));
            $params['unemployment_insurance'] = floatval($this->input->post('unemployment_insurance'));
            $params['personal_income_tax'] = floatval($this->input->post('personal_income_tax'));
            */

            if ($id == 0) { //add
                $this->employee_model->add_employee($params);
            } else { //update
                $this->employee_model->update_employee($id, $shop_id, $params);
            }


            redirect('/employees');
        }

        $data = array();
        $data['title'] = tb_word('shops.purchase.orders');
        $data["user"] = $this->user;

        $employee = $this->employee_model->get_employee($id, $shop_id);

        $data['employee'] = $employee;
        $data['id'] = $id;

        if (check_user_agent('mobile')) {
            $data['title_header'] = 'Người lao động';
            $this->load->view('mobile_views/html_header_app', $data);
            $this->load->view('mobile_views/employee', $data);
            $this->load->view('mobile_views/html_footer_app', $data);
        } else {
            $this->load->view('headers/html_header', $data);
            $this->load->view('employee', $data);
            $this->load->view('headers/html_footer');
        }
    }

    function employee_order()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $month = intval($this->input->get('month'));
        $year = intval($this->input->get('year'));
        $date = date('d/m/Y');

        $data = array();
        $this->load->model('shop_detail_model');
        $shop_detail = $this->shop_detail_model->get_shop_detail($shop_id);
        $data['shop_detail'] = $shop_detail;

        $this->load->model('employee_model');
        $employees = $this->employee_model->get_active_employees($shop_id);
        $es = array();
        foreach ($employees as $employee) {
            $employee['product_salary_quantity'] = 1;
            $employee['time_salary_quantity'] = 1;
            $employee['vacation_salary'] = 0;
            $employee['vacation_salary_quantity'] = 1;
            $employee['social_insurance'] = $employee['level'] * $shop_detail['social_insurance_rate'] * $shop_detail['base_salary'] / 100;
            $employee['health_insurance'] = $employee['level'] * $shop_detail['health_insurance_rate'] * $shop_detail['base_salary'] / 100;
            $employee['unemployment_insurance'] = $employee['level'] * $shop_detail['unemployment_insurance_rate'] * $shop_detail['base_salary'] / 100;
            $es[$employee['id']] = $employee;
        }


        $data['title'] = tb_word('shops.purchase.orders');
        $data["user"] = $this->user;

        $data['employees'] = $employees;
        $data['es'] = $es;
        //echo(json_encode($es));
        //$month = date('m');

        $data['month'] = $month;
        $data['year'] = $year;
        $data['date'] = $date;
        //toandk2 sửa
        if (check_user_agent('mobile')) {
            $data['title_header'] = 'Tạo mới bảng lương';
            $this->load->view('mobile_views/html_header_app', $data);
            $this->load->view('mobile_views/employee_order', $data);
        } else {
            $this->load->view('headers/html_header', $data);
            $this->load->view('employee_order', $data);
            $this->load->view('headers/html_footer');
        }
        // $this->load->view('employee_order', $data);
        // $this->load->view('headers/html_footer');
    }

    function employee_order_update2()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->post("order_id"));
        $data = $this->input->post("data");

        $order_name = std($this->input->post("order_name"));
        $order_date = date_from_vn(std($this->input->post("order_date")));
        $unit = std($this->input->post("unit"));
        $address = std($this->input->post("address"));
        $cashier = std($this->input->post("cashier"));
        $chief_accountant = std($this->input->post("chief_accountant"));
        $director = std($this->input->post("director"));

        $month = intval($this->input->post("month"));
        $year = intval($this->input->post("year"));

        $this->load->model('shop_detail_model');
        $shop_detail = $this->shop_detail_model->get_shop_detail($shop_id);
        $base_salary = 0;
        if ($shop_detail) {
            $base_salary = $shop_detail['base_salary'];
        }

        //echo(json_encode($data));
        $this->load->model('order_model');
        $params = array();
        $params['shop_id'] = $shop_id;
        $params['order_name'] = $order_name;

        $params['order_date'] = $order_date;
        $params['unit'] = $unit;
        $params['address'] = $address;
        $params['cashier'] = $cashier;
        $params['chief_accountant'] = $chief_accountant;
        $params['director'] = $director;
        $params['order_type'] = 'M';
        $params['user_id'] = $this->user->user_id;
        $params['order_type1'] = 1;
        $params['status1'] = 4;
        $params['month'] = $month;
        $params['year'] = $year;

        $this->order_model->update_order0($order_id, $shop_id, $params);
        $this->load->model('order_salary_model');
        $total = 0;
        foreach ($data as $key => $value) {
            $params = array();
            $params['shop_id'] = $shop_id;
            $params['order_id'] = $order_id;
            $params['employee_id'] = $key;
            $params['level'] = floatval($value['level']);
            $params['base_salary'] = floatval($value['base_salary']);
            $params['employee_name'] = std($value['name']);
            $params['product_salary'] = floatval($value['product_salary']);
            $params['product_salary_quantity'] = floatval($value['product_salary_quantity']);
            $params['time_salary'] = floatval($value['time_salary']);
            $params['time_salary_quantity'] = floatval($value['time_salary_quantity']);
            $params['vacation_salary'] = floatval($value['vacation_salary']);
            $params['vacation_salary_quantity'] = floatval($value['vacation_salary_quantity']);
            $params['salary_allowance'] = floatval($value['salary_allowance']);
            $params['allowance'] = floatval($value['allowance']);
            $params['bonus'] = floatval($value['bonus']);
            $params['social_insurance'] = floatval($value['social_insurance']);
            $params['health_insurance'] = floatval($value['health_insurance']);
            $params['unemployment_insurance'] = floatval($value['unemployment_insurance']);
            $params['personal_income_tax'] = floatval($value['personal_income_tax']);
            $this->order_salary_model->add_order_salary($params);

            $total1 = floatval($value['product_salary']) * floatval($value['product_salary_quantity']) + floatval($value['time_salary']) * floatval($value['time_salary_quantity']) + floatval($value['vacation_salary']) * floatval($value['vacation_salary_quantity']) + floatval($value['salary_allowance']) + floatval($value['allowance']) + floatval($value['bonus']);
            $total += $total1;
        }
        $params = array();
        $params['amount'] = $total;
        $this->order_model->update_order0($order_id, $shop_id, $params);

        $result = array();
        $result['order_id'] = $id;
        echo (json_encode($result));
    }


    function employee_order_save()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $data = $this->input->post("data");

        $order_name = std($this->input->post("order_name"));
        $order_date = date_from_vn(std($this->input->post("order_date")));
        $unit = std($this->input->post("unit"));
        $address = std($this->input->post("address"));
        $cashier = std($this->input->post("cashier"));
        $chief_accountant = std($this->input->post("chief_accountant"));
        $director = std($this->input->post("director"));

        $month = intval($this->input->post("month"));
        $year = intval($this->input->post("year"));

        $this->load->model('shop_detail_model');
        $shop_detail = $this->shop_detail_model->get_shop_detail($shop_id);
        $base_salary = 0;
        if ($shop_detail) {
            $base_salary = $shop_detail['base_salary'];
        }

        //echo(json_encode($data));
        $this->load->model('order_model');
        $params = array();
        $params['shop_id'] = $shop_id;
        $params['order_name'] = $order_name;

        $params['order_date'] = $order_date;
        $params['unit'] = $unit;
        $params['address'] = $address;
        $params['cashier'] = $cashier;
        $params['chief_accountant'] = $chief_accountant;
        $params['director'] = $director;
        $params['order_type'] = 'M';
        $params['user_id'] = $this->user->user_id;
        $params['order_type1'] = 1;
        $params['status1'] = 4;
        $params['month'] = $month;
        $params['year'] = $year;

        $order_id = $this->order_model->add_order($params);
        $this->load->model('order_salary_model');
        $total = 0;
        foreach ($data as $key => $value) {
            $params = array();
            $params['shop_id'] = $shop_id;
            $params['order_id'] = $order_id;
            $params['employee_id'] = $key;
            $params['level'] = floatval($value['level']);
            $params['base_salary'] = floatval($value['base_salary']);
            $params['employee_name'] = std($value['name']);
            $params['product_salary'] = floatval($value['product_salary']);
            $params['product_salary_quantity'] = floatval($value['product_salary_quantity']);
            $params['time_salary'] = floatval($value['time_salary']);
            $params['time_salary_quantity'] = floatval($value['time_salary_quantity']);
            $params['vacation_salary'] = floatval($value['vacation_salary']);
            $params['vacation_salary_quantity'] = floatval($value['vacation_salary_quantity']);
            $params['salary_allowance'] = floatval($value['salary_allowance']);
            $params['allowance'] = floatval($value['allowance']);
            $params['bonus'] = floatval($value['bonus']);
            $params['social_insurance'] = floatval($value['social_insurance']);
            $params['health_insurance'] = floatval($value['health_insurance']);
            $params['unemployment_insurance'] = floatval($value['unemployment_insurance']);
            $params['personal_income_tax'] = floatval($value['personal_income_tax']);
            $this->order_salary_model->add_order_salary($params);

            $total1 = floatval($value['product_salary']) * floatval($value['product_salary_quantity']) + floatval($value['time_salary']) * floatval($value['time_salary_quantity']) + floatval($value['vacation_salary']) * floatval($value['vacation_salary_quantity']) + floatval($value['salary_allowance']) + floatval($value['allowance']) + floatval($value['bonus']);
            $total += $total1;
        }
        $params = array();
        $params['amount'] = $total;
        $this->order_model->update_order0($order_id, $shop_id, $params);

        $result = array();
        $result['order_id'] = $id;
        echo (json_encode($result));
    }

    function employee_order_edit()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get('id'));
        $this->load->model('order_model');
        $order = $this->order_model->get_order($order_id, $shop_id);
        if (!$order) {
            return;
        }

        $data = array();

        $data['order'] = $order;
        $this->load->model('shop_detail_model');
        $shop_detail = $this->shop_detail_model->get_shop_detail($shop_id);
        $data['shop_detail'] = $shop_detail;

        $this->load->model('order_salary_model');
        $employees = $this->order_salary_model->get_all_order_salaries($shop_id, $order_id);
        $es = array();
        foreach ($employees as $employee) {
            $es[$employee['id']] = $employee;
        }

        $data['title'] = tb_word('shops.purchase.orders');
        $data["user"] = $this->user;


        $data['employees'] = $employees;
        $data['es'] = $es;
        //echo(json_encode($es));
        //$month = date('m');
        $date = date('d/m/Y');

        $data['date'] = $date;
        //toandk2 sửa
        if (check_user_agent('mobile')) {
            $data['title_header'] = 'Bảng lương';
            $this->load->view('mobile_views/html_header_app', $data);
            $this->load->view('mobile_views/employee_order_edit', $data);
        } else {
            $this->load->view('headers/html_header', $data);
            $this->load->view('employee_order_edit', $data);
            $this->load->view('headers/html_footer');
        }
    }


    function employee_order_update()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = $this->input->post('order_id');
        $data = $this->input->post("data");

        $order_name = std($this->input->post("order_name"));
        $order_date = date_from_vn(std($this->input->post("order_date")));
        $unit = std($this->input->post("unit"));
        $address = std($this->input->post("address"));
        $cashier = std($this->input->post("cashier"));
        $chief_accountant = std($this->input->post("chief_accountant"));
        $director = std($this->input->post("director"));

        //echo(json_encode($data));
        $this->load->model('order_model');
        $params = array();
        $params['shop_id'] = $shop_id;
        $params['order_name'] = $order_name;

        $params['order_date'] = $order_date;
        $params['unit'] = $unit;
        $params['address'] = $address;
        $params['cashier'] = $cashier;
        $params['chief_accountant'] = $chief_accountant;
        $params['director'] = $director;
        $params['order_type'] = 'M';
        $params['user_id'] = $this->user->user_id;
        $params['order_type1'] = 1;
        $params['status1'] = 4;

        $this->order_model->update_order0($order_id, $shop_id, $params);


        $this->load->model('order_salary_model');

        $this->order_salary_model->update_order_salaries($order_id, $shop_id);
        $total = 0;
        foreach ($data as $key => $value) {
            $params = array();
            $params['shop_id'] = $shop_id;
            $params['order_id'] = $order_id;
            $params['employee_id'] = intval($key);
            $params['level'] = floatval($value['level']);
            $params['employee_name'] = std($value['employee_name']);
            $params['product_salary'] = floatval($value['product_salary']);
            $params['product_salary_quantity'] = floatval($value['product_salary_quantity']);
            $params['time_salary'] = floatval($value['time_salary']);
            $params['time_salary_quantity'] = floatval($value['time_salary_quantity']);
            $params['vacation_salary'] = floatval($value['vacation_salary']);
            $params['vacation_salary_quantity'] = floatval($value['vacation_salary_quantity']);
            $params['salary_allowance'] = floatval($value['salary_allowance']);
            $params['allowance'] = floatval($value['allowance']);
            $params['bonus'] = floatval($value['bonus']);
            $params['social_insurance'] = floatval($value['social_insurance']);
            $params['health_insurance'] = floatval($value['health_insurance']);
            $params['unemployment_insurance'] = floatval($value['unemployment_insurance']);
            $params['personal_income_tax'] = floatval($value['personal_income_tax']);
            $this->order_salary_model->add_order_salary($params);

            $total1 = floatval($value['product_salary']) * floatval($value['product_salary_quantity']) + floatval($value['time_salary']) * floatval($value['time_salary_quantity']) + floatval($value['vacation_salary']) * floatval($value['vacation_salary_quantity']) + floatval($value['salary_allowance']) + floatval($value['allowance']) + floatval($value['bonus']);
            $total += $total1;
        }
        $params = array();
        $params['amount'] = $total;
        $this->order_model->update_order0($order_id, $shop_id, $params);

        $result = array();
        $result['order_id'] = $id;
        echo (json_encode($result));
    }


    function salary_order_detail()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = std($this->input->get("id"));


        $this->load->model('order_model');
        $order = $this->order_model->get_order($order_id, $shop_id);
        if (!$order) {
            return;
        }

        $data = array();
        $data['title'] = 'Lương';
        $data["user"] = $this->user;
        $this->load->view('headers/html_header', $data);

        $data['order'] = $order;

        $this->load->model('order_salary_model');
        $items = $this->order_salary_model->get_all_order_salaries($shop_id, $order_id);
        $data['items'] = $items;
        $this->load->view('salary_order', $data);
        $this->load->view('headers/html_footer');
    }

    function salary_order_report()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = std($this->input->get("id"));

        $this->load->model('order_model');
        $order = $this->order_model->get_order($order_id, $shop_id);
        if (!$order) {
            return;
        }

        $data = array();
        $data['order'] = $order;

        $data['date'] = $order['order_date'];

        $this->load->model('order_salary_model');
        $items = $this->order_salary_model->get_order_salary_items($shop_id, $order_id);
        $data['items'] = $items;
        $this->load->view('salary_order_report', $data);
    }

    function report132_1()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $data = array();
        $date = std($this->input->get('date'));
        $year = intval($this->input->get('year'));
        $unit = std($this->input->get('unit'));
        $address = std($this->input->get('address'));
        $open_date = std($this->input->get('open_date'));
        $director = std($this->input->get('director'));
        $chief_accountant = std($this->input->get('chief_accountant'));
        $creator = std($this->input->get('creator'));

        $date_from = std($this->input->get('date_from'));
        if ($date_from != '') {
            $date_from = date_from_vn($date_from);
        }
        $date_to = std($this->input->get('date_to'));
        if ($date_to != '') {
            $date_to = date_from_vn($date_to);
        }
        if ($date_to != '' && $date_from != '') {
            $year = 0;
        }

        $data['date'] = $date;
        $data['year'] = $year;
        $data['unit'] = $unit;
        $data['address'] = $address;
        $data['open_date'] = $open_date;
        $data['director'] = $director;
        $data['chief_accountant'] = $chief_accountant;
        $data['creator'] = $creator;

        $data['date_from'] = $date_from;
        $data['date_to'] = $date_to;

        $this->load->model('report_model');

        $items = $this->report_model->report132_1($shop_id, $year, $date_from, $date_to);
        $data['items'] = $items;

        $this->load->view('report132_1', $data);
    }


    function report132_4()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $date = std($this->input->get('date'));
        $year = intval($this->input->get('year'));
        $unit = std($this->input->get('unit'));
        $address = std($this->input->get('address'));
        $open_date = std($this->input->get('open_date'));
        $director = std($this->input->get('director'));
        $chief_accountant = std($this->input->get('chief_accountant'));
        $creator = std($this->input->get('creator'));


        $date_from = std($this->input->get('date_from'));
        if ($date_from != '') {
            $date_from = date_from_vn($date_from);
        }
        $date_to = std($this->input->get('date_to'));
        if ($date_to != '') {
            $date_to = date_from_vn($date_to);
        }
        if ($date_to != '' && $date_from != '') {
            $year = 0;
        }

        $data['date'] = $date;
        $data['year'] = $year;
        $data['unit'] = $unit;
        $data['address'] = $address;
        $data['open_date'] = $open_date;
        $data['director'] = $director;
        $data['chief_accountant'] = $chief_accountant;
        $data['creator'] = $creator;
        $data['date_from'] = $date_from;
        $data['date_to'] = $date_to;

        $this->load->model('report_model');
        $tax1 = $this->report_model->report132_4_1($shop_id, $year, $date_from, $date_to);
        $tax2 = $this->report_model->report132_4_2($shop_id, $year, $date_from, $date_to);

        $tax01 = $this->report_model->report132_4_1_0($shop_id, $year, $date_from, $date_to);
        $tax02 = $this->report_model->report132_4_2_0($shop_id, $year, $date_from, $date_to);

        $data['tax1'] = $tax1;
        $data['tax2'] = $tax2;

        $data['tax01'] = $tax01;
        $data['tax02'] = $tax02;

        $this->load->view('report132_4', $data);
    }


    function report132_3()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $date = std($this->input->get('date'));
        $year = intval($this->input->get('year'));
        $unit = std($this->input->get('unit'));
        $address = std($this->input->get('address'));
        $open_date = std($this->input->get('open_date'));
        $director = std($this->input->get('director'));
        $chief_accountant = std($this->input->get('chief_accountant'));
        $creator = std($this->input->get('creator'));

        $date_from = std($this->input->get('date_from'));
        if ($date_from != '') {
            $date_from = date_from_vn($date_from);
        }
        $date_to = std($this->input->get('date_to'));
        if ($date_to != '') {
            $date_to = date_from_vn($date_to);
        }
        if ($date_to != '' && $date_from != '') {
            $year = 0;
        }

        $data['date'] = $date;
        $data['year'] = $year;
        $data['unit'] = $unit;
        $data['address'] = $address;
        $data['open_date'] = $open_date;
        $data['director'] = $director;
        $data['chief_accountant'] = $chief_accountant;
        $data['creator'] = $creator;

        $data['date_from'] = $date_from;
        $data['date_to'] = $date_to;

        $product_id = intval($this->input->get('product_id'));

        $this->load->model('report_model');
        $this->load->model('product_model');

        $b0 = $this->report_model->report132_3_b0($shop_id, $product_id, $year, $date_from, $date_to)['quantity'];
        $m0 = $this->report_model->report132_3_m0($shop_id, $product_id, $year, $date_from, $date_to)['quantity'];

        $b1 = $this->report_model->report132_3_b1($shop_id, $product_id, $year, $date_from, $date_to)['quantity'];
        $m1 = $this->report_model->report132_3_m1($shop_id, $product_id, $year, $date_from, $date_to)['quantity'];

        $product = $this->product_model->get_product($product_id, $shop_id);

        $items = $this->report_model->report132_3($shop_id, $product_id, $year, $date_from, $date_to);

        $data['m0'] = $m0;
        $data['b0'] = $b0;
        $data['m1'] = $m1;
        $data['b1'] = $b1;

        $data['items'] = $items;

        $inventory = $product['stock'] - $m1 + $b1;
        $data['inventory'] = $inventory;

        $data['product'] = $product;


        $this->load->view('report132_3', $data);
    }


    function report132_2()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $data = array();
        $this->load->model('shop_detail_model');


        $date_from = std($this->input->get('date_from'));
        if ($date_from != '') {
            $date_from = date_from_vn($date_from);
        }
        $date_to = std($this->input->get('date_to'));
        if ($date_to != '') {
            $date_to = date_from_vn($date_to);
        }
        if ($date_to != '' && $date_from != '') {
            $year = 0;
        }

        $date = std($this->input->get('date'));
        $year = intval($this->input->get('year'));
        $unit = std($this->input->get('unit'));
        $address = std($this->input->get('address'));
        $open_date = std($this->input->get('open_date'));
        $director = std($this->input->get('director'));
        $chief_accountant = std($this->input->get('chief_accountant'));
        $creator = std($this->input->get('creator'));


        $data['date'] = $date;
        $data['year'] = $year;
        $data['unit'] = $unit;
        $data['address'] = $address;
        $data['open_date'] = $open_date;
        $data['director'] = $director;
        $data['chief_accountant'] = $chief_accountant;
        $data['creator'] = $creator;
        $data['date_from'] = $date_from;
        $data['date_to'] = $date_to;


        $this->load->model('report_model');
        $report132_2 = $this->report_model->report132_2($shop_id, $year, 1, $date_from, $date_to);
        $salary = $this->report_model->report132_2_salary($shop_id, $year, 1, $date_from, $date_to);
        $social_insurance = $this->report_model->report132_2_social_insurance($shop_id, $year, 1, $date_from, $date_to);
        $health_insurance = $this->report_model->report132_2_health_insurance($shop_id, $year, 1, $date_from, $date_to);
        $unemployment_insurance = $this->report_model->report132_2_unemployment_insurance($shop_id, $year, 1, $date_from, $date_to);


        $report132_2_0 = $this->report_model->report132_2($shop_id, $year, 0, $date_from, $date_to);
        $salary_0 = $this->report_model->report132_2_salary($shop_id, $year, 0, $date_from, $date_to);
        $social_insurance_0 = $this->report_model->report132_2_social_insurance($shop_id, $year, 0, $date_from, $date_to);
        $health_insurance_0 = $this->report_model->report132_2_health_insurance($shop_id, $year, 0, $date_from, $date_to);
        $unemployment_insurance_0 = $this->report_model->report132_2_unemployment_insurance($shop_id, $year, 0, $date_from, $date_to);


        $data['report132_2'] = $report132_2;
        $data['report132_2_0'] = $report132_2_0;

        $data['salary'] = $salary;
        $data['social_insurance'] = $social_insurance;
        $data['health_insurance'] = $health_insurance;
        $data['health_insurance'] = $health_insurance;
        $data['unemployment_insurance'] = $unemployment_insurance;

        $data['salary_0'] = $salary_0;
        $data['social_insurance_0'] = $social_insurance_0;
        $data['health_insurance_0'] = $health_insurance_0;
        $data['health_insurance_0'] = $health_insurance_0;
        $data['unemployment_insurance_0'] = $unemployment_insurance_0;


        $salary_phaitra0 = $report132_2_0['product_salary'] + $report132_2_0['time_salary'] + $report132_2_0['vacation_salary'] + $report132_2_0['salary_allowance'] + $report132_2_0['allowance'] + $report132_2_0['allowance'];
        $salary_phaitra = $report132_2['product_salary'] + $report132_2['time_salary'] + $report132_2['vacation_salary'] + $report132_2['salary_allowance'] + $report132_2['allowance'] + $report132_2['allowance'];

        $data['salary_phaitra0'] = $salary_phaitra0;
        $data['salary_phaitra'] = $salary_phaitra;

        $this->load->view('report132_2', $data);
    }


    function report132_5()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $date = std($this->input->get('date'));
        $year = intval($this->input->get('year'));
        $unit = std($this->input->get('unit'));
        $address = std($this->input->get('address'));
        $open_date = std($this->input->get('open_date'));
        $director = std($this->input->get('director'));
        $chief_accountant = std($this->input->get('chief_accountant'));
        $creator = std($this->input->get('creator'));

        $date_from = std($this->input->get('date_from'));
        if ($date_from != '') {
            $date_from = date_from_vn($date_from);
        }
        $date_to = std($this->input->get('date_to'));
        if ($date_to != '') {
            $date_to = date_from_vn($date_to);
        }
        if ($date_to != '' && $date_from != '') {
            $year = 0;
        }

        $data['date'] = $date;
        $data['year'] = $year;
        $data['unit'] = $unit;
        $data['address'] = $address;
        $data['open_date'] = $open_date;
        $data['director'] = $director;
        $data['chief_accountant'] = $chief_accountant;
        $data['creator'] = $creator;

        $data['date_from'] = $date_from;
        $data['date_to'] = $date_to;

        $product_id = intval($this->input->get('product_id'));

        $this->load->model('report_model');
        $this->load->model('product_model');

        $b0 = $this->report_model->report132_3_b0($shop_id, $product_id, $year, $date_from, $date_to)['amount'];
        $m0 = $this->report_model->report132_3_m0($shop_id, $product_id, $year, $date_from, $date_to)['amount'];


        $product = $this->product_model->get_product($product_id, $shop_id);

        $items = $this->report_model->report132_3($shop_id, $product_id, $year, $date_from, $date_to);

        $data['m0'] = $m0;
        $data['b0'] = $b0;

        $data['items'] = $items;

        $inventory = $b0 - $m0;
        $data['inventory'] = $inventory;


        $data['product'] = $product;

        $this->load->view('report132_5', $data);
    }

    function report132_6()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $date = std($this->input->get('date'));

        $date_from = std($this->input->get('date_from'));
        if ($date_from != '') {
            $date_from = date_from_vn($date_from);
        }
        $date_to = std($this->input->get('date_to'));
        if ($date_to != '') {
            $date_to = date_from_vn($date_to);
        }

        $this->load->model('report_model');
        $data['date'] = $date;

        $rows = $this->report_model->report132_6($shop_id, $date_from, $date_to);
        $data['rows'] = $rows;
        $this->load->view('report132_6', $data);
    }


    function report132_7()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $date = std($this->input->get('date'));

        $date_from = std($this->input->get('date_from'));
        if ($date_from != '') {
            $date_from = date_from_vn($date_from);
        }
        $date_to = std($this->input->get('date_to'));
        if ($date_to != '') {
            $date_to = date_from_vn($date_to);
        }

        $this->load->model('report_model');
        $data['date'] = $date;

        $rows = $this->report_model->report132_7($shop_id, $date_from, $date_to);
        $data['rows'] = $rows;
        $this->load->view('report132_7', $data);
    }


    function report132_8()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $date = std($this->input->get('date'));

        $date_from = std($this->input->get('date_from'));
        if ($date_from != '') {
            $date_from = date_from_vn($date_from);
        }
        $date_to = std($this->input->get('date_to'));
        if ($date_to != '') {
            $date_to = date_from_vn($date_to);
        }

        $this->load->model('report_model');
        $data['date'] = $date;

        $rows0 = $this->report_model->report132_6($shop_id, $date_from, $date_to);
        $rows = $this->report_model->report132_8($shop_id, $date_from, $date_to);

        $data['rows'] = $rows;
        $data['rows0'] = $rows0;
        $this->load->view('report132_8', $data);
    }

    function report132_9()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $year = intval($this->input->get('year'));
        $month = intval($this->input->get('month'));

        $this->load->model('report_model');

        $rows = $this->report_model->report132_9_1($shop_id, $year, $month);

        $date = "$year-$month-01";
        $to_date = date("Y-m-t", strtotime($date));
        $from_date = date("Y-m-d", strtotime("-11 months", strtotime($date)));

        $data['rows'] = $rows;
        $data['from_date'] = $from_date;
        $data['to_date'] = $from_date;

        $date = std($this->input->get('date'));
        $data['date'] = $date;
        $data['from_date'] = $from_date;
        $data['to_date'] = $to_date;

        $this->load->view('report132_9', $data);
    }

    function report132_10()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $year = intval($this->input->get('year'));
        $month = intval($this->input->get('month'));

        $this->load->model('report_model');

        $rows = $this->report_model->report132_10_1($shop_id, $year, $month);

        $date = "$year-$month-01";
        $to_date = date("Y-m-t", strtotime($date));
        $from_date = date("Y-m-d", strtotime("-11 months", strtotime($date)));

        $data['rows'] = $rows;
        $data['from_date'] = $from_date;
        $data['to_date'] = $to_date;

        $date = std($this->input->get('date'));
        $data['date'] = $date;

        $this->load->view('report132_10', $data);
    }


    function report132()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        if (!empty($_POST)) {
            $type = intval($this->input->post('type'));
            $year = intval($this->input->post('year'));
            $date = date_from_vn($this->input->post('date'));

            $date_from = std($this->input->post('date_from'));
            $date_to = std($this->input->post('date_to'));

            $unit = urlencode(std($this->input->post('unit')));
            $address = urlencode(std($this->input->post('address')));
            $open_date = date_from_vn($this->input->post('open_date'));

            $director = urlencode(std($this->input->post('director')));
            $chief_accountant = urlencode(std($this->input->post('chief_accountant')));
            $creator = urlencode(std($this->input->post('creator')));

            if ($type == 1) {
                redirect("/report132_1?year=$year&date=$date&unit=$unit&address=$address&open_date=$open_date&director=$director&chief_accountant=$chief_accountant&creator=$creator&date_from=$date_from&date_to=$date_to");
            }
            if ($type == 2) {
                redirect("/report132_2?year=$year&date=$date&unit=$unit&address=$address&open_date=$open_date&director=$director&chief_accountant=$chief_accountant&creator=$creator&date_from=$date_from&date_to=$date_to");
            }
            if ($type == 3) {
                $product_id = intval($this->input->post('product_id'));
                redirect("/report132_3?year=$year&date=$date&unit=$unit&address=$address&open_date=$open_date&director=$director&chief_accountant=$chief_accountant&creator=$creator&product_id=$product_id&date_from=$date_from&date_to=$date_to");
            }
            if ($type == 4) {
                redirect("/report132_4?year=$year&date=$date&unit=$unit&address=$address&open_date=$open_date&director=$director&chief_accountant=$chief_accountant&creator=$creator&date_from=$date_from&date_to=$date_to");
            }
            if ($type == 5) {
                $product_id = intval($this->input->post('product_id'));
                redirect("/report132_5?year=$year&date=$date&unit=$unit&address=$address&open_date=$open_date&director=$director&chief_accountant=$chief_accountant&creator=$creator&product_id=$product_id&date_from=$date_from&date_to=$date_to");
            }
            if ($type == 6) {
                redirect("/report132_6?year=$year&date=$date&date_from=$date_from&date_to=$date_to");
            }
            if ($type == 7) {
                redirect("/report132_7?year=$year&date=$date&date_from=$date_from&date_to=$date_to");
            }
            if ($type == 8) {
                redirect("/report132_8?year=$year&date=$date&date_from=$date_from&date_to=$date_to");
            }
            if ($type == 9) {
                $y = date("Y", strtotime($date));
                $m = date("m", strtotime($date));
                redirect("/report132_9?&date=$date&year=$y&month=$m");
            }
            if ($type == 10) {
                $y = date("Y", strtotime($date));
                $m = date("m", strtotime($date));
                redirect("/report132_10?&date=$date&year=$y&month=$m");
            }
        }

        $shop_id = $this->user->shop_id;
        $data = array();
        $data['title'] = tb_word('shops.purchase.orders');
        $data["user"] = $this->user;
        $this->load->view('headers/html_header', $data);

        $date = date('Y-m-d');
        //$date_from = date("Y-m-01", strtotime("-1 month", strtotime($date)));
        //$date_to = date("Y-m-t", strtotime("-1 month", strtotime($date)));
        $date_from = date("Y-m-01");
        $date_to = date("Y-m-t");

        $data = array();
        $this->load->model('shop_detail_model');
        $shop_detail = $this->shop_detail_model->get_shop_detail($shop_id);
        $data['shop_detail'] = $shop_detail;
        $data['date'] = $date;
        $year = date('Y');
        $data['year'] = $year;

        $data['date_from'] = $date_from;
        $data['date_to'] = $date_to;

        $this->load->model('product_model');
        $products = $this->product_model->get_non_service_products($shop_id);
        $data['products'] = $products;

        $this->load->view('report132', $data);
        $this->load->view('headers/html_footer');
    }


    function get_scroll_shops()
    {
        $this->load->model('shop_model');
        $shops = $this->shop_model->get_shops_by_type(0);
        foreach ($shops as $shop) {
            echo ("<li>" . $shop['id'] . '. ' . $shop['name'] . "</li>");
        }
    }

    function show_hide_employee()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $id = intval($this->input->post('id'));
        $type = intval($this->input->post('type'));

        $this->load->model('employee_model');
        $this->employee_model->set_status($shop_id, $id, $type);
    }


    function check_payroll()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $month = intval($this->input->post('month'));
        $year = intval($this->input->post('year'));

        $this->load->model('order_model');
        $payroll = $this->order_model->check_payroll($shop_id, $year, $month);
        if ($payroll) {
            $result = array();
            $result['order_id'] = $payroll['id'];
            echo (json_encode($result));
        } else {
            $result = array();
            $result['order_id'] = 0;
            echo (json_encode($result));
        }
    }




    function get_parent_order_detail()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = $this->input->post('order_id');
        $this->load->model('order_model');
        $rows = $this->order_model->get_parent_order_detail($shop_id, $order_id);
        $data = array();
        $data['items'] = $rows;
        $this->load->view('parent_order_detail', $data);
    }


    function get_dealer()
    {
        $phone = std($this->input->get('promotion_code'));

        $this->load->model('dealer_model');
        $dealer = $this->dealer_model->is_duc_sub_dealer($phone);
        if ($dealer) {
            $result = array();
            $result['name'] = "<b>" . $dealer['name'] . " - Công ty TNHH Thương Mại Dược phẩm Đức An - Mã số thuế: 0314540246</b>";
            echo (json_encode($result));
            return;
        }

        $this->load->model('shop_model');
        $dealer = $this->shop_model->get_dealer($phone);
        if ($dealer) {
            $result = array();
            $result['name'] = $dealer['name'];
            echo (json_encode($result));
            return;
        }
    }

    function transfer()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $data = $this->input->post('data');
        //echo(json_encode($data));
        $date = date_from_vn($this->input->post('date'));
        $sub = intval($this->input->post('sub'));
        $name = std($this->input->post('name'));
        $invoice_number = std($this->input->post('invoice_number'));
        $content = std($this->input->post('content'));
        $customer_name = std($this->input->post('customer_name'));

        $params = array();

        $params['shop_id'] = $shop_id;
        $params['order_name'] = $name;
        $params['order_type'] = 'B';


        $params['order_date'] = $date;
        $params['customer_id'] = 0;
        $params['customer_name'] = $customer_name;
        $params['order_time'] = date('Y-m-d H:i:s', strtotime('now'));
        $params['create_user'] = $this->user->user_id;
        $params['last_user'] = $this->user->user_id;
        $params['last_update'] = date('Y-m-d H:i:s', strtotime('now'));
        $params['payment_type'] = 0;
        $params['currency'] = 'VND';
        $params['vat'] = 0;
        $params['invoice_number'] = $invoice_number;
        $params['content'] = $content;
        $params['user_id'] = $this->user->user_id;
        $params['status'] = 'MM';

        $this->load->model('order_model');

        $order_id = $this->order_model->add_order($params);
        $params = array();
        $this->load->model('bill_model');
        $params['shop_id'] = $shop_id;
        $params['order_id'] = $order_id;
        $bill_id = $this->bill_model->add_bill($params);

        $amount = 0;
        $this->load->model('product_model');
        $this->load->model('bill_item_model');
        foreach ($data as $item) {
            $product_id = intval($item['product_id']);
            $product_name = std($item['product_name']);
            //$nog = intval($item['nog']);
            $pr = $this->product_model->get_product($product_id, $shop_id);
            //$params = array();
            $params['order_id'] = $order_id;
            $params['product_id'] = $product_id;
            $params['product_code'] = '';
            $params['product_name'] = $product_name;
            $params['price'] = floatval($item['price']);

            $item['price'] = $params['price'];
            $params['quantity'] = floatval($item['quantity']);

            $params['status1'] = 4;
            $params['amount'] = $params['price'] * $item['quantity'];
            $amount += $params['amount'];

            //$params['nog'] = $nog;
            $bill_item_id = $this->bill_item_model->add_bill_item($params);

            $rows = $this->product_model->get_product_materials($shop_id, $product_id);
            foreach ($rows as $row) {
                $material_id = $row['id'];
                $material_name = $row['product_name'];
                $quantity = $row['quantity'];
                $params1 = array();
                $params1['shop_id'] = $shop_id;
                $params1['order_id'] = 0;
                $params1['parent'] = $bill_item_id;

                $params1['product_id'] = $material_id;
                $params1['product_name'] = $material_name;
                $params1['quantity'] = $quantity * $item['quantity'];

                $params1['create_user'] = $this->user->user_id;
                $params1['last_user'] = $this->user->user_id;
                $params1['last_update'] = date('Y-m-d H:i:s', strtotime('now'));
                $average_price = $row['average_price'];
                $params1['price'] = $average_price;
                $params1['amount'] = $average_price * $params1['quantity'];

                $this->bill_item_model->add_bill_item($params1);

                $params1 = array();
                $params1['stock'] = $row['stock'] - $quantity * $item['quantity'];
                $this->product_model->update_product($material_id, $shop_id, $params1);
            }

            $product_params['stock'] = $pr['stock'] - $item['quantity'];
            $this->product_model->update_product($product_id, $shop_id, $product_params);
            $new_bill[] = $item;
        }

        $params = array('id' => $bill_id, 'amount' => $amount);
        $this->bill_model->update_bill($bill_id, $shop_id, $params);
        $params = array('id' => $order_id, 'amount' => $amount, 'status1' => 4);
        $this->order_model->update_order($order_id, $shop_id, $params);
        $this->order_model->update_order_memo($shop_id, $order_id);
        //foreach()

        $this->transfer_to($shop_id, $order_id, $sub, $name);
    }

    function transfer_to($shop_id, $order_id, $destination_shop_id, $order_name)
    {

        //Check destination shop id owner
        $this->load->model('order_model');
        $order = $this->order_model->get_order($order_id, $shop_id);
        if (!$order) {
            return;
        }

        $this->load->model('bill_item_model');
        $items = $this->bill_item_model->get_order_item_for_transfer($shop_id, $order_id);
        $this->load->model('product_model');

        $this->load->model('order_model');
        $params = array();
        $params['shop_id'] = $destination_shop_id;
        $params['order_type'] = 'M';
        $params['order_date'] = $order['order_date'];
        $params['order_time'] = $order['order_time'];
        $params['customer_id'] = 0;
        $params['customer_name'] = $this->user->shop['name'];
        $params['order_name'] = $order_name; //$this->user->shop['name'] . ' chuyển kho';
        $params['amount'] = $order['amount'];
        $params['paid_date'] = $order['order_date'];
        $params['status'] = 'MM';
        $params['status1'] = 4;

        $order_id = $this->order_model->add_order($params);

        foreach ($items as $item) {
            $product_code = $item['product_code'];
            $product_id = $this->product_model->get_product_by_code($product_code, $destination_shop_id);
            if ($product_id == 0) {
                $params = array();
                $params['product_code'] = $product_code;
                $params['product_name'] = $item['product_name'];
                $params['list_price'] = $item['price'];
                $params['cost_price'] = $item['price'];
                $params['product_status'] = 1;
                //$params['stock'] = $item['quantity'];

                $params['shop_id'] = $destination_shop_id;

                $params['product_group'] = 0;


                $product_id = $this->product_model->add_product($params);
            }
            $params = array();
            $params['shop_id'] = $destination_shop_id;
            $params['order_id'] = $order_id;
            $params['product_id'] = $product_id;
            $params['product_code'] = $product_code;
            $params['product_name'] = $item['product_name'];
            $params['price'] = $item['price'];
            $params['quantity'] = $item['quantity'];
            $params['amount'] = $item['quantity'] * $item['price'];
            $params['last_update'] = date('Y-m-d H:i:s', strtotime('now'));
            $params['status1'] = 4;
            $this->bill_item_model->add_bill_item($params);

            //Change stock quantity;
            //$this->product_model->update_product_stock($product_id, $destination_shop_id, $item['quantity']);
            //$this->product_model->update_product_stock2($shop_id, $product_id);
        }
    }

    function change_room()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $result = array();
        if ($this->user->row['user_group'] != 'admin') {
            $result['success'] = 0;
            $result['message'] = "Bạn không có quyền đổi phòng";
            echo (json_encode($result));
            return;
        }
        $bill_id = intval($this->input->post('bill_id'));
        $new_product_id = intval($this->input->post('new_product_id'));
        $product_name = std($this->input->post('product_name'));
        $this->load->model('bill_item_model');

        $item = $this->bill_item_model->get_bill_item($bill_id, $shop_id);
        if (!$item) {
            $result['success'] = 0;
            $result['message'] = "";
            echo (json_encode($result));
            return;
        }
        $old_product_id = $item['product_id'];
        $order_id = $item['order_id'];

        $start_date = $item['start_date'];
        $end_date = $item['end_date'];

        $this->load->model('occupy_model');
        $occupy = $this->occupy_model->check_occupied_product($shop_id, $new_product_id, $start_date, $end_date);
        if (!$occupy) {
            $result['success'] = 0;
            $result['message'] = "Phòng này, thời gian từ " . vn_date($start_date) . " đến " . vn_date($end_date) . " đã có người ở";
            echo (json_encode($result));
            return;
        }

        //Un-occupy from room
        $this->occupy_model->update_product_occupy($shop_id, $old_product_id, $start_date, $end_date, 0);
        //Occupy to room
        $this->occupy_model->update_product_occupy($shop_id, $new_product_id, $start_date, $end_date, 1);

        $this->occupy_model->update_start_date_occupy($shop_id, $new_product_id, $start_date);

        //change room id in bill item

        $params = array();
        $params['product_id'] = $new_product_id;
        $params['product_name'] = $product_name . " (chuyển từ " . $item['product_name'] . ")";

        $this->bill_item_model->update_bill_item($shop_id, $item['id'], $params);

        $this->load->model('occupy_order_model');
        $this->occupy_order_model->update_room($shop_id, $order_id, $new_product_id);

        $result['success'] = 1;
        $result['message'] = "Đã đổi phòng thành công";
        echo (json_encode($result));
    }

    function report_visitor()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get('order_id'));

        //echo($order_id);
        $data = array();
        $this->load->model('visitor_model');
        $visitors = $this->visitor_model->get_order_visitors($shop_id, $order_id);

        $start_date = 2524604400;
        $end_date = 0;
        foreach ($visitors as $visitor) {
            if ($start_date > strtotime($visitor['checkin_date'])) {
                $start_date = strtotime($visitor['checkin_date']);
            }

            if ($end_date < strtotime($visitor['checkout_date'])) {
                $end_date = strtotime($visitor['checkout_date']);
            }
        }

        $start_date = date('Y-m-d', $start_date);
        $end_date = date('Y-m-d', $end_date);

        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;
        $data['visitors'] = $visitors;
        $this->load->view('report_visitor', $data);
    }


    function stay_register()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get('order_id'));

        $this->load->model('visitor_model');
        $visitors = $this->visitor_model->get_order_visitors($shop_id, $order_id);
        //echo(json_encode($visitors));
        //return;
        $data = array();
        foreach ($visitors as $visitor) {
            $row = array();
            $row['checkin'] = vn_date($visitor['checkin_date']);
            $row['checkout'] = vn_date($visitor['checkout_date']);
            $row['room_number'] = $visitor['room_number'];
            $row['name'] = $visitor['name'];
            $row['dob'] = vn_date($visitor['dob']);
            if ($visitor['gender'] == 'M') {
                $row['gender'] = 0;
            } else {
                $row['gender'] = 1;
            }
            $row['passport_type'] = $visitor['paper_type'];
            $row['passport_id'] = $visitor['passport_id'];
            $row['job'] = $visitor['profession'];
            $row['ethnicity'] = $visitor['ethnic'];
            $row['religion'] = $visitor['religion'];
            $row['purpose'] = $visitor['purpose'];
            $row['province'] = $visitor['province'];
            $row['district'] = $visitor['district'];
            $row['ward'] = $visitor['ward'];
            $row['address'] = $visitor['address'];
            $row['note'] = $visitor['note'];

            $data[] = $row;
        }

        $message = stay_register1('dinhdq@gmail.com', '1', $data);

        echo ($message);
    }
    //toandk2 sửa
    function payrolls()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('order_model');
        $orders = $this->order_model->payroll_orders($shop_id);

        $data = array();
        $data['title'] = tb_word('shops.purchase.orders');
        $data["user"] = $this->user;
        $data['orders'] = $orders;


        if (check_user_agent('mobile')) {
            $data['title_header'] = 'Danh sách bản lương';
            $this->load->view('mobile_views/html_header_app', $data);
            $this->load->view('mobile_views/payrolls', $data);
            $this->load->view('mobile_views/html_footer_app', $data);
        } else {
            $this->load->view('headers/html_header', $data);
            $this->load->view('payrolls', $data);
            $this->load->view('headers/html_footer');
        }
    }

    function product_excel()
    {
        ///$this->output->enable_profiler(TRUE);
        ///error_reporting(-1);
        ///ini_set('display_errors', 1);

        $config['upload_path']          = './uploads/';
        $config['allowed_types']        = 'xlsx|xls|csv';
        $config['max_size']             = 2000000;
        $this->load->library('upload', $config);
        $this->load->library('excel');
        if (!$this->upload->do_upload('file')) {
            echo ($this->upload->display_errors());
        } else {
            $data_file = array('upload_data' => $this->upload->data());
            $file = $data_file['upload_data']['full_path'];
            //echo($file);
            $objPHPExcel = PHPExcel_IOFactory::load($file);
            $sheet = $objPHPExcel->getActiveSheet();
            $totalrow  = $objPHPExcel->getActiveSheet()->getHighestRow();
            $lastColumn  = $objPHPExcel->getActiveSheet()->getHighestDataColumn();
            $totalCol = PHPExcel_Cell::columnIndexFromString($lastColumn);
            //echo($totalCol);
            $rows = array();
            for ($i = 2; $i <= $totalrow; $i++) {
                $row = array();
                $row['name'] = $sheet->getCellByColumnAndRow(0, $i)->getValue();
                $row['code'] = $sheet->getCellByColumnAndRow(1, $i)->getValue();
                $row['group'] = $sheet->getCellByColumnAndRow(2, $i)->getValue();
                $row['cost_price'] = $sheet->getCellByColumnAndRow(3, $i)->getValue();
                $row['list_price'] = $sheet->getCellByColumnAndRow(4, $i)->getValue();
                $row['quantity'] = floatval($sheet->getCellByColumnAndRow(5, $i)->getValue());
                $row['unit'] = $sheet->getCellByColumnAndRow(6, $i)->getValue();
                $row['gtgt'] = $sheet->getCellByColumnAndRow(7, $i)->getValue();
                $row['tncn'] = $sheet->getCellByColumnAndRow(8, $i)->getValue();
                if ($totalCol > 9) {
                    $tags = array();
                    for ($j = 9; $j < $totalCol; $j++) {
                        if ($sheet->getCellByColumnAndRow($j, $i)->getValue()) {
                            $tags[] = $sheet->getCellByColumnAndRow($j, $i)->getValue();
                        }
                    }
                    $row['tags'] = $tags;
                }

                $rows[] = $row;
            }
            echo (json_encode($rows));
        }
    }

    function selected_products_barcode()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $ids = $this->input->get('ids');
        $product_ids = array();
        foreach ($ids as $id) {
            $product_ids[] = intval($id);
        }
        $this->load->model('product_model');
        $products = $this->product_model->get_product_by_ids($shop_id, $product_ids);
        $data['products'] = $products;
        $this->load->view('products_barcode', $data);
    }

    function test1234()
    {
        echo (dqg_authenticate('gpp_70_000002_1030', '123456a'));
    }

    function update_vat()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $id = intval($this->input->post('order_id'));
        $vat = intval($this->input->post('vat'));
        $this->load->model('order_model');

        $params = array();
        $params['vat_rate'] = $vat;
        $this->order_model->update_order($id, $shop_id, $params);

        echo ($vat);
    }

    function delete_customer()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        //echo($this->user->row['user_role']);
        if ('shops.lists.user-roles.manager' != $this->user->row['user_role']) {
            return;
        }

        $id = intval($this->input->post('id'));
        $this->load->model('customer_model');
        $params = array();
        $params['deleted'] = 1;
        $this->customer_model->update_customer($id, $shop_id, $params);
    }

    function update_shop_profile()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);
        //$this->output->enable_profiler(TRUE);

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $shop_name = std($this->input->post('shop_name'));
        //$name = std($this->input->post('full_name'));

        $email = std($this->input->post('email'));
        $qrcode_shop = $this->input->post('qrcode_shop');
        $qrcode_bank = $this->input->post('qrcode_bank');

        $facebook = std($this->input->post('facebook'));
        $website = std($this->input->post('website'));

        $address = std($this->input->post('address'));
        $province = std($this->input->post('province1'));
        $district = std($this->input->post('district1'));
        $ward = std($this->input->post('ward'));
        $this->load->model('vietnam_model');
        $this->load->model('shop_model');

        //$location = $this->vietnam_model->get_vietnam($ward);
        //$ward_name = $location['name'];
        //$location_id = $location['aux_id'];

        $type = intval($this->input->post('type'));
        //$promotion_code = std($this->input->post('promotion_code'));
        //$gpp = std($this->input->post('gpp'));

        $pharm_representative = std($this->input->post('pharm_representative'));
        $pharm_representative_id = std($this->input->post('pharm_representative_id'));
        $pharm_representative_email = std($this->input->post('pharm_representative_email'));


        $pharm_responsible = std($this->input->post('pharm_responsible'));
        $pharm_responsible_id = std($this->input->post('pharm_responsible_id'));
        $pharm_responsible_no = std($this->input->post('pharm_responsible_no'));
        $pharm_responsible_level = std($this->input->post('pharm_responsible_level'));
        $pharm_responsible_phone = std($this->input->post('pharm_responsible_phone'));
        $pharm_responsible_email = std($this->input->post('pharm_responsible_email'));

        $phone_in_receipt = std($this->input->post('phone_in_receipt'));
        $negative_stock = intval($this->input->post('negative_stock'));

        $large = intval($this->input->post('large'));

        $bank = std($this->input->post('bank'));
        $bank_account_name = std($this->input->post('bank_account_name'));
        $bank_account_number = std($this->input->post('bank_account_number'));

        $quote = std($this->input->post('quote'));


        $minvoice_username = std($this->input->post('minvoice_username'));
        $minvoice_password = std($this->input->post('minvoice_password'));
        $minvoice_branch_code = std($this->input->post('minvoice_branch_code'));

        $period_closed_date = date_from_vn($this->input->post('period_closed_date'));
        $precision = intval($this->input->post('precision'));

        $app = array();
        $app['app_name'] = std($this->input->post('app_name'));
        $app['app_key'] = std($this->input->post('app_key'));
        $note1 = json_encode($app);


        $lon = floatval($this->input->post('lon'));
        $lat = floatval($this->input->post('lat'));
        //toandk2 thêm
        $link_online = $this->input->post('link_online');

        $params = array();
        $params['name'] = $shop_name;
        $params['email'] = $email;
        //toandk2 thêm
        $params['link_online'] = $link_online;

        $this->user->email = $email;

        $params['state'] = $province;

        $params['facebook'] = $facebook;
        $params['website'] = $website;

        $params['district'] = $district;
        $params['ward'] = $ward;
        $params['address'] = $address;
        $params['location_id'] = $location_id;
        //$params['promotion_code'] = $promotion_code;
        //$params['gpp'] = $gpp;

        $params['pharm_representative'] = $pharm_representative;
        $params['pharm_representative_id'] = $pharm_representative_id;

        $params['pharm_responsible'] = $pharm_responsible;
        $params['pharm_responsible_no'] = $pharm_responsible_no;
        $params['pharm_responsible_id'] = $pharm_responsible_id;
        $params['pharm_responsible_level'] = $pharm_responsible_level;
        $params['pharm_responsible_phone'] = $pharm_responsible_phone;
        $params['pharm_responsible_email'] = $pharm_responsible_email;

        $params['phone_in_receipt'] = $phone_in_receipt;
        $params['negative_stock'] = $negative_stock;
        $params['large'] = $large;

        //toandk2 them
        $params['qrcode_shop'] = $qrcode_shop;
        $params['qrcode_bank'] = $qrcode_bank;
        $params['bankId'] = $bankId;


        $params['bank'] = $bank;
        $params['bank_account_name'] = $bank_account_name;
        $params['bank_account_number'] = $bank_account_number;

        $params['lat'] = $lat;
        $params['lon'] = $lon;

        $params['quote'] = $quote;

        $params['minvoice_username'] = $minvoice_username;
        $params['minvoice_password'] = $minvoice_password;
        $params['minvoice_branch_code'] = $minvoice_branch_code;
        $params['period_closed_date'] = $period_closed_date;
        $params['precision'] = $precision;
        $params['note1'] = $note1;

        $code = std($this->input->post('code'));
        $code1 = std($this->input->post('code1'));
        if ($code != '') {
            $params['code'] = $code;
        }
        if ($code1 != '') {
            $params['code1'] = $code1;
        }

        $this->shop_model->update_shop($shop_id, $params);

        $row = $this->shop_model->get_shop($shop_id);
        //var_dump($row);
        $user = $this->user;
        $user->shop = $row;
        $user->save_session();
    }
    //toandk thêm
    function update_shop_config()
    {

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $this->load->model('shop_model');
        $shop_id = $this->user->shop_id;
        $negative_stock = intval($this->input->post('negative_stock'));
        $large = intval($this->input->post('large'));
        $period_closed_date = date_from_vn($this->input->post('period_closed_date'));
        $precision = intval($this->input->post('precision'));
        $params = array();
        $params['negative_stock'] = $negative_stock;
        $params['large'] = $large;
        $params['period_closed_date'] = $period_closed_date;
        $params['precision'] = $precision;
        $this->shop_model->update_shop($shop_id, $params);
        $row = $this->shop_model->get_shop($shop_id);
        $user = $this->user;
        $user->shop = $row;
        $user->save_session();
    }

    function update_shop_profile1()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);
        //$this->output->enable_profiler(TRUE);

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('shop_model');

        $minvoice_username = std($this->input->post('minvoice_username'));
        $minvoice_password = std($this->input->post('minvoice_password'));
        $minvoice_branch_code = std($this->input->post('minvoice_branch_code'));


        $params = array();

        $params['minvoice_username'] = $minvoice_username;
        $params['minvoice_password'] = $minvoice_password;
        $params['minvoice_branch_code'] = $minvoice_branch_code;

        $this->shop_model->update_shop($shop_id, $params);
    }

    function update_negative_stock()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $negative_stock = intval($this->input->post('negative_stock'));

        $this->load->model('shop_model');
        $params['negative_stock'] = $negative_stock;
        $this->shop_model->update_shop($shop_id, $params);
        $this->user->shop['negative_stock'] = $negative_stock;
        $user->save_session();
    }


    function gpp_reg()
    {
        $phone = std($this->input->get('phone'));
        $data = array();
        $this->load->model('vietnam_model');
        $provinces = $this->vietnam_model->get_all_province();
        $data['provinces'] = $provinces;
        $data['phone'] = $phone;
        $this->load->view('gpp_reg', $data);
    }

    function gpp_reg_action()
    {
        if (!empty($_POST)) {
            //$this->output->enable_profiler(TRUE);
            //return;
            $this->load->model('vietnam_model');
            $this->load->model('shop_model');
            $count_shop_by_ip = $this->shop_model->count_shop_by_ip($this->input->ip_address());
            if ($count_shop_by_ip > 100) {
                redirect('/shop_create');
            }
            session_start();
            $count_shop_by_session = $this->shop_model->count_shop_by_session(session_id());
            if ($count_shop_by_session > 100) {
                redirect('/shop_create');
            }

            $shop_name = std($this->input->post('shop_name'));
            $name = std($this->input->post('full_name'));
            $phone = std($this->input->post('phone'));
            $this->load->model('shop_user_model');
            if ($this->shop_user_model->check_phone_existence($phone) == 1) {
                echo ('Số điện thoại đã tồn tại');
                return;
            }

            $password = std($this->input->post('password'));
            $code = std($this->input->post('code'));
            $code1 = std($this->input->post('code1'));
            $email = std($this->input->post('email'));
            $address = std($this->input->post('address'));
            $province = std($this->input->post('province1'));
            $district = std($this->input->post('district1'));
            $ward = intval($this->input->post('ward'));
            $location = $this->vietnam_model->get_vietnam($ward);
            $ward_name = $location['name'];
            $location_id = $location['aux_id'];
            $pharm_type = std($this->input->post('pharm_type'));
            $pharm_representative = std($this->input->post('pharm_representative'));
            $pharm_representative_id = std($this->input->post('pharm_representative_id'));

            $pharm_responsible = std($this->input->post('pharm_responsible'));
            $pharm_responsible_no = std($this->input->post('pharm_responsible_no'));
            $pharm_responsible_id = std($this->input->post('pharm_responsible_id'));
            $pharm_responsible_level = std($this->input->post('pharm_responsible_level'));
            $pharm_responsible_phone = std($this->input->post('pharm_responsible_phone'));
            $pharm_responsible_email = std($this->input->post('pharm_responsible_email'));


            $type = 11;

            $promotion_code = std($this->input->post('promotion_code'));

            $params = array();
            $params['name'] = $shop_name;
            $params['phone'] = $phone;
            $params['email'] = $email;
            $params['state'] = $province;
            $params['district'] = $district;
            $params['ward'] = $ward_name;
            $params['address'] = $address;
            $params['location_id'] = $location_id;
            $params['type'] = $type;
            $params['promotion_code'] = $promotion_code;
            $params['ip'] = $this->input->ip_address();
            $params['session'] = session_id();
            $params['code'] = $code;
            $params['code1'] = $code1;
            $params['pharm_type'] = $pharm_type;
            $params['pharm_representative'] = $pharm_representative;
            $params['pharm_representative_id'] = $pharm_representative_id;

            $params['pharm_responsible'] = $pharm_responsible;
            $params['pharm_responsible_no'] = $pharm_responsible_no;

            $params['pharm_responsible_id'] = $pharm_responsible_id;
            $params['pharm_responsible_level'] = $pharm_responsible_level;
            $params['pharm_responsible_phone'] = $pharm_responsible_phone;
            $params['pharm_responsible_email'] = $pharm_responsible_email;

            $now = strtotime(date("Y-m-d"));
            $expired = date("Y-m-d", strtotime("+45 days", $now));
            $params['expired'] = $expired;


            $shop_id = $this->shop_model->add_shop($params);

            $user = new shop_user();
            //$data['user_pass'] = $password;
            $data['user_group'] = "admin";
            $data['user_role'] = "shops.lists.user-roles.manager";
            $data['full_name'] = $name;

            $data['phone'] = $phone;
            $data['email'] = $email;
            $data['shop_id'] = intval($shop_id);

            $data['user_group'] = 'admin';
            $token = ktk_get_token();
            $data['user_pass'] = dinhdq_encode($phone, $password);


            $id = $this->shop_user_model->add($data);

            $this->shop_user_model->init_table($shop_id);

            if ($type == 11) {
                $this->shop_user_model->init_table_data($shop_id, $type);
            }

            $this->load->helper('cookie');

            $cookie = array(
                'name'   => 'phone',
                'value'  => $phone,
                'expire' => time() + 6 * 30 * 24 * 3600,
                'domain' => $_SERVER['HTTP_HOST'],
                'path'   => '/',
                'prefix' => '',
            );
            set_cookie($cookie);
            //

            //$shop_name = $name;
            $shops = get_cookie('shops');
            if ($shops == null) {
                $shops = array();
            } else {
                $shops = json_decode($shops, true);
            }
            $shops[$shop_id] = $phone;
            $shops = json_encode($shops);
            $cookie = array(
                'name'   => 'shops',
                'value'  => $shops,
                'expire' => time() + 6 * 30 * 24 * 3600,
                'domain' => $_SERVER['HTTP_HOST'],
                'path'   => '/',
                'prefix' => '',
            );
            set_cookie($cookie);

            $message = "Bạn đã tạo cửa hàng thành công, xin mời đăng nhập";
            echo ($message);
        }
    }
    function get_survey1()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('survey1_model');
        $survey = $this->survey1_model->get_survey($shop_id);
        if (!$survey) {
            $result = array();
            $result['result'] = 0;
            $content = $this->load->view('survey1', array(), true);
            $result['content'] = $content;
            echo (json_encode($result));
        } else {
            $result = array();
            $result['result'] = 1;
            echo (json_encode($result));
        }
    }

    function answer_survey1()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('survey1_model');
        $answer = intval($this->input->post('answer'));
        $survey = $this->survey1_model->get_survey($shop_id);
        if (!$survey) {
            $params = array();
            $params['shop_id'] = $shop_id;
            $params['answer'] = $answer;
            $this->survey1_model->add_survey($params);
        }
    }

    function create_ebill()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->post('order_id'));

        $this->load->model('shop_ebill_model');
        $this->load->model('order_model');
        $post = 0;
        $shop_ebill = $this->shop_ebill_model->get_shop_ebill($shop_id, 'nacencomm');


        $data = array();
        $data['MauSoHD'] = urlencode($this->input->post('mauso_hd'));
        if ($shop_ebill) {
            $data['Kyhieu'] = $shop_ebill['ky_hieu'];
            $data['MaloaiHD'] = $shop_ebill['ma_loai_hd'];
            $data['MaChiNhanh'] = $shop_ebill['ma_so_thue'];
            //$data['Mamau'] = $shop_ebill['ma_mau'];
            $data['usernamews'] = $shop_ebill['usernamews'];
            $data['passwordws'] = $shop_ebill['passwordws'];
        }

        $order = $this->order_model->get_order($order_id, $shop_id);
        if (!$order) {
            $result = array();
            $result[0] = "0";
            echo (json_encode($result));
            return;
        }

        $data['MauSoHD'] = '01GTKT0/001';
        $data['SoHoaDon'] = urlencode($this->input->post('so_hd'));
        $data['TennguoiMH'] = urlencode($this->input->post('ten_nguoi_mh'));
        $data['DonviMH'] = urlencode($this->input->post('don_vi_mh'));
        $data['Diachi'] = urlencode($this->input->post('dia_chi'));
        $data['Masothue'] = urlencode($this->input->post('ma_so_thue'));
        $data['Sotaikhoan'] = urlencode($this->input->post('so_tai_khoan'));
        $data['HinhthucTT'] = urlencode($this->input->post('hinh_thuc_tt'));
        $data['NgayHD'] = $order['order_date'];

        $data['Mamau'] = '001';
        $data['loaitien'] = 'VND';
        $data['Tygia'] = '1';
        $data['type'] = 2;
        //$data['dshanghoa'] = "[{STT:'1',MaHH:'HH1',TenHH:'AasasdsaBC',SL:1,DVT:'Cai',Thuesuat:10,Dongia:1250000},{STT:'2',MaHH:'HH1',TenHH:'asdadas',SL:1,DVT:'Cai',Thuesuat:10,Dongia:180000}]";
        $this->load->model('bill_item_model');
        $data['dshanghoa'] = $this->bill_item_model->get_order_item_json($shop_id, $order_id, $order['vat_rate']);
        $data['key'] = $order_id;

        //echo(json_encode($data));
        //return;
        $result = ebill_create($data);
        $xml = simplexml_load_string($result);

        $result = array();
        switch (intval($xml[0])) {
            case 1:
                $result[] = "Không tạo được hóa đơn";
                break;
            case 2:
                $result[] = "Không tạo được hóa đơn, Lỗi hệ thống";
                break;
            case 3:
                $result[] = "Tài khoản không hợp lệ";
                break;
            case 4:
                $result[] = "Không có hàng hóa";
                break;
            case 5:
                $result[] = "Ngày hóa đơn không hợp lệ";
                break;
            case 6:
                $result[] = "Ngày hóa đơn < ngày phát hành";
                break;
            case 7:
                $result[] = "Số hóa đơn đã tồn tại";
                break;
            case 8:
                $result[] = "Key đã tồn tại";
                break;
            case 9:
                $result[] = "Số hóa đơn không hợp lệ";
                break;
            default:
                $result[] = "Tạo hóa đơn điện tử thành công";
                $params = array();
                $params['shop_id'] = $shop_id;
                $params['order_id'] = $order_id;
                $params['ma_hoa_don'] = intval($xml[0]);
                $params['mauso_hd'] = $data['MauSoHD'];
                $params['so_hoa_don'] = $data['SoHoaDon'];
                $params['ma_loai_hoa_don'] = $data['MaloaiHD'];
                $params['ten_nguoi_mh'] = $data['TennguoiMH'];
                $params['don_vi_mh'] = $data['DonviMH'];
                $params['dia_chi'] = $data['Diachi'];
                $params['ma_so_thue'] = $data['Masothue'];

                $params['so_tai_khoan'] = $data['Sotaikhoan'];
                $params['hinhthuc_tt'] = $data['HinhthucTT'];
                $params['ngay_hd'] = $data['NgayHD'];
                $params['ma_chi_nhanh'] = $data['MaChiNhanh'];
                $params['ma_mau'] = $data['Mamau'];
                $params['loai_tien'] = $data['loaitien'];
                $params['ty_gia'] = $data['Tygia'];
                $params['type'] = $data['type'];

                $params['ds_hang_hoa'] = $data['dshanghoa'];
                $params['key'] = $data['key'];

                $this->update_ebill($params);

                break;
        }

        echo (json_encode($result));
        //echo(json_encode($data));

    }

    function update_ebill($params)
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('ebill_model');
        $ebill = $this->ebill_model->get_order_ebill($shop_id, $params['order_id']);
        if ($ebill) {
            return;
        }

        $this->ebill_model->add_ebill($params);
    }



    function init_ebill()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->post('order_id'));

        $this->load->model('ebill_model');
        $ebill = $this->ebill_model->get_order_ebill($shop_id, $order_id);

        if ($ebill) {
            //$result = array();
            $result = $ebill;
            echo (json_encode($result));
            return;
        } else {
            $this->load->model('order_model');
            $order = $this->order_model->get_order($order_id, $shop_id);
            $this->load->model('shop_ebill_model');
            $shop_ebill = $this->shop_ebill_model->get_shop_ebill($shop_id, 'nacencomm');

            $result = array();
            $result['so_hd'] = $order_id;
            if ($shop_ebill) {
                $result['ma_so_thue'] = $shop_ebill['ma_so_thue'];
                $result['usernamews'] = $shop_ebill['usernamews'];
                $result['passwordws'] = $shop_ebill['passwordws'];
                $result['mau_so_hd'] = $shop_ebill['mau_so_hd'];
                $result['ky_hieu'] = $shop_ebill['ky_hieu'];
                $result['ma_loai_hd'] = $shop_ebill['ma_loai_hd'];
                $result['mauso_hd'] = $shop_ebill['mau_so_hd'];;
            }

            $result['ten_nguoi_mh'] = $order['customer_name'];
            $result['dia_chi'] = $order['customer_address'];
            $result['mauso_hd'] = $result['mauso_hd'] . '/' . $order['id'];

            echo (json_encode($result));
            //return;

        }
    }


    function init_ebill1()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->post('order_id'));

        $this->load->model('order_model');
        $order = $this->order_model->get_order2($order_id, $shop_id);

        $result = array();
        $bill_book = $order['bill_book'];
        if (!$bill_book) {
            // $invoice_issued_date, $buyer_taxcode, $buyer_display_name
            $result['invoice_issued_date'] = date('Y-m-d');
            $result['buyer_display_name'] = $order['customer_name'];
            $result['buyer_taxcode'] = $order['buyer_taxcode'];
            $result['buyer_address'] = $order['customer_address'];
            $result['buyer_unit'] = $order['last_name'];
        } else {
            $bill_book = json_decode($bill_book, true);
            if (array_key_exists('minvoice', $bill_book)) {
                $bill_book = $bill_book['minvoice'];
                $result['invoice_issued_date'] = $bill_book['invoice_issued_date'];
                $result['buyer_display_name'] = $bill_book['buyer_display_name'];
                $result['buyer_taxcode'] = $bill_book['buyer_taxcode'];
                $result['buyer_address'] = $bill_book['buyer_address'];
                $result['buyer_unit'] = $bill_book['buyer_unit'];
                $result['buyer_phone'] = $bill_book['buyer_phone'];
                $result['buyer_id'] = $bill_book['buyer_id'];
                $result['serial'] = $bill_book['serial'];
                $result['id'] = $bill_book['id'];
            }

            if (array_key_exists('ncc_invoice', $bill_book)) {
                $bill_book = $bill_book['ncc_invoice'];
                $result['invoice_issued_date'] = $bill_book['invoice_issued_date'];
                $result['buyer_display_name'] = $bill_book['buyer_display_name'];
                $result['buyer_taxcode'] = $bill_book['buyer_taxcode'];
                $result['buyer_address'] = $bill_book['buyer_address'];
                $result['buyer_unit'] = $bill_book['buyer_unit'];
                $result['buyer_phone'] = $bill_book['buyer_phone'];
                $result['buyer_id'] = $bill_book['buyer_id'];
            }
            if (array_key_exists('vnpt', $bill_book)) {
                $bill_book = $bill_book['vnpt'];
                $result['invoice_issued_date'] = $bill_book['invoice_issued_date'];
                $result['buyer_display_name'] = $bill_book['buyer_display_name'];
                $result['buyer_taxcode'] = $bill_book['buyer_taxcode'];
                $result['buyer_address'] = $bill_book['buyer_address'];
                $result['buyer_unit'] = $bill_book['buyer_unit'];
                $result['buyer_phone'] = $bill_book['buyer_phone'];
                $result['buyer_id'] = $bill_book['buyer_id'];
            }
        }

        echo (json_encode($result));
        //return;


    }

    function update_product_image()
    {

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $product_id = $this->input->post('product_id');
        $new_path = FCPATH;
        $new_path = $new_path . 'img/' . $shop_id . '/product_' . $product_id;
        if (!file_exists(FCPATH . 'img/' . $shop_id)) {
            mkdir(FCPATH . 'img/' . $shop_id);
        }

        $config['upload_path']          = '/tmp/';
        $config['allowed_types']        = 'gif|jpg|png';
        $config['max_size']             = 10000;
        $config['max_width']            = 30000;
        $config['max_height']           = 30000;
        $this->load->library('upload', $config);
        if ($this->upload->do_upload('file')) {
            $path = $this->upload->data()['full_path'];
            //echo($path);
            copy($path, $new_path);
            $params = array();
            $params['image_file'] = '/product_' . $product_id;
            $this->load->model('product_model');
            $this->product_model->update_product($product_id, $shop_id, $params);
        } else {
            echo ($this->upload->display_errors());
        }
    }

    function nacencomm()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $tax_id = std($this->input->post('tax_id'));
        $tax_name = std($this->input->post('tax_name'));
        $tax_phone = std($this->input->post('tax_phone'));
        $tax_email = std($this->input->post('tax_email'));

        $params = array();
        $params['shop_id'] = $shop_id;
        $params['tax_code'] = $tax_id;
        $params['name'] = $tax_name;
        $params['phone'] = $tax_phone;
        $params['email'] = $tax_email;

        $this->load->model('nacencomm_model');
        $this->nacencomm_model->add_nacencomm($params);
    }

    function pharm_shop()
    {
        header('Content-Type: application/javascript');
        $this->load->model('shop_model');
        $shops = $this->shop_model->get_shops_by_type(11);
        //$data["shops"] = $shops;
        echo ('var pharm_shop = "');
        foreach ($shops as $shop) {
            echo ($shop['id'] . '. ' . $shop['name'] . ' | ');
        }
        echo ('";');
        echo ('document.getElementById("scroll").innerHTML = pharm_shop;');
    }

    function extend_visitors()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $bill_item_id = intval($this->input->post('bill_item_id'));
        $start_date = std($this->input->post('start_date'));
        $start_date = date_from_vn($start_date);
        $end_date = std($this->input->post('end_date'));
        $end_date = date_from_vn($end_date);

        $this->load->model('visitor_model');
        $params = array();
        $params['checkin_date'] = $start_date;
        $params['checkout_date'] = $end_date;

        $this->visitor_model->update_visitors($shop_id, $bill_item_id, $params);

        $nogs = $this->visitor_model->count_bill_item_visitor($shop_id, $bill_item_id, $start_date, $end_date);
        $this->load->model('occupy_order_model');
        foreach ($nogs as $nog) {
            $date = $nog['date'];
            $count = $nog['count'];
            $this->occupy_order_model->update_occupy_order_nog($shop_id, $bill_item_id, $date, $count);
        }
    }

    function get_visitors_by_name()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $name = std($this->input->post('name'));

        $this->load->model('visitor_model');

        $visitors = $this->visitor_model->search_visitors($shop_id, $name);
        if (count($visitors) > 0) {
            $data = array();
            $data['visitors'] = $visitors;
            $this->load->view('search_visitors', $data);
        }
    }

    function muongi()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        //$token = tb_token();
        $muongi = "https://muongi.vn/$shop_id";

        redirect($muongi);
    }

    function muongi2()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $user_id = $this->user->user_id;
        $this->muongi_user();
        $shop = $this->muongi_shop();
        $this->muongi_product();

        $shop_id = $this->user->shop_id;
        $token = tb_token();
        //$muongi = "https://muongi.vn/store/tiem/". $shop_id ."/". $token ."/" . ktk_encode_value($this->user->user_id);

        $shop = json_decode($shop, true);
        $shop_id = $shop['muongi-store-id'];

        $muongi = 'https://muongi.vn/store/show/' . $shop_id;

        redirect($muongi);
    }


    function count_unpaid_orders()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $this->load->model('order_model');
        $orders = $this->order_model->count_unpaid_orders($shop_id);
        echo ($orders);
    }

    function paynow()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $order_ids = $this->input->post('order_ids');

        $this->load->model('order_model');
        $this->order_model->paid($shop_id, $order_ids);
    }

    function paid_all()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $order_id = $this->input->post('order_id');
        $this->load->model('order_model');

        $main_order = $this->order_model->get_order($order_id, $shop_id);
        $orders = $main_order['order_items'];
        $order_ids = json_decode($orders);
        $order_ids[] = $order_id;

        $this->order_model->paid($shop_id, $order_ids);
    }

    function get_material_items()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $order_ids = $this->input->post('order_ids');

        $order_ids = array('500', '501');

        $this->load->model('product_model');

        $materials = $this->product_model->get_material_items($shop_id, $order_ids);

        echo (json_encode($materials));
    }

    function muongi_user()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }

        $shop_id = $this->user->shop_id;
        $row = array();
        $row['user-id']     = $this->user->row['id'];
        $row['name']        = $this->user->row['full_name'];
        $row['user-role']   = $this->user->row['user_role'];
        $row['phone']       = $this->user->row['phone'];
        //$row['pass']        = 'muongi';
        $row['email']       = $this->user->row['email'];
        /*
        $row['province']    = 'province';
        $row['district']    = 'district';
        $row['ward']        = 'ward';
        $row['address']     = 'address';
        */
        $url = "https://muongi.vn/index.php/ext/user";
        $result = muongi_api('POST', $url, $row);
        return $result;
    }

    function muongi_shop()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $row = array();
        $row['user-id']     = $this->user->row['id'];
        $row['shop-id']     = $shop_id;
        $row['dai-dien']    = $this->user->shop['pharm_representative'];
        $row['name']        = $this->user->shop['name'];
        $row['title']       = 'Online Store';
        $row['phone']       = $this->user->shop['phone'];
        $row['email']       = $this->user->shop['email'];
        $row['tinh']        = $this->user->shop['state'];
        $row['huyen']       = $this->user->shop['district'];
        $row['xa']          = $this->user->shop['ward'];
        $row['dia-chi']     = $this->user->shop['address'];

        //echo(json_encode($row));
        $url = "https://muongi.vn/index.php/ext/store";
        $result = muongi_api('POST', $url, $row);
        return $result;
        //echo($result);
    }

    function muongi_product()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $user_id = $this->user->row['id'];
        $url = "https://muongi.vn/index.php/ext/sp";
        $this->load->model('product_model');
        $products = $this->product_model->get_shop_products($shop_id);
        foreach ($products as $item) {
            $row = array();
            $row['user-id']     = $user_id;
            $row['shop-id']     = $shop_id;
            $row['product-id']  = $item['id'];
            $row['category']    = '';
            $row['code']        = $item['product_code'];
            $row['title']       = $item['product_code'];
            $row['dvt']         = $item['unit_default'];
            $row['list-price']  = $item['list_price'];
            $row['so-luong']    = $item['stock'];
            $row['text']        = '';
            $row['is-hot']      = 0;
            $row['not-store']   = 0;
            $row['is-complex']  = 0;
            $row['setting']     = 0;
            if ($item['image_file'] != '') {
                $images = array();
                $images[] = 'https://hokinhdoanh.online/img/2896' . $item['image_file'];
                $row['images']     = $images;
            }


            $row['link-to-store'] = '';
            $row['link-to-view']  = '';

            $s1  = muongi_api('post', $url, $row);
            //echo($s1);
        }
    }

    function hotel_visitors()
    {
        //$this->output->enable_profiler(TRUE);        
        if ($this->check_user() === false) {
            $this->login();
            return;
        }

        $shop_id = $this->user->shop_id;
        $data = array();
        $data['title'] = tb_word('shops.purchase.orders');
        $data["user"] = $this->user;
        $this->load->view('headers/html_header', $data);
        $keyword = '';
        if (!empty($_POST)) {
            $keyword = std($this->input->post('keyword'));
            $this->load->model('visitor_model');
            $visitors = $this->visitor_model->search($shop_id, $keyword);
            $data['visitors'] = $visitors;
        }
        $data['keyword'] = $keyword;
        $this->load->view('visitors', $data);
        $this->load->view('headers/html_footer');
    }

    function update_visitor()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $id = intval($this->input->post('id'));
        $name = std($this->input->post('name'));
        $passport_id = std($this->input->post('passport_id'));
        $gender = std($this->input->post('gender'));
        $dob = date_from_vn($this->input->post('dob'));
        $province = std($this->input->post('province'));

        $params = array();
        $params['name'] = $name;
        $params['passport_id'] = $passport_id;
        $params['gender'] = $gender;
        $params['dob'] = $dob;
        $params['province'] = $province;

        $this->load->model('visitor_model');
        $this->visitor_model->update_visitor($id, $shop_id, $params);
    }

    function merge_visitor()
    {
        //$this->output->enable_profiler(TRUE);        
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $id = intval($this->input->post('id'));
        $ids = $this->input->post('ids');

        $this->load->model('visitor_model');
        $visitor = $this->visitor_model->get_visitor_lite($id, $shop_id);
        if (!$visitor) {
            return;
        }
        foreach ($ids as $item) {
            $item = intval($item);
            $v = $this->visitor_model->get_visitor_lite($item, $shop_id);
            if ($v) {
                $this->visitor_model->update_visitors2($shop_id, $v, $visitor);
            }
        }
    }

    function search_lib_products()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        //$this->output->enable_profiler(TRUE);

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = intval($this->input->post('shop_id'));
        $keyword = std($this->input->post('keyword'));

        $this->load->model('product_model');
        $products = $this->product_model->search_lib_product($shop_id, $keyword);

        $data = array();
        $data['shop_id'] = $shop_id;
        $data['products'] = $products;
        $this->load->view('lib_product', $data);
    }

    function search_lib_products2()
    {
        //$this->output->enable_profiler(TRUE);        
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $type = intval($this->input->post('type'));
        $keyword = std($this->input->post('keyword'));

        $this->load->model('product_model');
        $products = $this->product_model->search_lib_product2($type, $keyword);

        $data = array();
        $data['type'] = $type;
        $data['shop_id'] = $shop_id;
        $data['products'] = $products;
        $this->load->view('lib_product1', $data);
    }


    function reg_action()
    {
        if (!empty($_POST)) {
            //$this->output->enable_profiler(TRUE);
            //return;
            $this->load->model('shop_model');
            $count_shop_by_ip = $this->shop_model->count_shop_by_ip($this->input->ip_address());
            if ($count_shop_by_ip > 500) {
                redirect('/shop_create');
            }
            session_start();
            $count_shop_by_session = $this->shop_model->count_shop_by_session(session_id());
            if ($count_shop_by_session > 500) {
                redirect('/shop_create');
            }

            $shop_name = std($this->input->post('name'));
            $owner = std($this->input->post('owner'));
            $tax_code = std($this->input->post('tax_code'));
            $name = std($this->input->post('full_name'));
            $phone = std($this->input->post('phone'));
            $this->load->model('shop_user_model');
            if ($this->shop_user_model->check_phone_existence($phone) == 1) {
                echo ('Số điện thoại đã tồn tại');
                return;
            }

            $password = std($this->input->post('password'));
            $code = std($this->input->post('code'));
            $code1 = std($this->input->post('code1'));
            $email = std($this->input->post('email'));
            $address = std($this->input->post('address'));
            $province = std($this->input->post('province'));
            $district = std($this->input->post('district'));
            $ward = std($this->input->post('ward'));
            $ward1 = intval($this->input->post('ward1'));
            $quote = std($this->input->post('quote'));

            $tiem = intval($this->input->post('tiem'));
            $muongi = intval($this->input->post('muongi'));
            $micro = intval($this->input->post('micro'));
            $lon = floatval($this->input->post('lon'));
            $lat = floatval($this->input->post('lat'));

            $segment = std($this->input->post('segment'));

            $pharm_representative_id = std($this->input->post('pharm_representative_id'));

            $type = intval($this->input->post('type'));
            if ($type == 100) {
                $type = 0;
            }

            $promotion_code = std($this->input->post('promotion_code'));

            $params = array();
            $params['name'] = $shop_name;
            $params['owner'] = $owner;
            $params['tax_code'] = $tax_code;
            $params['phone'] = $phone;
            $params['email'] = $email;
            $params['state'] = $province;
            $params['district'] = $district;
            $params['ward'] = $ward;
            $params['location_id'] = $ward1;
            $params['address'] = $address;
            $params['quote'] = $quote;
            $params['type'] = $type;
            $params['tiem'] = $tiem;
            $params['muongi'] = 0;
            $params['micro'] = $micro;
            $params['lon'] = $lon;
            $params['lat'] = $lat;
            $params['segment'] = $segment;

            $params['promotion_code'] = $promotion_code;
            $params['ip'] = $this->input->ip_address();
            $params['session'] = session_id();

            $params['pharm_representative'] = $name;

            $now = strtotime(date("Y-m-d"));
            $expired = date("Y-m-d", strtotime("+15 days", $now));
            $params['expired'] = $expired;

            $period_closed_date = date('Y-m-1');
            $period_closed_date = date("Y-m-d", strtotime("-1 days", strtotime($period_closed_date)));
            $params['period_closed_date'] = $period_closed_date;


            $shop_id = $this->shop_model->add_shop($params);

            if ($type == 1000) {
                $types = $this->input->post('types');
                //echo(json_encode($types));
                $this->load->model('shop_type_model');
                foreach ($types as $item) {
                    $params = array();
                    $params['shop_id'] = $shop_id;
                    $params['type'] = $item;
                    $this->shop_type_model->add_shop_type($params);
                }
            }

            $user = new shop_user();
            //$data['user_pass'] = $password;
            $data['user_group'] = "admin";
            $data['user_role'] = "shops.lists.user-roles.manager";
            $data['full_name'] = $name;

            $data['phone'] = $phone;
            $data['email'] = $email;
            $data['shop_id'] = intval($shop_id);

            $data['user_group'] = 'admin';
            $token = ktk_get_token();
            $data['user_pass'] = dinhdq_encode($phone, $password);


            $id = $this->shop_user_model->add($data);

            $this->shop_user_model->init_table($shop_id);

            if ($type == 11) {
                $this->shop_user_model->init_table_data($shop_id, $type);
            }

            $this->load->helper('cookie');

            $cookie = array(
                'name'   => 'phone',
                'value'  => $phone,
                'expire' => time() + 6 * 30 * 24 * 3600,
                'domain' => $_SERVER['HTTP_HOST'],
                'path'   => '/',
                'prefix' => '',
            );
            set_cookie($cookie);
            //

            //$shop_name = $name;
            $shops = get_cookie('shops');
            if ($shops == null) {
                $shops = array();
            } else {
                $shops = json_decode($shops, true);
            }
            $shops[$shop_id] = $phone;
            $shops = json_encode($shops);
            $cookie = array(
                'name'   => 'shops',
                'value'  => $shops,
                'expire' => time() + 6 * 30 * 24 * 3600,
                'domain' => $_SERVER['HTTP_HOST'],
                'path'   => '/',
                'prefix' => '',
            );
            set_cookie($cookie);

            $message = "Bạn đã tạo cửa hàng thành công, xin mời đăng nhập";
            echo ($message);
        }
    }
    function export_excel()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $content = $this->input->post('content');
        //$content = strip_tags($content, '<table><tr><td>');
        $content = '<table>' . $content . '</table>';

        $data = array();
        $data['content'] = $content;
        $this->load->view('report_excel', $data);
    }

    function tentative_checkout()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $bill = $this->input->post("data");
        //echo(json_encode($bill));
        //return;
        if ($bill == NULL) {
            return;
        }

        $date = date('Y-m-d');
        $name = std($this->input->post("name"));
        $buyer_id = $shop_id;
        $buyer_name = $this->user->shop['name'];
        $buyer_phone = $this->user->shop['phone'];
        $buyer_address = $this->user->shop['address'];

        $params = array();

        $params['shop_id'] = $shop_id;
        $params['buyer_id'] = $buyer_id;
        $params['order_name'] = $name;
        $params['type'] = 1;

        $params['buyer_name'] = $buyer_name;
        $params['buyer_phone'] = $buyer_phone;
        $params['buyer_address'] = $buyer_address;

        $params['status'] = 0;

        $this->load->model('muongi_order_model');

        $order_id = $this->muongi_order_model->add_muongi_order($params);

        $this->load->model('product_model');

        $data = array();
        $products = array();
        $i = 0;

        $this->load->model('muongi_order_detail_model');

        $amount = 0;
        foreach ($bill as $item) {
            $params = array();
            $product_id = $item['product_id'];
            $product_name = $item['product_name'];
            $params['shop_id'] = $shop_id;
            $params['order_id'] = $order_id;
            $params['product_id'] = $product_id;
            $params['product_name'] = $product_name;
            $params['price'] = $item['price'];
            $params['quantity'] = floatval($item['quantity']);
            if (array_key_exists('start_date', $item)) {
                $params['start_date'] = date_from_vn($item['start_date']);
                $params['end_date'] = date_from_vn($item['end_date']);
            } else {
                unset($params['start_date']);
                unset($params['end_date']);
            }
            $params['status'] = 0;
            $params['amount'] = $params['price'] * $params['quantity'];
            $amount += $params['amount'];
            $this->muongi_order_detail_model->add_muongi_order_detail($params);
        }

        $params = array();
        $params['amount'] = $amount;
        $this->muongi_order_model->update_muongi_order($order_id, $params);

        echo ($order_id);
    }


    function tentative_orders()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $this->load->model('muongi_order_model');
        $orders = $this->muongi_order_model->get_muongi_orders($shop_id, 1, -1);
        $data = array();
        $data['title'] = 'Đơn hàng dự kiến';
        $data["user"] = $this->user;
        $data['orders'] = $orders;
        //toandk2 sửa
        if (check_user_agent('mobile')) {
            $data['title_header'] = 'Đơn hàng từ Muongi';
            $this->load->view('mobile_views/html_header_app', $data);
            $this->load->view('mobile_views/tentative_orders', $data);
            $this->load->view('mobile_views/html_footer_app');
        } else {
            $this->load->view('headers/html_header', $data);
            $this->load->view('tentative_orders', $data);
            $this->load->view('headers/html_footer');
        }
    }

    function muongi_orders()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $user_id = $this->user->row['id'];
        //echo($user_id);
        $this->load->model('shop_user_model');
        $token = $this->shop_user_model->generate_token_by_user_id($user_id);

        redirect('https://hkdo.vn/shop/login?token=' . $token);

        return;

        $this->load->model('muongi_order_model');
        $orders = $this->muongi_order_model->get_muongi_orders($shop_id, 0, -1);
        $data = array();
        $data['title'] = 'Đơn hàng từ Muongi';
        $data["user"] = $this->user;
        $this->load->view('headers/html_header', $data);
        $data['orders'] = $orders;
        $this->load->view('muongi_orders', $data);
        $this->load->view('headers/html_footer');
    }

    function muongi_order_detail()
    {
        //$this->output->enable_profiler(TRUE);
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get('id'));
        $this->load->model('muongi_order_model');
        $order = $this->muongi_order_model->get_muongi_order($shop_id, $order_id);
        if (!$order) {
            return;
        }

        $this->load->model('muongi_order_detail_model');
        $items = $this->muongi_order_detail_model->get_muongi_order_details($shop_id, $order_id);
        if (!$items) {
            return;
        }
        $data = array();
        $data['title'] = 'Muongi';

        $data["user"] = $this->user;
        $this->load->view('headers/html_header', $data);
        $data['items'] = $items;

        $this->load->model('message_model');
        $messages = $this->message_model->get_buyer_messages($order_id);
        $data['messages'] = $messages;
        $data['order_id'] = $order_id;
        $data['order'] = $order;

        if ($order['status'] == 2 || $order['status'] == 3) {
            $this->load->model('xeom_order_model');
            $xeom = $this->xeom_order_model->get_xeom($order['id']);
            $data['xeom'] = $xeom;
        }

        $this->load->view('muongi_order_detail', $data);
        $this->load->view('headers/html_footer');
    }

    function tentative_order_detail()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get('id'));

        $this->load->model('muongi_order_detail_model');
        $items = $this->muongi_order_detail_model->get_muongi_order_details($shop_id, $order_id);
        if (!$items) {
            return;
        }
        $data = array();
        $data['title'] = 'Muongi';
        $data["user"] = $this->user;
        $data['items'] = $items;

        $this->load->model('message_model');
        $messages = $this->message_model->get_buyer_messages($order_id);
        $data['messages'] = $messages;
        $data['order_id'] = $order_id;

        //toandk2 sửa
        if (check_user_agent('mobile')) {
            $data['title_header'] = 'Đơn dự kiến mua';
            $this->load->view('mobile_views/html_header_app', $data);
            $this->load->view('mobile_views/tentative_order_detail', $data);
        } else {
            $this->load->view('headers/html_header', $data);
            $this->load->view('tentative_order_detail', $data);
            $this->load->view('headers/html_footer');
        }
    }


    function add_message()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $order_id = intval($this->input->post('order_id'));

        $this->load->model('muongi_order_model');
        $order = $this->muongi_order_model->get_muongi_order($shop_id, $order_id);

        if (!$order) {
            echo (0);
            return;
        }
        $content = std($this->input->post('content'));

        $this->load->model('message_model');
        $params = array();
        $params['buyer_id'] = $order['buyer_id'];
        $params['shop_id'] = $order['shop_id'];
        $params['order_id'] = $order['id'];
        $params['content'] = $content;
        $params['type'] = 1;

        $message_id = $this->message_model->add_message($params);
        echo ($message_id);
    }


    function show_chat()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $order_id = intval($this->input->post('order_id'));
        $this->load->model('muongi_order_model');
        $order = $this->muongi_order_model->get_muongi_order($shop_id, $order_id);
        if (!$order) {
            return;
        }

        $this->load->model('message_model');
        $messages = $this->message_model->get_buyer_messages($order_id);
        $data = array();
        $data['messages'] = $messages;
        $this->load->view('muongi/message', $data);
    }

    function muongi_checkout()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->post('order_id'));
        $this->load->model('muongi_order_model');
        $this->load->model('muongi_order_detail_model');
        $order = $this->muongi_order_model->get_muongi_order($shop_id, $order_id);
        if (!$order) {
            echo (0);
            return;
        }
        $old_order_items = $this->muongi_order_detail_model->get_muongi_order_details($shop_id, $order_id);
        $order_items = $this->input->post("data");

        $old_order_items1 = array();
        foreach ($old_order_items as $item) {
            $old_order_items1[$item['product_id']] = $item;
        }
        $order_items1 = array();
        foreach ($order_items as $item) {
            $order_items1[$item['product_id']] = $item;
        }
        $update = false;
        $total = 0;
        foreach ($order_items as $item) {
            $product_id = $item['product_id'];
            $total += $item['price'] * $item['quantity'];

            if (array_key_exists($product_id, $old_order_items1)) {
                $params = array();
                $old_item = $old_order_items1[$product_id];

                if ($old_item['quantity'] != $item['quantity']) {
                    $params['quantity'] = $item['quantity'];
                }
                if ($old_item['price'] != $item['price']) {
                    $params['price'] = $item['price'];
                }
                if (!empty($params)) {
                    $params['status'] = 1;
                    $params['amount'] = $item['price'] * $item['quantity'];
                    $this->muongi_order_detail_model->update_muongi_order_detail($old_item['id'], $params);
                    $update = true;
                }
            } else {
                $params = array();
                $params['shop_id'] = $shop_id;
                $params['order_id'] = $order_id;
                $params['quantity'] = $item['quantity'];
                $params['price'] = $item['price'];
                $params['status'] = 3;
                $params['product_id'] = $product_id;
                $params['product_name'] = $item['product_name'];
                $params['amount'] = $item['quantity'] * $item['price'];
                $this->muongi_order_detail_model->add_muongi_order_detail($params);
                $update = true;
            }
        }

        foreach ($old_order_items as $item) {
            $product_id = $item['product_id'];
            if (!array_key_exists($product_id, $order_items1)) {
                $params = array();
                $params['status'] = 100;
                $this->muongi_order_detail_model->update_muongi_order_detail($item['id'], $params);
                $update = true;
            }
        }

        $params = array();
        $params['amount'] = $total;
        $this->muongi_order_model->update_muongi_order($order_id, $params);

        if ($update) {
            $this->load->model('message_model');
            $params = array();
            $params['content'] = 'Đơn hàng đã được sửa lúc ' . vn_date_time(date("Y-m-d H:i:s"));
            $params['order_id'] = $order_id;
            $params['type'] = 1;
            $params['shop_id'] = $shop_id;
            $params['buyer_id'] = $order['buyer_id'];

            $this->message_model->add_message($params);
        }

        echo ($order_id);
    }

    function change_muongi_order_status()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->post('order_id'));
        $status = intval($this->input->post('status'));

        $this->load->model('muongi_order_model');
        $order = $this->muongi_order_model->get_muongi_order($shop_id, $order_id);

        if (!$order) {
            return;
        }

        $params = array();
        $params['status'] = $status;
        $this->muongi_order_model->update_muongi_order($order_id, $params);
    }

    function scrolling_shops()
    {
        $this->load->model('shop_model');
        $shops = $this->shop_model->get_100_shops();
        $s = '';
        foreach ($shops as $item) {
            if ($item['name'] != '') {
                $s .= $item['id'] . '. ' . $item['name'] . ' - ';
            }
        }
        echo ('var companies1 = "' . $s . '";');
    }


    function muongi_update_shipping_cost()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->post('order_id'));
        $shipping_cost = intval($this->input->post('cost'));

        $this->load->model('muongi_order_model');
        $order = $this->muongi_order_model->get_muongi_order($shop_id, $order_id);

        if (!$order) {
            return;
        }

        $this->load->model('muongi_order_detail_model');
        $order_items = $this->muongi_order_detail_model->get_muongi_order_details($shop_id, $order_id);
        $amount = 0;
        foreach ($order_items as $item) {
            $amount += $item['amount'];
        }


        $params = array();
        $params['shipping_cost'] = $shipping_cost;
        $params['amount'] = $amount + $shipping_cost;
        $this->muongi_order_model->update_muongi_order($order_id, $params);
        echo (vn_amount($params['amount']));
    }

    function muongi_confirm_order()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->post('order_id'));

        $this->load->model('muongi_order_model');
        $order = $this->muongi_order_model->get_muongi_order($shop_id, $order_id);

        if (!$order) {
            return;
        }
        $params = array();
        $params['status'] = 1;
        $this->muongi_order_model->update_muongi_order($order_id, $params);
        echo (vn_amount($params['amount']));
    }

    function muongi_accept_xeom()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->post('order_id'));

        $this->load->model('muongi_order_model');
        $order = $this->muongi_order_model->get_muongi_order($shop_id, $order_id);

        if (!$order) {
            return;
        }
        $params = array();
        $params['status'] = 3;
        $params['xeom_approval_time'] = date('Y-m-d H:i:s');
        $this->muongi_order_model->update_muongi_order($order_id, $params);
        echo (vn_amount($params['amount']));
    }

    function muongi_deni_xeom()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->post('order_id'));

        $this->load->model('muongi_order_model');
        $order = $this->muongi_order_model->get_muongi_order($shop_id, $order_id);

        if (!$order) {
            return;
        }
        $params = array();
        $params['status'] = 1;
        $this->muongi_order_model->update_muongi_order($order_id, $params);
        echo (vn_amount($params['amount']));
    }


    function check_gps()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $result = array();
        $this->load->model('shop_model');
        $result['gps'] = $this->shop_model->check_gps($shop_id);
        echo (json_encode($result));
    }


    function update_gps()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $old_adress = $this->user->shop['address'];

        $params = array();
        if ($old_adress == '') {
            //$params = std($this->input->post('address'));
        }
        $params['lat'] = floatval($this->input->post('lat'));
        $params['lon'] = floatval($this->input->post('lon'));
        $params['state'] = std($this->input->post('state'));
        $params['district'] = std($this->input->post('district'));
        $params['ward'] = std($this->input->post('ward'));
        $params['address'] = std($this->input->post('address'));


        $this->load->model('shop_model');
        $this->shop_model->update_shop($shop_id, $params);
    }

    function check_segment()
    {
        $this->load->model('shop_model');
        $segment = std($this->input->post('segment'));
        $row = $this->shop_model->get_shop_by_segment($segment);
        $result = array();
        if ($row) {
            $result['existed'] = 1;
        } else {
            $result['existed'] = 0;
        }
        echo (json_encode($result));
    }

    function report7888()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }

        $shop_type = $this->user->shop['type'];
        if (intval($shop_type) == 11) {
            redirect('/pharm/report7888');
        }

        if (!empty($_POST)) {
            $type = intval($this->input->post('type'));
            $year = intval($this->input->post('year'));
            $month = $this->input->post('month');
            $date = date_from_vn($this->input->post('date'));

            $date_from = std($this->input->post('date_from'));
            $date_to = std($this->input->post('date_to'));

            $unit = urlencode(std($this->input->post('unit')));
            $address = urlencode(std($this->input->post('address')));
            $open_date = date_from_vn($this->input->post('open_date'));

            $director = urlencode(std($this->input->post('director')));
            $chief_accountant = urlencode(std($this->input->post('chief_accountant')));
            $creator = urlencode(std($this->input->post('creator')));
            $report = intval($this->input->post('report'));
            $rp = intval($this->input->post('rp'));

            if ($type == 1) {
                redirect("/report7888_5?year=$year&date=$date&month=$month&unit=$unit&address=$address&open_date=$open_date&director=$director&chief_accountant=$chief_accountant&creator=$creator&date_from=$date_from&date_to=$date_to&report=$report");
            }
            if ($type == 2) {
                redirect("/report7888_1?year=$year&date=$date&unit=$unit&address=$address&open_date=$open_date&director=$director&chief_accountant=$chief_accountant&creator=$creator&date_from=$date_from&date_to=$date_to&report=$report");
            }
            if ($type == 3) {
                //$this->output->enable_profiler(TRUE);
                $product_id = intval($this->input->post('product_id'));
                if ($product_id != 0) {
                    redirect("/report7888_2?year=$year&date=$date&unit=$unit&address=$address&open_date=$open_date&director=$director&chief_accountant=$chief_accountant&creator=$creator&product_id=$product_id&date_from=$date_from&date_to=$date_to&report=$report");
                }
                $product_ids = $this->input->post('product_ids');
                if (!empty($product_ids)) {
                    $st = '';
                    foreach ($product_ids as $product_id) {
                        $st .= '&product_ids[]=' . intval($product_id);
                    }
                    //redirect("/report7888_2_2?year=$year&date=$date&unit=$unit&address=$address&open_date=$open_date&director=$director&chief_accountant=$chief_accountant&creator=$creator'. $st .'&date_from=$date_from&date_to=$date_to&report=$rp");
                    header("Location: /report7888_2_2?year=$year&date=$date&unit=$unit&address=$address&open_date=$open_date&director=$director&chief_accountant=$chief_accountant&creator=$creator'. $st .'&date_from=$date_from&date_to=$date_to&report=$rp");
                    die();
                }
            }
            if ($type == 4) {
                redirect("/report7888_4?year=$year&date=$date&unit=$unit&address=$address&open_date=$open_date&director=$director&chief_accountant=$chief_accountant&creator=$creator&date_from=$date_from&date_to=$date_to&report=$report");
            }
            if ($type == 43) {
                redirect("/report7888_43?year=$year&date=$date&unit=$unit&address=$address&open_date=$open_date&director=$director&chief_accountant=$chief_accountant&creator=$creator&date_from=$date_from&date_to=$date_to&report=$report");
            }
            if ($type == 42) {
                redirect("/report7888_42?year=$year&date=$date&unit=$unit&address=$address&open_date=$open_date&director=$director&chief_accountant=$chief_accountant&creator=$creator&date_from=$date_from&date_to=$date_to&report=$report");
            }
            if ($type == 44) {
                redirect("/report7888_44?year=$year&date=$date&unit=$unit&address=$address&open_date=$open_date&director=$director&chief_accountant=$chief_accountant&creator=$creator&date_from=$date_from&date_to=$date_to&report=$report");
            }
            if ($type == 5) {
                redirect("/report7888_6?year=$year&date=$date&unit=$unit&address=$address&open_date=$open_date&director=$director&chief_accountant=$chief_accountant&creator=$creator&date_from=$date_from&date_to=$date_to&report=$report");
            }
            if ($type == 7) {
                redirect("/report7888_7?year=$year&date=$date&unit=$unit&address=$address&open_date=$open_date&director=$director&chief_accountant=$chief_accountant&creator=$creator&date_from=$date_from&date_to=$date_to&report=$report");
            }

            if ($type == 8) {
                redirect("/report7888_8?year=$year&date=$date&unit=$unit&address=$address&open_date=$open_date&director=$director&chief_accountant=$chief_accountant&creator=$creator&date_from=$date_from&date_to=$date_to&report=$report");
            }
            if ($type == 9) {
                redirect("/report7888_9?year=$year&date=$date&unit=$unit&address=$address&open_date=$open_date&director=$director&chief_accountant=$chief_accountant&creator=$creator&date_from=$date_from&date_to=$date_to&report=$report");
            }
        }

        $shop_id = $this->user->shop_id;
        $data = array();
        $data['title'] = tb_word('shops.purchase.orders');
        $data["user"] = $this->user;
        $this->load->view('headers/html_header', $data);

        $date = date('Y-m-d');

        //$date_from = date("Y-m-01", strtotime("-1 month", strtotime($date)));
        //$date_to = date("Y-m-t", strtotime("-1 month", strtotime($date)));
        $date_from = date("Y-m-01");
        $date_to = date("Y-m-t");

        if ($date >= date("Y-04-01") && $date <= date("Y-06-30")) {
            $date_from = date("Y-01-01");
            $date_to = date("Y-03-31");
        }

        if ($date >= date("Y-07-01") && $date <= date("Y-09-30")) {
            $date_from = date("Y-04-01");
            $date_to = date("Y-06-30");
        }

        if ($date >= date("Y-10-01") && $date <= date("Y-12-31")) {
            $date_from = date("Y-07-01");
            $date_to = date("Y-09-30");
        }

        if ($date >= date("Y-01-01") && $date <= date("Y-03-31")) {
            $y = date("Y");
            $y = $y - 1;
            $date_from = $y . '-10-01';
            $date_to = $y . '-12-31';
        }

        $data = array();
        $this->load->model('shop_detail_model');
        $shop_detail = $this->shop_detail_model->get_shop_detail($shop_id);
        $data['shop_detail'] = $shop_detail;
        $data['date'] = $date;
        $year = date('Y');
        $month = date('m');
        $data['year'] = $year;
        $data['month'] = $month;


        $data['date_from'] = $date_from;
        $data['date_to'] = $date_to;

        $this->load->model('product_model');
        $products = $this->product_model->get_non_service_products($shop_id);
        $data['products'] = $products;

        $this->load->model('product_group_model');
        $groups = $this->product_group_model->get_all_product_groups($shop_id);
        $data['groups'] = $groups;

        $this->load->view('report7888', $data);
        $this->load->view('headers/html_footer');
    }


    function report7888_5()
    {

        //$this->output->enable_profiler(TRUE);
        $report = intval($this->input->get('report'));
        if ($report == 1) {
            $filename = "report7888_5.xls";
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Content-Type: application/vnd.ms-excel");
            header("Pragma: no-cache");
            header("Expires: 0");
        }
        if ($report == 2) {
            $filename = "report7888_5.doc";
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Content-Type: application/vnd.ms-word");
            header("Pragma: no-cache");
            header("Expires: 0");
        }


        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $data = array();
        $this->load->model('shop_detail_model');
        $shop_detail = $this->shop_detail_model->get_shop_detail($shop_id);

        $data['shop_detail'] = $shop_detail;

        $date_from = std($this->input->get('date_from'));
        if ($date_from != '') {
            $date_from = date_from_vn($date_from);
        }
        $date_to = std($this->input->get('date_to'));
        if ($date_to != '') {
            $date_to = date_from_vn($date_to);
        }

        $date = std($this->input->get('month'));
        $year = intval($this->input->get('year'));
        $unit = std($this->input->get('unit'));
        $address = std($this->input->get('address'));
        $open_date = std($this->input->get('open_date'));
        $director = std($this->input->get('director'));
        $chief_accountant = std($this->input->get('chief_accountant'));
        $creator = std($this->input->get('creator'));


        $data['date'] = $date;
        $m = date('m', strtotime($date));
        $data['m'] = $m;
        $y = date('Y', strtotime($date));
        $data['y'] = $y;
        $data['year'] = $year;
        $data['unit'] = $unit;
        $data['address'] = $address;
        $data['open_date'] = $open_date;
        $data['director'] = $director;
        $data['chief_accountant'] = $chief_accountant;
        $data['creator'] = $creator;
        $data['date_from'] = $date_from;
        $data['date_to'] = $date_to;


        $this->load->model('order_model');
        $order = $this->order_model->search_employee_order($shop_id, $y, $m);
        if (!$order) {
            echo ('Không có bảng lương tháng này');
            return;
        }
        $data['order'] = $order;
        $this->load->model('order_salary_model');
        $items = $this->order_salary_model->get_order_salary_items($shop_id, $order['id']);
        $data['items'] = $items;

        $this->load->view('report7888_5', $data);
    }

    function report7888_6()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        //$this->output->enable_profiler(TRUE);
        $report = intval($this->input->get('report'));
        if ($report == 1) {
            $filename = "report7888_6.xls";
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Content-Type: application/vnd.ms-excel");
            header("Pragma: no-cache");
            header("Expires: 0");
        }
        if ($report == 2) {
            $filename = "report7888_6.doc";
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Content-Type: application/vnd.ms-word");
            header("Pragma: no-cache");
            header("Expires: 0");
        }

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $data = array();

        $this->load->model('shop_model');
        $shop = $this->shop_model->get_shop($shop_id);
        $data['shop'] = $shop;

        $this->load->model('shop_detail_model');
        $shop_detail = $this->shop_detail_model->get_shop_detail($shop_id);

        $data['shop_detail'] = $shop_detail;

        $date_from = std($this->input->get('date_from'));
        if ($date_from != '') {
            $date_from = date_from_vn($date_from);
        }
        $date_to = std($this->input->get('date_to'));
        if ($date_to != '') {
            $date_to = date_from_vn($date_to);
        }
        if ($date_to != '' && $date_from != '') {
            $year = 0;
        }

        $date = std($this->input->get('date'));
        $year = intval($this->input->get('year'));
        $unit = std($this->input->get('unit'));
        $address = std($this->input->get('address'));
        $open_date = std($this->input->get('open_date'));
        $director = std($this->input->get('director'));
        $chief_accountant = std($this->input->get('chief_accountant'));
        $creator = std($this->input->get('creator'));


        $data['date'] = $date;
        $d = date('d', strtotime($date));
        $data['d'] = $d;
        $m = date('m', strtotime($date));
        $data['m'] = $m;
        $y = date('Y', strtotime($date));
        $data['y'] = $y;
        $data['year'] = $year;
        $data['unit'] = $unit;
        $data['address'] = $address;
        $data['open_date'] = $open_date;
        $data['director'] = $director;
        $data['chief_accountant'] = $chief_accountant;
        $data['creator'] = $creator;
        $data['date_from'] = $date_from;
        $data['date_to'] = $date_to;


        $this->load->model('report_model');
        $orders = $this->report_model->report7888_6($shop_id, $date_from, $date_to);
        $data['orders'] = $orders;
        $order_ids = array();
        foreach ($orders as $order) {
            $order_ids[$order['order_id']] = $order;
        }

        $data['order_ids'] = $order_ids;

        $this->load->view('report7888_6', $data);
    }


    function report7888_7()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        //$this->output->enable_profiler(TRUE);
        $report = intval($this->input->get('report'));
        if ($report == 1) {
            $filename = "report7888_7.xls";
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Content-Type: application/vnd.ms-excel");
            header("Pragma: no-cache");
            header("Expires: 0");
        }
        if ($report == 2) {
            $filename = "report7888_7.doc";
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Content-Type: application/vnd.ms-word");
            header("Pragma: no-cache");
            header("Expires: 0");
        }


        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $data = array();

        $this->load->model('shop_model');
        $shop = $this->shop_model->get_shop($shop_id);
        $data['shop'] = $shop;

        $this->load->model('shop_detail_model');
        $shop_detail = $this->shop_detail_model->get_shop_detail($shop_id);

        $data['shop_detail'] = $shop_detail;

        $date_from = std($this->input->get('date_from'));
        if ($date_from != '') {
            $date_from = date_from_vn($date_from);
        }
        $date_to = std($this->input->get('date_to'));
        if ($date_to != '') {
            $date_to = date_from_vn($date_to);
        }
        if ($date_to != '' && $date_from != '') {
            $year = 0;
        }

        $date = std($this->input->get('date'));
        $year = intval($this->input->get('year'));
        $unit = std($this->input->get('unit'));
        $address = std($this->input->get('address'));
        $open_date = std($this->input->get('open_date'));
        $director = std($this->input->get('director'));
        $chief_accountant = std($this->input->get('chief_accountant'));
        $creator = std($this->input->get('creator'));


        $data['date'] = $date;
        $d = date('d', strtotime($date));
        $data['d'] = $d;
        $m = date('m', strtotime($date));
        $data['m'] = $m;
        $y = date('Y', strtotime($date));
        $data['y'] = $y;
        $data['year'] = $year;
        $data['unit'] = $unit;
        $data['address'] = $address;
        $data['open_date'] = $open_date;
        $data['director'] = $director;
        $data['chief_accountant'] = $chief_accountant;
        $data['creator'] = $creator;
        $data['date_from'] = $date_from;
        $data['date_to'] = $date_to;


        $this->load->model('report7888_model');

        $result = $this->report7888_model->report7888_7($shop_id, $date_from, $date_to);
        $data['result'] = $result;
        //$orders = $this->report_model->report7888_7($shop_id, $date_from, $date_to);
        //$data['orders'] = $orders;

        $this->load->view('report7888_7', $data);
    }


    function report7888_1()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        //$this->output->enable_profiler(TRUE);

        $report = intval($this->input->get('report'));
        if ($report == 1) {
            $filename = "report7888_1.xls";
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Content-Type: application/vnd.ms-excel");
            header("Pragma: no-cache");
            header("Expires: 0");
        }
        if ($report == 2) {
            $filename = "report7888_1.doc";
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Content-Type: application/vnd.ms-word");
            header("Pragma: no-cache");
            header("Expires: 0");
        }

        if ($this->check_user() === false) {
            $this->login();
            return;
        }

        $shop_id = $this->user->shop_id;
        $data = array();

        $this->load->model('shop_model');
        $shop = $this->shop_model->get_shop($shop_id);
        $data['shop'] = $shop;

        $this->load->model('shop_detail_model');
        $shop_detail = $this->shop_detail_model->get_shop_detail($shop_id);

        $data['shop_detail'] = $shop_detail;

        $date_from = std($this->input->get('date_from'));
        if ($date_from != '') {
            $date_from = date_from_vn($date_from);
        }
        $date_to = std($this->input->get('date_to'));
        if ($date_to != '') {
            $date_to = date_from_vn($date_to);
        }
        if ($date_to != '' && $date_from != '') {
            $year = 0;
        }

        $date = std($this->input->get('date'));
        $year = intval($this->input->get('year'));
        $unit = std($this->input->get('unit'));
        $address = std($this->input->get('address'));
        $open_date = std($this->input->get('open_date'));
        $director = std($this->input->get('director'));
        $chief_accountant = std($this->input->get('chief_accountant'));
        $creator = std($this->input->get('creator'));


        $data['date'] = $date;
        $d = date('d', strtotime($date));
        $data['d'] = $d;
        $m = date('m', strtotime($date));
        $data['m'] = $m;
        $y = date('Y', strtotime($date));
        $data['y'] = $y;
        $data['year'] = $year;
        $data['unit'] = $unit;
        $data['address'] = $address;
        $data['open_date'] = $open_date;
        $data['director'] = $director;
        $data['chief_accountant'] = $chief_accountant;
        $data['creator'] = $creator;
        $data['date_from'] = $date_from;
        $data['date_to'] = $date_to;

        //$this->load->model('order_model');
        //$orders = $this->order_model->filter_orders($shop_id, 'B', $date_from, $date_to);
        $this->load->model('report7888_model');

        $orders = $this->report7888_model->report7888_1_new($shop_id, $date_from, $date_to);
        $data['orders'] = $orders;

        $this->load->view('report7888_1_new', $data);
    }

    function report7888_2_2()
    {

        $report = intval($this->input->get('report'));
        if ($report == 1) {
            $filename = "report7888_2.xls";
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Content-Type: application/vnd.ms-excel");
            header("Pragma: no-cache");
            header("Expires: 0");
        }
        if ($report == 2) {
            $filename = "report7888_2.doc";
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Content-Type: application/vnd.ms-word");
            header("Pragma: no-cache");
            header("Expires: 0");
        }


        if ($this->check_user() === false) {
            $this->login();
            return;
        }

        $shop_id = $this->user->shop_id;
        $datas = array();


        $this->load->model('shop_model');
        $shop = $this->shop_model->get_shop($shop_id);
        $datas['shop'] = $shop;

        $this->load->model('shop_detail_model');
        $shop_detail = $this->shop_detail_model->get_shop_detail($shop_id);
        $datas['shop_detail'] = $shop_detail;


        $date = std($this->input->get('date'));
        $year = intval($this->input->get('year'));
        $unit = std($this->input->get('unit'));
        $address = std($this->input->get('address'));
        $open_date = std($this->input->get('open_date'));
        $director = std($this->input->get('director'));
        $chief_accountant = std($this->input->get('chief_accountant'));
        $creator = std($this->input->get('creator'));

        $date_from = std($this->input->get('date_from'));
        if ($date_from != '') {
            $date_from = date_from_vn($date_from);
        }
        $date_to = std($this->input->get('date_to'));
        if ($date_to != '') {
            $date_to = date_from_vn($date_to);
        }

        $data['date'] = $date;

        $d = date('d', strtotime($date));
        $datas['d'] = $d;
        $m = date('m', strtotime($date));
        $datas['month'] = $m;
        $y = date('Y', strtotime($date));
        $datas['y'] = $y;

        $datas['year'] = $year;
        $datas['unit'] = $unit;
        $datas['address'] = $address;
        $datas['open_date'] = $open_date;
        $datas['director'] = $director;
        $datas['chief_accountant'] = $chief_accountant;
        $datas['creator'] = $creator;

        $datas['date_from'] = $date_from;
        $datas['date_to'] = $date_to;

        $product_ids = $this->input->get('product_ids');

        $this->load->model('report_model');
        $this->load->model('product_model');

        foreach ($product_ids as $product_id) {
            $data = array();
            $b0 = $this->report_model->report132_3_b0($shop_id, $product_id, $year, $date_from, $date_to);
            $m0 = $this->report_model->report132_3_m0($shop_id, $product_id, $year, $date_from, $date_to);

            $b1 = $this->report_model->report132_3_b1($shop_id, $product_id, $year, $date_from, $date_to);

            $m1 = $this->report_model->report132_3_m1($shop_id, $product_id, $year, $date_from, $date_to);
            $product = $this->product_model->get_product($product_id, $shop_id);

            $items = $this->report_model->report132_3($shop_id, $product_id, $year, $date_from, $date_to);

            $this->load->model('shop_stock_model');
            $date_from2 = date('Y-m-d', strtotime('-1 day', strtotime($date_from)));
            $stock0 = $this->shop_stock_model->get_product_stock($shop_id, $product_id, $date_from2);


            /*
            $data['m0'] = $m0['quantity'];
            $data['m0_amount'] = $m0['amount'];
            $data['b0'] = $b0['quantity'];
            $data['b0_amount'] = $b0['amount'];
            */
            $data['b0'] = 0;
            $data['b0_amount'] = 0;
            $data['m0'] = $stock0['stock'];
            $data['m0_amount'] = $stock0['stock'] * $stock0['average_price'];


            $data['m1'] = $m1['quantity'];
            $data['b1'] = $b1['quantity'];

            //$data['m'] = $m['quantity'];
            //$data['m_amount'] = $m['amount'];

            //$amount0 = $m0['amount'] - $b0['amount'];

            $amount0 = $data['m0_amount'];

            $data['items'] = $items;

            $inventory = $m0['quantity'] - $b0['quantity'];
            //$amount0 = ($m0['amount']/$m0['quantity']) * $inventory;

            $inventory1 = $m1['quantity'] - $b1['quantity'];
            if ($m1['quantity'] != 0) {
                $amount1 = ($m1['amount'] / $m1['quantity']) * $inventory1;
            } else {
                $amount1 = 0;
            }
            /*
            if ($m0['quantity'] != 0){
                //$pricem0 = $m0['amount']/$m0['quantity'];
            }
            else{
                $pricem0 = 0;
            }
            */
            $pricem0 = $stock0['average_price'];
            if ($m1['quantity'] + $inventory != 0) {
                /*
                if ($data['m0']!=0){
                    //$amount0 = ($m0['amount'] / $data['m0']) * ($data['m0'] - $data['b0']);
                    $amount0 = $pricem0 * ($data['m0'] - $data['b0']);
                }
                else{
                    $amount0 = $m0['amount'] * ($data['m0'] - $data['b0']);
                }
                */
                $amount0 = $pricem0 * ($data['m0'] - $data['b0']);

                $pricem1 = ($amount0 + $m1['amount']) / ($inventory + $m1['quantity']);
                //$x = '(' . $amount0 . ' + ' . $m1['amount'] . ') / ('. $inventory  . ' + ' . $m1['quantity'] .')';
                //echo($x);
            } else {
                $pricem1 = 0;
            }
            if ($b1['quantity'] != 0) {
                $priceb1 = $b1['amount'] / $b1['quantity'];
            } else {
                $priceb1 = 0;
            }

            //$data['amount0'] = $amount0;
            $data['amount1'] = $amount1;

            $data['pricem1'] = $pricem1;
            $data['pricem0'] = $pricem0;
            $data['priceb1'] = $priceb1;

            $data['inventory'] = $inventory;
            //$data['inventory1'] = $inventory1;
            $data['product'] = $product;
            $datas['datas'][] = $data;
        }
        $this->load->view('report7888_2_2', $datas);
    }

    function report7888_2()
    {

        //error_reporting(-1);
        //ini_set('display_errors', 1);

        //$this->output->enable_profiler(TRUE);

        $report = intval($this->input->get('report'));
        if ($report == 1) {
            $filename = "report7888_2.xls";
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Content-Type: application/vnd.ms-excel");
            header("Pragma: no-cache");
            header("Expires: 0");
        }
        if ($report == 2) {
            $filename = "report7888_2.doc";
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Content-Type: application/vnd.ms-word");
            header("Pragma: no-cache");
            header("Expires: 0");
        }


        if ($this->check_user() === false) {
            $this->login();
            return;
        }

        $shop_id = $this->user->shop_id;
        $data = array();

        $this->load->model('shop_model');
        $shop = $this->shop_model->get_shop($shop_id);
        $data['shop'] = $shop;

        $this->load->model('shop_detail_model');
        $shop_detail = $this->shop_detail_model->get_shop_detail($shop_id);
        $data['shop_detail'] = $shop_detail;


        $date = std($this->input->get('date'));
        $year = intval($this->input->get('year'));
        $unit = std($this->input->get('unit'));
        $address = std($this->input->get('address'));
        $open_date = std($this->input->get('open_date'));
        $director = std($this->input->get('director'));
        $chief_accountant = std($this->input->get('chief_accountant'));
        $creator = std($this->input->get('creator'));

        $date_from = std($this->input->get('date_from'));
        if ($date_from != '') {
            $date_from = date_from_vn($date_from);
        }
        $date_to = std($this->input->get('date_to'));
        if ($date_to != '') {
            $date_to = date_from_vn($date_to);
        }

        $data['date'] = $date;

        $d = date('d', strtotime($date));
        $data['d'] = $d;
        $m = date('m', strtotime($date));
        $data['month'] = $m;
        $y = date('Y', strtotime($date));
        $data['y'] = $y;

        $data['year'] = $year;
        $data['unit'] = $unit;
        $data['address'] = $address;
        $data['open_date'] = $open_date;
        $data['director'] = $director;
        $data['chief_accountant'] = $chief_accountant;
        $data['creator'] = $creator;

        $data['date_from'] = $date_from;
        $data['date_to'] = $date_to;

        $product_id = intval($this->input->get('product_id'));

        $this->load->model('report_model');
        $this->load->model('product_model');

        $b0 = $this->report_model->report132_3_b0($shop_id, $product_id, $year, $date_from, $date_to);
        $m0 = $this->report_model->report132_3_m0($shop_id, $product_id, $year, $date_from, $date_to);

        $b1 = $this->report_model->report132_3_b1($shop_id, $product_id, $year, $date_from, $date_to);

        $m1 = $this->report_model->report132_3_m1($shop_id, $product_id, $year, $date_from, $date_to);
        /*
        if ($m0['quantity'] == $b0['quantity']){
            $m1 = $this->report_model->report132_3_m1($shop_id, $product_id, $year, $date_from, $date_to);
        }
        else{
            $m1 = $this->report_model->report132_3_m1($shop_id, $product_id, $year, '', $date_to);
        }
        */

        //$m = $this->report_model->report132_3_m($shop_id, $product_id, $year, $date_from, $date_to);

        $product = $this->product_model->get_product($product_id, $shop_id);

        $items = $this->report_model->report132_3($shop_id, $product_id, $year, $date_from, $date_to);

        $this->load->model('shop_stock_model');
        $date_from2 = date('Y-m-d', strtotime('-1 day', strtotime($date_from)));
        $stock0 = $this->shop_stock_model->get_product_stock($shop_id, $product_id, $date_from2);


        /*
        $data['m0'] = $m0['quantity'];
        $data['m0_amount'] = $m0['amount'];
        $data['b0'] = $b0['quantity'];
        $data['b0_amount'] = $b0['amount'];
        */
        $data['b0'] = 0;
        $data['b0_amount'] = 0;
        $data['m0'] = $stock0['stock'];
        $data['m0_amount'] = $stock0['stock'] * $stock0['average_price'];


        $data['m1'] = $m1['quantity'];
        $data['b1'] = $b1['quantity'];

        //$data['m'] = $m['quantity'];
        //$data['m_amount'] = $m['amount'];

        //$amount0 = $m0['amount'] - $b0['amount'];

        $amount0 = $data['m0_amount'];

        $data['items'] = $items;

        $inventory = $m0['quantity'] - $b0['quantity'];
        //$amount0 = ($m0['amount']/$m0['quantity']) * $inventory;

        $inventory1 = $m1['quantity'] - $b1['quantity'];
        if ($m1['quantity'] != 0) {
            $amount1 = ($m1['amount'] / $m1['quantity']) * $inventory1;
        } else {
            $amount1 = 0;
        }
        /*
        if ($m0['quantity'] != 0){
            //$pricem0 = $m0['amount']/$m0['quantity'];
        }
        else{
            $pricem0 = 0;
        }
        */
        $pricem0 = $stock0['average_price'];
        if ($m1['quantity'] + $inventory != 0) {
            /*
            if ($data['m0']!=0){
                //$amount0 = ($m0['amount'] / $data['m0']) * ($data['m0'] - $data['b0']);
                $amount0 = $pricem0 * ($data['m0'] - $data['b0']);
            }
            else{
                $amount0 = $m0['amount'] * ($data['m0'] - $data['b0']);
            }
            */
            $amount0 = $pricem0 * ($data['m0'] - $data['b0']);

            $pricem1 = ($amount0 + $m1['amount']) / ($inventory + $m1['quantity']);
            //$x = '(' . $amount0 . ' + ' . $m1['amount'] . ') / ('. $inventory  . ' + ' . $m1['quantity'] .')';
            //echo($x);
        } else {
            $pricem1 = 0;
        }
        if ($b1['quantity'] != 0) {
            $priceb1 = $b1['amount'] / $b1['quantity'];
        } else {
            $priceb1 = 0;
        }

        //$data['amount0'] = $amount0;
        $data['amount1'] = $amount1;

        $data['pricem1'] = $pricem1;
        $data['pricem0'] = $pricem0;
        $data['priceb1'] = $priceb1;

        $data['inventory'] = $inventory;
        $data['inventory1'] = $inventory1;
        $data['product'] = $product;

        $this->load->view('report7888_2', $data);
    }

    function report7888_43()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        //$this->output->enable_profiler(TRUE);
        $report = intval($this->input->get('report'));
        if ($report == 1) {
            $filename = "report7888_4.xls";
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Content-Type: application/vnd.ms-excel");
            header("Pragma: no-cache");
            header("Expires: 0");
        }

        if ($report == 2) {
            $filename = "report7888_4.doc";
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Content-Type: application/vnd.ms-word");
            header("Pragma: no-cache");
            header("Expires: 0");
        }

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $data = array();

        $this->load->model('shop_model');
        $shop = $this->shop_model->get_shop($shop_id);
        $data['shop'] = $shop;

        $this->load->model('shop_detail_model');
        $shop_detail = $this->shop_detail_model->get_shop_detail($shop_id);
        $data['shop_detail'] = $shop_detail;


        $date = std($this->input->get('date'));
        $d = date('d', strtotime($date));
        $data['d'] = $d;
        $m = date('m', strtotime($date));
        $data['m'] = $m;
        $y = date('Y', strtotime($date));
        $data['y'] = $y;

        $year = intval($this->input->get('year'));
        $unit = std($this->input->get('unit'));
        $address = std($this->input->get('address'));
        $open_date = std($this->input->get('open_date'));
        $director = std($this->input->get('director'));
        $chief_accountant = std($this->input->get('chief_accountant'));
        $creator = std($this->input->get('creator'));


        $date_from = std($this->input->get('date_from'));
        if ($date_from != '') {
            $date_from = date_from_vn($date_from);
        }
        $date_to = std($this->input->get('date_to'));
        if ($date_to != '') {
            $date_to = date_from_vn($date_to);
        }
        /*
        if ($date_to!='' && $date_from!=''){
            $year = 0;
        }
        */

        $data['date'] = $date;
        $data['year'] = $year;
        $data['unit'] = $unit;
        $data['address'] = $address;
        $data['open_date'] = $open_date;
        $data['director'] = $director;
        $data['chief_accountant'] = $chief_accountant;
        $data['creator'] = $creator;
        $data['date_from'] = $date_from;
        $data['date_to'] = $date_to;

        $this->load->model('report7888_model');

        //$tax1 = 0;
        /*
        for($type = 0; $type<=1; $type++){
            for($product_type = 0; $product_type<=3; $product_type++){
                $tax1 += $this->report7888_model->report7888_4_1($shop_id, $type, $product_type, $year, $date_from, $date_to);
            }
        }
        */
        $shop_type = $this->user->shop['type'];
        if ($shop_type != 11) {
            $tax1 = $this->report7888_model->gtgt_trong_ky($shop_id, $date_from, $date_to);
            $tax01 = $this->report7888_model->gtgt_phai_nop_dau_ky($shop_id, $date_from, $date_to);
        } else {
            $tax1 = $this->report7888_model->gtgt_trong_ky_pharm($shop_id, $date_from, $date_to);
            $tax01 = $this->report7888_model->gtgt_phai_nop_dau_ky_pharm($shop_id, $date_from, $date_to);
        }

        $tax2 = $this->report7888_model->nop_gtgt_trong_ky($shop_id, $date_from, $date_to);
        $tax02 = $this->report7888_model->gtgt_da_nop_dau_ky($shop_id, $date_from, $date_to);

        if (intval($tax01) < intval($tax02)) {
            $tax02 = intval($tax02) - intval($tax01);
            $tax01 = 0;
        } else {
            $tax01 = intval($tax01) - intval($tax02);
            $tax02 = 0;
        }

        $data['tax1'] = $tax1;
        $data['tax2'] = $tax2;

        $data['tax01'] = $tax01;
        $data['tax02'] = $tax02;

        $this->load->view('report7888_43', $data);
    }


    function report7888_4()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        //$this->output->enable_profiler(TRUE);
        $report = intval($this->input->get('report'));
        if ($report == 1) {
            $filename = "report7888_4.xls";
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Content-Type: application/vnd.ms-excel");
            header("Pragma: no-cache");
            header("Expires: 0");
        }

        if ($report == 2) {
            $filename = "report7888_4.doc";
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Content-Type: application/vnd.ms-word");
            header("Pragma: no-cache");
            header("Expires: 0");
        }

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $data = array();

        $this->load->model('shop_model');
        $shop = $this->shop_model->get_shop($shop_id);
        $data['shop'] = $shop;

        $this->load->model('shop_detail_model');
        $shop_detail = $this->shop_detail_model->get_shop_detail($shop_id);
        $data['shop_detail'] = $shop_detail;


        $date = std($this->input->get('date'));
        $d = date('d', strtotime($date));
        $data['d'] = $d;
        $m = date('m', strtotime($date));
        $data['m'] = $m;
        $y = date('Y', strtotime($date));
        $data['y'] = $y;

        $year = intval($this->input->get('year'));
        $unit = std($this->input->get('unit'));
        $address = std($this->input->get('address'));
        $open_date = std($this->input->get('open_date'));
        $director = std($this->input->get('director'));
        $chief_accountant = std($this->input->get('chief_accountant'));
        $creator = std($this->input->get('creator'));


        $date_from = std($this->input->get('date_from'));
        if ($date_from != '') {
            $date_from = date_from_vn($date_from);
        }
        $date_to = std($this->input->get('date_to'));
        if ($date_to != '') {
            $date_to = date_from_vn($date_to);
        }
        /*
        if ($date_to!='' && $date_from!=''){
            $year = 0;
        }
        */

        $data['date'] = $date;
        $data['year'] = $year;
        $data['unit'] = $unit;
        $data['address'] = $address;
        $data['open_date'] = $open_date;
        $data['director'] = $director;
        $data['chief_accountant'] = $chief_accountant;
        $data['creator'] = $creator;
        $data['date_from'] = $date_from;
        $data['date_to'] = $date_to;

        $this->load->model('report7888_model');

        //$tax1 = 0;
        /*
        for($type = 0; $type<=1; $type++){
            for($product_type = 0; $product_type<=3; $product_type++){
                $tax1 += $this->report7888_model->report7888_4_1($shop_id, $type, $product_type, $year, $date_from, $date_to);
            }
        }
        */
        $shop_type = $this->user->shop['type'];
        if ($shop_type != 11) {
            $tax1 = $this->report7888_model->gtgt_trong_ky($shop_id, $date_from, $date_to);
            $tax01 = $this->report7888_model->gtgt_phai_nop_dau_ky($shop_id, $date_from, $date_to);
        } else {
            $tax1 = $this->report7888_model->gtgt_trong_ky_pharm($shop_id, $date_from, $date_to);
            $tax01 = $this->report7888_model->gtgt_phai_nop_dau_ky_pharm($shop_id, $date_from, $date_to);
        }

        $tax2 = $this->report7888_model->nop_gtgt_trong_ky($shop_id, $date_from, $date_to);
        $tax02 = $this->report7888_model->gtgt_da_nop_dau_ky($shop_id, $date_from, $date_to);

        if (intval($tax01) < intval($tax02)) {
            $tax02 = intval($tax02) - intval($tax01);
            $tax01 = 0;
        } else {
            $tax01 = intval($tax01) - intval($tax02);
            $tax02 = 0;
        }

        $data['tax1'] = $tax1;
        $data['tax2'] = $tax2;

        $data['tax01'] = $tax01;
        $data['tax02'] = $tax02;

        $this->load->view('report7888_4', $data);
    }


    function report7888_42()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        //$this->output->enable_profiler(TRUE);
        $report = intval($this->input->get('report'));
        if ($report == 1) {
            $filename = "report7888_4.xls";
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Content-Type: application/vnd.ms-excel");
            header("Pragma: no-cache");
            header("Expires: 0");
        }
        if ($report == 2) {
            $filename = "report7888_4.doc";
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Content-Type: application/vnd.ms-word");
            header("Pragma: no-cache");
            header("Expires: 0");
        }


        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $data = array();

        $this->load->model('shop_model');
        $shop = $this->shop_model->get_shop($shop_id);
        $data['shop'] = $shop;

        $this->load->model('shop_detail_model');
        $shop_detail = $this->shop_detail_model->get_shop_detail($shop_id);
        $data['shop_detail'] = $shop_detail;


        $date = std($this->input->get('date'));
        $d = date('d', strtotime($date));
        $data['d'] = $d;
        $m = date('m', strtotime($date));
        $data['m'] = $m;
        $y = date('Y', strtotime($date));
        $data['y'] = $y;

        $year = intval($this->input->get('year'));
        $unit = std($this->input->get('unit'));
        $address = std($this->input->get('address'));
        $open_date = std($this->input->get('open_date'));
        $director = std($this->input->get('director'));
        $chief_accountant = std($this->input->get('chief_accountant'));
        $creator = std($this->input->get('creator'));


        $date_from = std($this->input->get('date_from'));
        if ($date_from != '') {
            $date_from = date_from_vn($date_from);
        }
        $date_to = std($this->input->get('date_to'));
        if ($date_to != '') {
            $date_to = date_from_vn($date_to);
        }
        /*
        if ($date_to!='' && $date_from!=''){
            $year = 0;
        }
        */

        $data['date'] = $date;
        $data['year'] = $year;
        $data['unit'] = $unit;
        $data['address'] = $address;
        $data['open_date'] = $open_date;
        $data['director'] = $director;
        $data['chief_accountant'] = $chief_accountant;
        $data['creator'] = $creator;
        $data['date_from'] = $date_from;
        $data['date_to'] = $date_to;

        $this->load->model('report7888_model');

        //$tax1 = 0;
        /*
        for($type = 0; $type<=1; $type++){
            for($product_type = 0; $product_type<=3; $product_type++){
                $tax1 += $this->report7888_model->report7888_4_1($shop_id, $type, $product_type, $year, $date_from, $date_to);
            }
        }
        */
        $shop_type = $this->user->shop['type'];
        if ($shop_type != 11) {
            $tax1 = $this->report7888_model->tncn_trong_ky($shop_id, $date_from, $date_to);
            $tax01 = $this->report7888_model->tncn_phai_nop_dau_ky($shop_id, $date_from, $date_to);
        } else {
            $tax1 = $this->report7888_model->tncn_trong_ky_pharm($shop_id, $date_from, $date_to);
            $tax01 = $this->report7888_model->tncn_phai_nop_dau_ky_pharm($shop_id, $date_from, $date_to);
        }

        $tax2 = $this->report7888_model->nop_tncn_trong_ky($shop_id, $date_from, $date_to);

        if (intval($tax01) < intval($tax02)) {
            $tax02 = intval($tax02) - intval($tax01);
            $tax01 = 0;
        } else {
            $tax01 = intval($tax01) - intval($tax02);
            $tax02 = 0;
        }

        /*
        for($type = 0; $type<=1; $type++){
            for($product_type = 0; $product_type<=3; $product_type++){
                $tax01 += $this->report7888_model->report7888_4_1_0($shop_id, $type, $product_type, $year, $date_from, $date_to);
            }
        }
        */

        $tax02 = $this->report7888_model->tncn_da_nop_dau_ky($shop_id, $date_from, $date_to);

        $data['tax1'] = $tax1;
        $data['tax2'] = $tax2;

        $data['tax01'] = $tax01;
        $data['tax02'] = $tax02;

        $this->load->view('report7888_42', $data);
    }

    function report7888_44()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        //$this->output->enable_profiler(TRUE);
        $report = intval($this->input->get('report'));
        if ($report == 1) {
            $filename = "report7888_4.xls";
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Content-Type: application/vnd.ms-excel");
            header("Pragma: no-cache");
            header("Expires: 0");
        }
        if ($report == 2) {
            $filename = "report7888_4.doc";
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Content-Type: application/vnd.ms-word");
            header("Pragma: no-cache");
            header("Expires: 0");
        }


        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $data = array();

        $this->load->model('shop_model');
        $shop = $this->shop_model->get_shop($shop_id);
        $data['shop'] = $shop;

        $this->load->model('shop_detail_model');
        $shop_detail = $this->shop_detail_model->get_shop_detail($shop_id);
        $data['shop_detail'] = $shop_detail;


        $date = std($this->input->get('date'));
        $d = date('d', strtotime($date));
        $data['d'] = $d;
        $m = date('m', strtotime($date));
        $data['m'] = $m;
        $y = date('Y', strtotime($date));
        $data['y'] = $y;

        $year = intval($this->input->get('year'));
        $unit = std($this->input->get('unit'));
        $address = std($this->input->get('address'));
        $open_date = std($this->input->get('open_date'));
        $director = std($this->input->get('director'));
        $chief_accountant = std($this->input->get('chief_accountant'));
        $creator = std($this->input->get('creator'));


        $date_from = std($this->input->get('date_from'));
        if ($date_from != '') {
            $date_from = date_from_vn($date_from);
        }
        $date_to = std($this->input->get('date_to'));
        if ($date_to != '') {
            $date_to = date_from_vn($date_to);
        }
        /*
        if ($date_to!='' && $date_from!=''){
            $year = 0;
        }
        */

        $data['date'] = $date;
        $data['year'] = $year;
        $data['unit'] = $unit;
        $data['address'] = $address;
        $data['open_date'] = $open_date;
        $data['director'] = $director;
        $data['chief_accountant'] = $chief_accountant;
        $data['creator'] = $creator;
        $data['date_from'] = $date_from;
        $data['date_to'] = $date_to;

        $this->load->model('report7888_model');

        //$tax1 = 0;
        /*
        for($type = 0; $type<=1; $type++){
            for($product_type = 0; $product_type<=3; $product_type++){
                $tax1 += $this->report7888_model->report7888_4_1($shop_id, $type, $product_type, $year, $date_from, $date_to);
            }
        }
        */
        $shop_type = $this->user->shop['type'];
        if ($shop_type != 11) {
            $tax1 = $this->report7888_model->tncn_trong_ky($shop_id, $date_from, $date_to);
            $tax01 = $this->report7888_model->tncn_phai_nop_dau_ky($shop_id, $date_from, $date_to);
        } else {
            $tax1 = $this->report7888_model->tncn_trong_ky_pharm($shop_id, $date_from, $date_to);
            $tax01 = $this->report7888_model->tncn_phai_nop_dau_ky_pharm($shop_id, $date_from, $date_to);
        }

        $tax2 = $this->report7888_model->nop_tncn_trong_ky($shop_id, $date_from, $date_to);

        if (intval($tax01) < intval($tax02)) {
            $tax02 = intval($tax02) - intval($tax01);
            $tax01 = 0;
        } else {
            $tax01 = intval($tax01) - intval($tax02);
            $tax02 = 0;
        }

        /*
        for($type = 0; $type<=1; $type++){
            for($product_type = 0; $product_type<=3; $product_type++){
                $tax01 += $this->report7888_model->report7888_4_1_0($shop_id, $type, $product_type, $year, $date_from, $date_to);
            }
        }
        */

        $tax02 = $this->report7888_model->tncn_da_nop_dau_ky($shop_id, $date_from, $date_to);

        $data['tax1'] = $tax1;
        $data['tax2'] = $tax2;

        $data['tax01'] = $tax01;
        $data['tax02'] = $tax02;

        $this->load->view('report7888_44', $data);
    }


    function report7888_8()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        //$this->output->enable_profiler(TRUE);
        $report = intval($this->input->get('report'));
        if ($report == 1) {
            $filename = "report7888_8.xls";
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Content-Type: application/vnd.ms-excel");
            header("Pragma: no-cache");
            header("Expires: 0");
        }
        if ($report == 2) {
            $filename = "report7888_8.doc";
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Content-Type: application/vnd.ms-word");
            header("Pragma: no-cache");
            header("Expires: 0");
        }

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $data = array();

        $this->load->model('shop_model');
        $shop = $this->shop_model->get_shop($shop_id);
        $data['shop'] = $shop;

        $this->load->model('shop_detail_model');
        $shop_detail = $this->shop_detail_model->get_shop_detail($shop_id);

        $data['shop_detail'] = $shop_detail;

        $date_from = std($this->input->get('date_from'));
        if ($date_from != '') {
            $date_from = date_from_vn($date_from);
        }
        $date_to = std($this->input->get('date_to'));
        if ($date_to != '') {
            $date_to = date_from_vn($date_to);
        }
        if ($date_to != '' && $date_from != '') {
            $year = 0;
        }

        $date = std($this->input->get('date'));
        $year = intval($this->input->get('year'));
        $unit = std($this->input->get('unit'));
        $address = std($this->input->get('address'));
        $open_date = std($this->input->get('open_date'));
        $director = std($this->input->get('director'));
        $chief_accountant = std($this->input->get('chief_accountant'));
        $creator = std($this->input->get('creator'));


        $data['date'] = $date;
        $d = date('d', strtotime($date));
        $data['d'] = $d;
        $m = date('m', strtotime($date));
        $data['m'] = $m;
        $y = date('Y', strtotime($date));
        $data['y'] = $y;
        $data['year'] = $year;
        $data['unit'] = $unit;
        $data['address'] = $address;
        $data['open_date'] = $open_date;
        $data['director'] = $director;
        $data['chief_accountant'] = $chief_accountant;
        $data['creator'] = $creator;
        $data['date_from'] = $date_from;
        $data['date_to'] = $date_to;

        //$this->load->model('order_model');
        //$orders = $this->order_model->filter_orders($shop_id, 'B', $date_from, $date_to);
        $this->load->model('report7888_model');

        $result = $this->report7888_model->report7888_8($shop_id, $date_from, $date_to);
        $data['result'] = $result;

        $this->load->view('report7888_8', $data);
    }


    function report7888_9()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        //$this->output->enable_profiler(TRUE);
        $report = intval($this->input->get('report'));
        if ($report == 1) {
            $filename = "report7888_2.xls";
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Content-Type: application/vnd.ms-excel");
            header("Pragma: no-cache");
            header("Expires: 0");
        }
        if ($report == 2) {
            $filename = "report7888_2.doc";
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Content-Type: application/vnd.ms-word");
            header("Pragma: no-cache");
            header("Expires: 0");
        }


        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $data = array();

        $this->load->model('shop_model');
        $shop = $this->shop_model->get_shop($shop_id);
        $data['shop'] = $shop;

        $this->load->model('shop_detail_model');
        $shop_detail = $this->shop_detail_model->get_shop_detail($shop_id);

        $data['shop_detail'] = $shop_detail;

        $date_from = std($this->input->get('date_from'));
        if ($date_from != '') {
            $date_from = date_from_vn($date_from);
        }
        $date_to = std($this->input->get('date_to'));
        if ($date_to != '') {
            $date_to = date_from_vn($date_to);
        }
        if ($date_to != '' && $date_from != '') {
            $year = 0;
        }

        $date = std($this->input->get('date'));
        $year = intval($this->input->get('year'));
        $unit = std($this->input->get('unit'));
        $address = std($this->input->get('address'));
        $open_date = std($this->input->get('open_date'));
        $director = std($this->input->get('director'));
        $chief_accountant = std($this->input->get('chief_accountant'));
        $creator = std($this->input->get('creator'));


        $data['date'] = $date;
        $d = date('d', strtotime($date));
        $data['d'] = $d;
        $m = date('m', strtotime($date));
        $data['m'] = $m;
        $y = date('Y', strtotime($date));
        $data['y'] = $y;
        $data['year'] = $year;
        $data['unit'] = $unit;
        $data['address'] = $address;
        $data['open_date'] = $open_date;
        $data['director'] = $director;
        $data['chief_accountant'] = $chief_accountant;
        $data['creator'] = $creator;
        $data['date_from'] = $date_from;
        $data['date_to'] = $date_to;

        //$this->load->model('order_model');
        //$orders = $this->order_model->filter_orders($shop_id, 'B', $date_from, $date_to);
        $this->load->model('report7888_model');

        $result = $this->report7888_model->report7888_9($shop_id, $date_from, $date_to);
        $data['result'] = $result;

        $this->load->view('report7888_9', $data);
    }

    function test2022()
    {
        error_reporting(-1);
        ini_set('display_errors', 1);

        //$auth =  minvoice_auth('5700197590','123456','https://5700197590.mobifoneinvoice.vn');
        $auth =  minvoice_auth('8175847867', '123456', 'https://8175847867.mobifoneinvoice.vn');
        $auth = json_decode($auth, true);

        echo (json_encode($auth));
        echo ('<br>');
        //return;
        if (isset($auth["error"])) {
            echo ($auth["error"]);
            return;
        } else {
        }
        $token = $auth['token'];
        $ma_dvcs = $auth['ma_dvcs'];
        $wb_user_id = $auth['wb_user_id'];

        //$token = 'VmhPdW9GLzFYN1Zzd1paaXgvQjFVN1BQOVdWWHBxQ3I4TElGdSt6STgxND06RElOSDo2Mzc3NjY5MTg1NTg0MjIxMzY6VlA=';
        //$ma_dvcs = 'VP';

        $result = minvoice_get('https://8175847867.mobifoneinvoice.vn', $token, $ma_dvcs);

        //$result = json_decode($result,true);

        echo ($result);
        return;


        //$data = '{  "editmode": 1,  "data": [    {        "invoice_issued_date": "2021-12-18 00:00",        "inv_invoicecode_id": "a0785bf8-bb52-4f82-b8cb-a754c36a811d",        "currency_code": "VND",        "exchange_rate": "1",        "payment_method_name": "Tiền mặt/Chuyển khoản",        "seller_bank_account": "",        "seller_bank_name": "",        "customer_code": "1",        "buyer_taxcode": "0100686209",        "buyer_display_name": "Nguyễn Văn A",        "buyer_email": "quyen.nguyenhuu@mobifone.vn",        "buyer_legal_name": "Công ty TNHH ABC",        "buyer_address_line": "Số 5 Duy Tân,Dịch Vọng Hậu, Cầu Giấy, Hà Nội",        "buyer_bank_account": "02903292001",        "buyer_tel": "TP Bank",        "buyer_bank_name": "0387368125",        "total_amount_without_vat": 150000,        "tgtcthue10": 0,        "tgtcthue5": 0,        "tgtcthue0": 150000,        "tgtcthuek": 0,        "tgtcthuekct": 0,        "tgtcthuekkk": 0,        "ttcktmai": 0,        "ttcktmai10": 0,        "ttcktmai5": 0,        "ttcktmai0": 0,        "ttcktmaik": 0,        "ttcktmaikct": 0,        "ttcktmaikkk": 0,        "vat_amount": 0,        "tgtthue10": 0,        "tgtthue5": 0,        "tgtthue0": 0,        "tgtthuek": 0,        "tgtthuekct": 0,        "tgtthuekkk": 0,        "total_amount": 150000,        "tgtttbso10": 0,        "tgtttbso5": 0,        "tgtttbso0": 150000,        "tgtttbsok": 0,        "tgtttbsokct": 0,        "tgtttbsokkk": 0,        "tkcktmn": 0,        "tgtphi": 0,        "total_amount_last": 150000,        "invoice_status": 0,        "details": [            {            "data": [                {                "row_ord": 1,                "item_code": "1",                "item_name": "Máy tính",                "unit_code": "CAI",                "unit_price": 150000,                "tax_type": "10",                "quantity": 1,                "total_amount_without_vat": 150000,                "discount_percentage": 0,                "discount_amount": 0,                "vat_amount": 0,                "total_amount": 150000                }            ]            }        ],        "hoadon68_phi": [            {            "data": []            }        ],        "is_hdcma": 0        }    ]}';
        //$data = json_decode($data, true);
        //$data['data']['inv_invoicecode_id'] = $result[0]['qlkhsdung_id'];
        //$data = json_encode($data);

        $data = array();
        $data['editmode'] = 1;
        $subdata = array();
        $subdata['invoice_issued_date'] = '2022-02-18 00:00';
        $subdata['inv_invoicecode_id'] = '04731096-364d-4870-9b98-543ed76ce645'; //$result['qlkhsdung_id'];
        $subdata['currency_code'] = 'VND';
        $subdata['exchange_rate'] = '1';
        $subdata['payment_method_name'] = 'Tiền mặt/Chuyển khoản';
        $subdata['seller_bank_account'] = '';
        $subdata['seller_bank_name'] = '';
        $subdata['customer_code'] = '1';
        $subdata['buyer_taxcode'] = '0100686209';
        $subdata['buyer_display_name'] = 'Nguyễn Văn A';

        $subdata['buyer_email'] = '';
        $subdata['buyer_legal_name'] = '';
        $subdata['buyer_address_line'] = '1';
        $subdata['buyer_bank_account'] = '02903292001';
        $subdata['buyer_tel'] = '0387368125';
        $subdata['buyer_bank_name'] = 'TP Bank';
        $subdata['total_amount_without_vat'] = 150000;
        $subdata['tgtcthue10'] = 0;
        $subdata['tgtcthue5'] = 0;
        $subdata['tgtcthue0'] = 150000;

        $subdata['tgtcthuek'] = 0;
        $subdata['tgtcthuekct'] = 0;
        $subdata['tgtcthuekkk'] = 0;
        $subdata['ttcktmai'] = 0;
        $subdata['ttcktmai10'] = 0;
        $subdata['ttcktmai5'] = 0;
        $subdata['ttcktmai0'] = 0;
        $subdata['ttcktmaik'] = 0;
        $subdata['ttcktmaikct'] = 0;
        $subdata['ttcktmaikkk'] = 0;
        $subdata['vat_amount'] = 0;
        $subdata['tgtthue10'] = 0;
        $subdata['tgtthue5'] = 0;

        $subdata['tgtthue0'] = 0;
        $subdata['tgtthuek'] = 0;
        $subdata['tgtthuekct'] = 0;
        $subdata['tgtthuekkk'] = 0;
        $subdata['total_amount'] = 150000;
        $subdata['tgtttbso10'] = 0;
        $subdata['tgtttbso5'] = 0;
        $subdata['tgtttbso0'] = 150000;
        $subdata['tgtttbsok'] = 0;
        $subdata['tgtttbsokct'] = 0;
        $subdata['tgtttbsokkk'] = 0;

        $subdata['tkcktmn'] = 0;
        $subdata['tgtphi'] = 0;
        $subdata['tgtttbsokkk'] = 0;
        $subdata['total_amount_last'] = 150000;
        $subdata['invoice_status'] = 0;

        $details = array();
        $detail0_data = array();
        $detail = array();

        $detail0_data_0 = array();
        $detail0_data_0['row_ord'] = 1;
        $detail0_data_0['item_code'] = '1';
        $detail0_data_0['item_name'] = 'Máy tính';
        $detail0_data_0['unit_code'] = 'CAI';
        $detail0_data_0['unit_price'] = 150000;
        $detail0_data_0['tax_type'] = "10";
        $detail0_data_0['quantity'] = 1;
        $detail0_data_0['total_amount_without_vat'] = 150000;
        $detail0_data_0['discount_percentage'] = 0;
        $detail0_data_0['discount_amount'] = 0;

        $detail0_data_0['vat_amount'] = 0;
        $detail0_data_0['total_amount'] = 150000;

        $detail0_data_1 = array();
        $detail0_data_1['row_ord'] = 2;
        $detail0_data_1['item_code'] = '2';
        $detail0_data_1['item_name'] = 'Máy tính1';
        $detail0_data_1['unit_code'] = 'CAI1';
        $detail0_data_1['unit_price'] = 150000;
        $detail0_data_1['tax_type'] = "10";
        $detail0_data_1['quantity'] = 1;
        $detail0_data_1['total_amount_without_vat'] = 150000;
        $detail0_data_1['discount_percentage'] = 0;
        $detail0_data_1['discount_amount'] = 0;
        $detail0_data_1['vat_amount'] = 0;
        $detail0_data_1['total_amount'] = 150000;

        $detail0_data[] = $detail0_data_0;
        $detail0_data[] = $detail0_data_1;

        $detail['data'] = $detail0_data;

        $details[] = $detail;

        $subdata['details'] = $details;

        $hoadon68_phi = array();
        $hoadon68_phi[] = array('data' => array());

        $subdata['hoadon68_phi'] = $hoadon68_phi;

        $subdata['is_hdcma'] = 0;

        $subdata2 = array();

        $subdata2[] = $subdata;

        $data['data'] = $subdata2;


        $data = json_encode($data);

        echo ($data);

        $result = minvoice_create('https://testkinhdoanh.mobifoneinvoice.vn', $token, $ma_dvcs, $data);
        echo ($result);
    }

    function test2023()
    {
        error_reporting(-1);
        ini_set('display_errors', 1);

        $data = '<?xml version="1.0" encoding="utf-8"?>
        <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
            <soap:Body>
                <ImportInvByPattern xmlns="http://tempuri.org/">
                    <xmlInvData>
                        <![CDATA[<Invoices><Inv><key>vnpt_594</key><Invoice><CusCode>0</CusCode><Buyer></Buyer><CusAddress></CusAddress><CusName></CusName><CusTaxCode></CusTaxCode><CusBankName></CusBankName><CusBankNo/><PaymentMethod>Tiền mặt</PaymentMethod><KindOfService>06/01/2023</KindOfService><Products><Product><ProdName>Ổi</ProdName><ProdUnit>kg</ProdUnit><ProdQuantity>1.00</ProdQuantity><ProdPrice>25000.00</ProdPrice><Total>25000.00</Total><Amount>25000.00</Amount></Product></Products><Total>25000.00</Total><DiscountAmount>0</DiscountAmount><VATAmount>0</VATAmount><Amount>25000.00</Amount><AmountInWords>Hai mươi lăm nghìn  đồng ./.</AmountInWords><PaymentStatus>1</PaymentStatus></Invoice></Inv></Invoices>]]>
                    </xmlInvData>
        <Account>5700145183_admin</Account>
        <ACpass>Einv@oi@vn#pt20</ACpass>
        <username>5700145183service</username>
        <password>Einv@oi@vn#pt20</password>
        <pattern>2/001</pattern>
        <serial>C23TAA</serial>
        <convert>0</convert>
        </ImportInvByPattern>
        </soap:Body>
        </soap:Envelope>';
        $result = vnpt_invoice_create0('https://5700145183-tt78cadmin.vnpt-invoice.com.vn/PublishService.asmx', $data);

        echo ($result);
        return;

        $xml = simplexml_load_string($result);
        $json = json_encode($xml);
        echo ($json);
    }

    function create_vnpt_invoice()
    {

        //error_reporting(-1);
        //ini_set('display_errors', 1);
        //$this->output->enable_profiler(TRUE);

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $order_id = intval($this->input->get('order_id'));

        $this->load->model('order_model');
        $order = $this->order_model->get_order($order_id, $shop_id);
        if (!$order) {

            return;
        }
        //return;
        $ebill = $order['bill_book'];

        if ($ebill != '') {
            $ebill = json_decode($ebill, true);
            if ($ebill != null && array_key_exists('vnpt', $ebill)) {
                $vnpt = $ebill['vnpt'];
                $result = array();
                $result['message'] = 'Tạo hóa đơn thành công mã số hóa đơn là vnpt__' . $order['id'];
                echo (json_encode($result));
                return;
            }
        }


        $invoice_issued_date = $this->input->get('invoice_issued_date');
        $buyer_taxcode = std($this->input->get('buyer_taxcode'));
        $buyer_display_name = std($this->input->get('buyer_display_name'));
        $buyer_unit = std($this->input->get('buyer_unit'));
        $buyer_address = std($this->input->get('buyer_address'));
        $buyer_phone = std($this->input->get('buyer_phone'));
        $buyer_id = std($this->input->get('buyer_id'));
        $serial = std($this->input->get('serial'));
        $pattern = std($this->input->get('pattern'));

        /*        
        $data = '<?xml version="1.0" encoding="utf-8"?><soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"> <soap:Body> <ImportInv xmlns="http://tempuri.org/"> <xmlInvData><![CDATA[xxxyyyzzz]]> </xmlInvData> <username>ntdungservice</username> <password>Einv@oi@vn#pt20</password> <convert>0</convert> </ImportInv> </soap:Body></soap:Envelope>';
        */

        $data = '<?xml version="1.0" encoding="utf-8"?>
        <soap:Envelope
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xmlns:xsd="http://www.w3.org/2001/XMLSchema"
            xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
            <soap:Body>
                <ImportInvByPattern
                    xmlns="http://tempuri.org/">
                    <xmlInvData>
                        <![CDATA[xxxyyyzzz]]>
                    </xmlInvData>
                    <Account>xxaccountxx</Account>
                    <ACpass>xxacpassxx</ACpass>
                    <username>ntdungservice</username>
                    <password>xxpasswordxx</password>
                    <pattern>xxpatternxx</pattern>
                    <serial>xxserialxx</serial>
                    <convert>0</convert>
                </ImportInvByPattern>
            </soap:Body>
        </soap:Envelope>';

        $inv = '<Invoices><Inv>[inv]</Inv></Invoices>';

        $this->load->model('bill_item_model');
        if (!$order['order_items']) {
            $items = $this->bill_item_model->get_order_items2($shop_id, $order_id);
        } else {
            $items = $this->bill_item_model->get_parent_order_detail2($shop_id, $order_id);
        }
        $products = '';
        foreach ($items as $item) {
            $products .= '<Product>';
            $products .= '<ProdName>' . str_replace('&', 'và', $item['product_name']) . '</ProdName>';
            $products .= '<ProdUnit>' . $item['unit_default'] . '</ProdUnit>';
            $products .= '<ProdQuantity>' . $item['quantity'] . '</ProdQuantity>';
            $products .= '<ProdPrice>' . $item['price'] . '</ProdPrice>';
            $products .= '<Total>' . $item['amount'] . '</Total>';
            $products .= '<Amount>' . $item['amount'] . '</Amount>';
            $products .= '</Product>';
        }

        $_inv = '<key>vnpt_' . $order['id'] . '</key>';
        $_inv .= '<Invoice>';
        $_inv .= '<CusCode>' . $order['customer_id'] . '</CusCode>';
        $_inv .= '<Buyer>' . str_replace('&', 'và', $buyer_display_name) . '</Buyer>';
        $_inv .= '<CusAddress>' . str_replace('&', 'và', $buyer_address) . '</CusAddress>';
        $_inv .= '<CusName>' . str_replace('&', 'và', $buyer_unit) . '</CusName>';
        $_inv .= '<CusPhone>' . $buyer_phone . '</CusPhone>';
        $_inv .= '<CusTaxCode>' . $buyer_taxcode . '</CusTaxCode>';
        $_inv .= '<CCCDan>' . $buyer_id . '</CCCDan>';
        $_inv .= '<CusBankName></CusBankName>';
        $_inv .= '<CusBankNo/>';

        if ($order['payment_type'] == 0) {
            $_inv .= '<PaymentMethod>Tiền mặt</PaymentMethod>';
        } else {
            $_inv .= '<PaymentMethod>Chuyển khoản</PaymentMethod>';
        }

        $_inv .= '<KindOfService>' . vn_date($invoice_issued_date) . '</KindOfService>';
        $_inv .= '<Products>' . $products . '</Products>';
        $_inv .= '<Total>' . $order['amount'] . '</Total>';
        $_inv .= '<DiscountAmount>0</DiscountAmount>';

        $_inv .= '<VATAmount>0</VATAmount>';
        $_inv .= '<Amount>' . $order['amount'] . '</Amount>';
        $_inv .= '<AmountInWords>' . spell_amount($order['amount']) . ' ./.</AmountInWords>';
        $_inv .= '<PaymentStatus>1</PaymentStatus>';
        $_inv .= '</Invoice>';

        $inv = str_replace('[inv]', $_inv, $inv);

        $data = str_replace('xxxyyyzzz', $inv, $data);

        //echo($data);

        $this->load->model('shop_ebill_model');
        $content = $this->shop_ebill_model->get_shop_ebill($shop_id, 'vnpt');
        //echo(json_encode($content));
        //return;
        if (!$content) {
            return;
        }
        $url = $content['url'];
        $account = $content['account'];
        $acpass = $content['acpass'];
        $username = $content['username'];
        $password = $content['password'];
        //$serial = $content['serial'];
        //$pattern = $content['pattern'];
        /*
        if ($serial == ''){
            $serial = 'C23TDN';
        }
        if ($pattern == ''){
            $pattern = '2/001';
        }
        */

        $data = str_replace('xxaccountxx', $account, $data);
        $data = str_replace('xxacpassxx', $acpass, $data);
        $data = str_replace('ntdungservice', $username, $data);
        $data = str_replace('xxpasswordxx', $password, $data);
        $data = str_replace('xxserialxx', $serial, $data);
        $data = str_replace('xxpatternxx', $pattern, $data);

        $this->load->model('invoice_log_model');
        $params = array();
        $params['shop_id'] = $shop_id;
        $params['order_id'] = $order_id;
        $params['data'] = $data;
        $this->invoice_log_model->add_invoice_log($params);


        //$result = vnpt_invoice_create('ntdungservice', 'Einv@oi@vn#pt20', $data);
        $result = vnpt_invoice_create2($url, $account, $acpass, $username, $password, $serial, $data);
        //echo($result);
        if (strpos($result, 'OK:2')) {
            $result = array();
            $result['message'] = 'Tạo hóa đơn thành công mã số hóa đơn là vnpt_' . $order['id'];

            //$params = array();
            //$params['bill_book'] = $id;

            $ebill = json_decode($order['bill_book'], true);

            $bill = array();
            $bill['id'] = 'vnpt_' . $order['id'];
            $bill['invoice_issued_date'] = $invoice_issued_date;
            $bill['buyer_taxcode'] = $buyer_taxcode;
            $bill['buyer_display_name'] = $buyer_display_name;
            $bill['buyer_unit'] = $buyer_unit;
            $bill['buyer_address'] = $buyer_address;
            $bill['buyer_phone'] = $buyer_phone;
            $bill['serial'] = $serial;
            $bill['buyer_id'] = $buyer_id;

            $bill['status'] = 0;

            $ebill['vnpt'] = $bill;

            $params = array();
            $params['bill_book'] = json_encode($ebill);

            $this->load->model('order_model');
            $this->order_model->update_order($order_id, $shop_id, $params);

            echo (json_encode($result));
        } else {
            if (strpos($result, 'ERR:1')) {
                $result = array();
                $result['message'] = 'Tài khoản đăng nhập sai hoặc không có quyền thêm mới hóa đơn';
                echo (json_encode($result));
                return;
            }
            if (strpos($result, 'ERR:20')) {
                $result = array();
                $result['message'] = 'Pattern và Serial không phù hợp, hoặc không
                tồn tại hóa đơn đã đăng kí có sử dụng Pattern và
                Serial truyền vào.';
                echo (json_encode($result));
                return;
            }
            $this->load->helper('ncc');
            $result1 = array();
            $result1['message'] = 'Lỗi ' . get_between($result, '<ImportInvByPatternResult>', '</ImportInvByPatternResult>');
            echo (json_encode($result1));
        }
    }


    function create_vnpt_invoice_mtt()
    {

        //error_reporting(-1);
        //ini_set('display_errors', 1);
        //$this->output->enable_profiler(TRUE);

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $order_id = intval($this->input->get('order_id'));

        $this->load->model('order_model');
        $order = $this->order_model->get_order($order_id, $shop_id);
        if (!$order) {

            return;
        }
        //return;
        $ebill = $order['bill_book'];

        if ($ebill != '') {
            $ebill = json_decode($ebill, true);
            if ($ebill != null && array_key_exists('vnpt', $ebill)) {
                $vnpt = $ebill['vnpt'];
                $result = array();
                $result['message'] = 'Tạo hóa đơn thành công mã số hóa đơn là vnpt__' . $order['id'];
                echo (json_encode($result));
                return;
            }
        }


        $invoice_issued_date = $this->input->get('invoice_issued_date');
        $buyer_taxcode = std($this->input->get('buyer_taxcode'));
        $buyer_display_name = std($this->input->get('buyer_display_name'));
        $buyer_unit = std($this->input->get('buyer_unit'));
        $buyer_address = std($this->input->get('buyer_address'));
        $buyer_phone = std($this->input->get('buyer_phone'));
        $buyer_id = std($this->input->get('buyer_id'));
        $serial = std($this->input->get('serial'));
        $pattern = std($this->input->get('pattern'));


        $data = '<?xml version="1.0" encoding="utf-8"?>
        <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
            <soap:Body>
                <ImportAndPublishInvMTT xmlns="http://tempuri.org/">
                    <Account>xxaccountxx</Account>
                    <ACpass>xxacpassxx</ACpass>
                    <xmlInvData>
                    <![CDATA[xxxyyyzzz]]>
        
        </xmlInvData>
        <username>xxusernamexx</username>
        <password>xxpasswordxx</password>
        <pattern>xxpatternxx</pattern>
        <serial>xxserialxx</serial>
        <convert>0</convert>
        </ImportAndPublishInvMTT>
        </soap:Body>
        </soap:Envelope>';

        $this->load->model('bill_item_model');
        if (!$order['order_items']) {
            $items = $this->bill_item_model->get_order_items2($shop_id, $order_id);
        } else {
            $items = $this->bill_item_model->get_parent_order_detail2($shop_id, $order_id);
        }
        $products = '';
        $stt = 1;
        foreach ($items as $item) {
            $products .= '<HHDVu>';
            $products .= '<TChat>1</TChat>';
            $products .= '<STT>' . $stt . '</STT>';
            $products .= '<MHHDVu></MHHDVu>';
            $products .= '<THHDVu>' . str_replace('&', 'và', $item['product_name']) . '</THHDVu>';
            $products .= '<DVTinh>' . $item['unit_default'] . '</DVTinh>';
            $products .= '<SLuong>' . $item['quantity'] . '</SLuong>';
            $products .= '<DGia>' . $item['price'] . '</DGia>';
            $products .= '<TLCKhau></TLCKhau>';
            $products .= '<STCKhau></STCKhau>';
            $products .= '<ThTien>' . $item['amount'] . '</ThTien>';
            $products .= '<TSuat>0</TSuat>';
            $products .= '<TThue>0</TThue>';
            $products .= '<TSThue>' . $item['amount'] . '</TSThue>';
            $products .= '<TTKhac><TTin><TTruong></TTruong><KDLieu></KDLieu><DLieu></DLieu></TTin></TTKhac>';
            $products .= '</HHDVu>';
            $stt++;
        }
        $_inv = '<DSHDon><HDon>';
        $_inv .= '<key>vnpt_' . $order['id'] . '</key>';
        $_inv .= '<DLHDon>';
        $_inv .= '<TTChung>';
        $_inv .= '<NLap>' . $invoice_issued_date . '</NLap>';
        $_inv .= '<DVTTe>VND</DVTTe>';
        $_inv .= '<TGia>1</TGia>';
        if ($order['payment_type'] == 0) {
            $_inv .= '<HTTToan>Tiền mặt</HTTToan>';
        } else {
            $_inv .= '<HTTToan>Chuyển khoản</HTTToan>';
        }
        $_inv .= '</TTChung>';
        $_inv .= '<NDHDon>';
        $_inv .= '<NMua>';
        $_inv .= '<Ten>' . str_replace('&', 'và', $buyer_display_name) . '</Ten>';
        $_inv .= '<MST>' . $buyer_taxcode . '</MST>';
        $_inv .= '<SDThoai>' . $buyer_phone . '</SDThoai>';
        $_inv .= '<CCCDan>' . $buyer_id . '</CCCDan>';
        $_inv .= '<DCTDTu></DCTDTu>';
        $_inv .= '</NMua>';
        $_inv .= '<DSHHDVu>';
        $_inv .= $products;
        $_inv .= '</DSHHDVu>';
        $_inv .= '<TToan>
                    <THTTLTSuat>
                        <LTSuat>
                            <TSuat>0</TSuat>
                            <TThue>0</TThue>
                            <ThTien>' . $order['amount'] . '</ThTien>
                        </LTSuat>
                    </THTTLTSuat>
                    <TgTCThue>' . $order['amount'] . '</TgTCThue>
                    <TgTThue>0</TgTThue>
                    <TTCKTMai></TTCKTMai>
                    <TgTTTBSo>' . $order['amount'] . '</TgTTTBSo>
                    <TgTTTBChu>' . spell_amount($order['amount']) . '</TgTTTBChu>
                </TToan>';
        $_inv .= '</NDHDon>';
        $_inv .= '</DLHDon>';
        $_inv .= '</HDon></DSHDon>';

        $inv = $_inv;

        $data = str_replace('xxxyyyzzz', $inv, $data);

        //echo($data);
        //return;

        $this->load->model('shop_ebill_model');
        $content = $this->shop_ebill_model->get_shop_ebill($shop_id, 'vnpt');
        //echo(json_encode($content));
        //return;
        if (!$content) {
            return;
        }
        $url = $content['url'];
        $account = $content['account'];
        $acpass = $content['acpass'];
        $username = $content['username'];
        $password = $content['password'];
        //$serial = $content['serial'];
        //$pattern = $content['pattern'];
        /*
        if ($serial == ''){
            $serial = 'C23TDN';
        }
        if ($pattern == ''){
            $pattern = '2/001';
        }
        */

        $data = str_replace('xxaccountxx', $account, $data);
        $data = str_replace('xxacpassxx', $acpass, $data);
        $data = str_replace('xxusernamexx', $username, $data);
        $data = str_replace('xxpasswordxx', $password, $data);
        $data = str_replace('xxserialxx', $serial, $data);
        $data = str_replace('xxpatternxx', $pattern, $data);

        $this->load->model('invoice_log_model');
        $params = array();
        $params['shop_id'] = $shop_id;
        $params['order_id'] = $order_id;
        $params['data'] = $data;
        $this->invoice_log_model->add_invoice_log($params);


        //$result = vnpt_invoice_create('ntdungservice', 'Einv@oi@vn#pt20', $data);
        $result = vnpt_invoice_create2($url, $account, $acpass, $username, $password, $serial, $data);
        //echo($result);
        if (strpos($result, 'OK:2')) {
            $result = array();
            $result['message'] = 'Tạo hóa đơn thành công mã số hóa đơn là vnpt_' . $order['id'];

            //$params = array();
            //$params['bill_book'] = $id;

            $ebill = json_decode($order['bill_book'], true);

            $bill = array();
            $bill['id'] = 'vnpt_' . $order['id'];
            $bill['invoice_issued_date'] = $invoice_issued_date;
            $bill['buyer_taxcode'] = $buyer_taxcode;
            $bill['buyer_display_name'] = $buyer_display_name;
            $bill['buyer_unit'] = $buyer_unit;
            $bill['buyer_address'] = $buyer_address;
            $bill['buyer_phone'] = $buyer_phone;
            $bill['buyer_id'] = $buyer_id;
            $bill['serial'] = $serial;

            $bill['status'] = 0;

            $ebill['vnpt'] = $bill;

            $params = array();
            $params['bill_book'] = json_encode($ebill);

            $this->load->model('order_model');
            $this->order_model->update_order($order_id, $shop_id, $params);

            echo (json_encode($result));
        } else {
            if (strpos($result, 'ERR:1')) {
                $result = array();
                $result['message'] = 'Tài khoản đăng nhập sai hoặc không có quyền thêm mới hóa đơn';
                echo (json_encode($result));
            }
            if (strpos($result, 'ERR:20')) {
                $result = array();
                $result['message'] = 'Pattern và Serial không phù hợp, hoặc không
                tồn tại hóa đơn đã đăng kí có sử dụng Pattern và
                Serial truyền vào.';
                echo (json_encode($result));
            }
        }
    }
    function create_minvoice()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);
        //$this->output->enable_profiler(TRUE);

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $order_id = intval($this->input->post('order_id'));

        $this->load->model('order_model');
        $order = $this->order_model->get_order($order_id, $shop_id);
        if (!$order) {
            return;
        }
        //return;
        $discount = $order['diners'];
        $ebill = $order['bill_book'];


        $invoice_issued_date = $this->input->post('invoice_issued_date');
        $buyer_taxcode = std($this->input->post('buyer_taxcode'));
        $buyer_display_name = std($this->input->post('buyer_display_name'));
        $buyer_unit = std($this->input->post('buyer_unit'));
        $buyer_address = std($this->input->post('buyer_address'));
        $qlkhsdung_id = std($this->input->post('qlkhsdung_id'));
        $serial = std($this->input->post('serial'));

        if ($ebill != '') {
            $ebill = json_decode($ebill, true);
            if (array_key_exists('minvoice', $ebill) && $ebill['minvoice']['id'] != null) {
                $minvoice_id = $ebill['minvoice']['id'];
                //$result = array();
                //$result['message'] = 'Tạo hóa đơn thành công mã số hóa đơn là ' . $minvoice['id'];
                $result = $this->minvoice_update($shop_id, $order_id, $minvoice_id, $invoice_issued_date, $buyer_taxcode, $buyer_display_name, $buyer_address, $buyer_unit, $discount, $qlkhsdung_id);
                if (!array_key_exists('Message', $result)) {
                    if (array_key_exists('error', $result)) {
                        $result['message'] = $result['error'];
                    } else {
                        $result['message'] = 'Sửa hóa đơn thành công mã số hóa đơn là ' . $minvoice_id;
                        $result['error'] = 0;
                    }
                } else {
                    $result['message'] = $result['Message'];
                    $result['error'] = 1;
                }
                echo (json_encode($result));
                return;
            }
        }

        /*
        $invoice_issued_date = $this->input->post('invoice_issued_date');
        $buyer_taxcode = std($this->input->post('buyer_taxcode'));
        $buyer_display_name = std($this->input->post('buyer_display_name'));
        $buyer_unit = std($this->input->post('buyer_unit'));
        $buyer_address = std($this->input->post('buyer_address'));
        */

        $result = $this->minvoice($shop_id, $order_id, $invoice_issued_date, $buyer_taxcode, $buyer_display_name, $buyer_address, $buyer_unit, $discount, $qlkhsdung_id);

        //echo(json_encode($result));
        //return;

        //$result = json_decode($result, true);

        $id = $result['data']['id'];
        $tthai = $result['data']['status'];
        $bill['status'] = $status;
        $params = array();
        //$params['bill_book'] = $id;


        $ebill = json_decode($order['bill_book'], true);

        $bill = array();
        $bill['id'] = $id;
        $bill['type'] = 'HDDT';
        $bill['tthai'] = $tthai;
        $bill['serial'] = $serial;
        $bill['invoice_issued_date'] = $invoice_issued_date;
        $bill['buyer_taxcode'] = $buyer_taxcode;
        $bill['buyer_display_name'] = $buyer_display_name;
        $bill['buyer_unit'] = $buyer_unit;
        $bill['buyer_address'] = $buyer_address;
        $bill['status'] = 0;

        $ebill['minvoice'] = $bill;

        $params = array();
        $params['bill_book'] = json_encode($ebill);


        $this->load->model('order_model');
        $this->order_model->update_order($order_id, $shop_id, $params);

        //$result = array();
        //$result['message'] = 'Tạo hóa đơn thành công mã số hóa đơn là ' . $id;
        echo (json_encode($result));
    }

    function minvoice_get()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $order_id = intval($this->input->get('order_id'));
        $this->load->model('order_model');
        $order = $this->order_model->get_order($order_id, $shop_id);
        if (!$order) {
            return;
        }
        $ebill = $order['bill_book'];

        if ($ebill == '') {
            $result = array();
            $result['invoice'] = 0;
            echo (json_encode($result));
            return;
        }
        $ebill = json_decode($ebill, true);
        if (!(array_key_exists('minvoice', $ebill) && $ebill['minvoice']['id'] != null)) {
            return;
        }


        if (array_key_exists('minvoice', $ebill) && $ebill['minvoice']['id'] != null) {
            $minvoice_id = $ebill['minvoice']['id'];
        }

        $this->load->model('shop_ebill_model');
        $content = $this->shop_ebill_model->get_shop_ebill($shop_id, 'minvoice');
        if (!$content) {
            $result = array();
            $result['message'] = 'Không đúng username/password không tạo được hóa đơn';
            //echo(json_encode($result));
            return $result;
        }

        $minvoice_username = $content['minvoice_username'];
        $minvoice_password = $content['minvoice_password'];
        $minvoice_url = $content['minvoice_url'];
        $auth =  minvoice_auth($minvoice_username, $minvoice_password, $minvoice_url);

        //echo($auth);

        $auth = json_decode($auth, true);

        //var_dump($auth);
        if (isset($auth["error"])) {
            $result = array();
            $result['invoice'] = 0;
            $result['message'] = $auth["error"];
            echo (json_encode($result));
            return;
        }
        $token = $auth['token'];
        $ma_dvcs = $auth['ma_dvcs'];
        $wb_user_id = $auth['wb_user_id'];

        $result = minvoice_get_invoice($minvoice_url, $token, $ma_dvcs, $minvoice_id);

        $result = json_decode($result, true);

        $bill = $ebill['minvoice'];
        $bill['tthai'] = $result['tthai'];
        $bill['mccqthue'] = $result['mccqthue'];
        $ebill['minvoice'] = $bill;

        $params = array();
        $params['bill_book'] = json_encode($ebill);


        $this->load->model('order_model');
        $this->order_model->update_order($order_id, $shop_id, $params);
        $data = array();
        $data['invoice'] = 1;
        $data['tthai'] = $result['tthai'];
        $data['mccqthue'] = $result['mccqthue'];
        $data['tthdon'] = $result['tthdon'];

        echo (json_encode($data));
    }

    function delete_minvoice()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $order_id = intval($this->input->post('order_id'));
        $this->load->model('order_model');
        $order = $this->order_model->get_order($order_id, $shop_id);
        if (!$order) {
            return;
        }
        $ebill = $order['bill_book'];

        if ($ebill != '') {
            $ebill = json_decode($ebill, true);
            if (array_key_exists('minvoice', $ebill) && $ebill['minvoice']['id'] != null) {
                $minvoice_id = $ebill['minvoice']['id'];

                $this->load->model('shop_ebill_model');
                $content = $this->shop_ebill_model->get_shop_ebill($shop_id, 'minvoice');
                if (!$content) {
                    $result = array();
                    $result['message'] = 'Không đúng username/password không xóa được hóa đơn';
                    //echo(json_encode($result));
                    return $result;
                }

                $minvoice_username = $content['minvoice_username'];
                $minvoice_password = $content['minvoice_password'];
                $minvoice_url = $content['minvoice_url'];

                $auth =  minvoice_auth($minvoice_username, $minvoice_password, $minvoice_url);
                $auth = json_decode($auth, true);

                //var_dump($auth);
                if (isset($auth["error"])) {
                    $result = array();
                    $result['message'] = $auth["error"];
                    //echo(json_encode($result));
                    return $result;
                }
                $token = $auth['token'];
                $ma_dvcs = $auth['ma_dvcs'];
                $wb_user_id = $auth['wb_user_id'];
                //xyxyxy
                $data = '{
                    "editmode": 3,
                    "data": [
                        {
                            "hdon_id": "' . $minvoice_id . '",
                        }
                    ]
                }';

                $result = minvoice_delete($minvoice_url, $token, $ma_dvcs, $data);
                echo ($result);

                $result = json_decode($result, true);
                if ($result['ok'] == 'true') {
                    $params = array();
                    $params['bill_book'] = '';
                    $this->order_model->update_order($order_id, $shop_id, $params);
                }
                return;
            }
        }
    }

    function create_minvoice2()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);
        //$this->output->enable_profiler(TRUE);

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $order_id = intval($this->input->post('order_id'));

        $this->load->model('order_model');
        $order = $this->order_model->get_order($order_id, $shop_id);
        if (!$order) {
            return;
        }
        //return;
        $discount = $order['diners'];
        $ebill = $order['bill_book'];

        $type = intval($this->input->post('type'));
        $invoice_issued_date = $this->input->post('invoice_issued_date');
        $buyer_taxcode = std($this->input->post('buyer_taxcode'));
        $buyer_display_name = std($this->input->post('buyer_display_name'));
        $buyer_unit = std($this->input->post('buyer_unit'));
        $buyer_address = std($this->input->post('buyer_address'));
        $buyer_phone = std($this->input->post('buyer_phone'));
        $buyer_id = std($this->input->post('buyer_id'));
        $qlkhsdung_id = std($this->input->post('qlkhsdung_id'));
        $serial = std($this->input->post('serial'));


        if ($ebill != '') {
            $ebill = json_decode($ebill, true);
            if (array_key_exists('minvoice', $ebill) && $ebill['minvoice']['id'] != null) {
                $minvoice_id = $ebill['minvoice']['id'];
                //$result = array();
                //$result['message'] = 'Tạo hóa đơn thành công mã số hóa đơn là ' . $minvoice['id'];
                $result = $this->minvoice_update2($shop_id, $order_id, $minvoice_id, $invoice_issued_date, $buyer_taxcode, $buyer_display_name, $buyer_address, $buyer_unit, $discount, $qlkhsdung_id);
                if (!array_key_exists('Message', $result)) {
                    $result['message'] = 'Sửa hóa đơn thành công mã số hóa đơn là ' . $minvoice_id;
                    /*
                    if (array_key_exists('error', $result)){
                        $result['message'] = 'Sửa hóa đơn không thành công 1';//$result['error'];
                    }
                    else{
                        $result['message'] = 'Sửa hóa đơn thành công mã số hóa đơn là ' . $minvoice_id;
                        
                    }
                    */
                } else {
                    $result['message'] = 'Sửa hóa đơn không thành công'; //$result['Message'];

                }
                echo (json_encode($result));
                return;
            }
        }


        /*
        $invoice_issued_date = $this->input->post('invoice_issued_date');
        $buyer_taxcode = std($this->input->post('buyer_taxcode'));
        $buyer_display_name = std($this->input->post('buyer_display_name'));
        $buyer_unit = std($this->input->post('buyer_unit'));
        $buyer_address = std($this->input->post('buyer_address'));
        */

        $result = $this->minvoice2($shop_id, $order_id, $invoice_issued_date, $buyer_taxcode, $buyer_display_name, $buyer_address, $buyer_unit, $buyer_phone, $buyer_id, $discount, $qlkhsdung_id, $type);

        //echo(json_encode($result));
        //return;

        //$result = json_decode($result, true);

        $id = $result['0']['data']['id'];
        $tthai = $result['0']['data']['tthai'];
        $mccqthue = $result['mccqthue'];

        $params = array();

        $ebill = json_decode($order['bill_book'], true);

        $bill = array();
        $bill['id'] = $id;
        $bill['type'] = 'MTT';
        $bill['tthai'] = $tthai;
        $bill['mccqthue'] = $mccqthue;
        $bill['serial'] = $serial;
        $bill['invoice_issued_date'] = $invoice_issued_date;
        $bill['buyer_taxcode'] = $buyer_taxcode;
        $bill['buyer_display_name'] = $buyer_display_name;
        $bill['buyer_unit'] = $buyer_unit;
        $bill['buyer_address'] = $buyer_address;
        $bill['buyer_phone'] = $buyer_phone;
        $bill['buyer_id'] = $buyer_id;
        $bill['status'] = 0;

        $ebill['minvoice'] = $bill;

        $params = array();
        $params['bill_book'] = json_encode($ebill);


        $this->load->model('order_model');
        $this->order_model->update_order($order_id, $shop_id, $params);

        //$result = array();
        //$result['message'] = 'Tạo hóa đơn thành công mã số hóa đơn là ' . $id;
        echo (json_encode($result));
    }

    function get_ebill_pdf()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);
        //$this->output->enable_profiler(TRUE);

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get('order_id'));
        $this->load->model('order_model');
        $order = $this->order_model->get_order($order_id, $shop_id);
        if (!$order) {
            return;
        }
        //return;
        $bill = $order['bill_book'];
        if ($bill == '') {
            return;
        }

        $bill = json_decode($bill, true);
        if (!array_key_exists('minvoice', $bill)) {
            return;
        }

        $bill = $bill['minvoice'];

        $bill_id = $bill['id'];

        /*
        $this->load->model('shop_model');
        $shop = $this->shop_model->get_shop($shop_id);

        $minvoice_username = $shop['minvoice_username'];
        $minvoice_password = $shop['minvoice_password'];
        $minvoice_branch_code = $shop['minvoice_branch_code'];
        */

        $this->load->model('shop_ebill_model');
        $content = $this->shop_ebill_model->get_shop_ebill($shop_id, 'minvoice');
        if (!$content) {
            $result = array();
            $result['message'] = 'Không đúng username/password không tạo được hóa đơn';
            $result['error'] = 1;
            //echo(json_encode($result));
            return $result;
        }

        $minvoice_username = $content['minvoice_username'];
        $minvoice_password = $content['minvoice_password'];
        $minvoice_url = $content['minvoice_url'];

        $auth =  minvoice_auth($minvoice_username, $minvoice_password, $minvoice_url);
        $auth = json_decode($auth, true);

        //var_dump($auth);
        if (isset($auth["error"])) {
            $result = array();
            $result['message'] = $auth["error"];
            //echo(json_encode($result));
            return $result;
        }
        $token = $auth['token'];
        $ma_dvcs = $auth['ma_dvcs'];
        header("Content-type:application/pdf");
        // It will be called downloaded.pdf
        //header("Content-Disposition:attachment;filename=downloaded.pdf");

        $content = minvoice_pdf($minvoice_url, $token, $ma_dvcs, $bill_id);
        echo ($content);
    }

    function minvoice_do_sign()
    {
        error_reporting(-1);
        ini_set('display_errors', 1);

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get('order_id'));

        $result = $this->minvoice_sign($shop_id, $order_id);
        echo (json_encode($result));
    }

    function minvoice_sign($shop_id, $order_id)
    {
        $this->load->model('order_model');
        $order = $this->order_model->get_order2($order_id, $shop_id);

        $bill = $order['bill_book'];

        if ($bill == '') {
            $result = array();
            $result['success'] = 0;
            $result['message'] = 'Phải tạo hóa đơn trước khi ký';
            return $result;
        }

        $bill = json_decode($bill, true);
        if (!$bill) {
            $result = array();
            $result['success'] = 0;
            $result['message'] = 'Phải tạo hóa đơn trước khi ký';
            return $result;
        }

        if (!array_key_exists('minvoice', $bill)) {
            $result = array();
            $result['success'] = 0;
            $result['message'] = 'Phải tạo hóa đơn trước khi ký';

            return $result;
        }

        $bill = $bill['minvoice'];

        $invoice_id = $bill['id'];

        $this->load->model('shop_ebill_model');
        $content = $this->shop_ebill_model->get_shop_ebill($shop_id, 'minvoice');
        if (!$content) {
            $result = array();
            $result['message'] = 'Không đúng username/password không tạo được hóa đơn';
            //echo(json_encode($result));
            return $result;
        }

        $minvoice_username = $content['minvoice_username'];
        $minvoice_password = $content['minvoice_password'];
        $minvoice_url = $content['minvoice_url'];

        $auth =  minvoice_auth($minvoice_username, $minvoice_password, $minvoice_url);
        $auth = json_decode($auth, true);

        //var_dump($auth);
        if (isset($auth["error"])) {
            $result = array();
            $result['message'] = $auth["error"];
            //echo(json_encode($result));
            return $result;
        }
        $token = $auth['token'];
        $ma_dvcs = $auth['ma_dvcs'];

        return minvoice_sign($minvoice_url, $invoice_id, $token, $ma_dvcs, $minvoice_username);
    }
    function minvoice_update($shop_id, $order_id, $invoice_id, $invoice_issued_date, $buyer_taxcode, $buyer_display_name, $buyer_address, $buyer_unit, $discount, $qlkhsdung_id)
    {
        $this->load->model('shop_model');
        $this->load->model('order_model');
        $this->load->model('bill_item_model');
        $order = $this->order_model->get_order2($order_id, $shop_id);
        $items = $this->bill_item_model->get_order_items2($shop_id, $order_id);
        $vat = $order['vat'];
        $vat_rate = $order['vat_rate'];

        /*
        if ($vat == 0){
            $total_amount_without_vat = $order['amount'];
            $vat_amount = 0;
        }
        else{
            $total_amount_without_vat = ($order['amount'] * 100) / (100+$vat_rate);
            $vat_amount = $total_amount_without_vat * $vat_rate / 100;
        }
        */

        /*
        $shop = $this->shop_model->get_shop($shop_id);
        */
        $this->load->model('shop_ebill_model');
        $content = $this->shop_ebill_model->get_shop_ebill($shop_id, 'minvoice');
        if (!$content) {
            $result = array();
            $result['message'] = 'Không đúng username/password không tạo được hóa đơn';
            //echo(json_encode($result));
            return $result;
        }

        $minvoice_username = $content['minvoice_username'];
        $minvoice_password = $content['minvoice_password'];
        $minvoice_url = $content['minvoice_url'];

        $auth =  minvoice_auth($minvoice_username, $minvoice_password, $minvoice_url);
        $auth = json_decode($auth, true);

        //var_dump($auth);
        if (isset($auth["error"])) {
            $result = array();
            $result['message'] = $auth["error"];
            //echo(json_encode($result));
            return $result;
        }
        $token = $auth['token'];
        $ma_dvcs = $auth['ma_dvcs'];
        $wb_user_id = $auth['wb_user_id'];

        //$token = 'VmhPdW9GLzFYN1Zzd1paaXgvQjFVN1BQOVdWWHBxQ3I4TElGdSt6STgxND06RElOSDo2Mzc3NjY5MTg1NTg0MjIxMzY6VlA=';
        //$ma_dvcs = 'VP';
        /*
        $result = minvoice_get($minvoice_url, $token, $ma_dvcs);
        if (isset($result['message'])){
            //echo(json_encode($result));
            return $result;
        }
        $qlkhsdung_id = '';
        $y = date('y');
        foreach($result as $row){
            $qlkhsdung_id = $row['qlkhsdung_id'];
            if (($row['khdon'] == 'T') && (str_contains($row['khhdon'], $y))){
                break;
            }
        }
        */

        $total_amount_without_vat = $order['amount'];
        $vat_amount = 0;

        $data = array();
        $data['editmode'] = 2;
        $subdata = array();
        $subdata['inv_invoiceauth_id'] = $invoice_id;
        $subdata['invoice_issued_date'] = $invoice_issued_date;
        $subdata['inv_invoicecode_id'] = $qlkhsdung_id;
        $subdata['currency_code'] = 'VND';
        $subdata['exchange_rate'] = '1';
        $subdata['payment_method_name'] = 'Tiền mặt/Chuyển khoản';
        $subdata['seller_bank_account'] = '';
        $subdata['seller_bank_name'] = '';
        $subdata['customer_code'] = $order['customer_id'];
        $subdata['buyer_taxcode'] = $buyer_taxcode;
        $subdata['buyer_display_name'] = $buyer_display_name;
        $subdata['buyer_legal_name'] = $buyer_unit;

        $subdata['buyer_email'] = '';
        $subdata['buyer_legal_name'] = $buyer_unit;
        $subdata['buyer_address_line'] = $buyer_address;
        $subdata['buyer_bank_account'] = '';
        $subdata['buyer_tel'] = '';
        $subdata['buyer_bank_name'] = '';
        $subdata['total_amount_without_vat'] = $total_amount_without_vat;
        $subdata['tgtcthue10'] = 0;
        $subdata['tgtcthue5'] = 0;
        $subdata['tgtcthue0'] = $order['amount'];

        $subdata['tgtcthuek'] = 0;
        $subdata['tgtcthuekct'] = 0;
        $subdata['tgtcthuekkk'] = 0;
        $subdata['ttcktmai'] = 0;
        $subdata['ttcktmai10'] = 0;
        $subdata['ttcktmai5'] = 0;
        $subdata['ttcktmai0'] = 0;
        $subdata['ttcktmaik'] = 0;
        $subdata['ttcktmaikct'] = 0;
        $subdata['ttcktmaikkk'] = 0;
        $subdata['vat_amount'] = $vat_amount;
        $subdata['tgtthue10'] = 0;
        $subdata['tgtthue5'] = 0;

        $subdata['tgtthue0'] = 0;
        $subdata['tgtthuek'] = 0;
        $subdata['tgtthuekct'] = 0;
        $subdata['tgtthuekkk'] = 0;
        $subdata['total_amount'] = $order['amount'];
        $subdata['tgtttbso10'] = 0;
        $subdata['tgtttbso5'] = 0;
        $subdata['tgtttbso0'] = $order['amount'];
        $subdata['tgtttbsok'] = 0;
        $subdata['tgtttbsokct'] = 0;
        $subdata['tgtttbsokkk'] = 0;

        $subdata['tkcktmn'] = 0;
        $subdata['tgtphi'] = 0;
        $subdata['tgtttbsokkk'] = 0;
        $subdata['total_amount_last'] = $order['amount'];
        $subdata['invoice_status'] = 0;
        $subdata['branch_code'] = 'VP';
        if ($discount == 1) {
            $subdata['giamthuebanhang20'] = 1;
            $subdata['tiletienthuegtgtgiam'] = 5;
            $subdata['tienthuegtgtgiam'] = $order['amount'] * 0.2 / 100;
            $subdata['total_amount_last'] = $order['amount'] - $order['amount'] * 0.2 / 100;
        }


        $details = array();
        $detail0_data = array();
        $detail = array();

        $detail0_data_0 = array();
        $i = 0;
        $total_amount_without_vat2 = 0;
        $vat_amount2 = 0;
        $discount_amount2 = 0;
        $discount_amount = 0;
        foreach ($items as $item) {
            $i++;
            /*
            if ($vat == 0){
                $total_amount_without_vat = $item['amount'];
                $vat_amount = 0;
            }
            else{
                $total_amount_without_vat = ($item['amount'] * 100) / (100+$vat_rate);
                $vat_amount = $total_amount_without_vat * $vat_rate / 100;
            }
            */
            /*
            $total_amount_without_vat = ($item['amount'] * 100) / (100+ $item['gtgt']);
            $total_amount_without_vat2 += $total_amount_without_vat;

            $vat_amount = $total_amount_without_vat * $item['gtgt'] / 100;
            $vat_amount2+=$vat_amount;
            */

            $total_amount_without_vat = $item['amount'] * (100 - $item['gtgt']) / 100;
            $total_amount_without_vat2 += $total_amount_without_vat;
            $vat_amount = $item['amount'] * $item['gtgt'] / 100;
            $vat_amount2 += $vat_amount;

            if ($discount == 1) {
                $discount_amount = $vat_amount * 20 / 100;
                $discount_amount2 += $discount_amount;
            }

            $detail0_data_0['row_ord'] = $i;
            $detail0_data_0['item_code'] = $item['id'];
            $detail0_data_0['item_name'] = std_invoice($item['product_name']);
            $detail0_data_0['unit_code'] = $item['unit_default'];
            $detail0_data_0['unit_price'] = $item['price'];
            $detail0_data_0['tax_type'] = "10";
            $detail0_data_0['quantity'] = $item['quantity'];
            $detail0_data_0['total_amount_without_vat'] = round($item['amount']);
            //$detail0_data_0['discount_percentage'] = 2;
            //$detail0_data_0['discount_amount'] = $discount_amount;
            //if ($discount == 1){
            $detail0_data_0['kmai'] = 1;
            //}
            $detail0_data_0['vat_amount'] = 0; //$vat_amount;
            $detail0_data_0['total_amount'] = round($item['amount']);

            $detail0_data[] = $detail0_data_0;
        }
        //$detail0_data[] = $detail0_data_0;
        //$detail0_data[] = $detail0_data_1;

        $subdata['total_amount_without_vat'] = $total_amount_without_vat2;
        $subdata['vat_amount'] = $vat_amount2;
        $subdata['tienthuegtgtgiam'] = $discount_amount2;
        $subdata['total_amount_last'] = round($order['amount'] - $discount_amount2);

        $detail['data'] = $detail0_data;

        $details[] = $detail;

        $subdata['details'] = $details;

        $hoadon68_phi = array();
        $hoadon68_phi[] = array('data' => array());

        $subdata['hoadon68_phi'] = $hoadon68_phi;

        $subdata['is_hdcma'] = 0;

        $subdata2 = array();

        $subdata2[] = $subdata;

        $data['data'] = $subdata2;


        $data = json_encode($data);

        //echo($data);
        //return;
        $this->load->model('invoice_log_model');
        $params = array();
        $params['shop_id'] = $shop_id;
        $params['order_id'] = $order_id;
        $params['data'] = $data;
        $this->invoice_log_model->add_invoice_log($params);


        $result = minvoice_create($minvoice_url, $token, $ma_dvcs, $data);
        $result = json_decode($result, true);
        $result['message'] = 'Tạo hóa đơn thành công, mã số hóa đơn là: ' . $result['data']['id'];
        return $result;
    }
    function get_qlkhsdung()
    {
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('shop_ebill_model');
        $content = $this->shop_ebill_model->get_shop_ebill($shop_id, 'minvoice');
        if (!$content) {
            $result = array();
            $result['message'] = 'Không đúng username/password không tạo được hóa đơn';
            //echo(json_encode($result));
            return $result;
        }

        $minvoice_username = $content['minvoice_username'];
        $minvoice_password = $content['minvoice_password'];
        $minvoice_url = $content['minvoice_url'];

        $auth =  minvoice_auth($minvoice_username, $minvoice_password, $minvoice_url);
        $auth = json_decode($auth, true);

        //var_dump($auth);
        if (isset($auth["error"])) {
            $result = array();
            $result['message'] = $auth["error"];
            //echo(json_encode($result));
            return $result;
        }
        $token = $auth['token'];
        $ma_dvcs = $auth['ma_dvcs'];
        $wb_user_id = $auth['wb_user_id'];

        //$token = 'VmhPdW9GLzFYN1Zzd1paaXgvQjFVN1BQOVdWWHBxQ3I4TElGdSt6STgxND06RElOSDo2Mzc3NjY5MTg1NTg0MjIxMzY6VlA=';
        //$ma_dvcs = 'VP';

        $result = minvoice_get($minvoice_url, $token, $ma_dvcs);
        $result = json_decode($result, true);
        if (isset($result['message'])) {
            //echo(json_encode($result));
            return $result;
        }

        $qlkhsdung = array();
        $y = date('y');
        foreach ($result as $row) {
            $a = array();
            $khhdon = $row['khhdon'];
            if (str_contains($khhdon, $y)) {
                $a['khhdon'] = $khhdon;
                $a['qlkhsdung_id'] = $row['qlkhsdung_id'];
                $a['khdon'] = $row['khdon'];
                $qlkhsdung[] = $a;
            }
        }
        echo (json_encode($qlkhsdung));
    }

    function minvoice($shop_id, $order_id, $invoice_issued_date, $buyer_taxcode, $buyer_display_name, $buyer_address, $buyer_unit, $discount, $qlkhsdung_id)
    {

        $this->load->model('shop_model');
        $this->load->model('order_model');
        $this->load->model('bill_item_model');
        $order = $this->order_model->get_order2($order_id, $shop_id);
        $items = $this->bill_item_model->get_order_items2($shop_id, $order_id);
        $vat = $order['vat'];
        $vat_rate = $order['vat_rate'];

        if ($vat == 0) {
            $total_amount_without_vat = $order['amount'];
            $vat_amount = 0;
        } else {
            $total_amount_without_vat = ($order['amount'] * 100) / (100 + $vat_rate);
            $vat_amount = $total_amount_without_vat * $vat_rate / 100;
        }

        /*
        $shop = $this->shop_model->get_shop($shop_id);
        */
        $this->load->model('shop_ebill_model');
        $content = $this->shop_ebill_model->get_shop_ebill($shop_id, 'minvoice');
        if (!$content) {
            $result = array();
            $result['message'] = 'Không đúng username/password không tạo được hóa đơn';
            //echo(json_encode($result));
            return $result;
        }

        $minvoice_username = $content['minvoice_username'];
        $minvoice_password = $content['minvoice_password'];
        $minvoice_url = $content['minvoice_url'];

        $auth =  minvoice_auth($minvoice_username, $minvoice_password, $minvoice_url);
        $auth = json_decode($auth, true);

        //var_dump($auth);
        if (isset($auth["error"])) {
            $result = array();
            $result['message'] = $auth["error"];
            //echo(json_encode($result));
            return $result;
        }
        $token = $auth['token'];
        $ma_dvcs = $auth['ma_dvcs'];
        $wb_user_id = $auth['wb_user_id'];

        //$token = 'VmhPdW9GLzFYN1Zzd1paaXgvQjFVN1BQOVdWWHBxQ3I4TElGdSt6STgxND06RElOSDo2Mzc3NjY5MTg1NTg0MjIxMzY6VlA=';
        //$ma_dvcs = 'VP';
        /*
        $result = minvoice_get($minvoice_url, $token, $ma_dvcs);
        if (isset($result['message'])){
            //echo(json_encode($result));
            return $result;
        }
        $result = json_decode($result, true);
        $qlkhsdung_id = '';
        $y = date('y');
        foreach($result as $row){
            $qlkhsdung_id = $row['qlkhsdung_id'];
            if (($row['khdon'] == 'T') && (str_contains($row['khhdon'], $y))){
                break;
            }
        }
        //$result = $result[0];
        */

        $data = array();
        $data['editmode'] = 1;
        $subdata = array();
        $subdata['invoice_issued_date'] = $invoice_issued_date;
        $subdata['inv_invoicecode_id'] = $qlkhsdung_id;
        $subdata['currency_code'] = 'VND';
        $subdata['exchange_rate'] = '1';
        $subdata['payment_method_name'] = 'Tiền mặt/Chuyển khoản';
        $subdata['seller_bank_account'] = '';
        $subdata['seller_bank_name'] = '';
        $subdata['customer_code'] = $order['customer_id'];
        $subdata['buyer_taxcode'] = $buyer_taxcode;
        $subdata['buyer_display_name'] = $buyer_display_name;

        $subdata['buyer_email'] = '';
        $subdata['buyer_legal_name'] = $buyer_unit;
        $subdata['buyer_address_line'] = $buyer_address;
        $subdata['buyer_bank_account'] = '';
        $subdata['buyer_tel'] = '';
        $subdata['buyer_bank_name'] = '';
        $subdata['total_amount_without_vat'] = $total_amount_without_vat;
        $subdata['tgtcthue10'] = 0;
        $subdata['tgtcthue5'] = 0;
        $subdata['tgtcthue0'] = $order['amount'];

        $subdata['tgtcthuek'] = 0;
        $subdata['tgtcthuekct'] = 0;
        $subdata['tgtcthuekkk'] = 0;
        $subdata['ttcktmai'] = 0;
        $subdata['ttcktmai10'] = 0;
        $subdata['ttcktmai5'] = 0;
        $subdata['ttcktmai0'] = 0;
        $subdata['ttcktmaik'] = 0;
        $subdata['ttcktmaikct'] = 0;
        $subdata['ttcktmaikkk'] = 0;
        $subdata['vat_amount'] = $vat_amount;
        $subdata['tgtthue10'] = 0;
        $subdata['tgtthue5'] = 0;

        $subdata['tgtthue0'] = 0;
        $subdata['tgtthuek'] = 0;
        $subdata['tgtthuekct'] = 0;
        $subdata['tgtthuekkk'] = 0;
        $subdata['total_amount'] = $order['amount'];
        $subdata['tgtttbso10'] = 0;
        $subdata['tgtttbso5'] = 0;
        $subdata['tgtttbso0'] = $order['amount'];
        $subdata['tgtttbsok'] = 0;
        $subdata['tgtttbsokct'] = 0;
        $subdata['tgtttbsokkk'] = 0;

        $subdata['tkcktmn'] = 0;
        $subdata['tgtphi'] = 0;
        $subdata['tgtttbsokkk'] = 0;
        $subdata['total_amount_last'] = $order['amount'];
        $subdata['invoice_status'] = 0;
        $subdata['branch_code'] = 'VP';

        if ($discount == 1) {
            $subdata['giamthuebanhang20'] = 1;
            $subdata['tiletienthuegtgtgiam'] = 5;
            $subdata['tienthuegtgtgiam'] = $order['amount'] * 0.2 / 100;
            $subdata['total_amount_last'] = $order['amount'] - $order['amount'] * 0.2 / 100;
        }


        $details = array();
        $detail0_data = array();
        $detail = array();

        $detail0_data_0 = array();
        $i = 0;
        $total_amount_without_vat2 = 0;
        $vat_amount2 = 0;
        $discount_amount2 = 0;
        $discount_amount = 0;
        foreach ($items as $item) {
            $i++;
            /*
            if ($vat == 0){
                $total_amount_without_vat = $item['amount'];
                $vat_amount = 0;
            }
            else{
                $total_amount_without_vat = ($item['amount'] * 100) / (100+$vat_rate);
                $vat_amount = $total_amount_without_vat * $vat_rate / 100;
            }
            */

            //$total_amount_without_vat = ($item['amount'] * 100) / (100+ $item['gtgt']);
            $total_amount_without_vat = $item['amount'] * (100 - $item['gtgt']) / 100;
            $total_amount_without_vat2 += $total_amount_without_vat;
            $vat_amount = $item['amount'] * $item['gtgt'] / 100;
            $vat_amount2 += $vat_amount;

            if ($discount == 1) {
                $discount_amount = $vat_amount * 20 / 100;
                $discount_amount2 += $discount_amount;
            }

            $detail0_data_0['row_ord'] = $i;
            $detail0_data_0['item_code'] = $item['id'];
            $detail0_data_0['item_name'] = std_invoice($item['product_name']);
            $detail0_data_0['unit_code'] = $item['unit_default'];
            $detail0_data_0['unit_price'] = $item['price'];
            $detail0_data_0['tax_type'] = "10";
            $detail0_data_0['quantity'] = $item['quantity'];
            $detail0_data_0['total_amount_without_vat'] = $item['amount'];
            /*
            $detail0_data_0['discount_percentage'] = 2;
            $detail0_data_0['discount_amount'] = $discount_amount;
            */
            //if ($discount == 1){
            $detail0_data_0['kmai'] = 1;
            //}
            $detail0_data_0['vat_amount'] = 0; //$vat_amount;
            $detail0_data_0['total_amount'] = $item['amount'];

            $detail0_data[] = $detail0_data_0;
        }
        //$detail0_data[] = $detail0_data_0;
        //$detail0_data[] = $detail0_data_1;

        //$subdata['total_amount_without_vat'] = $total_amount_without_vat2;
        //$subdata['vat_amount'] = $vat_amount2;
        //$subdata['tienthuegtgtgiam'] = $discount_amount2;
        //$subdata['total_amount_last'] = round($order['amount'] - $discount_amount);

        $detail['data'] = $detail0_data;

        $details[] = $detail;

        $subdata['details'] = $details;

        $hoadon68_phi = array();
        $hoadon68_phi[] = array('data' => array());

        $subdata['hoadon68_phi'] = $hoadon68_phi;

        $subdata['is_hdcma'] = 0;

        $subdata2 = array();

        $subdata2[] = $subdata;

        $data['data'] = $subdata2;


        $data = json_encode($data);

        $this->load->model('invoice_log_model');
        $params = array();
        $params['shop_id'] = $shop_id;
        $params['order_id'] = $order_id;
        $params['data'] = $data;
        $this->invoice_log_model->add_invoice_log($params);

        //echo($data);
        //return;

        $result = minvoice_create($minvoice_url, $token, $ma_dvcs, $data);
        $result = json_decode($result, true);
        if (!array_key_exists('Message', $result)) {
            if (array_key_exists('error', $result)) {
                $result['message'] = $result['error'];
                $result['error'] = 1;
            } else {
                $result['message'] = 'Tạo hóa đơn thành công, mã số hóa đơn là: ' . $result['data']['id'];
                $result['error'] = 0;
            }
        } else {
            $result['message'] = $result['Message'];
            $result['error'] = 1;
        }

        return $result;
    }


    function minvoice2($shop_id, $order_id, $invoice_issued_date, $buyer_taxcode, $buyer_display_name, $buyer_address, $buyer_unit, $buyer_phone, $buyer_id, $discount, $qlkhsdung_id, $type = 0)
    {
        $this->load->model('shop_model');
        $this->load->model('order_model');
        $this->load->model('bill_item_model');
        $order = $this->order_model->get_order2($order_id, $shop_id);
        $items = $this->bill_item_model->get_order_items2($shop_id, $order_id);

        $bill_items = $this->bill_item_model->get_order_bill_items($shop_id, $order_id);
        $discount_amount = 0;
        foreach ($bill_items as $item) {
            if ($order['order_type'] == 'B' && $order['diners'] != 0) {
                $tax = $item['gtgt'];
                $tax = $item['amount'] * $tax / 100;
                $discount_amount += $tax * 20 / 100;
            }
        }

        $vat = $order['vat'];
        $vat_rate = $order['vat_rate'];

        if ($vat == 0) {
            $total_amount_without_vat = $order['amount'];
            $vat_amount = 0;
        } else {
            $total_amount_without_vat = ($order['amount'] * 100) / (100 + $vat_rate);
            $vat_amount = $total_amount_without_vat * $vat_rate / 100;
        }

        /*
        $shop = $this->shop_model->get_shop($shop_id);
        */
        $this->load->model('shop_ebill_model');
        $content = $this->shop_ebill_model->get_shop_ebill($shop_id, 'minvoice');
        if (!$content) {
            $result = array();
            $result['message'] = 'Không đúng username/password không tạo được hóa đơn';
            //echo(json_encode($result));
            return $result;
        }

        $minvoice_username = $content['minvoice_username'];
        $minvoice_password = $content['minvoice_password'];
        $minvoice_url = $content['minvoice_url'];

        $auth =  minvoice_auth($minvoice_username, $minvoice_password, $minvoice_url);
        $auth = json_decode($auth, true);

        //var_dump($auth);
        if (isset($auth["error"])) {
            $result = array();
            $result['message'] = $auth["error"];
            //echo(json_encode($result));
            return $result;
        }
        $token = $auth['token'];
        $ma_dvcs = $auth['ma_dvcs'];
        $wb_user_id = $auth['wb_user_id'];

        //$token = 'VmhPdW9GLzFYN1Zzd1paaXgvQjFVN1BQOVdWWHBxQ3I4TElGdSt6STgxND06RElOSDo2Mzc3NjY5MTg1NTg0MjIxMzY6VlA=';
        //$ma_dvcs = 'VP';
        /*
        $result = minvoice_get($minvoice_url, $token, $ma_dvcs);
        if (isset($result['message'])){
            //echo(json_encode($result));
            return $result;
        }
        $result = json_decode($result, true);
        //$result = $result[0];

        $qlkhsdung_id = '';
        foreach($result as $row){
            $qlkhsdung_id = $row['qlkhsdung_id'];
            if ($row['khdon'] == 'M'){
                break;
            }
        }
        */

        $address = array();
        $address['ttruong']  = 'dchi';
        $address['kdlieu']  = 'string';
        $address['dlieu']  = $buyer_address;

        $unit = array();
        $unit['ttruong']  = 'tendonvi';
        $unit['kdlieu']  = 'string';
        $unit['dlieu']  = $buyer_unit;


        $a[] = $address;
        $a[] = $unit;

        $khac = array();
        $data3 = array();
        $data3['data'] = $a;
        $khac[] = $data3;


        $data = array();
        $data['editmode'] = 1;
        $subdata = array();
        $subdata['nlap'] = $invoice_issued_date;
        $subdata['cctbao_id'] = $qlkhsdung_id;
        $subdata['sdhang'] = $order_id;
        $subdata['lhdon'] = 2;
        $subdata['hddckptquan'] = 0;
        $subdata['dvtte'] = 'VND';
        $subdata['tgia'] = '1';
        $subdata['htttoan'] = 'Tiền mặt/Chuyển khoản';
        $subdata['stknban'] = '';
        $subdata['tnhban'] = '';
        $subdata['mnmua'] = $order['customer_id'];
        $subdata['mst'] = $buyer_taxcode;
        $subdata['tnmua'] = $buyer_display_name;
        $subdata['ten'] = $buyer_unit;
        //$subdata['tendonvi'] = $buyer_unit;
        $subdata['dchi'] = $buyer_address;

        $subdata['hoadon68_khac'] = $khac;

        $subdata['sdtnmua'] = $buyer_phone;
        $subdata['cmndmua'] = $buyer_id;
        $subdata['tgtcthue'] = $order['amount'];
        $subdata['tgtttbso'] = $order['amount'];
        $subdata['tgtttbso_last'] = $order['amount'];

        $subdata['mdvi'] = 'VP';
        $subdata['tthdon'] = 0;

        if ($discount == 1) {
            $subdata['giamthuebanhang20'] = 1;
            $subdata['tiletienthuegtgtgiam'] = 5;
            $subdata['tienthuegtgtgiam'] = $discount_amount; //$order['amount'] * 0.2/100;
            $subdata['tgtttbso_last'] = $order['amount'] - $discount_amount; // $order['amount'] * 0.2/100;
        }

        $subdata['is_hdcma'] = 1;

        $details = array();
        $detail0_data = array();
        $detail = array();

        $detail0_data_0 = array();
        $i = 0;
        $total_amount_without_vat2 = 0;
        $vat_amount2 = 0;
        $discount_amount2 = 0;
        $discount_amount = 0;
        foreach ($items as $item) {
            $i++;
            /*
            if ($vat == 0){
                $total_amount_without_vat = $item['amount'];
                $vat_amount = 0;
            }
            else{
                $total_amount_without_vat = ($item['amount'] * 100) / (100+$vat_rate);
                $vat_amount = $total_amount_without_vat * $vat_rate / 100;
            }
            */

            //$total_amount_without_vat = ($item['amount'] * 100) / (100+ $item['gtgt']);
            $total_amount_without_vat = $item['amount'] * (100 - $item['gtgt']) / 100;
            $total_amount_without_vat2 += $total_amount_without_vat;
            $vat_amount = $item['amount'] * $item['gtgt'] / 100;
            $vat_amount2 += $vat_amount;

            if ($discount == 1) {
                $discount_amount = $vat_amount * 20 / 100;
                $discount_amount2 += $discount_amount;
            }

            $detail0_data_0['stt'] = $i;
            $detail0_data_0['ma'] = $item['id'];
            $detail0_data_0['ten'] = std_invoice($item['product_name']);
            $detail0_data_0['mdvtinh'] = $item['unit_default'];
            $detail0_data_0['dgia'] = $item['price'];
            $detail0_data_0['sluong'] = $item['quantity'];
            $detail0_data_0['thtien'] = $item['amount'];
            $detail0_data_0['tlckhau'] = 0;
            $detail0_data_0['stckhau'] = 0;
            $detail0_data_0['tsuat'] = '';
            $detail0_data_0['tthue'] = 0;
            $detail0_data_0['tgtien'] = $item['amount'];
            $detail0_data_0['kmai'] = 1;

            $detail0_data[] = $detail0_data_0;
        }
        //$detail0_data[] = $detail0_data_0;
        //$detail0_data[] = $detail0_data_1;


        $detail['data'] = $detail0_data;

        $details[] = $detail;

        $subdata['details'] = $details;

        $subdata2 = array();

        $subdata2[] = $subdata;

        $data['data'] = $subdata2;


        $data = json_encode($data);

        $this->load->model('invoice_log_model');
        $params = array();
        $params['shop_id'] = $shop_id;
        $params['order_id'] = $order_id;
        $params['data'] = $data;
        $this->invoice_log_model->add_invoice_log($params);

        //echo($data);
        //return;

        $result = minvoice_create2($minvoice_url, $token, $ma_dvcs, $data, $type);
        $result = json_decode($result, true);
        if (!array_key_exists('Message', $result)) {
            if (array_key_exists('error', $result)) {
                $result['message'] = $result['error'];
                $result['error'] = 1;
            } else {
                /*
                $send = minvoice_send($minvoice_url, $token, $ma_dvcs, '', $result['0']['data']['id']);
                $send = json_decode($send, true);
                //if ($send['ok']){
                    $invoice = minvoice_get_invoice($minvoice_url, $token, $ma_dvcs, $result['0']['data']['id']);
                    $invoice = json_decode($invoice, true);
                    if (array_key_exists('mccqthue',$invoice)){
                        $result['message'] = 'Tạo hóa đơn thành công, mã số hóa đơn là: ' . $result['0']['data']['id'] . ', Mã cq thuế: ' . $invoice['mccqthue'];
                        $result['mccqthue'] = $invoice['mccqthue'];
                    }
                    else{
                        $result['message'] = 'Tạo hóa đơn thành công, mã số hóa đơn là: ' . $result['0']['data']['id'];
                        
                    }
                    
                //}
                */
                $result['message'] = 'Tạo hóa đơn thành công, mã số hóa đơn là: ' . $result['0']['data']['id'];
                $result['error'] = 0;
            }
        } else {
            $result['message'] = $result['Message'];
            $result['error'] = 1;
        }

        return $result;
    }


    function minvoice_update2($shop_id, $order_id, $invoice_id, $invoice_issued_date, $buyer_taxcode, $buyer_display_name, $buyer_address, $buyer_unit, $discount, $qlkhsdung_id)
    {
        $this->load->model('shop_model');
        $this->load->model('order_model');
        $this->load->model('bill_item_model');
        $order = $this->order_model->get_order2($order_id, $shop_id);
        $items = $this->bill_item_model->get_order_items2($shop_id, $order_id);
        $vat = $order['vat'];
        $vat_rate = $order['vat_rate'];

        $bill_items = $this->bill_item_model->get_order_bill_items($shop_id, $order_id);
        $discount_amount = 0;
        foreach ($bill_items as $item) {
            if ($order['order_type'] == 'B' && $order['diners'] != 0) {
                $tax = $item['gtgt'];
                $tax = $item['amount'] * $tax / 100;
                $discount_amount += $tax * 20 / 100;
            }
        }


        /*
        if ($vat == 0){
            $total_amount_without_vat = $order['amount'];
            $vat_amount = 0;
        }
        else{
            $total_amount_without_vat = ($order['amount'] * 100) / (100+$vat_rate);
            $vat_amount = $total_amount_without_vat * $vat_rate / 100;
        }
        */


        /*
        $shop = $this->shop_model->get_shop($shop_id);
        */
        $this->load->model('shop_ebill_model');
        $content = $this->shop_ebill_model->get_shop_ebill($shop_id, 'minvoice');
        if (!$content) {
            $result = array();
            $result['message'] = 'Không đúng username/password không tạo được hóa đơn';
            //echo(json_encode($result));
            return $result;
        }

        $minvoice_username = $content['minvoice_username'];
        $minvoice_password = $content['minvoice_password'];
        $minvoice_url = $content['minvoice_url'];

        $auth =  minvoice_auth($minvoice_username, $minvoice_password, $minvoice_url);
        $auth = json_decode($auth, true);

        //var_dump($auth);
        if (isset($auth["error"])) {
            $result = array();
            $result['message'] = $auth["error"];
            //echo(json_encode($result));
            return $result;
        }
        $token = $auth['token'];
        $ma_dvcs = $auth['ma_dvcs'];
        $wb_user_id = $auth['wb_user_id'];

        //$token = 'VmhPdW9GLzFYN1Zzd1paaXgvQjFVN1BQOVdWWHBxQ3I4TElGdSt6STgxND06RElOSDo2Mzc3NjY5MTg1NTg0MjIxMzY6VlA=';
        //$ma_dvcs = 'VP';
        /*
        $result = minvoice_get($minvoice_url, $token, $ma_dvcs);
        if (isset($result['message'])){
            //echo(json_encode($result));
            return $result;
        }
        $result = json_decode($result, true);
        //$result = $result[0];

        $qlkhsdung_id = '';
        foreach($result as $row){
            $qlkhsdung_id = $row['qlkhsdung_id'];
            if ($row['khdon'] == 'M'){
                break;
            }
        }
        */

        $address = array();
        $address['ttruong']  = 'dchi';
        $address['kdlieu']  = 'decimal';
        $address['dlieu']  = $buyer_address;

        $unit = array();
        $unit['ttruong']  = 'tendonvi';
        $unit['kdlieu']  = 'string';
        $unit['dlieu']  = $buyer_unit;


        $a[] = $address;
        $a[] = $unit;


        $khac = array();
        $data3 = array();

        $data3['data'] = $a;
        $khac[] = $data3;


        $data = array();
        $data['editmode'] = 2;
        $subdata = array();
        $subdata['nlap'] = $invoice_issued_date;

        $subdata['cctbao_id'] = $qlkhsdung_id;
        $subdata['hdon_id'] = $invoice_id;

        $subdata['sdhang'] = $order_id;
        $subdata['lhdon'] = 2;
        $subdata['hddckptquan'] = 0;
        $subdata['dvtte'] = 'VND';
        $subdata['tgia'] = '1';
        $subdata['htttoan'] = 'Tiền mặt/Chuyển khoản';
        $subdata['stknban'] = '';
        $subdata['tnhban'] = '';
        $subdata['mnmua'] = $order['customer_id'];
        $subdata['mst'] = $buyer_taxcode;
        $subdata['tnmua'] = $buyer_display_name;
        $subdata['ten'] = $buyer_unit;
        $subdata['tendonvi'] = $buyer_unit;
        $subdata['dchi'] = $buyer_address;

        $subdata['hoadon68_khac'] = $khac;

        $subdata['sdtnmua'] = $buyer_phone;
        $subdata['cmndmua'] = $buyer_id;
        $subdata['tgtcthue'] = $order['amount'];
        $subdata['tgtttbso'] = $order['amount'];
        $subdata['tgtttbso_last'] = $order['amount'];

        $subdata['mdvi'] = 'VP';
        $subdata['tthdon'] = 0;
        $subdata['is_hdcma'] = 1;
        if ($discount == 1) {
            $subdata['giamthuebanhang20'] = 1;
            $subdata['tiletienthuegtgtgiam'] = 5;
            $subdata['tienthuegtgtgiam'] = $discount_amount; //$order['amount'] * 0.2/100;
            $subdata['tgtttbso_last'] = $order['amount'] - $discount_amount; //$order['amount'] * 0.2/100;
        }


        $details = array();
        $detail0_data = array();
        $detail = array();

        $detail0_data_0 = array();
        $i = 0;
        $total_amount_without_vat2 = 0;
        $vat_amount2 = 0;
        $discount_amount2 = 0;
        $discount_amount = 0;
        foreach ($items as $item) {
            $i++;
            /*
            if ($vat == 0){
                $total_amount_without_vat = $item['amount'];
                $vat_amount = 0;
            }
            else{
                $total_amount_without_vat = ($item['amount'] * 100) / (100+$vat_rate);
                $vat_amount = $total_amount_without_vat * $vat_rate / 100;
            }
            */

            //$total_amount_without_vat = ($item['amount'] * 100) / (100+ $item['gtgt']);
            $total_amount_without_vat = $item['amount'] * (100 - $item['gtgt']) / 100;
            $total_amount_without_vat2 += $total_amount_without_vat;
            $vat_amount = $item['amount'] * $item['gtgt'] / 100;
            $vat_amount2 += $vat_amount;

            if ($discount == 1) {
                $discount_amount = $vat_amount * 20 / 100;
                $discount_amount2 += $discount_amount;
            }

            $detail0_data_0['stt'] = $i;
            $detail0_data_0['ma'] = $item['id'];
            $detail0_data_0['ten'] = std_invoice($item['product_name']);
            $detail0_data_0['mdvtinh'] = $item['unit_default'];
            $detail0_data_0['dgia'] = $item['price'];
            $detail0_data_0['sluong'] = $item['quantity'];
            $detail0_data_0['thtien'] = $item['amount'];
            $detail0_data_0['tlckhau'] = 0;
            $detail0_data_0['stckhau'] = 0;
            $detail0_data_0['tsuat'] = '';
            $detail0_data_0['tthue'] = 0;
            $detail0_data_0['tgtien'] = $item['amount'];
            $detail0_data_0['kmai'] = 1;

            $detail0_data[] = $detail0_data_0;
        }
        //$detail0_data[] = $detail0_data_0;
        //$detail0_data[] = $detail0_data_1;


        $detail['data'] = $detail0_data;

        $details[] = $detail;

        $subdata['details'] = $details;

        $subdata2 = array();

        $subdata2[] = $subdata;

        $data['data'] = $subdata2;


        $data = json_encode($data);

        $this->load->model('invoice_log_model');
        $params = array();
        $params['shop_id'] = $shop_id;
        $params['order_id'] = $order_id;
        $params['data'] = $data;
        $this->invoice_log_model->add_invoice_log($params);

        //echo($data);
        //return;

        $result = minvoice_create2($minvoice_url, $token, $ma_dvcs, $data, $type);
        $result = json_decode($result, true);
        if (!array_key_exists('Message', $result)) {
            if (array_key_exists('error', $result)) {
                $result['message'] = $result['error'];
                $result['error'] = 1;
            } else {
                /*
                $send = minvoice_send($minvoice_url, $token, $ma_dvcs, '', $result['0']['data']['id']);
                $send = json_decode($send, true);
                //if ($send['ok']){
                    $invoice = minvoice_get_invoice($minvoice_url, $token, $ma_dvcs, $result['0']['data']['id']);
                    $invoice = json_decode($invoice, true);
                    if (array_key_exists('mccqthue',$invoice)){
                        $result['message'] = 'Tạo hóa đơn thành công, mã số hóa đơn là: ' . $result['0']['data']['id'] . ', Mã cq thuế: ' . $invoice['mccqthue'];
                        $result['mccqthue'] = $invoice['mccqthue'];
                    }
                    else{
                        $result['message'] = 'Tạo hóa đơn thành công, mã số hóa đơn là: ' . $result['0']['data']['id'];
                        
                    }
                    
                //}
                */
                $result['message'] = 'Tạo hóa đơn thành công, mã số hóa đơn là: ' . $result['0']['data']['id'];
                $result['error'] = 0;
            }
        } else {
            $result['message'] = $result['Message'];
            $result['error'] = 1;
        }

        return $result;
    }
    public function tt40()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        if (empty($_POST)) {
            $data['title'] = 'Thông tư 40';
            $data['user'] = $this->user;
            $this->load->view('headers/html_header', $data);

            $this->load->model('shop_model');
            $shop = $this->shop_model->get_shop($shop_id);
            $tt40 = $shop['tt40'];
            if ($tt40 != '') {
                $tt40 = json_decode($tt40, true);
                $data['tt40'] = $tt40;
            }

            $this->load->view('tt40_form', $data);
            $this->load->view('headers/html_footer');
        } else {
            //do add
            //$this->output->enable_profiler(TRUE);
            $province = std($this->input->post("province"));
            $date = std($this->input->post("date"));

            $f001 = intval($this->input->post("001"));
            $f002 = intval($this->input->post("002"));
            $f003 = intval($this->input->post("003"));
            $f004 = intval($this->input->post("004"));
            $f005 = intval($this->input->post("005"));
            $f006 = intval($this->input->post("006"));

            $f01a = intval($this->input->post("01a"));
            $f01a1 = intval($this->input->post("01a1"));
            $f01b = intval($this->input->post("01b"));
            $f01b1 = intval($this->input->post("01b1"));
            $f01b2 = intval($this->input->post("01b2"));

            $f01c = intval($this->input->post("01c"));
            $f01c1 = intval($this->input->post("01c1"));
            $f01c2 = intval($this->input->post("01c2"));

            $f01d = intval($this->input->post("01d"));
            $f01d1 = std($this->input->post("01d1"));

            $f02 = intval($this->input->post("02"));
            $f03 = intval($this->input->post("03"));

            $f04 = std($this->input->post("04"));
            $f05 = std($this->input->post("05"));
            $f06 = std($this->input->post("06"));
            $f07 = std($this->input->post("07"));
            $f08 = std($this->input->post("08"));

            $f08a = intval($this->input->post("08a"));
            $f08b = std($this->input->post("08b"));

            $f09 = std($this->input->post("09"));
            $f09 = intval($f09);
            $f09a = intval($this->input->post("09a"));

            $f10 = std($this->input->post("10"));
            $f11a = std($this->input->post("11a"));
            $f11b = std($this->input->post("11b"));
            $f12 = std($this->input->post("12"));
            $f12a = intval($this->input->post("12a"));

            $f12b = std($this->input->post("12b"));
            $f12c = std($this->input->post("12c"));
            $f12c1 = std($this->input->post("12c1"));
            $f12d = std($this->input->post("12d"));
            $f12dd = std($this->input->post("12đ"));

            $f12e = intval($this->input->post("12e"));

            $f13 = std($this->input->post("13"));
            $f13a = std($this->input->post("13a"));
            $f13b = std($this->input->post("13b"));
            $f13c = std($this->input->post("13c"));
            $f13d = std($this->input->post("13d"));

            $f14 = std($this->input->post("14"));
            $f15 = std($this->input->post("15"));
            $f16 = std($this->input->post("16"));
            $f17 = std($this->input->post("17"));
            $f17a = std($this->input->post("17a"));


            $f18a = std($this->input->post("18a"));
            $f18b = std($this->input->post("18b"));
            $f18c = std($this->input->post("18c"));
            $f18c1 = std($this->input->post("18c1"));
            $f18c2 = std($this->input->post("18c2"));

            $f18d = std($this->input->post("18d"));
            $f18d1 = std($this->input->post("18d1"));
            $f18d2 = std($this->input->post("18d2"));

            $f18dd = std($this->input->post("18đ"));
            $f18dd1 = std($this->input->post("18đ1"));
            $f18dd2 = std($this->input->post("18đ2"));

            $f18e = std($this->input->post("18e"));
            $f18e1 = std($this->input->post("18e1"));
            $f18e2 = std($this->input->post("18e2"));

            $f18f = std($this->input->post("18f"));
            $f18f1 = std($this->input->post("18f1"));
            $f18f2 = std($this->input->post("18f2"));

            $f18g = std($this->input->post("18g"));
            $f18g1 = std($this->input->post("18g1"));
            $f18g2 = std($this->input->post("18g2"));
            $f18g3 = std($this->input->post("18g3"));
            $f18g4 = std($this->input->post("18g4"));

            $f18h = std($this->input->post("18h"));
            $f18h1 = std($this->input->post("18h1"));
            $f18h2 = std($this->input->post("18h2"));
            $f18h3 = std($this->input->post("18h3"));
            $f18h4 = std($this->input->post("18h4"));

            $f18i = std($this->input->post("18h"));
            $f18i1 = std($this->input->post("18i1"));
            $f18i2 = std($this->input->post("18i2"));

            $f18k = std($this->input->post("18k"));

            $f19 = std($this->input->post("19"));
            $f20 = std($this->input->post("20"));
            $f21 = std($this->input->post("21"));
            $f21a = std($this->input->post("21a"));

            $staff = std($this->input->post("staff"));
            $license = std($this->input->post("license"));

            $f22 = std($this->input->post("22"));
            $f23 = std($this->input->post("23"));
            $f24 = std($this->input->post("24"));
            $f25 = std($this->input->post("25"));
            $f26 = std($this->input->post("26"));
            $f27 = std($this->input->post("27"));

            $cct = std($this->input->post("cct"));
            $cct_name = std($this->input->post("cct_name"));

            $a = array();

            $a['province'] = $province;
            $a['date'] = $date;
            $a['f001'] = $f001;
            $a['f002'] = $f002;
            $a['f003'] = $f003;
            $a['f004'] = $f004;
            $a['f005'] = $f005;
            $a['f006'] = $f006;
            $a['f01a'] = $f01a;
            $a['f01a1'] = $f01a1;
            $a['f01b'] = $f01b;
            $a['f01b1'] = $f01b1;
            $a['f01b2'] = $f01b2;
            $a['f01c'] = $f01c;
            $a['f01c1'] = $f01c1;
            $a['f01c2'] = $f01c2;
            $a['f01d'] = $f01d;
            $a['f01d1'] = $f01d1;
            $a['f02'] = $f02;
            $a['f03'] = $f03;
            $a['f04'] = $f04;
            $a['f05'] = $f05;
            $a['f06'] = $f06;
            $a['f07'] = $f07;
            $a['f08'] = $f08;
            $a['f08a'] = $f08a;
            $a['f08b'] = $f08b;
            $a['f09'] = $f09;
            $a['f09a'] = $f09a;
            $a['f10'] = $f10;
            $a['f11a'] = $f11a;
            $a['f11b'] = $f11b;
            $a['f12'] = $f12;
            $a['f12a'] = $f12a;
            $a['f12b'] = $f12b;
            $a['f12c'] = $f12c;
            $a['f12c1'] = $f12c1;
            $a['f12d'] = $f12d;
            $a['f12dd'] = $f12dd;
            $a['f12e'] = $f12e;
            $a['f13'] = $f13;
            $a['f13a'] = $f13a;
            $a['f13b'] = $f13b;
            $a['f13c'] = $f13c;
            $a['f13d'] = $f13d;
            $a['f14'] = $f14;
            $a['f15'] = $f15;
            $a['f16'] = $f16;
            $a['f17'] = $f17;
            $a['f17a'] = $f17a;
            $a['f18a'] = $f18a;
            $a['f18b'] = $f18b;
            $a['f18c'] = $f18c;
            $a['f18c1'] = $f18c1;
            $a['f18c2'] = $f18c2;
            $a['f18d'] = $f18d;
            $a['f18d1'] = $f18d1;
            $a['f18d2'] = $f18d2;
            $a['f18dd'] = $f18dd;
            $a['f18dd1'] = $f18dd1;
            $a['f18dd2'] = $f18dd2;
            $a['f18e'] = $f18e;
            $a['f18e1'] = $f18e1;
            $a['f18e2'] = $f18e2;
            $a['f18f'] = $f18f;
            $a['f18f1'] = $f18f1;
            $a['f18f2'] = $f18f2;
            $a['f18g'] = $f18g;
            $a['f18g1'] = $f18g1;
            $a['f18g2'] = $f18g2;
            $a['f18g3'] = $f18g3;
            $a['f18g4'] = $f18g4;
            $a['f18h'] = $f18h;
            $a['f18h1'] = $f18h1;
            $a['f18h2'] = $f18h2;
            $a['f18h3'] = $f18h3;
            $a['f18h4'] = $f18h4;
            $a['f18i'] = $f18i;
            $a['f18i1'] = $f18i1;
            $a['f18i2'] = $f18i2;
            $a['f18k'] = $f18k;
            $a['f19'] = $f19;
            $a['f20'] = $f20;
            $a['f21'] = $f21;
            $a['f21a'] = $f21a;

            $a['staff'] = $staff;
            $a['license'] = $license;

            $a['f22'] = $f22;
            $a['f23'] = $f23;
            $a['f24'] = $f24;
            $a['f25'] = $f25;
            $a['f26'] = $f26;
            $a['f27'] = $f27;
            $a['cct'] = $cct;
            $a['cct_name'] = $cct_name;

            $params['tt40'] = json_encode($a);

            $this->load->model('shop_model');
            $this->shop_model->update_shop($shop_id, $params);

            $save = $this->input->post('update');
            $report = $this->input->post('update_and_report');
            $xml = $this->input->post('update_and_xml');
            $excel = $this->input->post('update_and_excel');
            $phuluc_excel = $this->input->post('update_and_phuluc');
            if ($save) {
                $data['title'] = 'Thông tư 40';
                $data['user'] = $this->user;
                $this->load->view('headers/html_header', $data);

                $this->load->model('shop_model');
                $shop = $this->shop_model->get_shop($shop_id);
                $tt40 = $shop['tt40'];

                if ($tt40 != '') {
                    $tt40 = json_decode($tt40, true);
                    $data['tt40'] = $tt40;
                }
                $data['save'] = 1;
                $this->load->view('tt40_form', $data);
                $this->load->view('headers/html_footer');
            }
            if ($report) {
                redirect('/tt40_report');
            }
            if ($xml) {
                redirect('/report88/tt40_xml');
            }
            if ($excel) {
                redirect('/tt40_report?excel=1');
            }
            if ($phuluc_excel) {
                redirect('/report88/phuluc_excel');
            }
        }
    }

    function tt40_report()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $excel = intval($this->input->get('excel'));
        if ($excel == 1) {
            header("Content-Disposition:attachment;filename=tt40.xls");
            header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        }

        $shop_id = $this->user->shop_id;

        if ($shop_id == 4915) {
            shell_exec('php /var/www/shop/index.php test2 tt40_report_4915');
            redirect('https://hokinhdoanh.online/files/report_tt40_4915.html');
            return;
        }
        if ($shop_id == 4274) {
            shell_exec('php /var/www/shop/index.php test2 tt40_report_4274');
            redirect('https://hokinhdoanh.online/files/report_tt40_4274.html');
            return;
        }


        $this->load->model('shop_model');
        $shop = $this->shop_model->get_shop($shop_id);
        $tt40 = $shop['tt40'];

        //$bank = $shop['bank'];
        //$bank_account_number = $shop['bank_account_number'];

        $data = array();

        //$data['bank'] = $bank;
        //$data['bank_account_number'] = $bank_account_number;

        if ($tt40 != '') {
            $tt40 = json_decode($tt40, true);
            $data['tt40'] = $tt40;
        } else {
            return;
        }

        $f01a = $tt40['f01a'];
        $f01b = $tt40['f01b'];
        $f01c = $tt40['f01c'];
        $f01d = $tt40['f01d'];

        $month_from = 0;
        $month_to = 0;


        if ($f01a == 1) {
            //$date_from = 
            $year = $tt40['f01a1'];
            $date_from = $year . '-01-01';
            $date_to = $year . '-12-31';
        }
        if ($f01b == 1) {
            $month = $tt40['f01b1'];
            $year = $tt40['f01b2'];
            $date_from = $year . '-' . $month . '-01';
            $date_to = date('Y-m-t', strtotime($date_from));
        }
        $q = 0;
        if ($f01c == 1) {
            $q = $tt40['f01c1'];
            $year = $tt40['f01c2'];

            if ($q == 1) {
                $date_from = $year . '-01-01';
                $date_to = $year . '-03-31';
                $month_from = 1;
                $month_to = 3;
            }
            if ($q == 2) {
                $date_from = $year . '-04-01';
                $date_to = $year . '-06-30';
                $month_from = 4;
                $month_to = 6;
            }
            if ($q == 3) {
                $date_from = $year . '-07-01';
                $date_to = $year . '-09-30';
                $month_from = 7;
                $month_to = 9;
            }
            if ($q == 4) {
                $date_from = $year . '-10-01';
                $date_to = $year . '-12-31';
                $month_from = 10;
                $month_to = 12;
            }
        }
        if ($f01d == 1) {
            $date_from = date_from_vn($tt40['f01d1']);
            $date_to = date('Y-m-d');
        }

        //echo($from_date);
        //echo('<br>');
        //echo($to_date);

        $this->load->model('report7888_model');
        if ($this->user->shop['type'] != 11) {
            $revenue1 = $this->report7888_model->tt40_revenue($shop_id, $date_from, $date_to, 0);
        } else {
            $revenue1 = $this->report7888_model->tt40_revenue_11($shop_id, $date_from, $date_to, 0);
        }
        $revenue2 = $this->report7888_model->tt40_revenue($shop_id, $date_from, $date_to, 1);
        $revenue3 = $this->report7888_model->tt40_revenue($shop_id, $date_from, $date_to, 2);
        $revenue4 = $this->report7888_model->tt40_revenue($shop_id, $date_from, $date_to, 3);

        if ($this->user->shop['type'] != 11) {
            $gtgt1 = $this->report7888_model->tt40_gtgt($shop_id, $date_from, $date_to, 0);
        } else {
            $gtgt1 = $this->report7888_model->tt40_gtgt_11($shop_id, $date_from, $date_to, 0);
        }
        $gtgt2 = $this->report7888_model->tt40_gtgt($shop_id, $date_from, $date_to, 1);
        $gtgt3 = $this->report7888_model->tt40_gtgt($shop_id, $date_from, $date_to, 2);
        $gtgt4 = $this->report7888_model->tt40_gtgt($shop_id, $date_from, $date_to, 3);

        if ($this->user->shop['type'] != 11) {
            $tncn1 = $this->report7888_model->tt40_tncn($shop_id, $date_from, $date_to, 0);
        } else {
            $tncn1 = $this->report7888_model->tt40_tncn_11($shop_id, $date_from, $date_to, 0);
        }
        $tncn2 = $this->report7888_model->tt40_tncn($shop_id, $date_from, $date_to, 1);
        $tncn3 = $this->report7888_model->tt40_tncn($shop_id, $date_from, $date_to, 2);
        $tncn4 = $this->report7888_model->tt40_tncn($shop_id, $date_from, $date_to, 3);


        $data['revenue1'] = $revenue1;
        $data['revenue2'] = $revenue2;
        $data['revenue3'] = $revenue3;
        $data['revenue4'] = $revenue4;

        $data['gtgt1'] = $gtgt1;
        $data['gtgt2'] = $gtgt2;
        $data['gtgt3'] = $gtgt3;
        $data['gtgt4'] = $gtgt4;

        $data['tncn1'] = $tncn1;
        $data['tncn2'] = $tncn2;
        $data['tncn3'] = $tncn3;
        $data['tncn4'] = $tncn4;

        $s_c_tax = $this->report7888_model->tt40_s_c_tax($shop_id, $date_from, $date_to);
        $data['s_c_tax'] = $s_c_tax;

        if ($this->user->shop['type'] != 11) {
            $product_stock = $this->report7888_model->tt40_product_stock($shop_id, $date_from, $date_to);
        } else {
            $product_stock = $this->report7888_model->tt40_product_stock_11($shop_id, $date_from, $date_to);
        }

        //echo(json_encode($product_stock));

        $data['product_stock'] = $product_stock;
        //$this->output->enable_profiler(TRUE);
        $cpql = $this->report7888_model->cpql($shop_id, $date_from, $date_to);
        $cpnc = $this->report7888_model->cpnc($shop_id, $date_from, $date_to);
        //echo('xxx' . $cpnc);


        $data['cpql'] = $cpql;


        $date = date_from_vn($tt40['date']);

        $d = date('d', strtotime($date));
        $m = date('m', strtotime($date));
        $y = date('Y', strtotime($date));

        $data['d'] = $d;
        $data['m'] = $m;
        $data['y'] = $y;
        $data['q'] = $q;

        $data['month_from'] = $month_from;
        $data['month_to'] = $month_to;

        $this->load->model('product_model');
        $pd106 = $this->product_model->pd106($shop_id);
        $pds = array();
        foreach ($pd106 as $pd) {
            foreach ($cpql as $cp) {
                if ($pd['id'] == $cp['product_id']) {
                    $pd['amount'] = $cp['amount'];
                }
            }
            if ($pd['id'] == 1001) {
                $pd['amount'] += $cpnc;
            }

            $pds[] = $pd;
        }

        $data['pd106'] = $pd106;
        $data['pds'] = $pds;
        if ($this->user->shop['type'] != 11) {
            $revenue15_1 = $this->report7888_model->nd15_revenue($shop_id, $date_from, $date_to, 0);
        } else {
            $revenue15_1 = $this->report7888_model->nd15_revenue_11($shop_id, $date_from, $date_to, 0);
        }
        $revenue15_2 = $this->report7888_model->nd15_revenue($shop_id, $date_from, $date_to, 1);
        $revenue15_3 = $this->report7888_model->nd15_revenue($shop_id, $date_from, $date_to, 2);
        $revenue15_4 = $this->report7888_model->nd15_revenue($shop_id, $date_from, $date_to, 3);

        if ($this->user->shop['type'] != 11) {
            $gtgt_deduct1 = $this->report7888_model->tt40_gtgt_deduct($shop_id, $date_from, $date_to, 0);
        } else {
            $gtgt_deduct1 = $this->report7888_model->tt40_gtgt_deduct_11($shop_id, $date_from, $date_to, 0);
        }
        $gtgt_deduct2 = $this->report7888_model->tt40_gtgt_deduct($shop_id, $date_from, $date_to, 1);
        $gtgt_deduct3 = $this->report7888_model->tt40_gtgt_deduct($shop_id, $date_from, $date_to, 2);
        $gtgt_deduct4 = $this->report7888_model->tt40_gtgt_deduct($shop_id, $date_from, $date_to, 3);

        $data['revenue15_1'] = $revenue15_1;
        $data['revenue15_2'] = $revenue15_2;
        $data['revenue15_3'] = $revenue15_3;
        $data['revenue15_4'] = $revenue15_4;

        $data['gtgt_deduct1'] = $gtgt_deduct1;
        $data['gtgt_deduct2'] = $gtgt_deduct2;
        $data['gtgt_deduct3'] = $gtgt_deduct3;
        $data['gtgt_deduct4'] = $gtgt_deduct4;


        $this->load->view('tt40', $data);
    }

    public function nd15()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        if (empty($_POST)) {
            $data['title'] = 'Nghị định 15';
            $data['user'] = $this->user;
            $this->load->view('headers/html_header', $data);

            $this->load->model('shop_model');
            $shop = $this->shop_model->get_shop($shop_id);
            $nd15 = $shop['nd15'];
            if ($nd15 != '') {
                $nd15 = json_decode($nd15, true);
                $data['nd15'] = $nd15;
            }

            $this->load->view('nd15_form', $data);
            $this->load->view('headers/html_footer');
        } else {

            $f01a = intval($this->input->post("01a"));
            $f01a1 = intval($this->input->post("01a1"));
            $f01b = intval($this->input->post("01b"));
            $f01b1 = intval($this->input->post("01b1"));
            $f01b2 = intval($this->input->post("01b2"));

            $f01c = intval($this->input->post("01c"));
            $f01c1 = intval($this->input->post("01c1"));
            $f01c2 = intval($this->input->post("01c2"));

            $f01d = intval($this->input->post("01d"));
            $f01d1 = std($this->input->post("01d1"));

            //do add
            //$this->output->enable_profiler(TRUE);
            $f00 = std($this->input->post("00"));
            $f01 = std($this->input->post("01"));
            $f02 = std($this->input->post("02"));
            $f03 = std($this->input->post("03"));
            $f04 = std($this->input->post("04"));

            $province = std($this->input->post("province"));
            $staff = std($this->input->post("staff"));
            $license = std($this->input->post("license"));

            $a = array();

            $a['f01a'] = $f01a;
            $a['f01a1'] = $f01a1;
            $a['f01b'] = $f01b;
            $a['f01b1'] = $f01b1;
            $a['f01b2'] = $f01b2;
            $a['f01c'] = $f01c;
            $a['f01c1'] = $f01c1;
            $a['f01c2'] = $f01c2;
            $a['f01d'] = $f01d;
            $a['f01d1'] = $f01d1;

            $a['f00'] = $f00;
            $a['f01'] = $f01;
            $a['f02'] = $f02;
            $a['f03'] = $f03;
            $a['f04'] = $f04;

            $a['province'] = $province;
            $a['staff'] = $staff;
            $a['license'] = $license;
            $params['nd15'] = json_encode($a);

            $this->load->model('shop_model');
            $this->shop_model->update_shop($shop_id, $params);

            $save = $this->input->post('update');
            $report = $this->input->post('update_and_report');
            $excel = $this->input->post('update_and_excel');
            if ($save) {
                $data['title'] = 'Thông tư 40';
                $data['user'] = $this->user;
                $this->load->view('headers/html_header', $data);

                $this->load->model('shop_model');
                $shop = $this->shop_model->get_shop($shop_id);
                $nd15 = $shop['nd15'];
                if ($nd15 != '') {
                    $nd15 = json_decode($nd15, true);
                    $data['nd15'] = $nd15;
                }
                $data['save'] = 1;
                $this->load->view('nd15_form', $data);
                $this->load->view('headers/html_footer');
            }
            if ($report) {
                redirect('/nd15_report');
            }
            if ($excel) {
                redirect('/nd15_report?excel=1');
            }
        }
    }

    function nd15_report()
    {
        //$this->output->enable_profiler(TRUE);

        if ($this->check_user() === false) {
            $this->login();
            return;
        }

        $excel = intval($this->input->get('excel'));
        if ($excel == 1) {
            header("Content-Disposition:attachment;filename=nd15.xls");
            header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        }

        $shop_id = $this->user->shop_id;

        $this->load->model('shop_model');
        $shop = $this->shop_model->get_shop($shop_id);
        $nd15 = $shop['nd15'];
        $data = array();

        if ($nd15 != '') {
            $nd15 = json_decode($nd15, true);
            $data['nd15'] = $nd15;
        } else {
            return;
        }

        $f00 = $nd15['f00'];
        $f01 = $nd15['f01'];
        $f02 = $nd15['f02'];
        $f03 = $nd15['f03'];
        $f04 = $nd15['f04'];

        $date = date_from_vn($f00);
        $d = date('d', strtotime($date));
        $m = date('m', strtotime($date));
        $y = date('Y', strtotime($date));

        $data['nd15'] = $nd15;
        $data['d'] = $d;
        $data['m'] = $m;
        $data['y'] = $y;

        $f01a = $nd15['f01a'];
        $f01b = $nd15['f01b'];
        $f01c = $nd15['f01c'];
        $f01d = $nd15['f01d'];

        if ($f01a == 1) {
            //$date_from = 
            $year = $nd15['f01a1'];
            $date_from = $year . '-01-01';
            $date_to = $year . '-12-31';
        }
        if ($f01b == 1) {
            $month = $nd15['f01b1'];
            $year = $nd15['f01b2'];
            $date_from = $year . '-' . $month . '-01';
            $date_to = date('Y-m-t', strtotime($date_from));
        }
        if ($f01c == 1) {
            $q = $nd15['f01c1'];
            $year = $nd15['f01c2'];
            if ($q == 1) {
                $date_from = $year . '-01-01';
                $date_to = $year . '-03-31';
            }
            if ($q == 2) {
                $date_from = $year . '-04-01';
                $date_to = $year . '-06-30';
            }
            if ($q == 3) {
                $date_from = $year . '-07-01';
                $date_to = $year . '-09-30';
            }
            if ($q == 4) {
                $date_from = $year . '-10-01';
                $date_to = $year . '-12-31';
            }
        }
        if ($f01d == 1) {
            $date_from = date_from_vn($nd15['f01d1']);
            $date_to = date('Y-m-d');
        }

        /*
        echo($date_from);
        echo('<br>');
        echo($date_to);
        */


        $this->load->model('report7888_model');
        $orders = $this->report7888_model->nd15($shop_id, $date_from, $date_to);
        $data['orders'] = $orders;

        //echo(json_encode($orders));

        $this->load->view('nd15', $data);
    }


    function update_diners()
    {
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get('order_id'));
        $diners = intval($this->input->get('diners'));

        $this->load->model('order_model');
        $order = $this->order_model->get_order($order_id, $shop_id);
        if (!$order) {
            return;
        }
        $params = array();
        $params['diners'] = $diners;
        $this->order_model->update_order0($order_id, $shop_id, $params);
    }

    function business()
    {
        error_reporting(-1);
        ini_set('display_errors', 1);
        header('Content-Type: text/html; charset=utf-8');

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $this->load->model('business_model');

        $business = $this->business_model->get_all_business();

        $data = array();
        $data['business'] = $business;
        $this->load->view('business', $data);
    }

    function vn()
    {
        error_reporting(-1);
        ini_set('display_errors', 1);


        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;

        $this->load->model('vn_model');

        $vn = $this->vn_model->get_all_vn();

        $data = array();
        $data['vn'] = $vn;
        $this->load->view('vn', $data);
    }

    function test_product()
    {
        error_reporting(-1);
        ini_set('display_errors', 1);

        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $url = $this->config->base_url();
        $this->load->model('product_model');
        $this->load->model('product_group_model');
        $product_groups = $this->product_group_model->get_all_product_groups($shop_id);
        $id = intval($this->input->get("id"));
        $data = array();
        $data["url"] = $this->config->base_url();
        $path = FCPATH;
        $path = $path . 'img/0/';
        $files = scandir($path);
        $data["files"] = $files;

        $data['title'] = 'Thêm sản phẩm';
        $data['user'] = $this->user;
        $this->load->view('headers/html_header', $data);
        $data['product_groups'] = $product_groups;
        $data["type"] = 0;
        $data["id"] = 0;
        $data['url'] = $url;
        $this->load->view('product_add', $data);
        $this->load->view('headers/html_footer');
    }

    function cct()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $code = $this->input->get('code');
        $this->load->model('cct_model');

        $cct = $this->cct_model->search_cct($code);

        $data = array();
        $data['cct'] = $cct;
        $this->load->view('cct', $data);
        //echo(json_encode($cct, JSON_UNESCAPED_UNICODE ));
    }

    function delete_payroll()
    {
        /*
		error_reporting(-1);
		ini_set('display_errors', 1);

        $this->output->enable_profiler(TRUE);
        */
        if ($this->check_user() === false) {
            $this->login();
            return;
        }
        $shop_id = $this->user->shop_id;
        $id = intval($this->input->post("id"));
        $params = array();
        $params['status1'] = 100;
        $this->load->model('order_model');
        $this->order_model->update_order0($id, $shop_id, $params);
    }

    function product_units()
    {

        //error_reporting(-1);
        //ini_set('display_errors', 1);
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            redirect('/login');
            return;
        }
        $shop_id = $this->user->shop_id;
        $product_id = intval($this->input->get('product_id'));
        $this->load->model('product_unit_model');
        $units = $this->product_unit_model->get_product_units($shop_id, $product_id);
        $data = array();
        $data['units'] = $units;
        $this->load->view('product_units', $data);
    }

    function add_product_unit()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            redirect('/login');
            return;
        }
        $shop_id = $this->user->shop_id;
        $product_id = intval($this->input->get('product_id'));
        $unit_name = std($this->input->get('unit_name'));
        $quantity = floatval($this->input->get('quantity'));
        $list_price = floatval($this->input->get('list_price'));

        $params = array();
        $params['product_id'] = $product_id;
        $params['unit_name'] = $unit_name;
        $params['quantity'] = $quantity;
        $params['list_price'] = $list_price;

        $this->load->model('product_unit_model');
        $this->product_unit_model->add_product_unit($shop_id, $params);
    }
    //toandk2 thêm 
    function get_product_units()
    {
        if ($this->check_user() === false) {
            redirect('/login');
            return;
        }
        $shop_id = $this->user->shop_id;
        $product_id = intval($this->input->get('product_id'));
        $this->load->model('product_unit_model');
        $units = $this->product_unit_model->get_product_units($shop_id, $product_id);
        echo (json_encode($units));
    }

    function update_product_unit()
    {
        if ($this->check_user() === false) {
            redirect('/login');
            return;
        }
        $shop_id = $this->user->shop_id;
        $product_id = intval($this->input->get('product_id'));
        $unit_id = intval($this->input->get('unit_id'));
        $unit_name = std($this->input->get('unit_name'));
        $quantity = floatval($this->input->get('quantity'));
        $list_price = floatval($this->input->get('list_price'));

        $params = array();
        $params['unit_name'] = $unit_name;
        $params['quantity'] = $quantity;
        $params['list_price'] = $list_price;

        $this->load->model('product_unit_model');
        $this->product_unit_model->update_product_unit($shop_id, $unit_id, $params);
    }

    function search_product()
    {
        if ($this->check_user() === false) {
            redirect('/login');
            return;
        }
        $shop_id = $this->user->shop_id;
        $kw = std($this->input->post('kw'));
        $this->load->model('product_model');
        if ($kw == '') {
            echo ('[]');
            return;
        }
        $products  = $this->product_model->search_products($shop_id, $kw);
        echo (json_encode($products));
    }

    function scan_product()
    {
        if ($this->check_user() === false) {
            redirect('/login');
            return;
        }
        $shop_id = $this->user->shop_id;
        $code = std($this->input->post('code'));
        $this->load->model('product_model');
        if ($code == '') {
            echo ('{}');
            return;
        }
        $product  = $this->product_model->scan_product($shop_id, $code);
        echo (json_encode($product));
    }

    function export_order()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);
        //$this->output->enable_profiler(TRUE);
        if ($this->check_user() === false) {
            redirect('/login');
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->get('id'));
        $this->load->model('bill_item_model');
        $items = $this->bill_item_model->get_bill_items_lite($shop_id, $order_id);
        $data = array();
        $data['items'] = $items;

        $this->load->view('export_order', $data);
    }

    function p_search()
    {
        //$this->output->enable_profiler(TRUE);
        $this->load->model('p_model');
        $keyword = std($this->input->post('keyword'));
        $type = intval($this->input->post('type'));
        $products = $this->p_model->search_product($keyword, $type);
        $data = array();
        $data['rows'] = $products;
        $this->load->view('product_search', $data);
    }

    function product_cron()
    {
        if ($this->check_user() === false) {
            redirect('/login');
            return;
        }
        $shop_id = $this->user->shop_id;
        $product_id = intval($this->input->post('product_id'));
        $p2 = array();
        $p2['shop_id'] = $shop_id;
        $p2['product_id'] = $product_id;

        $this->load->model('stock_cron2_model');
        $this->stock_cron2_model->add_stock_cron2($p2);
    }

    function messages()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        if ($this->check_user() === false) {
            redirect('/login');
            return;
        }
        $shop_id = $this->user->shop_id;
        $this->load->model('shop_message_model');
        $messages = $this->shop_message_model->get_shop_messages($shop_id);

        $data = array();
        $data['messages'] = $messages;
        $data['title'] = 'Liên lạc với bên bán phần mềm';
        $data["user"] = $this->user;

        //toandk2 sửa
        if (check_user_agent('mobile')) {
            $data['title_header'] = 'Liên hệ với quản trị';
            $this->load->view('mobile_views/html_header_app', $data);
            $this->load->view('mobile_views/messages', $data);
        } else {
            $this->load->view('headers/html_header', $data);
            $this->load->view('messages', $data);
            $this->load->view('headers/html_footer');
        }

        // $this->load->view('headers/html_header', $data);
        // $this->load->view('messages', $data);

        // $this->load->view('headers/html_footer');
    }

    function send_message()
    {
        if ($this->check_user() === false) {
            redirect('/login');
            return;
        }
        $shop_id = $this->user->shop_id;
        $content = std($this->input->post('content'));
        $this->load->model('shop_message_model');
        $params = array();
        $params['shop_id'] = $shop_id;
        $params['content'] = $content;
        $this->shop_message_model->add_message($params);
    }

    function check_product_transaction()
    {
        if ($this->check_user() === false) {
            redirect('/login');
            return;
        }
        $shop_id = $this->user->shop_id;
        $product_id = intval($this->input->post('product_id'));
        $this->load->model('bill_item_model');
        $result = $this->bill_item_model->check_product_transaction($shop_id, $product_id);
        if ($result) {
            $result = array();
            $result['existed'] = 1;
            echo (json_encode($result));
        } else {
            $result = array();
            $result['existed'] = 0;
            echo (json_encode($result));
        }
    }

    function create_salary_order()
    {

        //error_reporting(-1);
        //ini_set('display_errors', 1);

        //$this->output->enable_profiler(TRUE);

        if ($this->check_user() === false) {
            redirect('/login');
            return;
        }
        $shop_id = $this->user->shop_id;
        $order_id = intval($this->input->post('order_id'));

        $this->load->model('order_model');
        $order = $this->order_model->get_order($order_id, $shop_id);

        $this->load->model('order_salary_model');
        $this->load->model('bill_item_model');
        $employees = $this->order_salary_model->get_all_order_salaries($shop_id, $order_id);
        $salary = 0;
        $social_insurance = 0;
        $health_insurance = 0;
        $unemployment_insurance = 0;
        $personal_income_tax = 0;
        foreach ($employees as $row) {
            $salary = $salary + $row['product_salary_quantity'] * $row['product_salary'] + $row['time_salary_quantity'] * $row['time_salary'] + $row['vacation_salary_quantity'] * $row['vacation_salary'] + $row['salary_allowance'] + $row['allowance'] + $row['bonus'];
            $social_insurance = $social_insurance + $row['social_insurance'];
            $health_insurance = $health_insurance + $row['health_insurance'];
            $unemployment_insurance = $unemployment_insurance + $row['unemployment_insurance'];
            $personal_income_tax = $personal_income_tax + $row['personal_income_tax'];
        }
        $salary = $salary - $social_insurance - $health_insurance - $unemployment_insurance - $personal_income_tax;

        $this->load->model('product_model');
        $salary_product = $this->product_model->get_product_by_code2('salary', $shop_id);
        $social_insurance_product = $this->product_model->get_product_by_code2('trabhxh', $shop_id);
        $health_insurance_product = $this->product_model->get_product_by_code2('trabhyt', $shop_id);
        $unemployment_insurance_product = $this->product_model->get_product_by_code2('trabhtn', $shop_id);
        $personal_income_tax_product = $this->product_model->get_product_by_code2('thuethunhap', $shop_id);

        $params = array();

        $params['shop_id'] = $shop_id;
        $params['order_name'] = 'Trả lương tháng ' . $order['month'] . ' năm ' . $order['year'];
        $params['order_type'] = 'M';

        $params['order_date'] = date('Y-m-d');
        $params['customer_id'] = 0;
        $params['customer_name'] = '';
        $params['order_time'] = date('Y-m-d H:i:s', strtotime('now'));
        $params['create_user'] = $this->user->user_id;
        $params['last_user'] = $this->user->user_id;
        $params['last_update'] = date('Y-m-d H:i:s', strtotime('now'));
        $params['payment_type'] = 0;
        $params['currency'] = 'VND';
        $params['deposit_amount'] = 0;
        $params['vat'] = 0;
        $params['paid'] = 0;
        $params['vat_rate'] = 0;
        $params['vat_type'] = 1;
        $params['invoice_number'] = '';
        $params['content'] = '';
        $params['user_id'] = $this->user->user_id;

        $params['tax'] = 0;
        $params['diners'] = 0;
        $params['unit'] = '';
        $params['address'] = '';
        //$params['bill_book'] = $bill_book;
        $params['director'] = '';
        $params['chief_accountant'] = '';
        $params['cashier'] = '';
        $params['storekeeper'] = '';
        $params['amount'] = $salary + $social_insurance + $health_insurance + $unemployment_insurance + $personal_income_tax;
        $params['status1'] = 4;
        $order_id = $this->order_model->add_order($params);
        $status1 = 4;
        if ($salary > 0) {
            $params = array();
            $params['shop_id'] = $shop_id;
            $product_id = $salary_product['id'];
            $product_name = $salary_product['product_name'];
            $params['order_id'] = $order_id;
            $params['product_id'] = $product_id;
            $params['product_code'] = '';
            $params['product_name'] = $product_name;
            $params['price'] = $salary;
            $params['quantity'] = 1;
            $params['amount'] = $salary;
            $params['status1'] = $status1;
            $bill_item_id = $this->bill_item_model->add_bill_item($params);
        }

        if ($social_insurance > 0) {
            $params = array();
            $params['shop_id'] = $shop_id;
            $product_id = $social_insurance_product['id'];
            $product_name = $social_insurance_product['product_name'];
            $params['order_id'] = $order_id;
            $params['product_id'] = $product_id;
            $params['product_code'] = '';
            $params['product_name'] = $product_name;
            $params['price'] = $social_insurance;
            $params['quantity'] = 1;
            $params['amount'] = $social_insurance;
            $params['status1'] = $status1;
            $bill_item_id = $this->bill_item_model->add_bill_item($params);
        }

        if ($health_insurance > 0) {
            $params = array();
            $params['shop_id'] = $shop_id;
            $product_id = $health_insurance_product['id'];
            $product_name = $health_insurance_product['product_name'];
            $params['order_id'] = $order_id;
            $params['product_id'] = $product_id;
            $params['product_code'] = '';
            $params['product_name'] = $product_name;
            $params['price'] = $health_insurance;
            $params['quantity'] = 1;
            $params['amount'] = $health_insurance;
            $params['status1'] = $status1;
            $bill_item_id = $this->bill_item_model->add_bill_item($params);
        }

        if ($unemployment_insurance > 0) {
            $params = array();
            $params['shop_id'] = $shop_id;
            $product_id = $unemployment_insurance_product['id'];
            $product_name = $unemployment_insurance_product['product_name'];
            $params['order_id'] = $order_id;
            $params['product_id'] = $product_id;
            $params['product_code'] = '';
            $params['product_name'] = $product_name;
            $params['price'] = $unemployment_insurance;
            $params['quantity'] = 1;
            $params['amount'] = $unemployment_insurance;
            $params['status1'] = $status1;
            $bill_item_id = $this->bill_item_model->add_bill_item($params);
        }
        if ($personal_income_tax > 0) {
            $params = array();
            $params['shop_id'] = $shop_id;
            $product_id = $personal_income_tax_product['id'];
            $product_name = $personal_income_tax_product['product_name'];
            $params['order_id'] = $order_id;
            $params['product_id'] = $product_id;
            $params['product_code'] = '';
            $params['product_name'] = $product_name;
            $params['price'] = $personal_income_tax;
            $params['quantity'] = 1;
            $params['amount'] = $personal_income_tax;
            $params['status1'] = $status1;
            $bill_item_id = $this->bill_item_model->add_bill_item($params);
        }
        $params = array();
        $params['re_shop_id'] = $order_id;
        $this->order_model->update_order($order['id'], $shop_id, $params);

        $data = array();
        $data['order_id'] = $order_id;
        echo (json_encode($data));
    }

    function show_product()
    {
        //error_reporting(-1);
        //ini_set('display_errors', 1);

        if ($this->check_user() === false) {
            redirect('/login');
            return;
        }
        $shop_id = $this->user->shop_id;
        $group_id = intval($this->input->get('group_id'));
        $page = intval($this->input->get('page'));
        $this->load->model('product_model');
        $products = $this->product_model->get_shop_product_by_group0($shop_id, $group_id, $page);
        $count = $this->product_model->count_shop_product_by_group0($shop_id, $group_id);
        $pages = intval($count / 50) + 1;
        $data = array();
        $data['products'] = $products;
        $data['pages'] = $pages;
        $data['group_id'] = $group_id;
        $this->load->view('products2', $data);
    }
}
