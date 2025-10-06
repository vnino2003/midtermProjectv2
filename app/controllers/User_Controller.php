<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Controller: User_Controller
 * 
 * Automatically generated via CLI.
 */
class User_Controller extends Controller {
    public function __construct()
    {
        parent::__construct();
        $this->call->model('UserModel');
        $this->call->helper('message');
    }


    public function registerForm(){
          if ($this->session->userdata('logged_in')) {
        if ($this->session->userdata('role') === 'admin') {
            redirect('/admin/user-management');
        } else {
            redirect('/home'); // 
        }
    } else {
        $this->call->view('users/register');
    }
    }


    public function createUser() {

        $this->form_validation
            ->name('first_name')
                ->required()
                ->max_length(250)
            ->name('last_name')
                ->required()
                ->max_length(250)
            ->name('username')
                ->required()
                ->max_length(20)
            ->name('password')
                ->required()
                ->min_length(8)
                ->max_length(100)
            ->name('confirm_password')
                ->required()
                ->min_length(8)
                ->max_length(100)
            ->name('email')
                ->required()
                ->valid_email();

        // Run validation
        if($this->form_validation->run() == FALSE) {
            $errors = $this->form_validation->get_errors();
            setErrors($errors);
            redirect('/register');
            return;
        }

        // Get form input
        $first_name = $this->io->post('first_name');
        $last_name  = $this->io->post('last_name');
        $username   = $this->io->post('username');
        $email      = $this->io->post('email');
        $password   = $this->io->post('password');
        $confirm    = $this->io->post('confirm_password');

        // Check existing email/username
        if($this->UserModel->findByEmail($email)) {
            setMessage('danger', 'Email already exists!');
            redirect('register');
            return;
        }

        if($this->UserModel->findByUsername($username)) {
            setMessage('danger', 'Username already exists!');
            redirect('register');
            return;
        }

        // Check password confirmation
        if($password !== $confirm) {
            setMessage('danger', 'Passwords do not match!');
            redirect('register');
            return;
        }
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_picture']; 

            // Load upload library with file
            $this->call->library('upload', $file);

            $this->upload
                ->set_dir('uploads') // siguraduhin na may uploads/ folder sa loob ng public/
                ->allowed_extensions(['jpg','jpeg','png'])
                ->allowed_mimes(['image/jpeg','image/png'])
                ->max_size(2)
                ->is_image()
                ->encrypt_name();

            if ($this->upload->do_upload()) {
                // Save relative path, not just filename
                $filename   = $this->upload->get_filename();
        $profilePic = 'uploads/' . $filename;
            } else {
                $errors = $this->upload->get_errors();
                setMessage('warning', implode(", ", $errors));

                // default image path
                $profilePic = "uploads/default.png";
            }
        } else {
            $profilePic = "uploads/default.png";
        }


        // Insert user
        $this->UserModel->insert([
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'username'   => $username,
            'email'      => $email,
            'password'   => password_hash($password, PASSWORD_DEFAULT),
            'profile_picture' => $profilePic,
            'role' => "user"
        ]);

        setMessage('success', 'Account created successfully!');
        redirect('/register');
    }

        public function userHomepage(){
        if (!$this->session->userdata('logged_in')) {
                return redirect('/');
            }

            // Check if role is admin
            if ($this->session->userdata('role') === 'admin') {
                return redirect(site_url('admin/user-management'));
            }

                $user_id = $this->session->userdata('user_id');
                $data['user'] = $this->UserModel->find($user_id);
                $this->call->view('users/dashboard', $data);
        }

}




