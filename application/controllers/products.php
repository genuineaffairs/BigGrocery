<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Products extends My_Controller {

	public function __construct(){
		parent::__construct();
        $this->load->library('datatables');        
        $this->load->model('ProductsModel');
        $this->load->helper(array('product','utility','admin'));
	}





	public function view_products($SubCatid){
		//create a single array
		//that holds the sub_category name 
		//and all the products
		$data=array();

		//insert sub_category name
		$data['sub_cat_name']=getSubCatNameBySubCatId($SubCatid);
		$data['products']=$this->ProductsModel->getAllProductsBySubCategory($SubCatid);
		$this->load->view('products',['data'=>$data]);

	}

	public function product_info($ProductId){
		$ProductInfo=$this->ProductsModel->returnProductInfo($ProductId);
		$this->load->view('single',['data'=>$ProductInfo]);
	}

	public function cat_name_by_id(){
		echo getCatNameByCatId($this->input->post('id'));
	}

	public function list(){
		if (is_logged_in()) {
            $this->load->model(['SubCatModel','ProductCatModel']);

            $page_data=array('title' => 'Manage Products');
            $id=$this->session->userdata('bg_sys_ss_admin_id');
            $categories=$this->ProductCatModel->FetchAllCat();
            $AllProduct=$this->ProductsModel->getAllProducts();
            $this->load->view('admin/common/head', ['data' => $page_data]);
            $this->load->view('admin/common/nav',['id' => $id]);
            $this->load->view('admin/products/list',['products'=>$AllProduct]);
            $this->load->view('admin/common/footer');
        }
	}

	function get_product_json() { //get product data and encode to be JSON object
      header('Content-Type: application/json');
      echo $this->ProductsModel->get_all_product();
  }
 
	


	public function add(){
		if (is_logged_in()) {
            $this->load->model(['SubCatModel','ProductCatModel']);
            $page_data=array('title' => 'Add Product');
            $id=$this->session->userdata('bg_sys_ss_admin_id');
            $categories=$this->ProductCatModel->FetchAllCat();

            $this->load->view('admin/common/head', ['data' => $page_data]);
            $this->load->view('admin/common/nav',['id' => $id]);
            $this->load->view('admin/products/add',['product_cat'=>$categories]);
            $this->load->view('admin/common/footer');
        }
	}

	public function doAddProduct(){
		
		$FullImage=$this->doUploadFullImage();

		$ThumbNail=$this->doUploadThumbnail();
		if (!$FullImage['status']) {
			if (!$ThumbNail['status']) {

				$isProductEdible=($this->input->post('isEdible')?$this->input->post('veg_nonveg'):$this->input->post('isEdible'));

				$ProductsInfo=array(
								'cat_id'=>$this->input->post('product_cat'),
								'sub_cat_id'=>$this->input->post('product_sub_cat'),
								'product_name'=>trim($this->input->post('product_name')),
								'company_name'=>trim($this->input->post('product_company')),
								'product_image'=>substr($ThumbNail['full_file_path'],2),
								'product_image_full'=>substr($FullImage['full_file_path'],2),
								'product_weight'=>trim($this->input->post('product_weight'))." ".$this->input->post('weight_unit'),
								'veg_nonveg'=>$isProductEdible,
								'product_desc'=>htmlspecialchars($this->input->post('product_desc')),
								'product_discount'=>$this->input->post('product_discount'),
								'product_price'=>trim($this->input->post('product_price')),
								'old_price'=>0,
								'has_offers'=>$this->input->post('has_offers'),
								'product_status'=>1,
								'product_stock'=>$this->input->post('product_stock')
									);
					if ($this->ProductsModel->addNewProduct($ProductsInfo)) {
						$this->session->set_flashdata('bg_sys_msg', alert_style('success','The Product is Addedd Successfully..'));
						return redirect('Products/add');
					}else{
						$this->session->set_flashdata('bg_sys_msg',alert_style('success','An error while adding Product...'));
						return redirect('Products/add');
					}
			}	
			else{
				$this->session->set_flashdata('bg_sys_msg',alert_style('danger',"<strong>Full Image: </strong>".$ThumbNail['error']));
				return redirect('Products/add');
				
			}
		}else{
			 	$this->session->set_flashdata('bg_sys_msg',alert_style('danger',"<strong>Thumbnail Image: </strong>".$FullImage['error']));
				return redirect('Products/add');
		}
	}

	public function doUploadThumbnail(){
		$config['upload_path'] = './product_images/thumb/';
		$config['allowed_types'] = 'jpg|png|jpeg|PNG|JPG|JPEG';
		$config['max_size']  = '100';
		$config['max_width']  = '250';
		$config['max_height']  = '250';
		$config['encrypt_name']=TRUE;

		$this->upload->initialize($config);//tnx mate you saved my ass
		
		$this->load->library('upload', $config);
		

		if (!$this->upload->do_upload('product_thumb_image')){
			$error = array('error' => $this->upload->display_errors(),'status'=>1);
			return $error;
		}
		else{
			$data = array('upload_data' => $this->upload->data());
			$success=array('full_file_path'=>$config['upload_path'].$data['upload_data']['file_name'],'status'=>0);
			return $success;
		}
	}

	public function doUploadFullImage(){
		$config['upload_path'] = './product_images/full/';
		$config['allowed_types'] = 'jpg|png|jpeg|PNG|JPG|JPEG';
		$config['max_size']  = '100';
		$config['max_width']  = '600';
		$config['max_height']  = '600';
		$config['encrypt_name']=TRUE;
		$this->load->library('upload', $config);
		

		if (!$this->upload->do_upload('product_image_full')){
			$error = array('error' => $this->upload->display_errors(),'status'=>1);
			return $error;
		}
		else{
			$data = array('upload_data' => $this->upload->data());
			$success=array('full_file_path'=>$config['upload_path'].$data['upload_data']['file_name'],'status'=>0);
			return $success;
		}
	}

	public function doSearchProduct(){
		$search_data = $this->input->post('search_data');
		if (!empty($search_data)) {
			
			$result = $this->ProductsModel->get_searches($search_data);
			if (!empty($result)){
	          for($i=0;$i<count($result);$i++){
	          		$product_name=explode(',',$result[$i]['product_name']);
	          		echo "
	          		<div class='table-responsive' style='padding-left: 10px;padding-right: 10px;padding-bottom:0px;'>
	                	<table class='table table-hover' align='left' width='100%'>
	                    <tr>
	                        <td>
	                            <img class='img-responsive' src='".site_url($result[$i]['product_image'])."' height='65px' width='65px'>
	                        </td>
	                        <td style='padding-left: 10px;'>
	                            <a href='".site_url('products/product_info/').$result[$i]['product_id']."'>".$product_name[0]."</a>
	                        </td>
	                        <td>
	                            <span>".$result[$i]['product_weight']."</span>
	                        </td>

	                        <td>
	                            <span>".$this->lang->line('rs').$result[$i]['product_price']."</span>
	                        </td>
	                        <td>
	                            <button type='button' class='btn btn-xs btn-success'>
	                                Add <span class='glyphicon glyphicon-shopping-cart'></span>
	                            </button>
	                        </td>
	                    </tr>
	                	</table>
	            	</div>";
	          }
	     	}
	     	else{
	           echo "<em> Not found ... </em>";
			}

		}else{
			show_404();
		}
	}

	public function remove_product(){
		$this->load->view('admin/products/remove');
	}






}

/* End of file products.php */
/* Location: ./application/controllers/products.php */