<?php
namespace App\Controller;

use Cake\Event\Event;


/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 */
class UsersController extends AppController
{

    public function initialize(){
        parent::initialize();
        $this->loadComponent('FileUpload.FileUpload',[
            'defaultThumb'=>[
                'small'=>[30,30],
                'medium' => [90,90 ]
            ],
            'uploadDir' =>WWW_ROOT.'uploads'.DS.'profile_pic'.DS,
            'maintainAspectRation'=>true
        ]);
    }



    public function beforeFilter(Event $event){
        parent::beforeFilter($event);
        $this->Auth->allow('register');
    }

    public function login(){
        if($this->Auth->user()){
            $this->redirect($this->Auth->redirectUrl());
        }
        if($this->request->is('Post')){
            $user = $this->Auth->identify();
            if($user){
                $this->Auth->setUser($user);
                return $this->redirect($this->Auth->redirectUrl());
            }else{
                $this->Flash->error('Invalid login details!!!','default',[],'auth');
            }
        }
    }

    public function logout()
    {
        return $this->redirect($this->Auth->logout());
    }


    /**
     * Index method
     *
     * @return void
     */
    public function index()
    {
        $this->paginate = array(
            'fields'=>array('id','first_name','last_name','gender','email','created','updated')
        );
        $this->set('users', $this->paginate($this->Users));
        $this->set('_serialize', ['users']);
    }

    /**
     * View method
     *
     * @param string|null $id User id.
     * @return void
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function view($id = null)
    {

        $user = $this->Users->get($id, [
            'contain' => ['Bookmarks']
        ]);
        $this->set('user', $user);
        $this->set('_serialize', ['user']);
    }

    /**
     * Add method
     *
     * @return void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $user = $this->Users->newEntity();
        if ($this->request->is('post')) {
            $this->request->data['profile_pic'] = $this->FileUpload->doFileUpload($this->request->data['profile_pic']);
            $user = $this->Users->patchEntity($user, $this->request->data);
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));
                return $this->redirect(['controller' => 'Users','action' => 'index']);
            } else {
                $this->Flash->error(__('The user could not be saved. Please, try again.'));
            }
        }
        $this->set(compact('user'));
        $this->set('_serialize', ['user']);
    }

    public function register()
    {
        $user = $this->Users->newEntity();
        if ($this->request->is('post')) {
            $user = $this->Users->patchEntity($user, $this->request->data);
            $this->Users->active = true;
            if ($this->Users->save($user)) {
                $this->Auth->setUser($user->toArray());
                $this->Flash->success(__('you are registered successfully.'));
                return $this->redirect(['controller' => 'Dashboards','action' => 'index']);
            } else {
                $this->Flash->error(__('Couldn\'t register. Please, try again.'));
            }
        }
        $this->set(compact('user'));
        $this->set('_serialize', ['user']);
    }

    /**
     * Edit method
     *
     * @param string|null $id User id.
     * @return void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $user = $this->Users->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $user = $this->Users->patchEntity($user, $this->request->data);
            pr($this->request->data);exit;
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));
                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('The user could not be saved. Please, try again.'));
            }
        }
        $this->set(compact('user'));
        $this->set('_serialize', ['user']);
    }

    /**
     * Delete method
     *
     * @param string|null $id User id.
     * @return void Redirects to index.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $user = $this->Users->get($id);
        if ($this->Users->delete($user)) {
            $this->Flash->success(__('The user has been deleted.'));
        } else {
            $this->Flash->error(__('The user could not be deleted. Please, try again.'));
        }
        return $this->redirect(['action' => 'index']);
    }
}
