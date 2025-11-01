<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Products extends Base_Controller {
    private $productModel;
    private $taxModel;
    private $accountModel;
    private $activityModel;

    public function __construct() {
        parent::__construct();
        $this->requirePermission('products', 'read');
        $this->productModel = $this->loadModel('Product_model');
        $this->taxModel = $this->loadModel('Tax_model');
        $this->accountModel = $this->loadModel('Account_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }

    public function index() {
        $type = $_GET['type'] ?? null;
        $category = $_GET['category'] ?? null;
        $search = $_GET['search'] ?? '';

        try {
            if ($search) {
                $products = $this->productModel->search($search);
            } elseif ($category) {
                $products = $this->productModel->getByCategory($category);
            } elseif ($type) {
                $products = $this->productModel->getByType($type);
            } else {
                $products = $this->productModel->getAll();
            }

            $categories = $this->productModel->getCategories();
        } catch (Exception $e) {
            $products = [];
            $categories = [];
        }

        $data = [
            'page_title' => 'Products & Services',
            'products' => $products,
            'categories' => $categories,
            'selected_type' => $type,
            'selected_category' => $category,
            'search' => $search,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('products/index', $data);
    }

    public function create() {
        $this->requirePermission('products', 'create');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'product_code' => sanitize_input($_POST['product_code'] ?? ''),
                'product_name' => sanitize_input($_POST['product_name'] ?? ''),
                'description' => sanitize_input($_POST['description'] ?? ''),
                'type' => sanitize_input($_POST['type'] ?? 'product'),
                'category' => sanitize_input($_POST['category'] ?? ''),
                'unit_price' => floatval($_POST['unit_price'] ?? 0),
                'cost_price' => floatval($_POST['cost_price'] ?? 0),
                'tax_id' => !empty($_POST['tax_id']) ? intval($_POST['tax_id']) : null,
                'account_id' => !empty($_POST['account_id']) ? intval($_POST['account_id']) : null,
                'inventory_tracked' => !empty($_POST['inventory_tracked']) ? 1 : 0,
                'stock_quantity' => floatval($_POST['stock_quantity'] ?? 0),
                'reorder_level' => floatval($_POST['reorder_level'] ?? 0),
                'unit_of_measure' => sanitize_input($_POST['unit_of_measure'] ?? 'unit'),
                'status' => sanitize_input($_POST['status'] ?? 'active')
            ];

            if (empty($data['product_code'])) {
                $data['product_code'] = $this->productModel->getNextProductCode();
            }

            if ($this->productModel->create($data)) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Products', 'Created product: ' . $data['product_name']);
                $this->setFlashMessage('success', 'Product created successfully.');
                redirect('products');
            } else {
                $this->setFlashMessage('danger', 'Failed to create product.');
            }
        }

        try {
            $taxes = $this->taxModel->getActive();
            $accounts = $this->accountModel->getByType('Revenue');
        } catch (Exception $e) {
            $taxes = [];
            $accounts = [];
        }

        $data = [
            'page_title' => 'Create Product/Service',
            'taxes' => $taxes,
            'accounts' => $accounts,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('products/create', $data);
    }

    public function edit($id) {
        $this->requirePermission('products', 'update');

        $product = $this->productModel->getById($id);
        if (!$product) {
            $this->setFlashMessage('danger', 'Product not found.');
            redirect('products');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'product_code' => sanitize_input($_POST['product_code'] ?? ''),
                'product_name' => sanitize_input($_POST['product_name'] ?? ''),
                'description' => sanitize_input($_POST['description'] ?? ''),
                'type' => sanitize_input($_POST['type'] ?? 'product'),
                'category' => sanitize_input($_POST['category'] ?? ''),
                'unit_price' => floatval($_POST['unit_price'] ?? 0),
                'cost_price' => floatval($_POST['cost_price'] ?? 0),
                'tax_id' => !empty($_POST['tax_id']) ? intval($_POST['tax_id']) : null,
                'account_id' => !empty($_POST['account_id']) ? intval($_POST['account_id']) : null,
                'inventory_tracked' => !empty($_POST['inventory_tracked']) ? 1 : 0,
                'stock_quantity' => floatval($_POST['stock_quantity'] ?? 0),
                'reorder_level' => floatval($_POST['reorder_level'] ?? 0),
                'unit_of_measure' => sanitize_input($_POST['unit_of_measure'] ?? 'unit'),
                'status' => sanitize_input($_POST['status'] ?? 'active')
            ];

            if ($this->productModel->update($id, $data)) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Products', 'Updated product: ' . $data['product_name']);
                $this->setFlashMessage('success', 'Product updated successfully.');
                redirect('products');
            } else {
                $this->setFlashMessage('danger', 'Failed to update product.');
            }
        }

        try {
            $taxes = $this->taxModel->getActive();
            $accounts = $this->accountModel->getByType('Revenue');
        } catch (Exception $e) {
            $taxes = [];
            $accounts = [];
        }

        $data = [
            'page_title' => 'Edit Product/Service',
            'product' => $product,
            'taxes' => $taxes,
            'accounts' => $accounts,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('products/edit', $data);
    }

    public function delete($id) {
        $this->requirePermission('products', 'delete');

        $product = $this->productModel->getById($id);
        if (!$product) {
            $this->setFlashMessage('danger', 'Product not found.');
            redirect('products');
        }

        if ($this->productModel->delete($id)) {
            $this->activityModel->log($this->session['user_id'], 'delete', 'Products', 'Deleted product: ' . $product['product_name']);
            $this->setFlashMessage('success', 'Product deleted successfully.');
        } else {
            $this->setFlashMessage('danger', 'Failed to delete product.');
        }

        redirect('products');
    }
}

