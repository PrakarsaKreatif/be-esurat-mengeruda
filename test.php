<?php
$user = new \App\Models\User();
$user->kk_path = 'test';
echo json_encode($user);
