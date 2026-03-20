<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../modules/FollowModel.php';

class FollowController extends BaseController {

    private FollowModel $model;

    public function __construct() {
        $this->model = new FollowModel();
    }

    public function follow() {

        $userId = $this->requireLogin();

        $data = $this->getJSONInput();

        if (!is_array($data)) {
            $this->jsonResponse(["message"=>"Invalid request body"],400);
        }

        $targetId = (int)($data['user_id'] ?? 0);

        if ($targetId <= 0) {
            $this->jsonResponse(["message"=>"User ID required"],400);
        }

        if ($targetId === (int)$userId) {
            $this->jsonResponse(["message"=>"You cannot follow yourself"],400);
        }

        if ($this->model->isFollowing($userId, $targetId)) {
            $this->jsonResponse(["message"=>"You already follow this user"],409);
        }

        $this->model->followUser($userId,$targetId);

        $this->jsonResponse([
            "message"=>"User followed"
        ]);
    }

    public function unfollow() {

        $userId = $this->requireLogin();

        $data = $this->getJSONInput();

        if (!is_array($data)) {
            $this->jsonResponse(["message"=>"Invalid request body"],400);
        }

        $targetId = (int)($data['user_id'] ?? 0);

        if ($targetId <= 0) {
            $this->jsonResponse(["message"=>"User ID required"],400);
        }

        if ($targetId === (int)$userId) {
            $this->jsonResponse(["message"=>"You cannot unfollow yourself"],400);
        }

        if (!$this->model->isFollowing($userId, $targetId)) {
            $this->jsonResponse(["message"=>"You do not follow this user"],409);
        }

        $this->model->unfollowUser($userId,$targetId);

        $this->jsonResponse([
            "message"=>"User unfollowed"
        ]);
    }

    public function followingList() {
        $userId = $this->requireLogin();
        $users = $this->model->getFollowingUsers($userId);
        $this->jsonResponse($users);
    }

    public function followersList() {
        $userId = $this->requireLogin();
        $users = $this->model->getFollowersUsers($userId);
        $this->jsonResponse($users);
    }
}

if(isset($_GET['action'])){

    $controller = new FollowController();

    switch($_GET['action']){

        case "follow":
            $controller->follow();
        break;

        case "unfollow":
            $controller->unfollow();
        break;

        case "followingList":
            $controller->followingList();
        break;

        case "followersList":
            $controller->followersList();
        break;

    }
}