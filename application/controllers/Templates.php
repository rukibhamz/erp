<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Templates extends Base_Controller {
    private $templateModel;
    private $activityModel;

    public function __construct() {
        parent::__construct();
        $this->requirePermission('settings', 'read');
        $this->templateModel = $this->loadModel('Template_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }

    public function index() {
        $type = $_GET['type'] ?? null;

        try {
            if ($type) {
                $templates = $this->templateModel->getByType($type);
            } else {
                $templates = $this->templateModel->getAll();
            }
        } catch (Exception $e) {
            $templates = [];
        }

        $data = [
            'page_title' => 'Templates',
            'templates' => $templates,
            'selected_type' => $type,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('templates/index', $data);
    }

    public function create() {
        $this->requirePermission('settings', 'create');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'template_name' => sanitize_input($_POST['template_name'] ?? ''),
                'template_type' => sanitize_input($_POST['template_type'] ?? 'invoice'),
                'template_html' => $_POST['template_html'] ?? '',
                'status' => sanitize_input($_POST['status'] ?? 'active')
            ];
            
            // Only add is_default if column exists
            $hasIsDefaultColumn = $this->checkColumnExists('templates', 'is_default');
            if ($hasIsDefaultColumn) {
                $data['is_default'] = !empty($_POST['is_default']) ? 1 : 0;
            }

            if ($this->templateModel->create($data)) {
                if ($hasIsDefaultColumn && !empty($data['is_default'])) {
                    $template = $this->templateModel->getByTypeAndName($data['template_type'], $data['template_name']);
                    if ($template) {
                        $this->templateModel->setDefault($template['id']);
                    }
                }
                
                $this->activityModel->log($this->session['user_id'], 'create', 'Templates', 'Created template: ' . $data['template_name']);
                $this->setFlashMessage('success', 'Template created successfully.');
                redirect('templates');
            } else {
                $this->setFlashMessage('danger', 'Failed to create template.');
            }
        }

        $data = [
            'page_title' => 'Create Template',
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('templates/create', $data);
    }

    public function edit($id) {
        $this->requirePermission('settings', 'update');

        $template = $this->templateModel->getById($id);
        if (!$template) {
            $this->setFlashMessage('danger', 'Template not found.');
            redirect('templates');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'template_name' => sanitize_input($_POST['template_name'] ?? ''),
                'template_html' => $_POST['template_html'] ?? '',
                'status' => sanitize_input($_POST['status'] ?? 'active')
            ];
            
            // Only add is_default if column exists
            $hasIsDefaultColumn = $this->checkColumnExists('templates', 'is_default');
            if ($hasIsDefaultColumn) {
                $data['is_default'] = !empty($_POST['is_default']) ? 1 : 0;
            }

            if ($this->templateModel->update($id, $data)) {
                if ($hasIsDefaultColumn && !empty($data['is_default'])) {
                    $this->templateModel->setDefault($id);
                }
                
                $this->activityModel->log($this->session['user_id'], 'update', 'Templates', 'Updated template: ' . $data['template_name']);
                $this->setFlashMessage('success', 'Template updated successfully.');
                redirect('templates');
            } else {
                $this->setFlashMessage('danger', 'Failed to update template.');
            }
        }

        $data = [
            'page_title' => 'Edit Template',
            'template' => $template,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('templates/edit', $data);
    }
}

