<?php

class Reports extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('item_model');
        $this->load->library(array('session'));
        if (!$this->session->has_userdata('isloggedin')) {
            redirect('/login');
        }
    }

    public function index() {
        if ($this->session->userdata('type') == 0 OR $this->session->userdata('type') == 1) {
            # These are the values when a user first visits the page. Should be changeable using dropdown or text input
            $daily = $this->db->query("SELECT sales_date, FROM_UNIXTIME(sales_date, '%b %d, %Y') as sales_d, SUM(income) as income FROM `sales` WHERE status = 1 AND FROM_UNIXTIME(SALES_DATE, '%u') = " . date('W') . " GROUP BY sales_d ORDER BY sales_date DESC");
            $weekly = $this->db->query("SELECT sales_date, FROM_UNIXTIME(sales_date, '%U'), SUM(income) as income FROM `sales` WHERE status = 1 AND FROM_UNIXTIME(sales_date, '%Y') = 2018 GROUP BY WEEK(FROM_UNIXTIME(SALES_DATE))");
            $monthly = $this->db->query("SELECT FROM_UNIXTIME(sales_date, '%M') as sales_month, SUM(income) as income FROM `sales` WHERE status = 1 AND FROM_UNIXTIME(sales_date, '%Y') = 2017 GROUP BY sales_month ORDER BY sales_date ASC");
            $annual = $this->db->query("SELECT FROM_UNIXTIME(sales_date, '%Y') as sales_y, SUM(income) as income FROM `sales` WHERE status = 1 GROUP BY sales_y ORDER BY sales_y DESC");

            $feedback = $this->db->query("SELECT customer_id, feedback_id, product_id, feedback, added_at, rating FROM `feedback` ORDER BY added_at DESC");

            $this->db->select(array("customer_id", "username", "lastname", "firstname", "image", "product_preference"));
            $customer = $this->item_model->fetch("customer", "status = 1");

            $dailytotal = 0;
            foreach($daily->result() as $day)
                $dailytotal += $day->income;

            $weeklytotal = 0;
            foreach($weekly->result() as $week)
                $weeklytotal += $week->income;

            $monthlytotal = 0;
            foreach($monthly->result() as $month)
                $monthlytotal += $month->income;

            $annualtotal = 0;
            foreach($annual->result() as $ann)
                $annualtotal += $ann->income;

            $data = array(
                'title' => 'Business Reports',
                'heading' => 'Reports',
                'daily' => $daily->result(),
                'weekly' => $weekly->result(),
                'monthly' => $monthly->result(),
                'annual' => $annual->result(),
                'dailytotal' => $dailytotal,
                'weeklytotal' => $weeklytotal,
                'monthlytotal' => $monthlytotal,
                'annualtotal' => $annualtotal,
                'customer' => $customer,
                'feedback' => $feedback->result()
            );

            $this->load->view("paper/includes/header", $data);
            $this->load->view("paper/includes/navbar");
            $this->load->view("paper/reports/reports");
            $this->load->view("paper/includes/footer");
        } else {
            redirect("home/");
        }
    }

    public function inventory() {
        if ($this->session->userdata('type') == 0 OR $this->session->userdata('type') == 1) {
            $this->db->select(array("product_id", "product_name", "product_quantity", "product_price"));
            $inventory = $this->item_model->fetch("product", "status = 1");
            #
            $data = array(
                'title' => 'Inventory Report',
                'heading' => 'Inventory',
                'inventory' => $inventory,
            );

            $this->load->view("paper/includes/header", $data);
            $this->load->view("paper/includes/navbar");
            $this->load->view("paper/inventory/report");
            $this->load->view("paper/includes/footer");
        } else {
            redirect('home');
        }
    }

    public function active_customers() {
        if ($this->session->userdata('type') == 0 OR $this->session->userdata('type') == 1) {
            $this->db->select(array("customer_id", "username", "lastname", "firstname", "image"));
            $customer = $this->item_model->fetch("customer", "status = 1");

            $data = array(
                'title' => 'Weekly Active Customers',
                'heading' => 'User Log',
                'customer' => $customer,
            );

            $this->load->view("paper/includes/header", $data);
            $this->load->view("paper/includes/navbar");
            $this->load->view("paper/user_log/active_users");
            $this->load->view("paper/includes/footer");
        } else {
            redirect('home');
        }
    }

}
