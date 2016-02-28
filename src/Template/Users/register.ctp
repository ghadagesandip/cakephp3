<?php $this->assign('title','Register')?>
<div class="actions columns large-2 medium-3">


    <?= $this->Html->link(__('Back Login'), ['action' => 'login']) ?>

</div>
<div class="users form large-10 medium-9 columns">
    <?= $this->Form->create($user) ?>
    <fieldset>
        <legend><?= __('Add User') ?></legend>
        <?php
        echo $this->Form->input('first_name');
        echo $this->Form->input('last_name');
        echo $this->Form->radio('gender',[['value'=>1,'text'=>'Male'],['value'=>0,'text'=>'Female']]);
        echo $this->Form->input('email');
        echo $this->Form->input('password');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
