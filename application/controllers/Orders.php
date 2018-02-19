<?php
/**
 * Created by PhpStorm.
 * User: Micha
 * Date: 12/19/2017
 * Time: 2:27 PM
 */

date_default_timezone_set("Asia/Manila");
class Orders extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('item_model');
        $this->load->library(array('session'));
        if (!$this->session->has_userdata('isloggedin')) {
            $this->session->set_flashdata("error", "You must login first to continue.");
            redirect('/login');
        }
    }

    public function index() {
        $this->page();
    }

    public function page() {
        $this->load->library('pagination');
        $perpage = 10;
        $config['base_url'] = base_url() . "orders/page";
        $config['per_page'] = $perpage;
        $config['full_tag_open'] = '<nav><ul class="pagination">';
        $config['full_tag_close'] = ' </ul></nav>';
        $config['first_link'] = 'First';
        $config['first_tag_open'] = '<li>';
        $config['first_tag_close'] = '</li>';
        $config['first_url'] = '';
        $config['last_link'] = 'Last';
        $config['last_tag_open'] = '<li>';
        $config['last_tag_close'] = '</li>';
        $config['next_link'] = '&raquo;';
        $config['next_tag_open'] = '<li>';
        $config['next_tag_close'] = '</li>';
        $config['prev_link'] = '&laquo;';
        $config['prev_tag_open'] = '<li>';
        $config['prev_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="active"><a href="#">';
        $config['cur_tag_close'] = '</a></li>';
        $config['num_tag_open'] = '<li>';
        $config['num_tag_close'] = '</li>';

        $date = $this->input->post('date') ? "Here are the list of orders for <b><u>" . date("F j, Y", strtotime($this->input->post('date'))) . "</b></u>.<br><a href = '". base_url() . "orders'>Click  here to view all recorded transactions.</a>" : "Here are the overall orders of the customers.";

        if ($this->session->userdata('type') == 0 OR $this->session->userdata('type') == 1) {
            $config['total_rows'] = $this->input->post('date') ? $this->item_model->getCount('orders', array('status' => 1, 'FROM_UNIXTIME(transaction_date,"%Y-%m-%d")' => $this->input->post('date'))) : $this->item_model->getCount('orders', array('status' => 1));
            $this->pagination->initialize($config);
            $orders = ($this->input->post('date')) ? ($this->item_model->getItemsWithLimit('orders', $perpage, $this->uri->segment(3), 'transaction_date', 'DESC', array( 'status' => 1, 'FROM_UNIXTIME(transaction_date,"%Y-%m-%d")' => $this->input->post('date')))) : ($this->item_model->getItemsWithLimit('orders', $perpage, $this->uri->segment(3), 'transaction_date', 'DESC', array( 'status' => 1)));

            $data = array(
                'title' => 'Orders Management',
                'heading' => 'Orders Management',
                'orders' => $orders,
                'date' => $date,
                'links' => $this->pagination->create_links()
            );
            
            $this->load->view("paper/includes/header", $data);
            $this->load->view("paper/includes/navbar");
            $this->load->view("paper/orders/orders");
            $this->load->view("paper/includes/footer");
        } else {
            redirect("home/");
        }
    }

    public function view() {
        if ($this->session->userdata('type') == 0 OR $this->session->userdata('type') == 1) {
            $order = $this->item_model->fetch("orders", 'order_id = ' . $this->uri->segment(3))[0];
            $customer = $this->item_model->fetch("customer", "customer_id = " . $order->customer_id)[0];
            $order_items = $this->item_model->fetch('order_items', 'order_id = ' . $this->uri->segment(3));

            if($order_items) {
                $data = array(
                    'title' => "Orders: View Order",
                    'heading' => "Orders Management",
                    'order_items' => $order_items,
                    'customer' => $customer,
                    'order_details' => $order
                );
                $this->load->view('paper/includes/header', $data);
                $this->load->view("paper/includes/navbar");
                $this->load->view('paper/orders/view');
                $this->load->view('paper/includes/footer');
            } else {
                redirect('orders');
            }
            #$order = $this->item_model->fetch('orders', array('order_id' => $this->uri->segment(3)));
        } else {
            redirect('home');
        }
    }

    public function track() {
        if ($this->session->userdata('type') == 0 OR $this->session->userdata('type') == 1) {
            $this->db->select("delivery_date");
            $delivery_date = $this->item_model->fetch('orders', 'order_id = ' . $this->uri->segment(3))[0];
            $order_items = $this->item_model->fetch('order_items', 'order_id = ' . $this->uri->segment(3));
            if($order_items) {
                $data = array(
                    'title' => "Orders: Track Order",
                    'heading' => "Orders Management",
                    'order_items' => $order_items,
                    'delivery' => $delivery_date
                );
                $this->load->view('paper/includes/header', $data);
                $this->load->view("paper/includes/navbar");
                $this->load->view('paper/orders/track');
                $this->load->view('paper/includes/footer');
            } else {
                redirect('orders');
            }
        } else {
            redirect('home');
        }
    }

    public function track_exec() {
        $data = array(
            "shipper_id" => $this->input->post("shipper"),
            "process_status" => $this->input->post("progress"),
            "admin_id" => $this->session->uid
        );
        $track = $this->item_model->updatedata("orders", $data, "order_id = " . $this->uri->segment(3));
        $customer = $this->item_model->fetch("orders", "order_id = " . $this->uri->segment(3))[0];
        $change_delivery = $this->item_model->updatedata("orders", array("delivery_date" => strtotime(html_escape($this->input->post("order_date")))), "order_id = " . $this->uri->segment(3));

        if ($track) {
            $for_log = array(
                "admin_id" => $this->session->uid,
                "user_type" => $this->session->userdata('type'),
                "username" => $this->session->userdata('username'),
                "date" => time(),
                "action" => 'Edited order #' . $this->uri->segment(3)
            );

            $this->item_model->insertData("user_log", $for_log);

            if($this->input->post("progress") == 2){
                $data = array (
                  "description_status" => "Your order has been succesfully verified and is now shipped and will be delivered to you.",
                  "customer_id" => $customer->customer_id,
                  "order_id" => $customer->order_id,
                  "transaction_date" => time()
                ); 
            }
            else if($this->input->post("progress") == 3){
                $data = array (
                  "description_status" => "Thank you for shopping with Technoholics! your order has arrived at your location.",
                  "customer_id" => $customer->customer_id,
                  "order_id" => $customer->order_id,
                  "transaction_date" => time()
                ); 
            }

            $this->item_model->insertData("order_status", $data);
        }

        if ($change_delivery) {
            $for_log = array(
                "admin_id" => $this->session->uid,
                "user_type" => $this->session->userdata('type'),
                "username" => $this->session->userdata('username'),
                "date" => time(),
                "action" => 'Changed delivery date of order #' . $this->uri->segment(3) . " to " . $this->input->post("order_date")
            );
            $this->item_model->insertData("user_log", $for_log);

            $data = array (
                  "description_status" => 'Changed delivery date of order #' . $this->uri->segment(3) . " to " . $this->input->post("order_date"),
                  "customer_id" => $customer->customer_id,
                  "order_id" => $customer->order_id,
                  "transaction_date" => time()
                ); 

            $this->item_model->insertData("order_status", $data);
        }

        $order = $this->item_model->fetch("orders", "order_id = " . $this->uri->segment(3))[0];

        if($order->process_status == 3) {
            $order_items = $this->item_model->fetch("order_items", "order_id = " . $this->uri->segment(3));

            foreach ($order_items as $order_item) {
                $item = $this->item_model->fetch('product', array('product_id' => $order_item->product_id))[0];
                $quantity = $item->times_bought + $order_item->quantity;
                $this->item_model->updatedata("product", array("times_bought" => $quantity), "product_id = " .$order_item->product_id);
            }

            # insert to sales table:
            $for_sales = array(
                "sales_detail" => "In this transaction, $order->order_quantity items were bought and " . number_format($order->total_price, 2) . " is earned.",
                "income" => $order->total_price,
                "sales_date" => time(),
                "admin_id" => $this->session->uid,
                "order_id" => $order->order_id
            );
            $this->item_model->insertData("sales", $for_sales);

            $for_log = array(
                "admin_id" => $this->session->uid,
                "user_type" => $this->session->userdata('type'),
                "username" => $this->session->userdata('username'),
                "date" => time(),
                "action" => 'Edited order #' . $this->uri->segment(3) . "'s status to 'delivered'."
            );
            $this->item_model->insertData("user_log", $for_log);
        }
        redirect("orders/");
    }

    public function cancel() {
        $customer = $this->item_model->fetch("orders", "order_id = " . $this->uri->segment(3))[0];
        $cancel = $this->item_model->updatedata("orders", array("status" => 0, "process_status" => 0), "order_id = " . $this->uri->segment(3));
        $restore = $this->item_model->fetch("order_items", array("order_id" => $this->uri->segment(3)));

        foreach ($restore as $restore) {
                $item = $this->item_model->fetch('product', array('product_id' => $restore->product_id))[0];
                $quantity = $item->product_quantity + $restore->quantity;
                $this->item_model->updatedata("product", array("product_quantity" => $quantity), "product_id = " .$restore->product_id);
                $this->item_model->updatedata("order_items", array("status" =>  0), "product_id = " .$restore->product_id);
        }

        if($cancel) {
            $user_id = ($this->session->userdata("type") == 2) ? "customer_id" : "admin_id";
            $for_log = array(
                "$user_id" => $this->session->uid,
                "user_type" => $this->session->userdata('type'),
                "username" => $this->session->userdata('username'),
                "date" => time(),
                "action" => 'Cancelled order #' . $this->uri->segment(3)
            );

            $this->item_model->insertData("user_log", $for_log);

            $data = array (
              "description_status" => "Your order has been cancelled.",
              "customer_id" => $customer->customer_id,
              "order_id" => $customer->order_id,
              "transaction_date" => time()
            ); 

            $this->item_model->insertData("order_status", $data);
            $this->item_model->updatedata("audit_trail", array("status" => 0), "order_id = " . $this->uri->segment(3));
            redirect("orders/page");
        }
    }

    public function getGender() {
        if($this->session->userdata("type") == 1 OR $this->session->userdata("type") == 0) {
            header('Content-Type: application/json');
            $data = $this->db->query("SELECT COUNT(customer.gender) AS gender_count, customer.gender AS gender FROM orders INNER JOIN customer ON orders.customer_id = customer.customer_id WHERE orders.status = 1 GROUP BY customer.gender");
            print json_encode($data->result());
        } else {
            redirect("home");
        }
    }

    public function getProcessStatus() {
        if($this->session->userdata("type") == 1 OR $this->session->userdata("type") == 0) {
            header('Content-Type: application/json');
            $data = $this->db->query("SELECT COUNT(*) AS no_of_orders, process_status FROM orders WHERE status = 1 AND FROM_UNIXTIME(transaction_date, '%m-%d-%Y') = '". date("m-d-Y") ."' GROUP BY process_status");
            # '02-21-2017'
            # echo date("m-d-Y", strtotime('02-11-2018'));
            print json_encode(($data) ? $data->result() : NULL);
        } else {
            redirect("home");
        }
    }
}
 ?>